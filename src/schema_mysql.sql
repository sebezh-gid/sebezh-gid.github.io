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


CREATE TABLE IF NOT EXISTS `files` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255),
    `real_name` VARCHAR(1024),
    `type` VARCHAR(255),
    `length` INTEGER UNSIGNED NOT NULL,
    `created` INTEGER UNSIGNED NOT NULL,
    `body` MEDIUMBLOB,
    `hash` VARCHAR(255),
    PRIMARY KEY(`id`),
    KEY(`name`),
    KEY(`created`),
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
