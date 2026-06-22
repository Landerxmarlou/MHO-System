-- Add profile image support for users
ALTER TABLE users
    ADD COLUMN profile_image VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Relative path to uploaded profile photo'
    AFTER phone;
