# Simple wiki

This is a simple but fast wiki written in PHP.  It is inspired by [Fossil
SCM][2], which is really great, but quite hard to tweak because it's written in
plain C.  However, the idea that everyhing is stored in one database file still
looks great and is the key feature of this wiki.

Features:

- Data is stored in an SQLite database.
- Pages are rendered using [CommonMark][1], with wiki links added.
- Rendered pages are stored in the database for quick response handling.

TODO:

- Upload files.  Store them in the SQLite database also.


## Authors

Written by Justin Forest <hex@umonkey.net>.  Send all suggestions by email.

[1]: http://commonmark.org/
[2]: https://www.fossil-scm.org/
