REMOTE=sebezh-gid.ru

all: tags

autoload:
	composer dump-autoload

deploy:
	rsync -avz -e ssh src templates vendor $(REMOTE):wiki/

flush:
	echo "UPDATE pages SET html = null;" | sqlite3 data/database.sqlite

pull-data:
	rm -f data/database.sqlite~
	cp data/database.sqlite data/database.sqlite~
	hgget data/database.sqlite

serve:
	php -S 127.0.0.1:8080 -t public public/router.php

shell:
	ssh $(REMOTE)

sql:
	sqlite3 -header data/database.sqlite

tags:
	@echo "Rebuilding ctags (see doc/HOWTO_dev.md)"
	@find src -name "*.php" | xargs ctags-exuberant -f .tags -h ".php" -R --totals=yes --tag-relative=yes --PHP-kinds=+cf --regex-PHP='/abstract class ([^ ]*)/\1/c/' --regex-PHP='/interface ([^ ]*)/\1/c/' --regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/' >/dev/null 2>&1

.PHONY: tags
