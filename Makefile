autoload:
	composer dump-autoload

serve:
	php -S 127.0.0.1:8080 -t public public/index.php

sql:
	sqlite3 -header public/database.sqlite
