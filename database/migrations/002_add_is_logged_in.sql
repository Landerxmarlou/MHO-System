-- Track whether a user currently has an active login session (1 = logged in)
ALTER TABLE users
    ADD COLUMN is_logged_in TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = user is currently logged in; prevents double login'
    AFTER is_active;
