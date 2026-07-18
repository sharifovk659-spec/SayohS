SET NAMES utf8mb4;
USE aroma_restaurant;

-- Администратор создаётся через database/create-admin.php (пароль не хранится в seed)

INSERT INTO categories (id, name, slug, description, image, sort_order, is_active) VALUES
(1, 'Пицца', 'pizza', 'Пицца из печи на тонком и пышном тесте', 'cat-pizza.webp', 1, 1),
(2, 'Бургеры', 'burgers', 'Сочные бургеры с авторскими соусами', 'cat-burgers.webp', 2, 1),
(3, 'Шаурма', 'shawarma', 'Шаурма в лаваше и питах', 'cat-shawarma.webp', 3, 1),
(4, 'Гриль', 'grill', 'Мясо и овощи с гриля', 'cat-grill.webp', 4, 1),
(5, 'Салаты', 'salads', 'Свежие и сытные салаты', 'cat-salads.webp', 5, 1),
(6, 'Напитки', 'drinks', 'Лимонады, кофе и авторские напитки', 'cat-drinks.webp', 6, 1),
(7, 'Десерты', 'desserts', 'Сладкое завершение вечера', 'cat-desserts.webp', 7, 1);

INSERT INTO dishes (category_id, name, slug, short_description, description, ingredients, price, old_price, image, weight, calories, is_popular, is_available, sort_order) VALUES
(1, 'Пицца Маргарита', 'pizza-margherita', 'Томаты, моцарелла и базилик', 'Классическая пицца на тонком тесте из печи.', 'Тесто, томатный соус, моцарелла, базилик, оливковое масло', 690.00, 790.00, 'pizza-margherita.webp', '450 г', '780 ккал', 1, 1, 1),
(1, 'Пицца Пепперони', 'pizza-pepperoni', 'Острая пепперони и сыр', 'Пикантная пицца с пепперони и тянущимся сыром.', 'Тесто, томатный соус, моцарелла, пепперони, орегано', 820.00, NULL, 'pizza-pepperoni.webp', '480 г', '920 ккал', 1, 1, 2),
(1, 'Пицца Четыре сыра', 'pizza-quatro', 'Сливочный микс сыров', 'Нежная пицца с четырьмя видами сыра.', 'Тесто, моцарелла, горгонзола, пармезан, чеддер', 890.00, NULL, 'pizza-margherita.webp', '470 г', '980 ккал', 0, 1, 3),
(2, 'Классический бургер', 'classic-burger', 'Говядина, чеддер и овощи', 'Сочная котлета, сыр и свежие овощи в мягкой булочке.', 'Булочка, говядина, чеддер, салат, томат, соус', 640.00, 720.00, 'burger-classic.webp', '320 г', '640 ккал', 1, 1, 1),
(2, 'Бургер с беконом', 'bacon-burger', 'Бекон и соус BBQ', 'Бургер с хрустящим беконом и карамелизированным луком.', 'Булочка, говядина, бекон, лук, BBQ, сыр', 740.00, NULL, 'burger-bacon.webp', '350 г', '790 ккал', 1, 1, 2),
(2, 'Чикен бургер', 'chicken-burger', 'Хрустящая курица', 'Бургер с куриной котлетой и сливочным соусом.', 'Булочка, курица, салат, соус, огурец', 620.00, NULL, 'burger-classic.webp', '310 г', '580 ккал', 0, 1, 3),
(3, 'Шаурма классическая', 'classic-shawarma', 'Курица и овощи в лаваше', 'Сочная курица со свежими овощами и фирменным соусом.', 'Лаваш, курица, капуста, томат, огурец, соус', 420.00, NULL, 'shawarma.webp', '380 г', '520 ккал', 1, 1, 1),
(3, 'Шаурма острая', 'spicy-shawarma', 'С острым соусом', 'Шаурма с пикантным соусом и свежими овощами.', 'Лаваш, курица, овощи, острый соус', 450.00, NULL, 'shawarma.webp', '390 г', '540 ккал', 0, 1, 2),
(3, 'Шаурма в пите', 'pita-shawarma', 'Подача в пите', 'Компактная шаурма в мягкой пите.', 'Пита, курица, овощи, соус', 430.00, NULL, 'shawarma.webp', '360 г', '510 ккал', 0, 1, 3),
(4, 'Стейк на гриле', 'grill-steak', 'Стейк и овощи гриль', 'Сочный стейк средней прожарки с сезонными овощами.', 'Говядина, овощи гриль, соль, перец, соус', 1890.00, 2100.00, 'grill-steak.webp', '280 г', '690 ккал', 1, 1, 1),
(4, 'Овощи гриль', 'grilled-vegetables', 'Сезонные овощи', 'Лёгкое блюдо из овощей с травами и оливковым маслом.', 'Цукини, перец, баклажан, масло, травы', 480.00, NULL, 'grill-veg.webp', '280 г', '210 ккал', 0, 1, 2),
(4, 'Куриные шашлычки', 'chicken-skewers', 'Курица на шпажках', 'Нежные куриные шашлычки с соусом.', 'Курица, специи, соус', 790.00, NULL, 'grill-steak.webp', '250 г', '430 ккал', 1, 1, 3),
(5, 'Салат Цезарь', 'caesar-salad', 'Курица, романо, пармезан', 'Классический цезарь с хрустящими крутонами.', 'Романо, курица, пармезан, крутоны, соус', 560.00, NULL, 'salad-caesar.webp', '260 г', '410 ккал', 1, 1, 1),
(5, 'Греческий салат', 'greek-salad', 'Фета и овощи', 'Свежий средиземноморский салат.', 'Томат, огурец, фета, оливки, орегано', 490.00, NULL, 'salad-greek.webp', '240 г', '320 ккал', 0, 1, 2),
(5, 'Тёплый салат с киноа', 'quinoa-salad', 'Киноа и овощи', 'Лёгкий тёплый салат с киноа и зеленью.', 'Киноа, овощи, зелень, масло', 540.00, NULL, 'salad-caesar.webp', '250 г', '360 ккал', 0, 1, 3),
(6, 'Авторский лимонад', 'house-lemonade', 'Цитрусы и мята', 'Освежающий лимонад собственного приготовления.', 'Лимон, лайм, мята, сироп, вода', 390.00, NULL, 'lemonade.webp', '400 мл', '90 ккал', 1, 1, 1),
(6, 'Эспрессо-тоник', 'espresso-tonic', 'Кофе и тоник', 'Бодрящий напиток на двойном эспрессо.', 'Эспрессо, тоник, цедра', 420.00, NULL, 'lemonade.webp', '300 мл', '45 ккал', 0, 1, 2),
(6, 'Домашний морс', 'berry-morse', 'Ягодный морс', 'Морс из сезонных ягод.', 'Ягоды, вода, сахар', 350.00, NULL, 'lemonade.webp', '400 мл', '80 ккал', 0, 1, 3),
(7, 'Шоколадный фондан', 'chocolate-fondant', 'Тёплый шоколадный десерт', 'Фондан с жидкой сердцевиной и мороженым.', 'Шоколад, масло, яйца, сахар, мука', 590.00, 650.00, 'fondant.webp', '140 г', '450 ккал', 1, 1, 1),
(7, 'Тирамису', 'tiramisu', 'Кофе и маскарпоне', 'Классический десерт с эспрессо и какао.', 'Савоярди, маскарпоне, эспрессо, какао', 550.00, NULL, 'tiramisu.webp', '160 г', '480 ккал', 1, 1, 2),
(7, 'Чизкейк ягодный', 'berry-cheesecake', 'Нежный чизкейк', 'Чизкейк на песочной основе с ягодным кули.', 'Сыр, печенье, ягоды, сливки', 520.00, NULL, 'tiramisu.webp', '150 г', '430 ккал', 0, 1, 3);

