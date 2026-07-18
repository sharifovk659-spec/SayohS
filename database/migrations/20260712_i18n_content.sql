-- Proper EN/DE dish translations (idempotent upserts by dish slug)
SET NAMES utf8mb4;

UPDATE dish_translations dt
INNER JOIN dishes d ON d.id = dt.dish_id
SET
  dt.name = CASE d.slug
    WHEN 'pizza-margherita' THEN 'Margherita Pizza'
    WHEN 'pizza-pepperoni' THEN 'Pepperoni Pizza'
    WHEN 'pizza-quatro' THEN 'Four Cheese Pizza'
    WHEN 'classic-burger' THEN 'Classic Burger'
    WHEN 'bacon-burger' THEN 'Bacon Burger'
    WHEN 'chicken-burger' THEN 'Chicken Burger'
    WHEN 'classic-shawarma' THEN 'Classic Shawarma'
    WHEN 'spicy-shawarma' THEN 'Spicy Shawarma'
    WHEN 'pita-shawarma' THEN 'Pita Shawarma'
    WHEN 'grill-steak' THEN 'Grilled Steak'
    WHEN 'grilled-vegetables' THEN 'Grilled Vegetables'
    WHEN 'chicken-skewers' THEN 'Chicken Skewers'
    WHEN 'caesar-salad' THEN 'Caesar Salad'
    WHEN 'greek-salad' THEN 'Greek Salad'
    WHEN 'quinoa-salad' THEN 'Warm Quinoa Salad'
    WHEN 'house-lemonade' THEN 'House Lemonade'
    WHEN 'espresso-tonic' THEN 'Espresso Tonic'
    WHEN 'berry-morse' THEN 'Berry Morse'
    WHEN 'chocolate-fondant' THEN 'Chocolate Fondant'
    WHEN 'tiramisu' THEN 'Tiramisu'
    WHEN 'berry-cheesecake' THEN 'Berry Cheesecake'
    ELSE dt.name
  END,
  dt.short_description = CASE d.slug
    WHEN 'pizza-margherita' THEN 'Tomatoes, mozzarella and basil'
    WHEN 'pizza-pepperoni' THEN 'Spicy pepperoni and cheese'
    WHEN 'pizza-quatro' THEN 'Creamy four-cheese mix'
    WHEN 'classic-burger' THEN 'Beef, cheddar and vegetables'
    WHEN 'bacon-burger' THEN 'Bacon and BBQ sauce'
    WHEN 'chicken-burger' THEN 'Crispy chicken'
    WHEN 'classic-shawarma' THEN 'Chicken and vegetables in lavash'
    WHEN 'spicy-shawarma' THEN 'With spicy sauce'
    WHEN 'pita-shawarma' THEN 'Served in pita'
    WHEN 'grill-steak' THEN 'Steak with grilled vegetables'
    WHEN 'grilled-vegetables' THEN 'Seasonal vegetables'
    WHEN 'chicken-skewers' THEN 'Chicken on skewers'
    WHEN 'caesar-salad' THEN 'Chicken, romaine, parmesan'
    WHEN 'greek-salad' THEN 'Feta and vegetables'
    WHEN 'quinoa-salad' THEN 'Quinoa and vegetables'
    WHEN 'house-lemonade' THEN 'Citrus and mint'
    WHEN 'espresso-tonic' THEN 'Coffee and tonic'
    WHEN 'berry-morse' THEN 'Berry soft drink'
    WHEN 'chocolate-fondant' THEN 'Warm chocolate dessert'
    WHEN 'tiramisu' THEN 'Coffee and mascarpone'
    WHEN 'berry-cheesecake' THEN 'Delicate cheesecake'
    ELSE dt.short_description
  END,
  dt.description = CASE d.slug
    WHEN 'pizza-margherita' THEN 'Classic thin-crust pizza from the oven.'
    WHEN 'pizza-pepperoni' THEN 'Spicy pepperoni pizza with melted cheese.'
    WHEN 'grill-steak' THEN 'Juicy medium steak with seasonal vegetables.'
    WHEN 'chocolate-fondant' THEN 'Fondant with a molten center and ice cream.'
    ELSE COALESCE(dt.description, dt.short_description)
  END
WHERE dt.language_code = 'en';

