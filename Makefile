REMOTE=sebezh-gid.ru

all: assets tags

assets:
	php -f tools/compress.php src/assets.php

autoload:
	composer dump-autoload

deploy:
	rsync -avz -e ssh src templates vendor $(REMOTE):wiki/
	hgput public/libs.* public/app.*

flush:
	echo "UPDATE pages SET html = null;" | sqlite3 data/database.sqlite

flush-remote:
	echo "UPDATE pages SET html = null;" | ssh $(REMOTE) mysql

lint:
	vendor/bin/phpstan --configuration=dev/phpstan.neon analyze --no-progress --no-ansi src
	vendor/bin/phpcs -s --standard=dev/phpcs.xml src

migrate:
	vendor/bin/phinx --configuration=config/phinx.php --parser=php migrate

pull-data:
	ssh $(REMOTE) mysqldump sebgid | pv > data/remote.sql
	mysql < remote.sql

pull-files:
	php -f tools/cli.php pull-files

reindex:
	php -f tools/cli.php reindex

reindex-remote:
	ssh $(REMOTE) php -f wiki/tools/cli.php reindex

serve:
	vendor/bin/composer dump-autoload
	APP_ENV=local php -d upload_max_filesize=100M -S 127.0.0.1:8080 -t public public/router.php

shell:
	ssh $(REMOTE)

sql:
	sqlite3 -header data/database.sqlite

sql-public:
	ssh -t $(REMOTE) mysql

tags:
	@echo "Rebuilding ctags (see doc/HOWTO_dev.md)"
	@find src -name "*.php" | xargs ctags-exuberant -f .tags -h ".php" -R --totals=yes --tag-relative=yes --PHP-kinds=+cf --regex-PHP='/abstract class ([^ ]*)/\1/c/' --regex-PHP='/interface ([^ ]*)/\1/c/' --regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/' >/dev/null 2>&1

test: lint

.PHONY: assets tags
