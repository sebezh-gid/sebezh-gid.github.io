CREATE TABLE IF NOT EXISTS `pages` (
	`name` TEXT,
	`source` TEXT,
	`html` TEXT,
	`created` INTEGER,
	`updated` INTEGER
);

CREATE UNIQUE INDEX IF NOT EXISTS `IDX_pages_name` ON `pages` (`name`);
