DROP TABLE IF EXISTS files_tmp;


CREATE TABLE IF NOT EXISTS `files_tmp` (
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


INSERT INTO files_tmp SELECT id, name, mime_type, kind, length, created, uploaded, hash, original, thumbnail FROM files;

DROP TABLE files;

ALTER TABLE files_tmp RENAME TO files;

VACUUM;
