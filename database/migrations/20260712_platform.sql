-- Aroma Restaurant platform migration (SAFE: no DROP DATABASE / no DROP of existing data tables)
-- Adds i18n, users, favorites, cart, orders. Keeps Russian columns in place.
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_status (status),
  KEY idx_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_ip_hash VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_resets_token (token_hash),
  KEY idx_password_resets_user (user_id),
  KEY idx_password_resets_expires (expires_at),
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(80) NULL,
  address VARCHAR(255) NOT NULL,
  landmark VARCHAR(255) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_user_addresses_user (user_id),
  CONSTRAINT fk_user_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS category_translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  language_code CHAR(2) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  UNIQUE KEY uq_category_lang (category_id, language_code),
  KEY idx_category_translations_lang (language_code),
  CONSTRAINT fk_category_translations_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dish_translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dish_id BIGINT UNSIGNED NOT NULL,
  language_code CHAR(2) NOT NULL,
  name VARCHAR(160) NOT NULL,
  short_description VARCHAR(255) NULL,
  description TEXT NULL,
  ingredients TEXT NULL,
  UNIQUE KEY uq_dish_lang (dish_id, language_code),
  KEY idx_dish_translations_lang (language_code),
  KEY idx_dish_translations_name (language_code, name),
  CONSTRAINT fk_dish_translations_dish FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  language_code CHAR(2) NOT NULL,
  title VARCHAR(190) NOT NULL,
  subtitle VARCHAR(255) NULL,
  content MEDIUMTEXT NULL,
  meta_title VARCHAR(190) NULL,
  meta_description VARCHAR(255) NULL,
  UNIQUE KEY uq_page_lang (page_id, language_code),
  KEY idx_page_translations_lang (language_code),
  CONSTRAINT fk_page_translations_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS setting_translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL,
  language_code CHAR(2) NOT NULL,
  setting_value TEXT NULL,
  UNIQUE KEY uq_setting_lang (setting_key, language_code),
  KEY idx_setting_translations_lang (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorites (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  dish_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_favorites_user_dish (user_id, dish_id),
  KEY idx_favorites_dish (dish_id),
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_dish FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  session_token VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_carts_user (user_id),
  UNIQUE KEY uq_carts_session (session_token),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id BIGINT UNSIGNED NOT NULL,
  dish_id BIGINT UNSIGNED NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  options_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart_item_dish (cart_id, dish_id),
  KEY idx_cart_items_dish (dish_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_dish FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(32) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_phone VARCHAR(40) NOT NULL,
  customer_email VARCHAR(190) NULL,
  delivery_type ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  delivery_address VARCHAR(255) NULL,
  landmark VARCHAR(255) NULL,
  comment TEXT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method ENUM('cash','on_receipt') NOT NULL DEFAULT 'cash',
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  order_status ENUM('new','confirmed','preparing','ready','delivering','completed','cancelled') NOT NULL DEFAULT 'new',
  admin_comment TEXT NULL,
  language_code CHAR(2) NOT NULL DEFAULT 'ru',
  idempotency_key VARCHAR(64) NULL,
  created_ip_hash VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_number (order_number),
  UNIQUE KEY uq_orders_idempotency (idempotency_key),
  KEY idx_orders_user (user_id),
  KEY idx_orders_status (order_status),
  KEY idx_orders_payment (payment_status),
  KEY idx_orders_created (created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  dish_id BIGINT UNSIGNED NULL,
  dish_name VARCHAR(160) NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  options_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_dish (dish_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_dish FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed RU translations from existing Russian columns (idempotent)
INSERT IGNORE INTO category_translations (category_id, language_code, name, description)
SELECT id, 'ru', name, description FROM categories;

INSERT IGNORE INTO dish_translations (dish_id, language_code, name, short_description, description, ingredients)
SELECT id, 'ru', name, short_description, description, ingredients FROM dishes;

INSERT IGNORE INTO page_translations (page_id, language_code, title, subtitle, content, meta_title, meta_description)
SELECT id, 'ru', title, subtitle, content, meta_title, meta_description FROM pages;

-- Optional EN/DE stubs (admin can edit later). Only insert if missing.
INSERT IGNORE INTO category_translations (category_id, language_code, name, description)
SELECT c.id, 'en',
  CASE c.slug
    WHEN 'pizza' THEN 'Pizza'
    WHEN 'burgers' THEN 'Burgers'
    WHEN 'shawarma' THEN 'Shawarma'
    WHEN 'grill' THEN 'Grill'
    WHEN 'salads' THEN 'Salads'
    WHEN 'drinks' THEN 'Drinks'
    WHEN 'desserts' THEN 'Desserts'
    ELSE c.name
  END,
  COALESCE(c.description, '')
FROM categories c;

INSERT IGNORE INTO category_translations (category_id, language_code, name, description)
SELECT c.id, 'de',
  CASE c.slug
    WHEN 'pizza' THEN 'Pizza'
    WHEN 'burgers' THEN 'Burger'
    WHEN 'shawarma' THEN 'Shawarma'
    WHEN 'grill' THEN 'Grill'
    WHEN 'salads' THEN 'Salate'
    WHEN 'drinks' THEN 'Getränke'
    WHEN 'desserts' THEN 'Desserts'
    ELSE c.name
  END,
  COALESCE(c.description, '')
FROM categories c;

INSERT IGNORE INTO dish_translations (dish_id, language_code, name, short_description, description, ingredients)
SELECT d.id, 'en', d.name, d.short_description, d.description, d.ingredients
FROM dishes d
WHERE NOT EXISTS (
  SELECT 1 FROM dish_translations t WHERE t.dish_id = d.id AND t.language_code = 'en'
);

INSERT IGNORE INTO dish_translations (dish_id, language_code, name, short_description, description, ingredients)
SELECT d.id, 'de', d.name, d.short_description, d.description, d.ingredients
FROM dishes d
WHERE NOT EXISTS (
  SELECT 1 FROM dish_translations t WHERE t.dish_id = d.id AND t.language_code = 'de'
);

-- Delivery settings (safe insert)
INSERT IGNORE INTO settings (setting_key, setting_value, setting_type) VALUES
('delivery_fee', '50', 'string'),
('delivery_free_from', '1500', 'string'),
('min_order_amount', '0', 'string'),
('cart_max_qty', '20', 'string'),
('default_language', 'ru', 'string'),
('mail_driver', 'log', 'string'),
('smtp_host', '', 'string'),
('smtp_port', '587', 'string'),
('smtp_user', '', 'string'),
('smtp_pass', '', 'string'),
('smtp_from', '', 'string');
