-- Restore known-good Russian texts without dropping tables (safe upserts by id/slug)
SET NAMES utf8mb4;

UPDATE categories SET name='Пицца', description='Пицца из печи на тонком и пышном тесте' WHERE slug='pizza';
UPDATE categories SET name='Бургеры', description='Сочные бургеры с авторскими соусами' WHERE slug='burgers';
UPDATE categories SET name='Шаурма', description='Шаурма в лаваше и питах' WHERE slug='shawarma';
UPDATE categories SET name='Гриль', description='Мясо и овощи с гриля' WHERE slug='grill';
UPDATE categories SET name='Салаты', description='Свежие и сытные салаты' WHERE slug='salads';
UPDATE categories SET name='Напитки', description='Лимонады, кофе и авторские напитки' WHERE slug='drinks';
UPDATE categories SET name='Десерты', description='Сладкое завершение вечера' WHERE slug='desserts';

UPDATE category_translations ct
INNER JOIN categories c ON c.id = ct.category_id
SET ct.name = c.name, ct.description = c.description
WHERE ct.language_code = 'ru';

UPDATE pages SET
  title='Aroma Restaurant',
  subtitle='Добро пожаловать',
  content='Авторская кухня, свежие ингредиенты и атмосфера, в которую хочется возвращаться.',
  meta_title='Aroma Restaurant',
  meta_description='Ресторан Aroma — авторская кухня и тёплая атмосфера.'
WHERE page_key='home_hero';

UPDATE pages SET
  title='Добро пожаловать в Aroma',
  subtitle='О нас',
  content='Мы объединяем свежие продукты, современную подачу и внимательный сервис, чтобы каждый визит оставлял приятные впечатления.\n\nСвежие продукты\nВысокое качество\nЛюбовь к деталям',
  meta_title='О ресторане Aroma',
  meta_description='История и философия ресторана Aroma.'
WHERE page_key='about';

UPDATE pages SET
  title='Контакты',
  subtitle='Свяжитесь с нами',
  content='Напишите нам или позвоните — ответим по брони, меню и мероприятиям.',
  meta_title='Контакты Aroma',
  meta_description='Адрес, телефон и форма связи ресторана Aroma.'
WHERE page_key='contacts';

UPDATE page_translations pt
INNER JOIN pages p ON p.id = pt.page_id
SET pt.title=p.title, pt.subtitle=p.subtitle, pt.content=p.content,
    pt.meta_title=p.meta_title, pt.meta_description=p.meta_description
WHERE pt.language_code='ru';

UPDATE gallery SET title='Интерьер зала' WHERE sort_order=1;
UPDATE gallery SET title='Пицца из печи' WHERE sort_order=2;
UPDATE gallery SET title='Горячее блюдо' WHERE sort_order=3;
UPDATE gallery SET title='Авторские напитки' WHERE sort_order=4;
UPDATE gallery SET title='Десерт дня' WHERE sort_order=5;
UPDATE gallery SET title='Команда ресторана' WHERE sort_order=6;
UPDATE gallery SET title='Вечернее событие' WHERE sort_order=7;
UPDATE gallery SET title='Сервировка стола' WHERE sort_order=8;

UPDATE opening_hours SET day_name='Понедельник' WHERE day_number=1;
UPDATE opening_hours SET day_name='Вторник' WHERE day_number=2;
UPDATE opening_hours SET day_name='Среда' WHERE day_number=3;
UPDATE opening_hours SET day_name='Четверг' WHERE day_number=4;
UPDATE opening_hours SET day_name='Пятница' WHERE day_number=5;
UPDATE opening_hours SET day_name='Суббота' WHERE day_number=6;
UPDATE opening_hours SET day_name='Воскресенье' WHERE day_number=7;

UPDATE settings SET setting_value='Авторская кухня, свежие ингредиенты и атмосфера, в которую хочется возвращаться.' WHERE setting_key='hero_text';
UPDATE settings SET setting_value='Изысканная кухня в атмосфере тепла' WHERE setting_key='tagline';
UPDATE settings SET setting_value='Aroma Restaurant — авторская кухня, свежие ингредиенты и тёплая атмосфера.' WHERE setting_key='meta_description_default';
