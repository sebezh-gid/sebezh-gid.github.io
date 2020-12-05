# Development scripts for sebezh-gid.ru
#
# Most commands:
#
# make serve -- run local server in development mode.
# make build -- prepare the production image
# make push  -- publish the production image

VERSION = v2.0
IMAGE = umonkey/sebezh-gid

all: serve

serve:
	docker build --build-arg USER_ID=`id -u` --build-arg GROUP_ID=`id -g` --tag $(IMAGE):local --file build/Dockerfile.local .
	docker run --rm -it -v $(PWD):/app --name sebezh_gid -p 8000:80 $(IMAGE):local


assets:
	php -f vendor/bin/build-assets

db:
	mysql

db-remote:
	ssh -t $(REMOTE) mysql --defaults-file=.my.sebezh_gid.cnf

deploy:
	rsync -avz -e ssh src templates vendor public $(REMOTE):$(FOLDER)/

pull-data:
	ssh $(REMOTE) mysqldump --defaults-file=.my.sebezh_gid.cnf sebezh_gid files pages | pv > data/remote.sql
	mysql < data/remote.sql

pull-files:
	rsync -avz -e ssh --delete $(REMOTE):$(FOLDER)/data/files/sebezh-gid.ru data/files/

push-ufw:
	rsync -avz --delete --exclude .hg vendor/umonkey/ufw1/ ~/src/ufw1/
	cd ~/src/ufw1/ && bash -l

reindex:
	php -f tools/cli.php reindex

reindex-remote:
	ssh $(REMOTE) php -f wiki/tools/cli.php reindex

schema:
	mysql < src/schema_mysql.sql

shell:
	ssh -t $(REMOTE) cd hosts/sebezh-gid.ru \; bash -l

tags:
	@echo "Rebuilding ctags (see doc/HOWTO_dev.md)"
	@find src -name "*.php" | xargs ctags-exuberant -f .tags -h ".php" -R --totals=yes --tag-relative=yes --PHP-kinds=+cf --regex-PHP='/abstract class ([^ ]*)/\1/c/' --regex-PHP='/interface ([^ ]*)/\1/c/' --regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/' >/dev/null 2>&1

test-website:
	php -f tools/test-website "http://gid.local/" | tee test.log | grep -v improper | grep -E 'WRN|ERR'

update-ufw:
	hg --cwd vendor/umonkey/ufw1/ up -C
	hg --cwd vendor/umonkey/ufw1/ clean
	composer update umonkey/ufw1
	hg ci composer.json composer.lock -m "Dependency update: umonkey/ufw1"

update-ufw-raw:
	rsync -avz --delete --exclude .hg ~/src/ufw1/ vendor/umonkey/ufw1/

.PHONY: assets tags schema