INSERT INTO gallery (title, image, type, sort_order, is_active) VALUES
('Интерьер зала', 'gal-interior.webp', 'interior', 1, 1),
('Пицца из печи', 'gal-pizza.webp', 'dishes', 2, 1),
('Горячее блюдо', 'gal-hot.webp', 'dishes', 3, 1),
('Авторские напитки', 'gal-drinks.webp', 'drinks', 4, 1),
('Десерт дня', 'gal-dessert.webp', 'dishes', 5, 1),
('Команда ресторана', 'gal-team.webp', 'team', 6, 1),
('Вечернее событие', 'gal-event.webp', 'events', 7, 1),
('Сервировка стола', 'gal-table.webp', 'interior', 8, 1);

INSERT INTO pages (page_key, title, subtitle, content, image, video_url, meta_title, meta_description) VALUES
('home_hero', 'Aroma Restaurant', 'Добро пожаловать', 'Авторская кухня, свежие ингредиенты и атмосфера, в которую хочется возвращаться.', 'hero-main.webp', NULL, 'Aroma Restaurant', 'Ресторан Aroma — авторская кухня и тёплая атмосфера.'),
('about', 'Добро пожаловать в Aroma', 'О нас', 'Мы объединяем свежие продукты, современную подачу и внимательный сервис, чтобы каждый визит оставлял приятные впечатления.\n\nСвежие продукты\nВысокое качество\nЛюбовь к деталям', 'about-preview.webp', 'https://www.youtube.com/embed/ScMzIvxBSi4?rel=0', 'О ресторане Aroma', 'История и философия ресторана Aroma.'),
('contacts', 'Контакты', 'Свяжитесь с нами', 'Напишите нам или позвоните — ответим по брони, меню и мероприятиям.', NULL, NULL, 'Контакты Aroma', 'Адрес, телефон и форма связи ресторана Aroma.');

INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('restaurant_name', 'Aroma', 'string'),
('restaurant_full_name', 'Aroma Restaurant', 'string'),
('tagline', 'Изысканная кухня в атмосфере тепла', 'string'),
('phone', '+7 (495) 123-45-67', 'string'),
('phone_href', '+74951234567', 'string'),
('email', 'hello@aroma-rest.ru', 'string'),
('whatsapp', 'https://wa.me/74951234567', 'string'),
('address', 'Москва, ул. Тверская, 18', 'string'),
('map_url', 'https://yandex.ru/maps/?text=Москва%2C%20ул.%20Тверская%2C%2018', 'string'),
('map_embed', 'https://yandex.ru/map-widget/v1/?text=Москва%2C%20ул.%20Тверская%2C%2018&z=16', 'string'),
('base_url', '', 'string'),
('rating', '4.9', 'string'),
('guests_count_label', '5000+', 'string'),
('reviews_count_label', '1200+', 'string'),
('hero_title', 'Aroma Restaurant', 'string'),
('hero_text', 'Авторская кухня, свежие ингредиенты и атмосфера, в которую хочется возвращаться.', 'string'),
('hero_image', 'hero-main.webp', 'string'),
('about_video_url', 'https://www.youtube.com/embed/ScMzIvxBSi4?rel=0', 'string'),
('meta_title_default', 'Aroma Restaurant', 'string'),
('meta_description_default', 'Aroma Restaurant — авторская кухня, свежие ингредиенты и тёплая атмосфера.', 'string'),
('notify_email', '', 'string'),
('timezone', 'Europe/Moscow', 'string');

INSERT INTO opening_hours (day_number, day_name, time_from, time_to, is_closed, sort_order) VALUES
(1, 'Понедельник', '12:00:00', '23:00:00', 0, 1),
(2, 'Вторник', '12:00:00', '23:00:00', 0, 2),
(3, 'Среда', '12:00:00', '23:00:00', 0, 3),
(4, 'Четверг', '12:00:00', '23:00:00', 0, 4),
(5, 'Пятница', '12:00:00', '00:00:00', 0, 5),
(6, 'Суббота', '12:00:00', '00:00:00', 0, 6),
(7, 'Воскресенье', '12:00:00', '23:00:00', 0, 7);

INSERT INTO social_links (platform, url, icon, is_active, sort_order) VALUES
('WhatsApp', 'https://wa.me/74951234567', 'whatsapp', 1, 1),
('Instagram', 'https://instagram.com/', 'instagram', 1, 2),
('Facebook', 'https://facebook.com/', 'facebook', 1, 3),
('TikTok', 'https://tiktok.com/', 'tiktok', 1, 4);
