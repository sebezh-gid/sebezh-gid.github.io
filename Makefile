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
	sqlite3 -header var/database.sqlite

db-seed:
	rm -f var/database.sqlite
	sqlite3 var/database.sqlite < src/schema_sqlite.sql

reindex:
	php -f tools/cli.php reindex

tags:
	@echo "Rebuilding ctags (see doc/HOWTO_dev.md)"
	@find src -name "*.php" | xargs ctags-exuberant -f .tags -h ".php" -R --totals=yes --tag-relative=yes --PHP-kinds=+cf --regex-PHP='/abstract class ([^ ]*)/\1/c/' --regex-PHP='/interface ([^ ]*)/\1/c/' --regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/' >/dev/null 2>&1

.PHONY: assets tags schema
