all: tags

autoload:
	composer dump-autoload

flush:
	echo "UPDATE pages SET html = null;" | sqlite3 data/database.sqlite

serve:
	php -S 127.0.0.1:8080 -t public public/router.php

sql:
	sqlite3 -header data/database.sqlite

tags:
	@echo "Rebuilding ctags (see doc/HOWTO_dev.md)"
	@find src -name "*.php" | xargs ctags-exuberant -f .tags -h ".php" -R --totals=yes --tag-relative=yes --PHP-kinds=+cf --regex-PHP='/abstract class ([^ ]*)/\1/c/' --regex-PHP='/interface ([^ ]*)/\1/c/' --regex-PHP='/(public |static |abstract |protected |private )+function ([^ (]*)/\2/f/' >/dev/null 2>&1

.PHONY: tags
