CREATE TABLE IF NOT EXISTS `pages` (
    `name` TEXT,
    `source` TEXT,
    `html` TEXT,
    `created` INTEGER,
    `updated` INTEGER
);

CREATE UNIQUE INDEX IF NOT EXISTS `IDX_pages_name` ON `pages` (`name`);

-- Files are stored in a table.
-- They have a generated file name, which conists from server fingerprint,
-- file fingerprint and upload time, kind of like UUID.
CREATE TABLE IF NOT EXISTS `files` (
    `id` INTEGER NOT NULL PRIMARY KEY,      -- local file id, for maintenance
    `name` TEXT,                            -- generated file name, for accessing
    `real_name` TEXT,                       -- file name at the time of uploading
    `type` TEXT,                            -- mime type
    `length` INTEGER,                       -- byte size
    `created` INTEGER,                      -- UNIX timestamp
    `body` BLOB,                            -- file contents
    `hash` TEXT                             -- file contents hash, for ETag etc.
);

CREATE INDEX IF NOT EXISTS `IDX_files_name` ON `files` (`name`);
CREATE INDEX IF NOT EXISTS `IDX_files_created` ON `files` (`created`);
CREATE INDEX IF NOT EXISTS `IDX_files_hash` ON `files` (`hash`);

-- Image thumbnails.
CREATE TABLE IF NOT EXISTS `thumbnails` (
    `name` TEXT,
    `type` TEXT,
    `body` BLOB,
    `hash` TEXT
);

CREATE INDEX IF NOT EXISTS `IDX_thumbnails_name` ON `thumbnails` (`name`);
CREATE INDEX IF NOT EXISTS `IDX_thumbnails_type` ON `thumbnails` (`type`);

CREATE TABLE IF NOT EXISTS `shorts` (
    `id` INTEGER NOT NULL PRIMARY KEY,
    `created` DATETIME NOT NULL,
    `name1` TEXT,
    `name2` TEXT,
    `link` TEXT
);
CREATE INDEX IF NOT EXISTS `IDX_shorts_created` ON `shorts` (`created`);

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` TEXT,
    `updated` DATETIME NOT NULL,
    `data` TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS `IDX_sessions_id` ON `sessions` (`id`);

CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INTEGER NOT NULL PRIMARY KEY,
    `login` TEXT NOT NULL,
    `password` TEXT NOT NULL,
    `last_login` DATETIME NULL
);
