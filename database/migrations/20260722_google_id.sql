-- Optional: link Google accounts by stable subject id
ALTER TABLE users
  ADD COLUMN google_id VARCHAR(64) NULL AFTER email,
  ADD UNIQUE KEY uq_users_google_id (google_id);
