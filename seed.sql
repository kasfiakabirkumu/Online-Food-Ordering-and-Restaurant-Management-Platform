INSERT INTO categories (name, description) VALUES
 ('Burgers', 'Juicy grilled burgers'),
 ('Pizza', 'Stone baked pizzas'),
 ('Rice and Curry', 'Traditional Bangladeshi favourites'),
 ('Drinks', 'Hot and cold beverages'),
 ('Desserts', 'Sweet endings');

INSERT INTO menu_items (category_id, name, description, price, emoji) VALUES
 (1, 'Classic Beef Burger', 'Beef patty, lettuce, tomato and cheese', 280.00, '🍔'),
 (1, 'Chicken Crispy Burger', 'Crispy fried chicken with mayo', 240.00, '🍔'),
 (2, 'Margherita Pizza', 'Tomato sauce, mozzarella and basil', 450.00, '🍕'),
 (2, 'BBQ Chicken Pizza', 'BBQ chicken, onions and cheese', 550.00, '🍕'),
 (3, 'Chicken Biryani', 'Aromatic basmati rice with spiced chicken', 320.00, '🍛'),
 (3, 'Beef Tehari', 'Spicy beef and rice, Dhaka style', 300.00, '🍛'),
 (3, 'Khichuri with Egg', 'Comfort lentil rice with a fried egg', 180.00, '🍲'),
 (4, 'Mango Lassi', 'Chilled yoghurt and mango drink', 120.00, '🥭'),
 (4, 'Masala Tea', 'Hot milk tea with spices', 60.00, '☕'),
 (4, 'Fresh Lemonade', 'Lemon, mint and soda', 90.00, '🍋'),
 (5, 'Chocolate Brownie', 'Warm brownie with chocolate sauce', 150.00, '🍫'),
 (5, 'Firni', 'Traditional creamy rice pudding', 110.00, '🍮');
