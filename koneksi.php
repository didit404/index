<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "login";

// Supaya tidak throw fatal exception jika MySQL XAMPP belum aktif
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect($host, $user, $pass);

if ($conn) {
    // 1. Buat database jika belum ada
    @mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_select_db($conn, $db);

    // 2. Buat tabel users jika belum ada
    $createUsersTable = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nama_lengkap` VARCHAR(100) DEFAULT NULL,
        `no_telp` VARCHAR(30) DEFAULT NULL,
        `lokasi_sekolah` VARCHAR(255) DEFAULT 'Kelas / Ruangan',
        `catatan_lokasi` VARCHAR(255) DEFAULT NULL,
        `role` ENUM('user', 'admin') DEFAULT 'user',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    @mysqli_query($conn, $createUsersTable);

    // 3. Buat tabel products jika belum ada
    $createProductsTable = "CREATE TABLE IF NOT EXISTS `products` (
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
        `composition` TEXT,
        `taste_notes` VARCHAR(255) DEFAULT NULL,
        `serving` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    @mysqli_query($conn, $createProductsTable);

    // Cek apakah tabel products kosong, jika ya isi 4 menu awal
    $checkProd = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `products`");
    if ($checkProd) {
        $row = mysqli_fetch_assoc($checkProd);
        if (($row['cnt'] ?? 0) == 0) {
            $comp1 = json_encode(["Buah Stroberi Segar Grade A","Toasted Crispy Kataifi Kunafa","Artisanal Pistachio Cream","Warm Chocolate Ganache Drip"]);
            $comp2 = json_encode(["100% Buah Stroberi Segar Utuh Pilihan","Tanpa bahan pemanis tambahan","Tanpa bahan pengawet","Dikemas higienis menjaga kelembapan"]);
            $comp3 = json_encode(["1x SilverQueen Almonds Milk Chocolate (58g)","1x SilverQueen Chunky Bar with Almonds","1x SilverQueen Chunky White Cashews","Pita Satin Hadiah Eksklusif","Custom Handwritten Greeting Card"]);
            $comp4 = json_encode(["Buah Stroberi Segar Pilihan Berkualitas","Saus Cokelat Leleh Kental Khas Prestige","Aroma vanila & cocoa manis seimbang"]);

            $insertInitial = "INSERT INTO `products` (`id`, `name`, `category`, `slug`, `tag`, `badge_class`, `border_color`, `border_class`, `image`, `price`, `price_formatted`, `unit`, `rating`, `sold`, `short_desc`, `full_desc`, `composition`, `taste_notes`, `serving`) VALUES
            (1, 'Strawberry Chocolate Pistachio Kunafa Drip', 'Signature Kunafa', 'pistachio-kunafa-drip', '✨ Signature Handmade', 'bg-[#eef6db] text-[#557827] border-[#c8e29a]', '#22c55e', 'border-[2.5px] border-[#22c55e] hover:border-[#16a34a] hover:shadow-lg hover:shadow-emerald-100', 'images/pistachio-kunafa-drip.jpg', 30000, 'Rp 30.000', '/ cup', 4.9, '1.8k+ Terjual', 'Kunafa garing keemasan dengan krim pistachio Bronte, saus drip cokelat Belgia hangat dan stroberi segar.', 'Sensasi dessert istimewa buatan tangan! Perpaduan lembut kunafa (kataifi) renyah panggang mentega gurih, pasta pistachio murni yang lembut legit, siraman cokelat lumer yang lezat, serta potongan buah stroberi segar yang memberikan keseimbangan rasa manis dan asam segar alami.', '" . mysqli_real_escape_string($conn, $comp1) . "', 'Crunchy, Creamy, Nutty, Sweet & Refreshingly Tangy', 'Dessert Cup praktis 350ml dengan 2 sendok kayu eksklusif.'),
            (2, 'Fresh Strawberry', 'Fresh Fruits', 'fresh-strawberry', '🍓 100% Segar Alami', 'bg-[#eef6db] text-[#557827] border-[#c8e29a]', '#22c55e', 'border-[2.5px] border-[#22c55e] hover:border-[#16a34a] hover:shadow-lg hover:shadow-emerald-100', 'images/fresh-strawberry.jpg', 18000, 'Rp 18.000', '/ pack (250g)', 4.8, '2.5k+ Terjual', 'Buah stroberi segar berkualitas tinggi dipetik langsung dari kebun pilihan. Manis alami, asam segar, bertekstur padat dan kaya vitamin C.', 'Pilihan buah stroberi segar grade super yang dipanen dari perkebunan dataran tinggi berudara sejuk. Memiliki warna merah merona alami, tekstur padat berair (juicy), serta keseimbangan rasa manis dan asam segar yang nikmat. 100% alami tanpa bahan kimia pengawet.', '" . mysqli_real_escape_string($conn, $comp2) . "', 'Juicy, Naturally Sweet, Crisp, & Vibrant Acidity', 'Kemasan pack higienis 250 gram siap santap.'),
            (3, 'Chocolate & Notes', 'Gift & Hampers', 'chocolate-notes', '🎁 Special Gift & Birthday', 'bg-[#ffebee] text-[#c2185b] border-[#ffcdd2]', '#ef4444', 'border-[2.5px] border-[#ef4444] hover:border-[#dc2626] hover:shadow-lg hover:shadow-rose-100', 'images/chocolate-notes.jpg', 55000, 'Rp 55.000', '/ bundle set', 5.0, '920+ Terjual', 'Paket kado manis kombinasi aneka cokelat SilverQueen pilihan dengan hiasan pita satin dan kartu ucapan kustom (custom handwritten notes).', 'Ungkapkan rasa kasih dan perhatian Anda dengan kado manis yang berkesan! Paket ''Chocolate & Notes'' menghadirkan aneka varian cokelat SilverQueen favorit (Chunky Bar Cashew, Almonds Milk Chocolate, dan White Chocolate Cashew) yang diikat rapi dengan pita satin biru yang manis, dilengkapi custom message note untuk ucapan ulang tahun, anniversary, atau momen spesial lainnya.', '" . mysqli_real_escape_string($conn, $comp3) . "', 'Smooth Chocolate, Crunchy Roasted Almonds, Creamy Cocoa Butter', 'Paket kado siap kirim dengan packaging rapi dan manis.'),
            (4, 'Strawberry Chocolate Drip', 'Classic Drip', 'strawberry-chocolate-drip', '🍫 Classic Favorite', 'bg-[#ffebee] text-[#c2185b] border-[#ffcdd2]', '#ef4444', 'border-[2.5px] border-[#ef4444] hover:border-[#dc2626] hover:shadow-lg hover:shadow-rose-100', 'images/strawberry-chocolate-drip.jpg', 24000, 'Rp 24.000', '/ cup', 4.9, '3.4k+ Terjual', 'Potongan buah stroberi segar berukuran besar yang disiram lelehan saus cokelat lumer kental berlimpah dalam cup praktis to-go.', 'Kombinasi klasik manis yang selalu disukai! Buah stroberi merah segar berukuran montok ditata rapi dalam cup dessert, kemudian disiram saus cokelat leleh kental berkualitas tinggi yang melimpah hingga ke dasar cup. Rasa asam manis buah berpadu lembut dengan pekatnya cokelat lumer di setiap gigitan.', '" . mysqli_real_escape_string($conn, $comp4) . "', 'Decadent Melted Chocolate, Sweet Fresh Strawberry, Balanced Richness', 'Dessert Cup praktis 250ml dengan tutup kubah dan tusukan kayu estetik.')";
            @mysqli_query($conn, $insertInitial);
        }
    }

    // 4. Buat tabel orders jika belum ada
    $createOrdersTable = "CREATE TABLE IF NOT EXISTS `orders` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    @mysqli_query($conn, $createOrdersTable);

    // 5. Buat tabel sliders jika belum ada
    $createSlidersTable = "CREATE TABLE IF NOT EXISTS `sliders` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    @mysqli_query($conn, $createSlidersTable);

    // Seed data sliders jika tabel masih kosong
    $checkSliders = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `sliders`");
    if ($checkSliders) {
        $sRow = mysqli_fetch_assoc($checkSliders);
        if (($sRow['cnt'] ?? 0) == 0) {
            $sFile = __DIR__ . '/uploads/sliders.json';
            $defaultSliders = [];
            if (file_exists($sFile)) {
                $defaultSliders = json_decode(file_get_contents($sFile), true) ?: [];
            }
            if (!empty($defaultSliders)) {
                foreach ($defaultSliders as $idx => $ds) {
                    $sid = (int)($ds['id'] ?? ($idx + 1));
                    $stitle = mysqli_real_escape_string($conn, $ds['title'] ?? '');
                    $ssubtitle = mysqli_real_escape_string($conn, $ds['subtitle'] ?? '');
                    $sbadge = mysqli_real_escape_string($conn, $ds['badge'] ?? '');
                    $sdesc = mysqli_real_escape_string($conn, $ds['description'] ?? '');
                    $simg = mysqli_real_escape_string($conn, $ds['image'] ?? '');
                    $sctaText = mysqli_real_escape_string($conn, $ds['cta_text'] ?? 'Pilih Menu');
                    $sctaLink = mysqli_real_escape_string($conn, $ds['cta_link'] ?? '#katalog-produk');
                    $saccent = mysqli_real_escape_string($conn, $ds['accent_color'] ?? '#6a8738');
                    $sorder = (int)($ds['order_index'] ?? ($idx + 1));
                    @mysqli_query($conn, "INSERT INTO `sliders` (`id`, `title`, `subtitle`, `badge`, `description`, `image`, `cta_text`, `cta_link`, `accent_color`, `order_index`) VALUES ($sid, '$stitle', '$ssubtitle', '$sbadge', '$sdesc', '$simg', '$sctaText', '$sctaLink', '$saccent', $sorder)");
                }
            }
        }
    }

    // 6. Buat tabel keunggulan jika belum ada
    $createKeunggulanTable = "CREATE TABLE IF NOT EXISTS `keunggulan` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `image` VARCHAR(255) NOT NULL,
        `tag` VARCHAR(100) NOT NULL,
        `order_index` INT DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    @mysqli_query($conn, $createKeunggulanTable);
}

$koneksi = $conn; // Kompatibilitas dengan query legacy
?>
