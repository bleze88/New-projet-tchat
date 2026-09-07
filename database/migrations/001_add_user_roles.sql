-- Adds role-based permissions (member / moderator / admin).
-- Only needed if your database already existed before this change
-- (a fresh install via schema.sql already includes this column).
ALTER TABLE users
    ADD COLUMN role ENUM('member', 'moderator', 'admin') NOT NULL DEFAULT 'member' AFTER avatar_path;
