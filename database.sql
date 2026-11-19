-- ==============================================================
-- DATABASE SCHEMA: PISTACHIO PRESTIGE
-- Strawberry Chocolate Kunafa Artisanal Boutique
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `login` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `login`;

-- --------------------------------------------------------
-- 1. Tabel: users (Akun Pengguna & Admin)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) DEFAULT NULL,
  `no_telp` VARCHAR(30) DEFAULT NULL,
  `lokasi_sekolah` VARCHAR(255) DEFAULT 'Kelas / Ruangan',
  `catatan_lokasi` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample user & admin jika belum ada
-- Password 'admin123' dienkripsi MD5: 0192023a7bbd73250516f069df18b500
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `no_telp`, `lokasi_sekolah`, `catatan_lokasi`, `role`)
VALUES 
('admin', '0192023a7bbd73250516f069df18b500', 'Admin Dapur Prestige', '081234567890', 'Dapur Utama Sekolah', 'Pengelola Pesanan', 'admin')
ON DUPLICATE KEY UPDATE `role` = 'admin';

-- --------------------------------------------------------
-- 2. Tabel: products (Katalog 4 Produk)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `tag` VARCHAR(50) DEFAULT NULL,
  `badge_class` VARCHAR(150) DEFAULT NULL,
  `border_color` VARCHAR(20) DEFAULT '#22c55e',
  `border_class` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `price` INT NOT NULL,
  `price_formatted` VARCHAR(50) NOT NULL,
  `unit` VARCHAR(50) DEFAULT '/ cup',
  `rating` DECIMAL(2,1) DEFAULT 4.9,
  `sold` VARCHAR(50) DEFAULT '1k+ Terjual',
  `short_desc` TEXT,
  `full_desc` TEXT,
  `composition` TEXT, -- Disimpan dalam format JSON string
  `taste_notes` VARCHAR(255) DEFAULT NULL,
  `serving` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Masukkan 4 Menu Utama Pistachio Prestige
INSERT INTO `products` (`id`, `name`, `category`, `slug`, `tag`, `badge_class`, `border_color`, `border_class`, `image`, `price`, `price_formatted`, `unit`, `rating`, `sold`, `short_desc`, `full_desc`, `composition`, `taste_notes`, `serving`)
VALUES
(1, 'Strawberry Chocolate Pistachio Kunafa Drip', 'Pistachio Kunafa', 'pistachio-kunafa-drip', '✨ Fresh Handmade', 'bg-[#eef6db] text-[#557827] border-[#c8e29a]', '#22c55e', 'border-[2.5px] border-[#22c55e] hover:border-[#16a34a] hover:shadow-lg hover:shadow-emerald-100', 'images/pistachio-kunafa-drip.jpg', 30000, 'Rp 30.000', '/ cup', 4.9, '', 'Kunafa garing keemasan dengan krim pistachio Bronte, saus drip cokelat Belgia hangat dan stroberi segar.', 'Sensasi dessert istimewa buatan tangan! Perpaduan lembut kunafa (kataifi) renyah panggang mentega gurih, pasta pistachio murni yang lembut legit, siraman cokelat lumer yang lezat, serta potongan buah stroberi segar yang memberikan keseimbangan rasa manis dan asam segar alami.', '["Buah Stroberi Segar Grade A","Toasted Crispy Kataifi Kunafa","Pistachio Cream","Warm Chocolate Ganache Drip"]', 'Crunchy, Creamy, Nutty, Sweet & Refreshingly Tangy', 'Dessert Cup praktis 350ml dengan 2 sendok kayu eksklusif.'),

(2, 'Fresh Strawberry', 'Fresh Fruits', 'fresh-strawberry', '🍓 100% Segar Alami', 'bg-[#eef6db] text-[#557827] border-[#c8e29a]', '#22c55e', 'border-[2.5px] border-[#22c55e] hover:border-[#16a34a] hover:shadow-lg hover:shadow-emerald-100', 'images/fresh-strawberry.jpg', 28000, 'Rp 28.000', '/ pack (250g)', 4.8, '2.5k+ Terjual', 'Buah stroberi segar berkualitas tinggi dipetik langsung dari kebun pilihan. Manis alami, asam segar, bertekstur padat dan kaya vitamin C.', 'Pilihan buah stroberi segar grade super yang dipanen dari perkebunan dataran tinggi berudara sejuk. Memiliki warna merah merona alami, tekstur padat berair (juicy), serta keseimbangan rasa manis dan asam segar yang nikmat. 100% alami tanpa bahan kimia pengawet.', '["100% Buah Stroberi Segar Utuh Pilihan","Tanpa bahan pemanis tambahan","Tanpa bahan pengawet","Dikemas higienis menjaga kelembapan"]', 'Juicy, Naturally Sweet, Crisp, & Vibrant Acidity', 'Kemasan pack higienis 250 gram siap santap.'),

