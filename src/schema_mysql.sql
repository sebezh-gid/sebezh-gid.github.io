CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `login` VARCHAR(255) NOT NULL,
    `password` VARCHAR(1024) NULL,
    `last_login` DATETIME NULL,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`login`),
    KEY(`last_login`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `pages` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(1024) NOT NULL,
    `source` MEDIUMTEXT,
    `html` MEDIUMTEXT,
    `created` INTEGER UNSIGNED NOT NULL,
    `updated` INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id`),
    KEY(`name`),
    KEY(`created`),
    KEY(`updated`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `history` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `source` MEDIUMTEXT NOT NULL,
    `created` INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id`),
    KEY(`name`),
    KEY(`created`)
) DEFAULT CHARSET utf8;


-- Generic file storage table.  Uploaded files go here.
CREATE TABLE IF NOT EXISTS `files` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,    -- unique id for local access
    `name` VARCHAR(255),                              -- local name for public access (why we need this?)
    `real_name` VARCHAR(1024),                        -- original name on upload
    `type` VARCHAR(255),                              -- mime type
    `kind` ENUM('photo', 'video', 'other') NOT NULL,  -- for quick filtering
    `length` INTEGER UNSIGNED NOT NULL,               -- body size in bytes
    `created` INTEGER UNSIGNED NOT NULL,              -- file creation timestamp
    `uploaded` INTEGER UNSIGNED NOT NULL,             -- file upload timestamp
    `body` MEDIUMBLOB,                                -- file contents
    `hash` CHAR(32) NOT NULL,                         -- body hash for synchronizing
    PRIMARY KEY(`id`),
    KEY(`name`),
    KEY(`created`),
    KEY(`uploaded`),
    KEY(`hash`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `thumbnails` (
    `name` VARCHAR(255),
    `type` VARCHAR(255),
    `body` MEDIUMBLOB,
    `hash` VARCHAR(255),
    KEY(`name`),
    KEY(`type`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `shorts` (
    `id` INTEGER UNSIGNED NOT NULL,
    `created` DATETIME NOT NULL,
    `name1` VARCHAR(1024),
    `name2` VARCHAR(1024),
    `link` VARCHAR(1024),
    PRIMARY KEY(`id`),
    KEY(`created`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `sessions` (
    `id` CHAR(40) NOT NULL,
    `updated` DATETIME NOT NULL,
    `data` MEDIUMBLOB,
    PRIMARY KEY(`id`),
    KEY(`updated`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `storage` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `updated` DATETIME NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `body` MEDIUMBLOB,
    PRIMARY KEY(`id`),
    KEY(`updated`),
    UNIQUE KEY(`name`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `search` (
  `key` varchar(255) NOT NULL,
  `meta` mediumblob,
  `title` varchar(1024) DEFAULT NULL,
  `body` mediumtext,
  PRIMARY KEY (`key`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `odict` (
  `src` varchar(255) NOT NULL,
  `dst` varchar(255) NOT NULL,
  PRIMARY KEY (`src`)
) DEFAULT CHARSET=utf8;
