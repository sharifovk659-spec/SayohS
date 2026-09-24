-- Mobile home banner slides (admin-managed)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS home_banners (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  label VARCHAR(120) NOT NULL DEFAULT '',
  title VARCHAR(190) NOT NULL DEFAULT '',
  subtitle VARCHAR(255) NOT NULL DEFAULT '',
  image VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_home_banners_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO home_banners (sort_order, is_active, label, title, subtitle, image)
SELECT 1, 1, 'Вкусные блюда', 'Свежие ингредиенты', 'Настоящий вкус в каждой тарелке', NULL
WHERE NOT EXISTS (SELECT 1 FROM home_banners WHERE sort_order = 1);

INSERT INTO home_banners (sort_order, is_active, label, title, subtitle, image)
SELECT 2, 1, 'Чайхана Сайёх', 'Самая большая чайхана', 'Доставка и бронь столов онлайн', NULL
WHERE NOT EXISTS (SELECT 1 FROM home_banners WHERE sort_order = 2);

INSERT INTO home_banners (sort_order, is_active, label, title, subtitle, image)
SELECT 3, 1, 'Каждый день', 'Свежее каждый день', 'Приготовлено с любовью для вашего удовольствия', NULL
WHERE NOT EXISTS (SELECT 1 FROM home_banners WHERE sort_order = 3);

INSERT INTO home_banners (sort_order, is_active, label, title, subtitle, image)
SELECT 4, 1, 'Хиты меню', 'Популярные блюда', 'То, что гости заказывают чаще всего', NULL
WHERE NOT EXISTS (SELECT 1 FROM home_banners WHERE sort_order = 4);
