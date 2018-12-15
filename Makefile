REMOTE=sebezh-gid.ru
FOLDER=wiki

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
	echo "UPDATE pages SET html = null;" | ssh $(REMOTE) sqlite3 $(FOLDER)/data/database.sqlite

mysql2sqlite:
	sqlite3 data/database.sqlite < src/schema_sqlite.sql
	php -f tools/dbcopy.php "mysql://sebgid:sebgid725@localhost/sebgid" "sqlite:data/database.sqlite" accounts backlinks files history map_poi map_tags odict pages sessions

pull-data:
	ssh $(REMOTE) mysqldump sebgid accounts backlinks history map_poi map_tags odict pages | pv > data/remote.sql
	mysql < data/remote.sql

pull-files:
	php -f tools/cli.php pull-files -- --url=https://sebezh-gid.ru/files/export

pull-pages:
	ssh $(REMOTE) mysqldump sebgid pages history backlinks map_poi map_tags | pv > data/pages.sql
	mysql < data/pages.sql
	echo "UPDATE pages SET html = null;" | mysql sebgid
	rm -f data/pages.sql

reindex:
	php -f tools/cli.php reindex

reindex-remote:
	ssh $(REMOTE) php -f wiki/tools/cli.php reindex

schema:
	sqlite3 data/database.sqlite < src/schema_sqlite.sql

serve:
	php -d upload_max_filesize=100M -S 127.0.0.1:8080 -t public public/router.php

shell:
	ssh $(REMOTE)

sql:
	sqlite3 data/database.sqlite

sql-public:
	ssh -t $(REMOTE) sqlite3 $(FOLDER)/data/database.sqlite

tags:
	@echo "Rebuilding ctags (see doc/HOWTO_dev.md)"
	@find src -name "*.php" | xargs ctags-exuberant -f .tags -h ".php" -R --totals=yes --tag-relative=yes --PHP-kinds=+cf --regex-PHP='/abstract class ([^ ]*)/\1/c/' --regex-PHP='/interface ([^ ]*)/\1/c/' --regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/' >/dev/null 2>&1

.PHONY: assets tags sql schema
