CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INTEGER PRIMARY KEY,
    `login` TEXT NOT NULL,
    `password` TEXT NULL,
    `enabled` INTEGER NOT NULL,
    `last_login` DATETIME NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS IDX_accounts_login ON accounts (login);

INSERT INTO accounts VALUES (1, 'admin', '$2y$10$8pfQG/TaO7QzI24Abo9GJu6vRWnT8yeUfQvLzbNql7NDtks4gT1KS', 1, null);


CREATE TABLE IF NOT EXISTS `pages` (
    `id` INTEGER PRIMARY KEY,
    `name` TEXT NOT NULL,
    `source` TEXT,
    `html` TEXT,
    `created` INTEGER NOT NULL,
    `updated` INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS IDX_pages_name ON pages (name);
CREATE INDEX IF NOT EXISTS IDX_pages_created ON pages (created);
CREATE INDEX IF NOT EXISTS IDX_pages_updated ON pages (updated);


CREATE TABLE IF NOT EXISTS `history` (
    `id` INTEGER PRIMARY KEY,
    `name` TEXT NOT NULL,
    `source` TEXT NOT NULL,
    `created` INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS IDX_history_name ON history (name);
CREATE INDEX IF NOT EXISTS IDX_history_created ON history (created);


-- Backlinks.
CREATE TABLE IF NOT EXISTS `backlinks` (
    `page_id` INTEGER,
    `link` TEXT
);
CREATE INDEX IF NOT EXISTS `IDX_backlinks_page_id` ON `backlinks` (`page_id`);
CREATE INDEX IF NOT EXISTS `IDX_backlinks_link` ON `backlinks` (`link`);


-- Generic file storage table.  Uploaded files go here.
CREATE TABLE IF NOT EXISTS `files` (
    `id` INTEGER PRIMARY KEY,     -- unique id for local access
    `name` TEXT,                  -- local name for public access (why we need this?)
    `mime_type` TEXT,             -- mime type
    `kind` TEXT,                  -- for quick filtering
    `length` INTEGER NOT NULL,    -- body size in bytes
    `created` INTEGER NOT NULL,   -- file creation timestamp
    `uploaded` INTEGER NOT NULL,  -- file upload timestamp
    `hash` TEXT NOT NULL,         -- body hash for synchronizing
    `original` TEXT NOT NULL,     -- source file path
    `thumbnail` TEXT NULL         -- preview path
);
CREATE INDEX IF NOT EXISTS IDX_files_name ON files (name);
CREATE INDEX IF NOT EXISTS IDX_files_created ON files (created);
CREATE INDEX IF NOT EXISTS IDX_files_uploaded ON files (uploaded);
CREATE INDEX IF NOT EXISTS IDX_files_hash ON files (hash);


CREATE TABLE IF NOT EXISTS `shorts` (
    `id` INTEGER PRIMARY KEY,
    `created` DATETIME NOT NULL,
    `name1` TEXT,
    `name2` TEXT,
    `link` TEXT
);
CREATE INDEX IF NOT EXISTS IDX_shorts_created ON shorts (created);


CREATE TABLE IF NOT EXISTS `sessions` (
    `id` TEXT NOT NULL,
    `updated` DATETIME NOT NULL,
    `data` BLOB
);
CREATE UNIQUE INDEX IF NOT EXISTS IDX_sessions_id ON sessions (id);
CREATE INDEX IF NOT EXISTS updatedX_sessions_updated ON sessions (updated);


CREATE TABLE IF NOT EXISTS `storage` (
    `id` INTEGER PRIMARY KEY,
    `updated` DATETIME NOT NULL,
    `name` TEXT NOT NULL,
    `body` BLOB
);
CREATE INDEX IF NOT EXISTS IDX_storage_updated ON storage (updated);
CREATE UNIQUE INDEX IF NOT EXISTS IDX_storage_name ON storage (name);


CREATE VIRTUAL TABLE IF NOT EXISTS `search` USING fts5 (`key` UNINDEXED, `meta` UNINDEXED, `title`, `body`);


CREATE TABLE IF NOT EXISTS `search_log` (
    `id` INTEGER PRIMARY KEY,
    `date` TEXT NOT NULL,
    `query` TEXT NOT NULL,
    `results` INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS `IDX_search_log_date` ON `search_log` (`date`);


CREATE TABLE IF NOT EXISTS `odict` (
  `src` TEXT NOT NULL,
  `dst` TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS IDX_odict_src ON odict (src);


CREATE TABLE IF NOT EXISTS `cache` (
    `key` TEXT NOT NULL,
    `added` INTEGER NOT NULL,
    `value` BLOB
);
CREATE UNIQUE INDEX IF NOT EXISTS `IDX_cache_key` ON `cache` (`key`);


CREATE TABLE IF NOT EXISTS `map_poi` (
    `id` INTEGER PRIMARY KEY,
    `created` TEXT NOT NULL,
    `ll` TEXT NOT NULL,
    `title` TEXT NOT NULL,
    `link` TEXT NULL,
    `description` TEXT NULL,
    `icon` TEXT NULL,
    `tags` TEXT NULL
);

CREATE TABLE IF NOT EXISTS `map_tags` (
    `poi_id` INTEGER,
    `tag` TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS `IDX_map_tags_poi_id` ON `map_tags` (`poi_id`);
CREATE INDEX IF NOT EXISTS `IDX_map_tags_tag` ON `map_tags` (`tag`);
