CREATE DATABASE IF NOT EXISTS artwork
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE artwork;

CREATE TABLE IF NOT EXISTS users(
    userID int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username varchar(50) UNIQUE NOT NULL,
    email varchar(100) UNIQUE NOT NULL,
    password_hash varchar(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS artworks(
    artID int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID int UNSIGNED NOT NULL,
    title varchar(30) NOT NULL,
    descriptions text NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_artwork_owner
        FOREIGN KEY (userID)
        REFERENCES users(userID)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS artwork_images(
    imgID int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artID int UNSIGNED NOT NULL,
    filepath varchar(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT art_image
        FOREIGN KEY (artID)
        REFERENCES artworks(artID)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tags(
    tagID int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tag_name varchar(40) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS artwork_tags(
    artID int UNSIGNED NOT NULL,
    tagID int UNSIGNED NOT NULL,
    PRIMARY KEY (artID, tagID),

    CONSTRAINT fk_artwork_tags_art
        FOREIGN KEY (artID)
        REFERENCES artworks(artID)
        ON DELETE CASCADE,

    CONSTRAINT fk_artwork_tags_tag
        FOREIGN KEY (tagID)
        REFERENCES tags(tagID)
        ON DELETE CASCADE
);