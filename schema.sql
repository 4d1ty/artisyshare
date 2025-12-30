-- database: :memory:
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,

    password_hash VARCHAR(255) NOT NULL,

    role ENUM('user','moderator','admin') DEFAULT 'user',

    is_verified TINYINT(1) DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,

    bio TEXT NULL
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);


CREATE TABLE artworks (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,

    status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'pending',

    rejection_reason TEXT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

ALTER TABLE artworks
ADD thumbnail_path VARCHAR(255) NOT NULL;

ALTER TABLE artworks
ADD description TEXT NULL;

CREATE TABLE artwork_likes (
    artwork_id INT NOT NULL,
    user_id INT NOT NULL,

    PRIMARY KEY (artwork_id, user_id),

    FOREIGN KEY (artwork_id) REFERENCES artworks(id)
        ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_artwork_likes_artwork ON artwork_likes(artwork_id);

CREATE INDEX idx_artwork_likes_user ON artwork_likes(user_id);

ALTER TABLE artworks
ADD view_count INT DEFAULT 0;

CREATE INDEX idx_artworks_status ON artworks(status);
CREATE INDEX idx_artworks_created ON artworks(created_at);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artwork_id) REFERENCES artworks(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_comments_artwork ON comments(artwork_id);
CREATE INDEX idx_comments_user ON comments(user_id);

ALTER TABLE comments
ADD parent_comment_id INT NULL,
ADD FOREIGN KEY (parent_comment_id) REFERENCES comments(id)
    ON DELETE CASCADE;

CREATE INDEX idx_comments_parent ON comments(parent_comment_id);

CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE artwork_tags (
    artwork_id INT NOT NULL,
    tag_id INT NOT NULL,

    PRIMARY KEY (artwork_id, tag_id),

    FOREIGN KEY (artwork_id) REFERENCES artworks(id)
        ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_artwork_tags_tag ON artwork_tags(tag_id);
CREATE INDEX idx_artwork_tags_artwork ON artwork_tags(artwork_id);


ALTER TABLE users
ADD COLUMN per_day_upload_limit INT DEFAULT 10;

ALTER TABLE users
ADD COLUMN per_artwork_size_limit INT DEFAULT 10485760; -- 10 MB

-- Allow email to be nullable
ALTER TABLE users
MODIFY COLUMN email VARCHAR(255) NULL;

-- Create a default system user which will be used for system-generated content like announcements
INSERT INTO users (username, password_hash, role, is_verified, per_day_upload_limit, per_artwork_size_limit)
VALUES ('system', '$2y$10$systemgeneratedhashvalue1234567890ab', 'admin', 1, -1, -1);
-- The password hash above is just a placeholder and should be replaced with a secure hash if used in production.
-- The 'system' user cannot log in and is only for internal use.

-- Add index on artworks.user_id for faster lookups
CREATE INDEX idx_artworks_user ON artworks(user_id);

-- Create an announcements table for site-wide announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE CASCADE
);

ALTER TABLE announcements
ADD COLUMN show_until DATETIME NULL;

CREATE INDEX idx_announcements_created_by ON announcements(created_by);
CREATE INDEX idx_announcements_created_at ON announcements(created_at);
ALTER TABLE users
ADD COLUMN last_upload_at DATETIME NULL;
CREATE INDEX idx_users_last_upload_at ON users(last_upload_at);

ALTER TABLE artworks
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
CREATE INDEX idx_artworks_updated_at ON artworks(updated_at);

-- Create a table for messages between users
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);
CREATE INDEX idx_messages_is_read ON messages(is_read);

CREATE TABLE comment_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (comment_id, reporter_id),
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_comment_reports_comment ON comment_reports(comment_id);
CREATE INDEX idx_comment_reports_reporter ON comment_reports(reporter_id);
ALTER TABLE comments
ADD COLUMN is_hidden TINYINT(1) DEFAULT 0;
CREATE INDEX idx_comments_is_hidden ON comments(is_hidden);
ALTER TABLE users
ADD COLUMN warning_count INT DEFAULT 0;
CREATE INDEX idx_users_warning_count ON users(warning_count);


CREATE TABLE artwork_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (artwork_id, reporter_id),
    FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_artwork_reports_artwork ON artwork_reports(artwork_id);
CREATE INDEX idx_artwork_reports_reporter ON artwork_reports(reporter_id);
ALTER TABLE artworks
ADD COLUMN is_hidden TINYINT(1) DEFAULT 0;
CREATE INDEX idx_artworks_is_hidden ON artworks(is_hidden);
ALTER TABLE users
ADD COLUMN strike_count INT DEFAULT 0;
CREATE INDEX idx_users_strike_count ON users(strike_count);
ALTER TABLE users
ADD COLUMN per_artwork_resolution_limit INT DEFAULT 3000; -- 3000 pixels
CREATE INDEX idx_users_per_artwork_resolution_limit ON users(per_artwork_resolution_limit);


CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reported_user_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (reported_user_id, reporter_id),
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_reports_reported_user ON reports(reported_user_id);
CREATE INDEX idx_reports_reporter ON reports(reporter_id);
