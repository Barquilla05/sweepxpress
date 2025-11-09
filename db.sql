-- Create database & tables for SweepXpress (run in phpMyAdmin)
CREATE DATABASE IF NOT EXISTS sweepxpress_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sweepxpress_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  google_id VARCHAR(255) NULL,
  facebook_id VARCHAR(255) NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  profile_image VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  image_path VARCHAR(255),
  stock INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

/* Order Status*/

ALTER TABLE `orders` ADD `status` VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER `total`;
ALTER TABLE `orders` ADD `address` VARCHAR(255) NULL AFTER `total`;

-- seed admin user (email: admin@sx.local, password: admin123)
INSERT INTO users (name,email,password_hash,role) VALUES
('Admin','admin@.local', '$2y$10$97C0lN5nIyk7VJ5vUQHcYOmMZ2jCvBRKv8vujwES1jM2xZ2z8yZvy','admin')
ON DUPLICATE KEY UPDATE email=email;

-- seed sample products
INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_path`, `stock`, `created_at`) VALUES
(1, '3M Scotch Brite', 'a brand of cleaning and surface conditioning products, known for its non-woven abrasive pads and sponges made from synthetic fibers infused with abrasives like aluminum oxide or silicon carbide.', 100.00, '/sweepxpress/assets/3M Scotch Brite.jpg', 100, '2025-09-06 03:22:37'),
(2, '3M General Purpose Adhesive Cleaner', 'a solvent-based product designed to dissolve and remove sticky adhesive residue, grease, oil, wax, tar, and light paint overspray from various surfaces, including cured automotive paint, vinyl, and fabrics. ', 300.00, '/sweepxpress/assets/3M General Purpose Adhesive Cleaner.jpg', 50, '2025-09-06 03:15:16'),
(3, '3M Gloves', 'washable work gloves offering good dexterity, breathability, and grip for light to medium-duty tasks like material handling, small parts assembly, and general construction, featuring a nitrile or polyurethane foam-coated palm and a knit wrist cuff for comfort and a secure fit.', 150.00, '/sweepxpress/assets/3M Gloves.jpg', 50, '2025-09-06 03:15:16'),
(4, '3M Sharpshoote No Rinse Mark Remover', 'a ready-to-use, extra-strength cleaner designed to remove difficult stains, spots, and grease from a wide variety of hard, washable surfaces without requiring rinsing. ', 150.00, '/sweepxpress/assets/3M Sharpshoote No Rinse Mark Remover.jpg', 200, '2025-09-06 03:22:37'),
(5, '3M Duct Tapes', 'a durable, cloth-backed tape with a strong adhesive designed for general-purpose applications like bundling, sealing, and repairs, and it comes in different strengths and features, such as water resistance and hand-tearability.\r\n', 149.00, '/sweepxpress/assets/3M Duct Tapes.jpg', 50, '2025-09-06 03:08:21'),
(6, '3M Sanitizer Concentrate', 'an EPA-registered, concentrated liquid sanitizer for hard, non-porous surfaces, including food-contact surfaces in commercial settings. \r\n', 149.00, '/sweepxpress/assets/3M Sanitizer Concentrate.jpg', 50, '2025-09-06 03:08:21'),
(7, 'Scotch-Brite Quick Clean Griddle Starting Kit', 'a complete set designed for fast, safe, and efficient cleaning of hot commercial griddles, using a powerful, Green Seal™-certified liquid cleaner to remove burnt-on food soil without strong odors or caustic soda. ', 149.00, '/sweepxpress/assets/Scotch-Brite Quick Clean Griddle Starting Kit.jpg', 50, '2025-09-06 03:08:21'),
(8, '3M Solution Tablets', 'EPA-registered, concentrated disinfectant tablets that, when dissolved in water, create a solution to kill a broad spectrum of microbes, including Clostridioides difficile (C. diff) spores, Norovirus, SARS-CoV-2, and various bacteria, on hard, non-porous surfaces.', 200.00, '/sweepxpress/assets/3M Solution Tablets.jpg', 150, '2025-09-06 03:25:25'),
(9, '3M Spray Buff', 'a milky-white, ready-to-use emulsion designed for spray buffing operations to quickly clean, polish, and restore the luster of floor finishes, especially 3M and high-quality synthetic finishes.', 90.00, '/sweepxpress/assets/3M Spray Buff.jpg', 200, '2025-09-06 03:25:25'),
(10, '3M Stainless Steel Cleaner and Polish', 'a ready-to-use aerosol product designed to clean and polish metal surfaces in a single step, leaving a high-gloss, streak-free finish. ', 180.00, '/sweepxpress/assets/3M Stainless Steel Cleaner and Polish.jpg', 300, '2025-09-06 03:27:19'),
(11, '3M Super Shine', 'a floor finish that creates a durable, high-gloss, and protective layer on various hard floors, including ceramic tiles, vinyl, rubber, and terrazzo.', 300.00, '/sweepxpress/assets/3M Super Shine.jpg', 250, '2025-09-06 03:27:19'),
(12, '3M White Super Polish Pad 4100', 'a fine-grade, white, non-woven polyester fiber pad designed for light cleaning, buffing soft finishes, and polishing soft waxes on wood or other protected floors. ', 60.00, '/sweepxpress/assets/3M White Super Polish Pad 4100.jpg', 500, '2025-09-06 03:29:38'),
(13, '3m-scotchgard-stone-floor-protector-plus-3-785-liter-bag', 'a high-performance solution that hardens, seals, and protects porous stone floors like concrete, marble, and terrazzo by creating a durable, glossy, and scuff-resistant surface. ', 160.00, '/sweepxpress/assets/3m-scotchgard-stone-floor-protector-plus-3-785-liter-bag.avif', 500, '2025-09-06 03:29:38'),
(14, '3M™ Nomad™ Scraper Matting 7150, Light Green', 'Durable vinyl-loops scrape, trap and hide dirt and moisture, minimizing re-tracking into the building', 20.00, '/sweepxpress/assets/3M™ Nomad™ Scraper Matting 7150, Light Green.jpg', 250, '2025-09-06 03:33:23'),
(15, 'Karchar K 3 Power Control', 'a mid-range electric pressure washer designed for home and garden use, featuring a Power Control spray gun with an LED display for selecting and monitoring pressure levels. ', 1100.00, '/sweepxpress/assets/Karchar K 3 Power Control.jpg', 50, '2025-09-06 03:33:23'),
(16, 'Karcher Bag-Less Powerful Vacuum Cleaner,', 'provide consistent, high suction with multi-cyclone technology, eliminating the need for filter bags.', 1500.00, '/sweepxpress/assets/Karcher Bag-Less Powerful Vacuum Cleaner.jpg', 20, '2025-09-06 03:37:30'),
(17, 'Plain 3M Nomad Z Web Mat', 'a durable, all-vinyl floor mat having an open, continuously patterned surface. ', 50.00, '/sweepxpress/assets/Plain 3M Nomad Z Web Mat.webp', 100, '2025-09-06 03:37:30'),
(18, 'Scotch Brite Ultra Fine Hand Sanding', 'a load-resistant, non-woven abrasive pad that uses silicon carbide to achieve a fine, uniform finish, effectively replacing steel wool without the risks of rust, splintering, or shredding. ', 20.00, '/sweepxpress/assets/Scotch Brite Ultra Fine Hand Sanding.jpg', 300, '2025-09-06 03:40:48'),
(19, 'ScotchBrite Grout Brush', 'Easily clean in nooks and crannies with the Scotch-Brite Grout & Detail Brush. Its powerful non-scratch bristles are safe on grout, tile, bathroom fixtures, faucets, drains, and more! The Scotch-Brite® Grout & Detail Brush features antimicrobial bristle protection* that works to prevent bacterial odors. Get the most out of this durable, reusable brush with a thorough cleaning after use.', 60.00, '/sweepxpress/assets/ScotchBrite Grout Brush.jpg', 100, '2025-09-06 03:40:48');