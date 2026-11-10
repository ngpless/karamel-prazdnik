-- Схема базы данных сайта «Карамель» для MySQL / MariaDB (utf8mb4)
-- Импорт: phpMyAdmin → выбрать базу → Импорт → schema.sql, затем seed.sql

SET NAMES utf8mb4;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS sections;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS banners;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login                VARCHAR(40)  NOT NULL,
    email                VARCHAR(120) NOT NULL,
    password_hash        VARCHAR(255) NOT NULL,
    full_name            VARCHAR(120) NOT NULL,
    phone                VARCHAR(30)  NOT NULL DEFAULT '',
    role                 VARCHAR(10)  NOT NULL DEFAULT 'client',
    is_active            TINYINT      NOT NULL DEFAULT 1,
    must_change_password TINYINT      NOT NULL DEFAULT 0,
    failed_attempts      INT          NOT NULL DEFAULT 0,
    locked_until         DATETIME     NULL,
    created_by           INT UNSIGNED NULL,
    created_at           DATETIME     NOT NULL,
    UNIQUE KEY uq_users_login (login),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sections (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(40)  NOT NULL,
    title       VARCHAR(120) NOT NULL,
    description VARCHAR(400) NOT NULL,
    image       VARCHAR(60)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    UNIQUE KEY uq_sections_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articles (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    section_id   INT UNSIGNED NOT NULL,
    slug         VARCHAR(120) NOT NULL,
    title        VARCHAR(200) NOT NULL,
    summary      VARCHAR(500) NOT NULL,
    body         MEDIUMTEXT   NOT NULL,
    search_text  MEDIUMTEXT   NOT NULL,
    author       VARCHAR(80)  NOT NULL DEFAULT '',
    is_published TINYINT      NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL,
    updated_at   DATETIME     NOT NULL,
    UNIQUE KEY uq_articles_slug (slug),
    KEY ix_articles_section (section_id),
    CONSTRAINT fk_articles_section FOREIGN KEY (section_id) REFERENCES sections (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(120) NOT NULL,
    title        VARCHAR(200) NOT NULL,
    summary      VARCHAR(500) NOT NULL,
    body         MEDIUMTEXT   NOT NULL,
    search_text  MEDIUMTEXT   NOT NULL,
    is_published TINYINT      NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL,
    UNIQUE KEY uq_news_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(40)  NOT NULL,
    title       VARCHAR(120) NOT NULL,
    short_text  VARCHAR(300) NOT NULL,
    description TEXT         NOT NULL,
    includes    TEXT         NOT NULL,
    price_from  INT UNSIGNED NOT NULL,
    duration    VARCHAR(40)  NOT NULL,
    image       VARCHAR(60)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    UNIQUE KEY uq_services_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banners (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(120) NOT NULL,
    text        VARCHAR(300) NOT NULL,
    link        VARCHAR(200) NOT NULL,
    button      VARCHAR(40)  NOT NULL DEFAULT 'Подробнее',
    image       VARCHAR(60)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    is_active   TINYINT      NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_id   INT UNSIGNED NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,
    from_staff  TINYINT      NOT NULL DEFAULT 0,
    body        TEXT         NOT NULL,
    is_read     TINYINT      NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL,
    KEY ix_messages_client (client_id, created_at),
    CONSTRAINT fk_messages_client FOREIGN KEY (client_id) REFERENCES users (id),
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requests (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    client_id    INT UNSIGNED NOT NULL,
    service_id   INT UNSIGNED NOT NULL,
    event_date   DATE         NOT NULL,
    guests       INT UNSIGNED NOT NULL,
    child_age    VARCHAR(20)  NOT NULL DEFAULT '',
    place        VARCHAR(200) NOT NULL DEFAULT '',
    budget       INT UNSIGNED NULL,
    comment      TEXT         NOT NULL,
    status       VARCHAR(12)  NOT NULL DEFAULT 'new',
    manager_note TEXT         NOT NULL,
    created_at   DATETIME     NOT NULL,
    KEY ix_requests_client (client_id),
    CONSTRAINT fk_requests_client FOREIGN KEY (client_id) REFERENCES users (id),
    CONSTRAINT fk_requests_service FOREIGN KEY (service_id) REFERENCES services (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE feedback (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(120) NOT NULL,
    phone       VARCHAR(30)  NOT NULL DEFAULT '',
    topic       VARCHAR(60)  NOT NULL,
    body        TEXT         NOT NULL,
    is_done     TINYINT      NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
