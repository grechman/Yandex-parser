-- Создание новой базы данных
CREATE DATABASE yandex_music_parser CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Выбор созданной базы данных
USE yandex_music_parser;

-- Создание таблицы artists
CREATE TABLE artists (
    id INT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subscribers_count INT NOT NULL DEFAULT 0,
    monthly_listeners INT NOT NULL DEFAULT 0,
    albums_count INT NOT NULL DEFAULT 0,
    tracks_count INT NOT NULL DEFAULT 0,
    cover_url VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Создание таблицы tracks
CREATE TABLE tracks (
    id INT NOT NULL PRIMARY KEY,
    artist_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    duration_seconds INT NOT NULL,
    album_id INT DEFAULT NULL,
    album_title VARCHAR(255) DEFAULT NULL,
    cover_url VARCHAR(512) DEFAULT NULL,
    local_path VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
);

-- Создание индексов для оптимизации
CREATE INDEX idx_tracks_artist_id ON tracks(artist_id);
CREATE INDEX idx_tracks_album_id ON tracks(album_id);