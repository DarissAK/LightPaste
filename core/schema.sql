CREATE TABLE iplogs (
    ipaddress text,
    paste_time integer DEFAULT 0
);
CREATE TABLE pastes (
    id integer PRIMARY KEY AUTOINCREMENT,
    access_id text,
    text text,
    time integer DEFAULT 0,
    language text,
    md5 text,
    sha1 text,
    views integer DEFAULT 0,
    private integer DEFAULT 0,
    reported integer DEFAULT 0,
    ipaddress text,
    expiration integer DEFAULT 0,
    snap integer DEFAULT 0,
    hits integer DEFAULT 0,
    content_type text,
    content_length DEFAULT 0,
    content_charset text,
    deleted integer DEFAULT 0,
    password text
);
CREATE TABLE viewlogs (
    hash text PRIMARY KEY DESC,
    time integer DEFAULT 0
);
CREATE TABLE email_queue (
    id integer PRIMARY KEY AUTOINCREMENT,
    subject text,
    body text
);
CREATE INDEX idx_language ON pastes (language);
CREATE INDEX idx_access_id ON pastes (access_id);
