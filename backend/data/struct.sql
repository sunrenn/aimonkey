DROP TABLE IF EXISTS imgs;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS avartar_imgs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS app_config;

CREATE TABLE IF NOT EXISTS app_config (
    config_key VARCHAR(100) NOT NULL PRIMARY KEY,
    config_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    account_uid VARCHAR(40) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_state ENUM('active', 'frozen') NOT NULL DEFAULT 'active',
    avatar_count_for_contact INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
);

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    request_ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT uk_password_resets_token_hash UNIQUE (token_hash),
    INDEX idx_password_resets_user_id (user_id),
    INDEX idx_password_resets_expires_at (expires_at),
    CONSTRAINT fk_password_resets_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS classes (
    id VARCHAR(100) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    parent_id VARCHAR(100) NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_classes_user_id (user_id),
    CONSTRAINT fk_classes_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_classes_parent_id FOREIGN KEY (parent_id) REFERENCES classes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS imgs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    class_id VARCHAR(100) NOT NULL,
    img_url VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_imgs_user_id (user_id),
    CONSTRAINT fk_imgs_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_imgs_class_id FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
); 

CREATE TABLE IF NOT EXISTS avartar_imgs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    img_url VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_avatar_imgs_user_id (user_id),
    CONSTRAINT fk_avatar_imgs_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);