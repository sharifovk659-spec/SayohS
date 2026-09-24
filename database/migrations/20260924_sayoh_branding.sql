-- Rebrand legacy Aroma copy to Чайхана Сайёх (UTF-8)
SET NAMES utf8mb4;

UPDATE pages SET
  title = REPLACE(REPLACE(title, 'Aroma Restaurant', 'Чайхана Сайёх'), 'Aroma', 'Сайёх'),
  meta_title = REPLACE(REPLACE(meta_title, 'Aroma Restaurant', 'Чайхана Сайёх'), 'Aroma', 'Сайёх'),
  meta_description = REPLACE(REPLACE(meta_description, 'Aroma Restaurant', 'Чайхана Сайёх'), 'Aroma', 'Сайёх')
WHERE title LIKE '%Aroma%' OR meta_title LIKE '%Aroma%' OR meta_description LIKE '%Aroma%';

UPDATE settings SET setting_value = 'Сайёх' WHERE setting_key = 'restaurant_name' AND setting_value IN ('Aroma', 'Aroma Restaurant');
UPDATE settings SET setting_value = 'Чайхана Сайёх' WHERE setting_key = 'restaurant_full_name' AND setting_value LIKE '%Aroma%';
UPDATE settings SET setting_value = REPLACE(REPLACE(setting_value, 'Aroma Restaurant', 'Чайхана Сайёх'), 'Aroma', 'Сайёх')
WHERE setting_key IN ('hero_title', 'meta_title_default', 'meta_description_default', 'hero_text', 'tagline')
  AND setting_value LIKE '%Aroma%';

UPDATE settings SET setting_value = '+992 98 986 0007' WHERE setting_key = 'phone' AND setting_value LIKE '+7%';
UPDATE settings SET setting_value = '+992989860007' WHERE setting_key = 'phone_href' AND setting_value LIKE '7%';
UPDATE settings SET setting_value = 'hello@sayoh-chaykhana.tj' WHERE setting_key = 'email' AND setting_value LIKE '%aroma%';
UPDATE settings SET setting_value = 'Автовокзал' WHERE setting_key = 'address' AND setting_value LIKE '%Москва%';
UPDATE settings SET setting_value = 'https://wa.me/992989860007' WHERE setting_key = 'whatsapp' AND setting_value LIKE '%7495%';
UPDATE settings SET setting_value = 'https://instagram.com/sayoh_chaykhana' WHERE setting_key = 'instagram' AND (setting_value = '' OR setting_value IS NULL);
UPDATE settings SET setting_value = '' WHERE setting_key = 'base_url' AND setting_value LIKE '%aroma.inovaauto.com%';