(3, 'Chocolate & Notes', 'Gift & Hampers', 'chocolate-notes', '🎁 Special Gift & Birthday', 'bg-[#ffebee] text-[#c2185b] border-[#ffcdd2]', '#ef4444', 'border-[2.5px] border-[#ef4444] hover:border-[#dc2626] hover:shadow-lg hover:shadow-rose-100', 'images/chocolate-notes.jpg', 55000, 'Rp 55.000', '/ bundle set', 5.0, '920+ Terjual', 'Paket kado manis kombinasi aneka cokelat SilverQueen pilihan dengan hiasan pita satin dan kartu ucapan kustom (custom handwritten notes).', 'Ungkapkan rasa kasih dan perhatian Anda dengan kado manis yang berkesan! Paket ''Chocolate & Notes'' menghadirkan aneka varian cokelat SilverQueen favorit (Chunky Bar Cashew, Almonds Milk Chocolate, dan White Chocolate Cashew) yang diikat rapi dengan pita satin biru yang manis, dilengkapi custom message note untuk ucapan ulang tahun, anniversary, atau momen spesial lainnya.', '["1x SilverQueen Almonds Milk Chocolate (58g)","1x SilverQueen Chunky Bar with Almonds","1x SilverQueen Chunky White Cashews","Pita Satin Hadiah Eksklusif","Custom Handwritten Greeting Card"]', 'Smooth Chocolate, Crunchy Roasted Almonds, Creamy Cocoa Butter', 'Paket kado siap kirim dengan packaging rapi dan manis.'),

(4, 'Strawberry Chocolate Drip', 'Classic Drip', 'strawberry-chocolate-drip', '🍫 Classic Favorite', 'bg-[#ffebee] text-[#c2185b] border-[#ffcdd2]', '#ef4444', 'border-[2.5px] border-[#ef4444] hover:border-[#dc2626] hover:shadow-lg hover:shadow-rose-100', 'images/strawberry-chocolate-drip.jpg', 32000, 'Rp 32.000', '/ cup', 4.9, '3.4k+ Terjual', 'Potongan buah stroberi segar berukuran besar yang disiram lelehan saus cokelat lumer kental berlimpah dalam cup praktis to-go.', 'Kombinasi klasik manis yang selalu disukai! Buah stroberi merah segar berukuran montok ditata rapi dalam cup dessert, kemudian disiram saus cokelat leleh kental berkualitas tinggi yang melimpah hingga ke dasar cup. Rasa asam manis buah berpadu lembut dengan pekatnya cokelat lumer di setiap gigitan.', '["Buah Stroberi Segar Pilihan Berkualitas","Saus Cokelat Leleh Kental Khas Prestige","Aroma vanila & cocoa manis seimbang"]', 'Decadent Melted Chocolate, Sweet Fresh Strawberry, Balanced Richness', 'Dessert Cup praktis 250ml dengan tutup kubah dan tusukan kayu estetik.')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- --------------------------------------------------------
-- 3. Tabel: orders (Pemesanan, Checkout & Pembayaran)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` VARCHAR(50) NOT NULL UNIQUE,
  `username` VARCHAR(100) NOT NULL,
  `order_date` VARCHAR(50) NOT NULL,
  `order_status` VARCHAR(100) NOT NULL,
  `payment_status` VARCHAR(100) NOT NULL,
  `recipient_name` VARCHAR(100) NOT NULL,
  `recipient_phone` VARCHAR(30) NOT NULL,
  `lokasi_sekolah` VARCHAR(255) NOT NULL,
  `catatan_lokasi` VARCHAR(255) DEFAULT NULL,
  `shipping` VARCHAR(150) DEFAULT 'Diantar Langsung ke Kelas / Ruangan (Rp 0)',
  `shipping_cost` INT DEFAULT 0,
  `payment_method` VARCHAR(100) NOT NULL,
  `payment_proof` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT,
  `items_json` LONGTEXT NOT NULL,
  `total_amount` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel: sliders (5 Slider Carousel Header)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sliders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255) NOT NULL,
  `badge` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `cta_text` VARCHAR(100) NOT NULL,
  `cta_link` VARCHAR(255) NOT NULL,
  `accent_color` VARCHAR(50) DEFAULT '#6a8738',
  `order_index` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sliders` (`id`, `title`, `subtitle`, `badge`, `description`, `image`, `cta_text`, `cta_link`, `accent_color`, `order_index`)
