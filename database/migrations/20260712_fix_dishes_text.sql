SET NAMES utf8mb4;

UPDATE dishes SET
  name='Салат Цезарь',
  short_description='Курица, романо, пармезан',
  description='Классический цезарь с хрустящими крутонами.',
  ingredients='Романо, курица, пармезан, крутоны, соус',
  weight='260 г'
WHERE slug='caesar-salad';

UPDATE dishes SET
  name='Шоколадный фондан',
  short_description='Тёплый шоколадный десерт',
  description='Фондан с жидкой сердцевиной и мороженым.',
  ingredients='Шоколад, масло, яйца, сахар, мука',
  weight='140 г'
WHERE slug='chocolate-fondant';

-- Fix weight units and remaining dish fields from known seed
UPDATE dishes SET weight='450 г' WHERE slug='pizza-margherita';
UPDATE dishes SET weight='480 г' WHERE slug='pizza-pepperoni';
UPDATE dishes SET weight='470 г' WHERE slug='pizza-quatro';
UPDATE dishes SET weight='320 г' WHERE slug='classic-burger';
UPDATE dishes SET weight='350 г' WHERE slug='bacon-burger';
UPDATE dishes SET weight='310 г' WHERE slug='chicken-burger';
UPDATE dishes SET weight='380 г' WHERE slug='classic-shawarma';
UPDATE dishes SET weight='390 г' WHERE slug='spicy-shawarma';
UPDATE dishes SET weight='360 г' WHERE slug='pita-shawarma';
UPDATE dishes SET weight='280 г' WHERE slug IN ('grill-steak','grilled-vegetables');
UPDATE dishes SET weight='250 г' WHERE slug='chicken-skewers';
UPDATE dishes SET weight='240 г' WHERE slug='greek-salad';
UPDATE dishes SET weight='250 г' WHERE slug='quinoa-salad';
UPDATE dishes SET weight='400 мл' WHERE slug IN ('house-lemonade','berry-morse');
UPDATE dishes SET weight='300 мл' WHERE slug='espresso-tonic';
UPDATE dishes SET weight='160 г' WHERE slug='tiramisu';
UPDATE dishes SET weight='150 г' WHERE slug='berry-cheesecake';

UPDATE dish_translations dt
INNER JOIN dishes d ON d.id = dt.dish_id
SET
  dt.name = d.name,
  dt.short_description = d.short_description,
  dt.description = d.description,
  dt.ingredients = d.ingredients
WHERE dt.language_code = 'ru';
