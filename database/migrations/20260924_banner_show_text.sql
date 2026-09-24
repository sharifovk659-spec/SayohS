-- Toggle for hiding banner overlay text
SET NAMES utf8mb4;

ALTER TABLE home_banners
  ADD COLUMN show_text TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active;