VALUES
(1, 'Sensasi Lembut Pistachio Kunafa & Strawberry Chocolate Drip', 'Welcome to Pistachio Prestige', '✨ Pistachio Prestige', 'Cita rasa istimewa paduan kunafa renyah panggang mentega, pasta pistachio murni yang lembut gurih, lelehan cokelat kental berkualitas, dan buah stroberi segar pilihan.', 'images/logo-prestige.png', 'Pilih Menu Sekarang', '#katalog-produk', '#6a8738', 1),
(2, 'Strawberry Chocolate Pistachio Kunafa Drip', 'Fresh Handmade Recipe', '🏆 Paling Favorit', 'Kunafa garing keemasan dengan pasta pistachio Bronte murni, siraman lelehan cokelat kental hangat dan potongan buah stroberi segar.', 'images/pistachio-kunafa-drip.jpg', 'Pesan Menu Ini', 'javascript:openProductModal(1)', '#22c55e', 2),
(3, '100% Buah Stroberi Segar Alami Pilihan', 'Fresh Fruits Grade Super', '🍓 100% Segar', 'Dipetik langsung dari perkebunan dataran tinggi berhawa sejuk. Merah merona alami, manis asam menyegarkan, kaya vitamin C.', 'images/fresh-strawberry.jpg', 'Pesan Stroberi Segar', 'javascript:openProductModal(2)', '#e11d48', 3),
(4, 'Strawberry Chocolate Drip Cup Praktis To-Go', 'Classic Favorite', '🍫 Cokelat Lumer', 'Kombinasi klasik favorit cup dessert dengan stroberi segar montok disiram saus cokelat lumer tebal hingga ke dasar cup.', 'images/strawberry-chocolate-drip.jpg', 'Pesan Cup Classic', 'javascript:openProductModal(4)', '#d97706', 4),
(5, 'Chocolate & Notes Special Gift & Hampers', 'Special Gift & Birthday', '🎁 Kado Manis', 'Paket kado manis kombinasi aneka cokelat SilverQueen favorit dengan pita satin eksklusif dan kartu ucapan kustom.', 'images/chocolate-notes.jpg', 'Pesan Paket Kado', 'javascript:openProductModal(3)', '#be123c', 5)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- --------------------------------------------------------
-- 5. Tabel: keunggulan (4 Keunggulan Berbasis Foto)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `keunggulan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `tag` VARCHAR(100) NOT NULL,
  `order_index` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `keunggulan` (`id`, `title`, `description`, `image`, `tag`, `order_index`)
VALUES
(1, 'Bahan Baku Pilihan', 'Pasta pistachio Bronte murni pilihan tanpa perisa sintesis dengan cita rasa gurih legit alami.', 'images/keunggulan/bahan-baku.png', 'Pure Pistachio', 1),
(2, 'Tekstur Crunchy', 'Adonan kataifi kunafa dipanggang garing keemasan dengan butter gurih harum menggugah selera.', 'images/keunggulan/tekstur-crunchy.png', 'Toasted Butter', 2),
(3, '100% Stroberi Segar', 'Buah stroberi montok dipetik langsung dari kebun dataran tinggi, padat berair dan kaya vitamin C.', 'images/keunggulan/stroberi-segar.webp', 'Grade A Super', 3),
(4, 'Kualitas Premium & Halal', 'Diproses di dapur higienis berstandar food grade serta terjamin 100% Bersih & Halal Indonesia.', 'images/keunggulan/halal-premium.webp', '100% Bersih', 4)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

