CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `login` VARCHAR(255) NOT NULL,
    `password` VARCHAR(1024) NULL,
    `enabled` tinyint(1) unsigned not null default 0,
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
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,             -- unique id for local access
    `name` VARCHAR(255),                                       -- local name for public access (why we need this?)
    `mime_type` VARCHAR(255),                                  -- mime type
    `kind` ENUM('photo', 'video', 'audio', 'other') NOT NULL,  -- for quick filtering
    `length` INTEGER UNSIGNED NOT NULL,                        -- body size in bytes
    `created` INTEGER UNSIGNED NOT NULL,                       -- file creation timestamp
    `uploaded` INTEGER UNSIGNED NOT NULL,                      -- file upload timestamp
    `hash` CHAR(32) NOT NULL,                                  -- body hash for synchronizing
    `original` VARCHAR(1024) NOT NULL,                         -- source file path
    `thumbnail` VARCHAR(1024) NULL,                            -- preview path
    PRIMARY KEY(`id`),
    KEY(`name`),
    KEY(`created`),
    KEY(`uploaded`),
    KEY(`hash`)
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


CREATE TABLE IF NOT EXISTS `backlinks` (
    `page_id` INTEGER UNSIGNED NOT NULL,
    `link` VARCHAR(1024),
    KEY(`page_id`),
    KEY(`link`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `search_log` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `date` DATETIME NOT NULL,
    `query` VARCHAR(1024) NOT NULL,
    `results` INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY(`id`),
    KEY(`date`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `map_poi` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `created` DATETIME NOT NULL,
    `ll` VARCHAR(255) NOT NULL,
    `title` VARCHAR(1024) NOT NULL,
    `link` VARCHAR(1024) NOT NULL,
    `description` TEXT NULL,
    `icon` VARCHAR(255),
    `tags` VARCHAR(255),
    PRIMARY KEY (`id`),
    KEY (`tags`)
);

CREATE TABLE IF NOT EXISTS `map_tags` (
    `poi_id` INTEGER UNSIGNED NOT NULL,
    `tag` VARCHAR(255) NOT NULL,
    KEY(`poi_id`),
    KEY(`tag`)
);


CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(255) NOT NULL,
    `added` INTEGER UNSIGNED NOT NULL,
    `value` MEDIUMBLOB,
    PRIMARY KEY(`key`)
) DEFAULT CHARSET utf8 COMMENT='Generic cache table, key-value.';


CREATE TABLE IF NOT EXISTS `nodes` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,  -- Unique node id.
    `parent` INTEGER NULL,                          -- Parent node id, if any.
    `lb` INTEGER NOT NULL,                          -- COMMENT 'Nested set, left boundary.
    `rb` INTEGER NOT NULL,                          -- COMMENT 'Nested set, right boundary.
    `type` VARCHAR(32) NOT NULL,                    -- Some value to distinguish nodes, e.g.: wiki, article, user.
    `created` DATETIME NOT NULL,                    -- Date when the document was created, probably editable.
    `updated` DATETIME NOT NULL,                    -- Date when the document was last saved, autoupdated.
    `key` VARCHAR(255) NULL,                        -- Additional string key for things like wiki.
    `published` TINYINT(1) UNSIGNED NOT NULL,       -- Set to 1 to publish the document.
    `more` MEDIUMBLOB,                              -- Additional data, serialize()d.
    PRIMARY KEY(`id`),
    KEY(`parent`),
    KEY(`lb`),
    KEY(`rb`),
    KEY(`type`),
    KEY(`created`),
    KEY(`updated`),
    KEY(`published`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `nodes_user_idx` (
    `id` INTEGER UNSIGNED NOT NULL,
    `email` VARCHAR(255) NULL,
    FOREIGN KEY (`id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE,
    KEY(`email`)
) DEFAULT CHARSET utf8;


CREATE TABLE IF NOT EXISTS `nodes_file_idx` (
  `id` int(10) unsigned NOT NULL,
  `kind` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kind` (`kind`)
) DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `taskq` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `added` datetime not null,
  `priority` INTEGER NOT NULL DEFAULT 0,
  `payload` MEDIUMBLOB NOT NULL COMMENT 'serialized data',
  PRIMARY KEY(`id`),
  KEY(`priority`),
  KEY(`added`)
) DEFAULT CHARSET utf8;