UPDATE dish_translations dt
INNER JOIN dishes d ON d.id = dt.dish_id
SET
  dt.name = CASE d.slug
    WHEN 'pizza-margherita' THEN 'Pizza Margherita'
    WHEN 'pizza-pepperoni' THEN 'Pizza Pepperoni'
    WHEN 'pizza-quatro' THEN 'Pizza Quattro Formaggi'
    WHEN 'classic-burger' THEN 'Klassischer Burger'
    WHEN 'bacon-burger' THEN 'Bacon-Burger'
    WHEN 'chicken-burger' THEN 'Hähnchen-Burger'
    WHEN 'classic-shawarma' THEN 'Klassische Shawarma'
    WHEN 'spicy-shawarma' THEN 'Scharfe Shawarma'
    WHEN 'pita-shawarma' THEN 'Shawarma im Pitabrot'
    WHEN 'grill-steak' THEN 'Steak vom Grill'
    WHEN 'grilled-vegetables' THEN 'Gegrilltes Gemüse'
    WHEN 'chicken-skewers' THEN 'Hähnchen-Spieße'
    WHEN 'caesar-salad' THEN 'Caesar Salat'
    WHEN 'greek-salad' THEN 'Griechischer Salat'
    WHEN 'quinoa-salad' THEN 'Warmer Quinoa-Salat'
    WHEN 'house-lemonade' THEN 'Hausgemachte Limonade'
    WHEN 'espresso-tonic' THEN 'Espresso Tonic'
    WHEN 'berry-morse' THEN 'Beeren-Morse'
    WHEN 'chocolate-fondant' THEN 'Schokoladen-Fondant'
    WHEN 'tiramisu' THEN 'Tiramisu'
    WHEN 'berry-cheesecake' THEN 'Beeren-Cheesecake'
    ELSE dt.name
  END,
  dt.short_description = CASE d.slug
    WHEN 'pizza-margherita' THEN 'Tomaten, Mozzarella und Basilikum'
    WHEN 'pizza-pepperoni' THEN 'Scharfe Pepperoni und Käse'
    WHEN 'pizza-quatro' THEN 'Cremiger Vier-Käse-Mix'
    WHEN 'classic-burger' THEN 'Rindfleisch, Cheddar und Gemüse'
    WHEN 'bacon-burger' THEN 'Speck und BBQ-Sauce'
    WHEN 'chicken-burger' THEN 'Knuspriges Hähnchen'
    WHEN 'classic-shawarma' THEN 'Hähnchen und Gemüse im Lavash'
    WHEN 'spicy-shawarma' THEN 'Mit scharfer Sauce'
    WHEN 'pita-shawarma' THEN 'Im Pitabrot serviert'
    WHEN 'grill-steak' THEN 'Steak mit Grillgemüse'
    WHEN 'grilled-vegetables' THEN 'Saisonales Gemüse'
    WHEN 'chicken-skewers' THEN 'Hähnchen am Spieß'
    WHEN 'caesar-salad' THEN 'Hähnchen, Romana, Parmesan'
    WHEN 'greek-salad' THEN 'Feta und Gemüse'
    WHEN 'quinoa-salad' THEN 'Quinoa und Gemüse'
    WHEN 'house-lemonade' THEN 'Zitrusfrüchte und Minze'
    WHEN 'espresso-tonic' THEN 'Kaffee und Tonic'
    WHEN 'berry-morse' THEN 'Beeriger Erfrischungsdrink'
    WHEN 'chocolate-fondant' THEN 'Warmer Schokoladendessert'
    WHEN 'tiramisu' THEN 'Kaffee und Mascarpone'
    WHEN 'berry-cheesecake' THEN 'Zarter Käsekuchen'
    ELSE dt.short_description
  END,
  dt.description = CASE d.slug
    WHEN 'pizza-margherita' THEN 'Klassische Pizza mit dünnem Teig aus dem Ofen.'
    WHEN 'pizza-pepperoni' THEN 'Würzige Pepperoni-Pizza mit geschmolzenem Käse.'
    WHEN 'grill-steak' THEN 'Saftiges Steak medium mit saisonalem Gemüse.'
    WHEN 'chocolate-fondant' THEN 'Fondant mit flüssigem Kern und Eis.'
    ELSE COALESCE(dt.description, dt.short_description)
  END
WHERE dt.language_code = 'de';
