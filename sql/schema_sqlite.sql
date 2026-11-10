-- Та же схема для SQLite (режим проверки без MySQL). Создаётся автоматически при первом запуске.

CREATE TABLE users (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    login                TEXT NOT NULL UNIQUE,
    email                TEXT NOT NULL UNIQUE,
    password_hash        TEXT NOT NULL,
    full_name            TEXT NOT NULL,
    phone                TEXT NOT NULL DEFAULT '',
    role                 TEXT NOT NULL DEFAULT 'client',
    is_active            INTEGER NOT NULL DEFAULT 1,
    must_change_password INTEGER NOT NULL DEFAULT 0,
    failed_attempts      INTEGER NOT NULL DEFAULT 0,
    locked_until         TEXT NULL,
    created_by           INTEGER NULL,
    created_at           TEXT NOT NULL
);

CREATE TABLE sections (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT NOT NULL UNIQUE,
    title       TEXT NOT NULL,
    description TEXT NOT NULL,
    image       TEXT NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE articles (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    section_id   INTEGER NOT NULL REFERENCES sections (id),
    slug         TEXT NOT NULL UNIQUE,
    title        TEXT NOT NULL,
    summary      TEXT NOT NULL,
    body         TEXT NOT NULL,
    search_text  TEXT NOT NULL,
    author       TEXT NOT NULL DEFAULT '',
    is_published INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT NOT NULL,
    updated_at   TEXT NOT NULL
);
CREATE INDEX ix_articles_section ON articles (section_id);

CREATE TABLE news (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT NOT NULL UNIQUE,
    title        TEXT NOT NULL,
    summary      TEXT NOT NULL,
    body         TEXT NOT NULL,
    search_text  TEXT NOT NULL,
    is_published INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT NOT NULL
);

CREATE TABLE services (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT NOT NULL UNIQUE,
    title       TEXT NOT NULL,
    short_text  TEXT NOT NULL,
    description TEXT NOT NULL,
    includes    TEXT NOT NULL,
    price_from  INTEGER NOT NULL,
    duration    TEXT NOT NULL,
    image       TEXT NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE banners (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT NOT NULL,
    text        TEXT NOT NULL,
    link        TEXT NOT NULL,
    button      TEXT NOT NULL DEFAULT 'Подробнее',
    image       TEXT NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE messages (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id   INTEGER NOT NULL REFERENCES users (id),
    sender_id   INTEGER NOT NULL REFERENCES users (id),
    from_staff  INTEGER NOT NULL DEFAULT 0,
    body        TEXT NOT NULL,
    is_read     INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL
);
CREATE INDEX ix_messages_client ON messages (client_id, created_at);

CREATE TABLE requests (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id    INTEGER NOT NULL REFERENCES users (id),
    service_id   INTEGER NOT NULL REFERENCES services (id),
    event_date   TEXT NOT NULL,
    guests       INTEGER NOT NULL,
    child_age    TEXT NOT NULL DEFAULT '',
    place        TEXT NOT NULL DEFAULT '',
    budget       INTEGER NULL,
    comment      TEXT NOT NULL,
    status       TEXT NOT NULL DEFAULT 'new',
    manager_note TEXT NOT NULL DEFAULT '',
    created_at   TEXT NOT NULL
);
CREATE INDEX ix_requests_client ON requests (client_id);

CREATE TABLE feedback (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    email       TEXT NOT NULL,
    phone       TEXT NOT NULL DEFAULT '',
    topic       TEXT NOT NULL,
    body        TEXT NOT NULL,
    is_done     INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL
);
