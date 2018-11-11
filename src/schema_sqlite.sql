CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INTEGER PRIMARY KEY,
    `login` TEXT NOT NULL,
    `password` TEXT NULL,
    `last_login` DATETIME NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS IDX_accounts_login ON accounts (login);


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


-- Generic file storage table.  Uploaded files go here.
CREATE TABLE IF NOT EXISTS `files` (
    `id` INTEGER PRIMARY KEY,     -- unique id for local access
    `name` TEXT,                  -- local name for public access (why we need this?)
    `real_name` TEXT,             -- original name on upload
    `type` TEXT,                  -- mime type
    `kind` TEXT,                  -- for quick filtering
    `length` INTEGER NOT NULL,    -- body size in bytes
    `created` INTEGER NOT NULL,   -- file creation timestamp
    `uploaded` INTEGER NOT NULL,  -- file upload timestamp
    `body` BLOB,                  -- file contents
    `hash` TEXT NOT NULL          -- body hash for synchronizing
);
CREATE INDEX IF NOT EXISTS IDX_files_name ON files (name);
CREATE INDEX IF NOT EXISTS IDX_files_created ON files (created);
CREATE INDEX IF NOT EXISTS IDX_files_uploaded ON files (uploaded);
CREATE INDEX IF NOT EXISTS IDX_files_hash ON files (hash);


CREATE TABLE IF NOT EXISTS `thumbnails` (
    `name` TEXT PRIMARY KEY,
    `type` TEXT,
    `body` BLOB,
    `hash` TEXT
);
CREATE INDEX IF NOT EXISTS IDX_thumbnails_type ON thumbnails (type);


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


CREATE TABLE IF NOT EXISTS `odict` (
  `src` TEXT NOT NULL,
  `dst` TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS IDX_odict_src ON odict (src);
