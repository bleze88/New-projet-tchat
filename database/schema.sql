-- Schema for the modernized chat app.
-- Run with: mysql -u root -p < database/schema.sql
-- (adjust DB name/user to match your .env)

CREATE TABLE IF NOT EXISTS users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username          VARCHAR(32)  NOT NULL,
    email             VARCHAR(255) NOT NULL,
    password_hash     VARCHAR(255) NOT NULL,
    avatar_path       VARCHAR(255) NULL,
    role              ENUM('member', 'moderator', 'admin') NOT NULL DEFAULT 'member',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_username (username),
    UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(64) NOT NULL,
    slug        VARCHAR(64) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_room_name (name),
    UNIQUE KEY uniq_room_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id     BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    body        TEXT NOT NULL,
    created_at  DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    CONSTRAINT fk_messages_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_messages_room_id (room_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS direct_messages (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id    BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    body         TEXT NOT NULL,
    created_at   DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    read_at      DATETIME NULL,
    CONSTRAINT fk_dm_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_dm_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dm_pair_id (sender_id, recipient_id, id),
    INDEX idx_dm_recipient_id (recipient_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sliding-window flood control, keyed per user + action (e.g. 'chat_message', 'dm_message', 'login_attempt').
CREATE TABLE IF NOT EXISTS rate_limits (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier  VARCHAR(191) NOT NULL,
    action      VARCHAR(32) NOT NULL,
    created_at  DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    INDEX idx_rate_limits_lookup (identifier, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lightweight presence tracking (replaces the old single 'connected' table).
-- room_id = 0 is used as the sentinel for "online globally" (e.g. for DM presence).
CREATE TABLE IF NOT EXISTS presence (
    user_id     BIGINT UNSIGNED NOT NULL,
    room_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_seen   DATETIME(3) NOT NULL,
    PRIMARY KEY (user_id, room_id),
    CONSTRAINT fk_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per (room, user): a fresh time-out replaces the previous one.
-- The user cannot post in that room while expires_at is in the future.
CREATE TABLE IF NOT EXISTS room_timeouts (
    room_id       BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    moderator_id  BIGINT UNSIGNED NOT NULL,
    expires_at    DATETIME NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (room_id, user_id),
    CONSTRAINT fk_timeout_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_timeout_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_timeout_moderator FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per banned user, site-wide. expires_at NULL = permanent ban.
CREATE TABLE IF NOT EXISTS bans (
    user_id       BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    moderator_id  BIGINT UNSIGNED NOT NULL,
    expires_at    DATETIME NULL,
    reason        VARCHAR(255) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ban_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ban_moderator FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
