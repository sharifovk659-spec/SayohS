SET NAMES utf8mb4;
USE ambra_restaurant;

INSERT INTO admins (username, password_hash, name) VALUES
('admin', '$2y$10$1zBOLSbpWc30JPXxIj7pxOfqlWImL48Ly0mIkYt.oSv0Xn3jfs2Im', 'Администратор')
ON DUPLICATE KEY UPDATE username = username;
-- login: admin / admin123

DELETE FROM dishes;
DELETE FROM gallery;
DELETE FROM categories;

INSERT INTO categories (id, name, slug, image, sort_order) VALUES
(1, 'Пицца', 'pizza', 'cat-pizza.svg', 1),
(2, 'Бургеры', 'burgers', 'cat-burgers.svg', 2),
(3, 'Шаурма', 'shawarma', 'cat-shawarma.svg', 3),
(4, 'Гриль', 'grill', 'cat-grill.svg', 4),
(5, 'Салаты', 'salads', 'cat-salads.svg', 5),
(6, 'Напитки', 'drinks', 'cat-drinks.svg', 6),
(7, 'Десерты', 'desserts', 'cat-desserts.svg', 7);

INSERT INTO dishes (category_id, name, description, price, old_price, weight, image, is_featured, is_popular, is_available, is_active, sort_order) VALUES
(1, 'Пицца Маргарита', 'Томатный соус, моцарелла, базилик и оливковое масло', 690.00, 790.00, '450 г', 'pizza-margherita.svg', 1, 1, 1, 1, 1),
(1, 'Пицца Пепперони', 'Острая пепперони, сыр и фирменный томатный соус', 820.00, NULL, '480 г', 'pizza-pepperoni.svg', 1, 1, 1, 1, 2),
(2, 'Классический бургер', 'Говяжья котлета, сыр чеддер, соус и свежие овощи', 640.00, 720.00, '320 г', 'burger-classic.svg', 1, 1, 1, 1, 1),
(2, 'Бургер с беконом', 'Котлета, бекон, карамелизированный лук и соус BBQ', 740.00, NULL, '350 г', 'burger-bacon.svg', 0, 1, 1, 1, 2),
(3, 'Шаурма классическая', 'Курица, овощи, соус и тонкий лаваш', 420.00, NULL, '380 г', 'shawarma.svg', 1, 1, 1, 1, 1),
(4, 'Стейк на гриле', 'Сочный стейк с овощами гриль и соусом', 1890.00, 2100.00, '280 г', 'grill-steak.svg', 1, 1, 1, 1, 1),
(5, 'Салат Цезарь', 'Курица, романо, пармезан и соус цезарь', 560.00, NULL, '260 г', 'salad-caesar.svg', 1, 1, 1, 1, 1),
(5, 'Греческий салат', 'Томаты, огурцы, фета, оливки и орегано', 490.00, NULL, '240 г', 'salad-greek.svg', 0, 0, 1, 1, 2),
(6, 'Авторский лимонад', 'Цитрусы, мята и домашний сироп', 390.00, NULL, '400 мл', 'lemonade.svg', 0, 1, 1, 1, 1),
(7, 'Шоколадный фондан', 'Тёплый кекс с жидкой сердцевиной', 590.00, 650.00, '140 г', 'fondant.svg', 1, 1, 1, 1, 1);

INSERT INTO gallery (title, image, album, sort_order) VALUES
('Интерьер зала', 'gal-interior.svg', 'interior', 1),
('Пицца из печи', 'gal-pizza.svg', 'dishes', 2),
('Горячее блюдо', 'gal-hot.svg', 'dishes', 3),
('Авторские напитки', 'gal-drinks.svg', 'drinks', 4),
('Десерт дня', 'gal-dessert.svg', 'dishes', 5),
('Команда ресторана', 'gal-team.svg', 'team', 6),
('Вечернее событие', 'gal-event.svg', 'events', 7),
('Детали сервировки', 'gal-table.svg', 'interior', 8);

INSERT INTO settings (setting_key, setting_value) VALUES
('home_hero_title', 'Атмосфера вкуса и спокойствия'),
('home_hero_text', 'Авторская кухня, свежие ингредиенты и атмосфера, в которую хочется возвращаться.'),
('about_video_url', 'https://www.youtube.com/embed/ScMzIvxBSi4?rel=0'),
('about_short', 'Мы объединяем свежие продукты, современную подачу и внимательный сервис, чтобы каждый визит оставлял приятные впечатления.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
