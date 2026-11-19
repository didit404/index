<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$baseUri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
include_once("koneksi.php");

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : null;

// Data 4 Produk Utama Sesuai Foto & Mockup
$products = [
    [
        "id" => 1,
        "name" => "Strawberry Chocolate Pistachio Kunafa Drip",
        "category" => "Pistachio Kunafa",
        "slug" => "pistachio-kunafa-drip",
        "tag" => "✨ Fresh Handmade",
        "badge_class" => "bg-[#eef6db] text-[#557827] border-[#c8e29a]",
        "border_color" => "#22c55e",
        "border_class" => "border-[2.5px] border-[#22c55e] hover:border-[#16a34a] hover:shadow-lg hover:shadow-emerald-100",
        "image" => "images/pistachio-kunafa-drip.jpg",
        "price" => 30000,
        "price_formatted" => "Rp 30.000",
        "unit" => "/ cup",
        "rating" => 4.9,
        "short_desc" => "Kunafa garing keemasan dengan krim pistachio Bronte, saus drip cokelat Belgia hangat dan stroberi segar.",
        "full_desc" => "Sensasi dessert istimewa buatan tangan! Perpaduan lembut kunafa (kataifi) renyah panggang mentega gurih, pasta pistachio murni yang lembut legit, siraman cokelat lumer yang lezat, serta potongan buah stroberi segar yang memberikan keseimbangan rasa manis dan asam segar alami.",
        "composition" => [
            "Buah Stroberi Segar Grade A",
            "Toasted Crispy Kataifi Kunafa",
            "Pistachio Cream",
            "Warm Chocolate Ganache Drip"
        ],
        "taste_notes" => "Crunchy, Creamy, Nutty, Sweet & Refreshingly Tangy",
        "serving" => "Dessert Cup praktis 350ml dengan 2 sendok kayu eksklusif."
    ],
    [
        "id" => 2,
        "name" => "Fresh Strawberry",
        "category" => "Fresh Fruits",
        "slug" => "fresh-strawberry",
        "tag" => "🍓 100% Segar Alami",
        "badge_class" => "bg-[#eef6db] text-[#557827] border-[#c8e29a]",
        "border_color" => "#22c55e",
        "border_class" => "border-[2.5px] border-[#22c55e] hover:border-[#16a34a] hover:shadow-lg hover:shadow-emerald-100",
        "image" => "images/fresh-strawberry.jpg",
        "price" => 18000,
        "price_formatted" => "Rp 18.000",
        "rating" => 4.8,
        "short_desc" => "Buah stroberi segar berkualitas tinggi dipetik langsung dari kebun pilihan. Manis alami, asam segar, bertekstur padat dan kaya vitamin C.",
        "full_desc" => "Pilihan buah stroberi segar grade super yang dipanen dari perkebunan dataran tinggi berudara sejuk. Memiliki warna merah merona alami, tekstur padat berair (juicy), serta keseimbangan rasa manis dan asam segar yang nikmat. 100% alami tanpa bahan kimia pengawet.",
        "composition" => [
            "100% Buah Stroberi Segar Utuh Pilihan",
            "Tanpa bahan pemanis tambahan",
            "Tanpa bahan pengawet",
            "Dikemas higienis menjaga kelembapan"
        ],
        "taste_notes" => "Juicy, Naturally Sweet, Crisp, & Vibrant Acidity",
        "serving" => "Kemasan pack higienis 250 gram siap santap."
    ],
    [
        "id" => 3,
        "name" => "Chocolate & Notes",
        "category" => "Gift & Hampers",
        "slug" => "chocolate-notes",
        "tag" => "🎁 Special Gift & Birthday",
        "badge_class" => "bg-[#ffebee] text-[#c2185b] border-[#ffcdd2]",
        "border_color" => "#ef4444",
        "border_class" => "border-[2.5px] border-[#ef4444] hover:border-[#dc2626] hover:shadow-lg hover:shadow-rose-100",
        "image" => "images/chocolate-notes.jpg",
        "price" => 55000,
        "price_formatted" => "Rp 55.000",
        "unit" => "/ bundle set",
        "rating" => 5.0,
        "short_desc" => "Paket kado manis kombinasi aneka cokelat SilverQueen pilihan dengan hiasan pita satin dan kartu ucapan kustom (custom handwritten notes).",
        "full_desc" => "Ungkapkan rasa kasih dan perhatian Anda dengan kado manis yang berkesan! Paket 'Chocolate & Notes' menghadirkan aneka varian cokelat SilverQueen favorit (Chunky Bar Cashew, Almonds Milk Chocolate, dan White Chocolate Cashew) yang diikat rapi dengan pita satin biru yang manis, dilengkapi custom message note untuk ucapan ulang tahun, anniversary, atau momen spesial lainnya.",
        "composition" => [
            "1x SilverQueen Almonds Milk Chocolate (58g)",
            "1x SilverQueen Chunky Bar with Almonds",
            "1x SilverQueen Chunky White Cashews",
            "Pita Satin Hadiah Eksklusif",
            "Custom Handwritten Greeting Card"
        ],
        "taste_notes" => "Smooth Chocolate, Crunchy Roasted Almonds, Creamy Cocoa Butter",
        "serving" => "Paket kado siap kirim dengan packaging rapi dan manis."
    ],
    [
        "id" => 4,
        "name" => "Strawberry Chocolate Drip",
        "category" => "Classic Drip",
        "slug" => "strawberry-chocolate-drip",
        "tag" => "🍫 Classic Favorite",
        "badge_class" => "bg-[#ffebee] text-[#c2185b] border-[#ffcdd2]",
        "border_color" => "#ef4444",
        "border_class" => "border-[2.5px] border-[#ef4444] hover:border-[#dc2626] hover:shadow-lg hover:shadow-rose-100",
        "image" => "images/strawberry-chocolate-drip.jpg",
        "price" => 24000,
        "price_formatted" => "Rp 24.000",
        "unit" => "/ cup",
        "rating" => 4.9,
        "short_desc" => "Potongan buah stroberi segar berukuran besar yang disiram lelehan saus cokelat lumer kental berlimpah dalam cup praktis to-go.",
        "full_desc" => "Kombinasi klasik manis yang selalu disukai! Buah stroberi merah segar berukuran montok ditata rapi dalam cup dessert, kemudian disiram saus cokelat leleh kental berkualitas tinggi yang melimpah hingga ke dasar cup. Rasa asam manis buah berpadu lembut dengan pekatnya cokelat lumer di setiap gigitan.",
        "composition" => [
            "Buah Stroberi Segar Pilihan Berkualitas",
            "Saus Cokelat Leleh Kental Khas Prestige",
            "Aroma vanila & cocoa manis seimbang"
        ],
        "taste_notes" => "Decadent Melted Chocolate, Sweet Fresh Strawberry, Balanced Richness",
        "serving" => "Dessert Cup praktis 250ml dengan tutup kubah dan tusukan kayu estetik."
    ]
];

// Ambil produk dinamis dari MySQL Database jika aktif
if (!empty($conn)) {
    $dbProdQuery = @mysqli_query($conn, "SELECT * FROM `products` ORDER BY `id` ASC");
    if ($dbProdQuery && mysqli_num_rows($dbProdQuery) > 0) {
        $dbProducts = [];
        while ($row = mysqli_fetch_assoc($dbProdQuery)) {
            $row['id'] = (int)$row['id'];
            $row['composition'] = json_decode($row['composition'] ?? '[]', true);
            $row['price'] = (int)$row['price'];
            $row['rating'] = (float)$row['rating'];
            $dbProducts[] = $row;
        }
        if (!empty($dbProducts)) {
            $products = $dbProducts;
        }
    }
}

// Muat 5 Data Slider Hero (MySQL atau uploads/sliders.json)
// Muat 5 Data Slider Hero (MySQL atau uploads/sliders.json)
$slidersFile = __DIR__ . '/uploads/sliders.json';
$sliders = [];
if (!empty($conn)) {
    $slideRes = @mysqli_query($conn, "SELECT * FROM `sliders` ORDER BY `order_index` ASC");
    if ($slideRes && mysqli_num_rows($slideRes) > 0) {
        while ($sRow = mysqli_fetch_assoc($slideRes)) {
            $sliders[] = $sRow;
        }
    }
}
if (empty($sliders) && file_exists($slidersFile)) {
    $sliders = json_decode(file_get_contents($slidersFile), true) ?: [];
}

// Muat 4 Data Keunggulan Berbasis Foto (MySQL atau uploads/keunggulan.json)
$keunggulanFile = __DIR__ . '/uploads/keunggulan.json';
$keunggulan = [];
if (!empty($conn)) {
    $keungRes = @mysqli_query($conn, "SELECT * FROM `keunggulan` ORDER BY `order_index` ASC");
    if ($keungRes && mysqli_num_rows($keungRes) > 0) {
        while ($kRow = mysqli_fetch_assoc($keungRes)) {
            $keunggulan[] = $kRow;
        }
    }
}
if (empty($keunggulan) && file_exists($keunggulanFile)) {
    $keunggulan = json_decode(file_get_contents($keunggulanFile), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pistachio Prestige</title>
    <base href="<?= htmlspecialchars($baseUri) ?>">
    
    <!-- Favicon Logo Pistachio Prestige -->
    <link rel="icon" type="image/png" href="<?= $baseUri ?>images/logo-prestige.png">
    <link rel="shortcut icon" type="image/png" href="<?= $baseUri ?>images/logo-prestige.png">
    <link rel="apple-touch-icon" href="<?= $baseUri ?>images/logo-prestige.png">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        creamBg: '#faf5e8',
                        creamCard: '#fffef9',
                        pistachio: {
                            100: '#eef6db',
                            200: '#dceebb',
                            400: '#a5c96b',
                            500: '#8ba84f',
                            700: '#566e2c',
                            900: '#2c3c13',
                        },
                        berry: {
                            50: '#fff1f3',
                            100: '#ffe4e8',
                            200: '#fecdd6',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                        },
                        choco: {
                            100: '#f7ebe4',
                            500: '#8c533e',
                            700: '#5c3324',
                            900: '#381e14',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #faf5e8;
            color: #3b281c;
        }
        .font-serif { font-family: 'Playfair Display', serif; }
        .font-script { font-family: 'Alex Brush', cursive; }
        
        .nav-pastel {
            background: rgba(255, 253, 247, 0.94);
            backdrop-filter: blur(14px);
            border-bottom: 1.5px solid #ebdcc5;
        }

        /* Judul Produk Kaligrafi sesuai referensi slide dengan gaya estetik lembut */
        .title-produk-wrapper {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
        }
        .title-p {
            font-family: 'Alex Brush', cursive;
            font-size: 5rem;
            line-height: 0.9;
            color: #d1334c;
            text-shadow: 0 4px 12px rgba(209, 51, 76, 0.2);
            margin-right: -0.06em;
            transform: translateY(6px);
        }
        .title-roduk {
            font-family: 'Playfair Display', serif;
            font-size: 3.3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #d1334c;
            text-shadow: 0 4px 12px rgba(209, 51, 76, 0.2);
        }

        @media (max-width: 640px) {
            .title-p { font-size: 3.8rem; }
            .title-roduk { font-size: 2.5rem; }
        }

        /* Shopee Striped Ribbon Preview */
        .shopee-ribbon-mini {
            height: 3px;
            width: 100%;
            background-size: 60px 3px;
            background-image: repeating-linear-gradient(45deg, #f26b7a, #f26b7a 15px, transparent 0, transparent 20px, #5b9bd5 0, #5b9bd5 35px, transparent 0, transparent 40px);
        }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen selection:bg-rose-400 selection:text-white">

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 nav-pastel shadow-xs transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Brand Logo (Pistachio Prestige) -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3 group">
                        <div class="w-13 h-13 rounded-2xl overflow-hidden bg-white border border-[#e8d7be] p-1 shadow-sm group-hover:scale-105 transition-transform duration-200 shrink-0 flex items-center justify-center">
                            <img src="images/logo-prestige.png" alt="Pistachio Prestige Logo" class="w-11 h-11 object-contain">
                        </div>
                        <div>
                            <span class="text-xl sm:text-2xl font-serif font-extrabold tracking-tight text-[#3b281c] block leading-tight">
                                <span class="text-[#6a8738]">Pistachio</span> <span class="text-rose-500">Prestige</span>
                            </span>
                            <span class="text-[9.5px] uppercase font-bold tracking-wider text-[#6a8738] block -mt-0.5">
                                Strawberry Chocolate Kunafa
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Nav Links (Dengan Icon Lengkap) -->
                <nav class="hidden md:flex items-center space-x-7 text-sm font-bold text-[#634938]">
                    <a href="index.php" class="text-rose-600 hover:text-rose-700 transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-house text-xs text-rose-500"></i>
                        <span>Beranda</span>
                    </a>
                    <a href="#katalog-produk" class="hover:text-rose-600 transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-layer-group text-xs text-rose-500"></i>
                        <span>Menu Produk (4)</span>
                    </a>
                    <a href="#keunggulan" class="hover:text-rose-600 transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-award text-xs text-amber-500"></i>
                        <span>Keunggulan</span>
                    </a>
                    <a href="checkout.php" class="hover:text-[#ee4d2d] transition-colors flex items-center gap-1.5 bg-rose-50 border border-rose-200 px-3 py-1 rounded-xl text-rose-700">
                        <i class="fa-solid fa-bag-shopping text-xs text-[#ee4d2d]"></i>
                        <span>Checkout</span>
                    </a>
                </nav>

                <!-- Search, Riwayat Beli, Cart & Account -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    
                    <!-- Fitur Riwayat Beli (Garis Hitam Jadi Fitur Riwayat Beli) -->
                    <a href="riwayat.php" title="Lihat Riwayat Pembelian Anda" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-2xl bg-[#eef6db] hover:bg-[#dceebb] text-[#557827] border border-[#cbe1a3] text-xs font-bold shadow-xs transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                        <span>Riwayat Beli</span>
                    </a>

                    <!-- Cart Trigger Button -->
                    <button onclick="toggleCartDrawer()" class="relative p-2.5 rounded-2xl bg-white hover:bg-stone-50 border border-[#e8d7be] text-[#553b2a] hover:text-rose-600 transition shadow-xs group">
                        <i class="fa-solid fa-cart-shopping text-base group-hover:scale-110 transition-transform"></i>
                        <span id="cart-counter-badge" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center border-2 border-white shadow-xs">0</span>
                    </button>

                    <!-- User Auth Button -->
                    <?php if ($isLoggedIn): ?>
                        <div class="flex items-center gap-2 pl-2 border-l border-stone-300">
                            <span class="text-xs font-bold text-emerald-800 hidden sm:inline bg-[#eef6db] px-3 py-1.5 rounded-xl border border-[#cbe1a3]">
                                <i class="fa-solid fa-user-check mr-1 text-[#6a8738]"></i> <?= $username ?>
                            </span>
                            <a href="logout.php" class="px-3.5 py-2 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                <span>Keluar</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 pl-2 border-l border-stone-300">
                            <a href="login.php" class="px-3 py-1.5 text-xs font-bold text-[#553b2a] hover:text-rose-600 transition">
                                Masuk
                            </a>
                            <a href="register.php" class="px-3.5 py-1.5 text-xs font-bold bg-[#8ea750] hover:bg-[#7a933f] text-white rounded-xl shadow-xs transition">
                                Daftar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </header>

    <!-- MAIN BODY -->
    <main class="flex-grow">
        
        <!-- ================= 1. HERO IMAGE CAROUSEL (5 SLIDES DENGAN NAVIGASI & KONTROL LENGKAP) ================= -->
        <section id="hero-slider-section" class="relative overflow-hidden bg-gradient-to-b from-[#faf5e8] via-[#fffdf7] to-[#faf5e8] border-b border-[#ece0cb]">
            <!-- Soft pastel glow shapes -->
            <div class="absolute -top-24 -left-24 w-80 h-80 bg-[#eef6db] rounded-full blur-3xl opacity-70 pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-[#ffe4e8] rounded-full blur-3xl opacity-70 pointer-events-none"></div>

            <!-- Slider Wrapper -->
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
                <!-- Slides Container (Overflow Hidden) -->
                <div class="overflow-hidden rounded-3xl relative">
                    <div id="hero-slides-track" class="flex transition-transform duration-700 ease-in-out w-full">
                        <?php foreach ($sliders as $idx => $s): ?>
                            <div class="w-full shrink-0 min-w-full px-6 sm:px-12 lg:px-16 py-3 sm:py-6">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                                    
                                    <!-- Left Column: Text & CTA (Diberi ruang cukup agar tombol navigasi tidak menabrak teks) -->
                                    <div class="lg:col-span-7 space-y-4 sm:space-y-6 text-center lg:text-left pl-0 sm:pl-2">
                                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/90 border border-[#ebdcc5] shadow-2xs backdrop-blur-xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                            <span id="hero-slide-badge-<?= $idx ?>" class="text-[11px] font-bold text-[#6a8738] uppercase tracking-wider">
                                                <?= htmlspecialchars($s['badge'] ?? $s['subtitle'] ?? 'Pistachio Prestige') ?>
                                            </span>
                                        </div>

                                        <h1 id="hero-slide-title-<?= $idx ?>" class="text-3xl sm:text-4xl lg:text-5xl font-serif font-extrabold tracking-tight leading-tight sm:leading-[1.2] text-[#3b281c]">
                                            <?php 
                                             // Highlight text dengan gradien
                                            $t = $s['title'];
                                            if (strpos($t, '&') !== false) {
                                                $parts = explode('&', $t, 2);
                                                echo htmlspecialchars($parts[0]) . ' <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#6a8738] via-[#d1334c] to-[#a3442a]">& ' . htmlspecialchars($parts[1]) . '</span>';
                                            } else {
                                                echo htmlspecialchars($t);
                                            }
                                            ?>
                                        </h1>

                                        <p id="hero-slide-desc-<?= $idx ?>" class="text-[#6d513f] text-sm sm:text-base max-w-2xl leading-relaxed mx-auto lg:mx-0">
                                            <?= htmlspecialchars($s['description']) ?>
                                        </p>

                                        <!-- Buttons -->
                                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5 pt-2">
                                            <a id="hero-slide-cta-<?= $idx ?>" href="<?= htmlspecialchars($s['cta_link'] ?? '#katalog-produk') ?>" class="w-full sm:w-auto px-7 py-3.5 rounded-2xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-extrabold text-sm shadow-md shadow-rose-200 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                                                <span><?= htmlspecialchars($s['cta_text'] ?? 'Pilih Menu Sekarang') ?></span>
                                                <i class="fa-solid fa-arrow-right text-xs"></i>
                                            </a>
                                            <a href="checkout.php" class="w-full sm:w-auto px-7 py-3.5 rounded-2xl bg-white hover:bg-stone-50 border-2 border-[#ee4d2d] text-[#ee4d2d] font-extrabold text-sm shadow-2xs hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                                                <i class="fa-solid fa-bag-shopping"></i>
                                                <span>Checkout</span>
                                            </a>
                                        </div>

                                        <!-- Quick Stats -->
                                        <div class="grid grid-cols-2 gap-6 pt-5 border-t border-[#ebdcc5] max-w-xs mx-auto lg:mx-0 text-left">
                                            <div>
                                                <p class="text-xl sm:text-2xl font-extrabold text-[#6a8738]">100%</p>
                                                <p class="text-[11px] text-[#7e604d] font-semibold">Pure Bronte Pistachio</p>
                                            </div>
                                            <div>
                                                <p class="text-xl sm:text-2xl font-extrabold text-rose-500">Fresh</p>
                                                <p class="text-[11px] text-[#7e604d] font-semibold">Dibuat Setiap Hari</p>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Right Column: Image Card -->
                                    <div class="lg:col-span-5 flex items-center justify-center">
                                        <div class="relative w-full max-w-xs sm:max-w-sm lg:max-w-md bg-white border border-[#e6d6bc] rounded-3xl p-5 sm:p-7 shadow-xl flex items-center justify-center group overflow-hidden">
                                            <div class="aspect-square w-full rounded-2xl overflow-hidden bg-[#faf5e8] flex items-center justify-center">
                                                <img src="<?= htmlspecialchars($s['image']) ?>" 
                                                     alt="<?= htmlspecialchars($s['title']) ?>" 
                                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 filter drop-shadow-md">
                                            </div>
                                            <span class="absolute top-8 right-8 px-3 py-1 rounded-full bg-white/95 backdrop-blur-md text-[11px] font-bold text-[#382116] shadow-xs border border-[#ebdcc5]">
                                                Slide <?= ($idx + 1) ?> / <?= count($sliders) ?>
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Slider Controls: Prev & Next Buttons -->
                <button onclick="prevSlide()" aria-label="Slide Sebelumnya" class="absolute left-1.5 sm:left-3 top-1/2 -translate-y-1/2 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-white/95 hover:bg-white text-[#382116] hover:text-rose-600 shadow-md border border-[#ebdcc5] flex items-center justify-center transition-all z-20 hover:scale-110">
                    <i class="fa-solid fa-chevron-left text-sm"></i>
                </button>
                <button onclick="nextSlide()" aria-label="Slide Berikutnya" class="absolute right-1.5 sm:right-3 top-1/2 -translate-y-1/2 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-white/95 hover:bg-white text-[#382116] hover:text-rose-600 shadow-md border border-[#ebdcc5] flex items-center justify-center transition-all z-20 hover:scale-110">
                    <i class="fa-solid fa-chevron-right text-sm"></i>
                </button>

                <!-- 5 Slider Indicator Dots -->
                <div class="flex items-center justify-center gap-2.5 pt-6 sm:pt-8 z-20 relative" id="slider-dots-container">
                    <?php for ($i = 0; $i < count($sliders); $i++): ?>
                        <button onclick="goToSlide(<?= $i ?>)" aria-label="Ke Slide <?= $i+1 ?>" class="slider-dot transition-all duration-300 h-2.5 rounded-full <?= $i === 0 ? 'w-8 bg-rose-500' : 'w-2.5 bg-[#d4c1aa] hover:bg-rose-300' ?>" data-index="<?= $i ?>"></button>
                    <?php endfor; ?>
                </div>

            </div>
        </section>

        <!-- ================= 2. KEUNGGULAN DENGAN FOTO ASLI (4 KARTU BERBASIS GAMBAR) ================= -->
        <section id="keunggulan" class="scroll-mt-24 py-12 sm:py-16 lg:py-20 bg-[#faf5e8] border-b border-[#ece0cb]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Judul Kaligrafi Keunggulan serupa Produk Sesuai Permintaan User -->
                <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12 pt-2 sm:pt-4">
                    <div class="title-produk-wrapper">
                        <span class="title-p">K</span>
                        <span class="title-roduk">eunggulan</span>
                    </div>
                    <p class="text-xs sm:text-sm text-[#7d5f4c] mt-1 font-medium">
                        Cita Rasa Khas Pistachio Bronte Murni, Stroberi Segar Alami, dan Dapur Bersih Higienis.
                    </p>
                    <div class="w-16 h-1 bg-gradient-to-r from-[#8ea750] via-rose-500 to-[#d1334c] rounded-full mx-auto mt-3"></div>
                </div>

                <!-- 4 Kartu Keunggulan Berbasis Foto Asli -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
                    <?php foreach ($keunggulan as $k): ?>
                        <div class="group flex flex-col justify-between bg-white rounded-3xl p-5 border border-[#ebe0cc] shadow-2xs hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300">
                            <div>
                                <!-- Area Gambar Foto Keunggulan -->
                                <div class="relative h-44 sm:h-48 w-full rounded-2xl overflow-hidden bg-[#faf5e8] border border-[#f0e4cf] mb-4 flex items-center justify-center p-3">
                                    <img src="<?= htmlspecialchars($k['image']) ?>" 
                                         alt="<?= htmlspecialchars($k['title']) ?>" 
                                         class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500">
                                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md text-[10px] font-bold text-[#557827] border border-[#cbe1a3] shadow-xs">
                                        <?= htmlspecialchars($k['tag'] ?? 'Keunggulan') ?>
                                    </span>
                                </div>

                                <h4 class="text-sm sm:text-base font-bold text-[#3b281c] font-serif mb-1 group-hover:text-rose-600 transition-colors">
                                    <?= htmlspecialchars($k['title']) ?>
                                </h4>
                                <p class="text-xs text-[#7d5f4c] leading-relaxed">
                                    <?= htmlspecialchars($k['description']) ?>
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-[11px] text-[#8ea750] font-bold">
                                <span>Pistachio Prestige</span>
                                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>

        <!-- ================= SECTION KATALOG PRODUK (4 PRODUK + 5 BUTTON FILTER LENGKAP) ================= -->
        <section id="katalog-produk" class="scroll-mt-24 sm:scroll-mt-28 py-12 sm:py-16 lg:py-20 bg-[#faf5e8] border-b border-[#ece0cb]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Judul Kaligrafi Produk sesuai slide desain awal -->
                <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12 pt-2 sm:pt-4">
                    <div class="title-produk-wrapper">
                        <span class="title-p">P</span>
                        <span class="title-roduk">roduk</span>
                    </div>
                    <p class="text-xs sm:text-sm text-[#7d5f4c] mt-1 font-medium">
                        Kuantitas Tepat 4 Menu Favorit: Pistachio Kunafa Drip, Fresh Strawberry, Chocolate & Notes, dan Strawberry Chocolate Drip.
                    </p>
                    <div class="w-16 h-1 bg-gradient-to-r from-[#8ea750] via-rose-500 to-[#d1334c] rounded-full mx-auto mt-3"></div>
                </div>

                <!-- FILTER BAR DENGAN 5 TOMBOL LENGKAP (Termasuk Strawberry Chocolate Drip yang sebelumnya dicari pengguna) -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-8 sm:mb-10 bg-white p-3.5 sm:p-4 rounded-2xl border border-[#e8d7be] shadow-xs">
                    
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#eef6db] border border-[#cbe1a3] text-[#557827] text-xs font-bold">
                            <i class="fa-solid fa-cubes text-xs"></i> 4 Menu Pilihan
                        </span>
                    </div>

                    <!-- 5 Tombol Kategori Lengkap: Semua + 4 Pilihan Menu -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button onclick="filterCategory('all', event)" class="filter-btn px-3 py-1.5 text-xs rounded-xl bg-[#8ea750] text-white font-bold transition shadow-xs">
                            Semua (4)
                        </button>
                        <button onclick="filterCategory('Pistachio Kunafa', event)" class="filter-btn px-3 py-1.5 text-xs rounded-xl bg-stone-100 text-[#553b2a] hover:text-stone-900 font-semibold transition">
                            Pistachio Kunafa
                        </button>
                        <button onclick="filterCategory('Fresh Fruits', event)" class="filter-btn px-3 py-1.5 text-xs rounded-xl bg-stone-100 text-[#553b2a] hover:text-stone-900 font-semibold transition">
                            Fresh Strawberry
                        </button>
                        <button onclick="filterCategory('Gift & Hampers', event)" class="filter-btn px-3 py-1.5 text-xs rounded-xl bg-stone-100 text-[#553b2a] hover:text-stone-900 font-semibold transition">
                            Chocolate & Notes
                        </button>
                        <!-- TOMBOL PILIHAN STRAWBERRY CHOCOLATE DRIP (SELESAIKAN PERMINTAAN USER) -->
                        <button onclick="filterCategory('Classic Drip', event)" class="filter-btn px-3 py-1.5 text-xs rounded-xl bg-stone-100 text-[#553b2a] hover:text-stone-900 font-semibold transition">
                            Strawberry Chocolate Drip
                        </button>
                    </div>

                </div>

                <!-- 4 PRODUCT CARDS GRID (BINGKAI HIJAU & MERAH SESUAI FOTO) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6 lg:gap-7" id="product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="product-card group flex flex-col justify-between bg-white rounded-3xl p-4 sm:p-5 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 <?= $p['border_class'] ?>"
                             data-category="<?= $p['category'] ?>"
                             data-name="<?= strtolower($p['name']) ?>"
                             data-id="<?= $p['id'] ?>">
                            
                            <div>
                                <!-- Image Card dengan Aspect Ratio Estetik -->
                                <div class="relative aspect-[4/5] rounded-2xl overflow-hidden bg-[#faf5e8] mb-3.5 cursor-pointer" onclick="openProductModal(<?= $p['id'] ?>)">
                                    <img src="<?= $p['image'] ?>" 
                                         alt="<?= htmlspecialchars($p['name']) ?>" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    
                                    <!-- Badge Kategori / Tag & Bintang (Aman & Tidak Offside) -->
                                    <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-1.5 z-10 pointer-events-none">
                                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border backdrop-blur-md truncate max-w-[70%] <?= $p['badge_class'] ?> shadow-xs">
                                            <?= $p['tag'] ?>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full bg-white/95 backdrop-blur-md flex items-center justify-center text-amber-500 text-[11px] font-bold border border-[#e8d7be] shadow-xs shrink-0">
                                            <i class="fa-solid fa-star text-[9px] mr-1"></i> <?= $p['rating'] ?>
                                        </span>
                                    </div>

                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <span class="px-3 py-1.5 rounded-xl bg-white/95 text-[#3b281c] text-xs font-bold shadow-md">
                                            <i class="fa-regular fa-eye mr-1 text-rose-500"></i> Lihat Detail
                                        </span>
                                    </div>
                                </div>

                                <!-- Judul Produk -->
                                <h3 class="font-serif font-bold text-base text-[#3b281c] leading-snug mb-1.5 group-hover:text-rose-600 transition-colors cursor-pointer" onclick="openProductModal(<?= $p['id'] ?>)">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h3>

                                <!-- Cuplikan Deskripsi -->
                                <p class="text-xs text-[#7d5f4c] line-clamp-3 leading-relaxed mb-3">
                                    <?= $p['short_desc'] ?>
                                </p>
                            </div>

                            <!-- Bagian Bawah: Harga, Qty Counter, dan Action Buttons -->
                            <div class="pt-3 border-t border-stone-100 mt-2">
                                <div class="mb-2.5">
                                    <span class="text-[10px] text-stone-400 block font-bold uppercase tracking-wider">Harga</span>
                                    <span class="text-base sm:text-lg font-extrabold text-[#382116] font-serif">
                                        <?= $p['price_formatted'] ?>
                                        <?php if (!empty($p['unit'])): ?>
                                            <span class="text-xs font-normal text-stone-500"><?= $p['unit'] ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <!-- Kuantitas Selector -->
                                <div class="flex items-center justify-between bg-[#fcfaf4] rounded-xl p-1 mb-2.5 text-xs border border-[#ebe0cc]">
                                    <span class="text-[#7d5f4c] pl-2 font-bold">Qty:</span>
                                    <div class="flex items-center">
                                        <button onclick="decrementCardQty(<?= $p['id'] ?>)" class="w-6 h-6 rounded-lg bg-white text-stone-700 hover:bg-stone-200 flex items-center justify-center font-bold shadow-2xs">
                                            -
                                        </button>
                                        <span id="card-qty-<?= $p['id'] ?>" class="w-8 text-center font-extrabold text-[#382116]">1</span>
                                        <button onclick="incrementCardQty(<?= $p['id'] ?>)" class="w-6 h-6 rounded-lg bg-white text-stone-700 hover:bg-stone-200 flex items-center justify-center font-bold shadow-2xs">
                                            +
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Buttons: Rincian, Tambah Keranjang, dan Beli Langsung -->
                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <button onclick="openProductModal(<?= $p['id'] ?>)" class="py-2 px-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-[#553b2a] font-bold text-xs transition flex items-center justify-center gap-1">
                                            <i class="fa-solid fa-circle-info text-xs"></i> Rincian
                                        </button>
                                        <button onclick="addToCartFromCard(<?= $p['id'] ?>)" class="py-2 px-2.5 rounded-xl bg-[#eef6db] hover:bg-[#dceebb] text-[#557827] border border-[#cbe1a3] font-bold text-xs transition flex items-center justify-center gap-1">
                                            <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
                                        </button>
                                    </div>
                                    <button onclick="buyNowFromCard(<?= $p['id'] ?>)" class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-extrabold text-xs transition shadow-xs shadow-rose-200 flex items-center justify-center gap-1.5 transform hover:-translate-y-0.5">
                                        <i class="fa-solid fa-bag-shopping"></i> Beli Langsung
                                    </button>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Banner Informasi Checkout -->
                <div class="mt-12 sm:mt-16 bg-white rounded-3xl p-6 sm:p-8 lg:p-10 border border-[#e8d7be] shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-[#ee4d2d]/10 text-[#ee4d2d] flex items-center justify-center text-2xl shrink-0">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                        <div>
                            <h4 class="text-sm sm:text-base font-bold text-[#382116]">Siap Melakukan Pemesanan?</h4>
                            <p class="text-xs text-[#7d5f4c] mt-0.5">
                                Halaman checkout lengkap dengan pengaturan alamat pengiriman dan pilihan metode pembayaran yang mudah.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
                        <a href="checkout.php" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-[#ee4d2d] hover:bg-[#d73211] text-white text-xs font-extrabold shadow-md shadow-rose-200 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Buka Halaman Checkout</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- FOOTER Khas Boutique -->
    <footer class="bg-[#382116] text-[#e0cfbe] pt-12 sm:pt-16 pb-8 sm:pb-10 border-t border-[#4d2f21]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-10 text-xs">
                
                <!-- Col 1: Brand -->
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl overflow-hidden bg-white p-1 shrink-0">
                            <img src="images/logo-prestige.png" alt="Prestige Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <span class="text-lg font-serif font-bold text-white tracking-tight">
                                <span class="text-[#a5c96b]">Pistachio</span> <span class="text-rose-400">Prestige</span>
                            </span>
                            <span class="text-[9px] uppercase font-bold tracking-wider text-[#a5c96b] block -mt-0.5">
                                Strawberry Chocolate Kunafa
                            </span>
                        </div>
                    </div>
                    <p class="text-[#c2ab95] leading-relaxed">
                        Spesialis artisanal dessert. Sensasi Pistachio Kunafa, Strawberry Chocolate Drip, dan olahan buah stroberi segar.
                    </p>
                </div>

                <!-- Col 2: Navigasi -->
                <div>
                    <h4 class="text-white font-bold uppercase tracking-wider mb-3">Navigasi Toko</h4>
                    <ul class="space-y-2 text-[#c2ab95]">
                        <li><a href="index.php" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="#katalog-produk" class="hover:text-white transition">Katalog 4 Produk</a></li>
                        <li><a href="checkout.php" class="hover:text-white transition">Halaman Checkout</a></li>
                        <li><a href="riwayat.php" class="hover:text-white transition">Riwayat Beli</a></li>
                    </ul>
                </div>

                <!-- Col 3: 4 Menu -->
                <div>
                    <h4 class="text-white font-bold uppercase tracking-wider mb-3">4 Menu Unggulan</h4>
                    <ul class="space-y-2 text-[#c2ab95]">
                        <li><a href="javascript:openProductModal(1)" class="hover:text-white transition">Pistachio Kunafa Drip</a></li>
                        <li><a href="javascript:openProductModal(2)" class="hover:text-white transition">Fresh Strawberry</a></li>
                        <li><a href="javascript:openProductModal(3)" class="hover:text-white transition">Chocolate & Notes</a></li>
                        <li><a href="javascript:openProductModal(4)" class="hover:text-white transition">Strawberry Chocolate Drip</a></li>
                    </ul>
                </div>

                <!-- Col 4: Keamanan -->
                <div class="space-y-3">
                    <h4 class="text-white font-bold uppercase tracking-wider mb-3">Layanan Pengiriman</h4>
                    <p class="text-[#c2ab95]">Menerima pengiriman reguler & instan sameday dengan kemasan aman.</p>
                    <div class="flex gap-2">
                        <span class="px-2.5 py-1 bg-[#472c1f] rounded-lg text-[10px] text-white">QRIS</span>
                        <span class="px-2.5 py-1 bg-[#472c1f] rounded-lg text-[10px] text-white">Transfer Bank</span>
                        <span class="px-2.5 py-1 bg-[#472c1f] rounded-lg text-[10px] text-white">COD</span>
                    </div>
                </div>

            </div>

            <div class="pt-6 border-t border-[#4d2f21] text-center text-xs text-[#a98f79]">
                <p>&copy; <?= date('Y') ?> Pistachio Prestige. Strawberry Chocolate Kunafa Artisanal.</p>
            </div>
        </div>
    </footer>

    <!-- ================= MODAL DETAIL PRODUK ================= -->
    <div id="product-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs hidden opacity-0 transition-opacity duration-300">
        <div class="relative w-full max-w-xl bg-white rounded-3xl overflow-hidden shadow-2xl border border-[#ebdcc5] max-h-[90vh] flex flex-col transform scale-95 transition-transform duration-300 text-[#3b281c]" id="modal-container">
            
            <button onclick="closeProductModal()" class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="overflow-y-auto p-5 sm:p-7 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                    <div class="relative aspect-[4/5] rounded-2xl overflow-hidden bg-stone-50 border border-stone-200">
                        <img id="modal-image" src="" alt="" class="w-full h-full object-cover">
                        <span id="modal-tag" class="absolute top-3 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full border bg-white/90 text-[#3b281c] shadow-2xs"></span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <span id="modal-category" class="text-[11px] uppercase tracking-wider font-bold text-[#6a8738]"></span>
                            <h3 id="modal-title" class="font-serif font-bold text-xl text-[#382116] mt-0.5 leading-snug"></h3>
                        </div>

                        <div class="flex items-center gap-2">
                            <span id="modal-price" class="text-xl font-extrabold text-rose-600"></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-lg bg-[#eef6db] text-[#557827] border border-[#cbe1a3] font-bold">Ready</span>
                        </div>

                        <div>
                            <h4 class="text-[11px] uppercase tracking-wider font-bold text-stone-400 mb-1">Deskripsi Menu</h4>
                            <p id="modal-desc" class="text-xs text-[#6d513f] leading-relaxed"></p>
                        </div>

                        <div class="bg-[#faf5e8] p-3 rounded-xl border border-[#ebdcc5]">
                            <span class="text-[10.5px] text-[#8ea750] font-bold block uppercase tracking-wider mb-0.5">
                                <i class="fa-solid fa-utensils mr-1"></i> Karakter Rasa:
                            </span>
                            <p id="modal-taste" class="text-xs text-[#5c3e2e] italic"></p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-stone-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <span class="text-xs text-[#7d5f4c] font-bold">Jumlah:</span>
                        <div class="flex items-center bg-[#fcfaf4] border border-[#d6c7af] rounded-xl p-1 text-sm">
                            <button onclick="decrementModalQty()" class="w-7 h-7 flex items-center justify-center text-stone-600 hover:bg-stone-200 rounded-lg transition font-bold">-</button>
                            <span id="modal-qty-val" class="w-8 text-center font-bold text-[#382116]">1</span>
                            <button onclick="incrementModalQty()" class="w-7 h-7 flex items-center justify-center text-stone-600 hover:bg-stone-200 rounded-lg transition font-bold">+</button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button onclick="addModalItemToCart()" class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-[#3b281c] text-xs font-bold transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-cart-plus"></i> + Keranjang
                        </button>
                        <button onclick="buyNowToCheckout()" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-[#ee4d2d] hover:bg-[#d73211] text-white text-xs font-extrabold shadow-xs transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-bag-shopping"></i> Beli Langsung
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ================= CART SLIDE-OVER DRAWER (MULTI-ITEM DENGAN REKOMENDASI PRODUK) ================= -->
    <div id="cart-drawer-overlay" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-2xs hidden opacity-0 transition-opacity duration-300" onclick="toggleCartDrawer()"></div>
    <div id="cart-drawer" class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white border-l border-[#ebdcc5] shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300 ease-out text-[#3b281c]">
        
        <!-- Cart Header -->
        <div class="p-4 border-b border-[#ebdcc5] flex items-center justify-between bg-[#faf5e8]">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-cart-shopping text-rose-500 text-lg"></i>
                <h3 class="font-bold text-[#382116] text-sm sm:text-base font-serif">Keranjang Belanja</h3>
                <span id="cart-total-badge" class="text-xs bg-[#eef6db] border border-[#cbe1a3] text-[#557827] px-2.5 py-0.5 rounded-full font-bold">0 Jenis, 0 Unit</span>
            </div>
            <button onclick="toggleCartDrawer()" class="w-8 h-8 rounded-full bg-white text-stone-500 hover:text-stone-900 flex items-center justify-center border border-stone-200 transition">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <!-- Shopee Ribbon Mini -->
        <div class="shopee-ribbon-mini"></div>

        <!-- Scrollable Middle Section: Cart Items + Rekomendasi -->
        <div class="flex-1 overflow-y-auto">
            <!-- Cart Items List -->
            <div id="cart-items-container" class="p-4 space-y-3">
                <div id="empty-cart-state" class="text-center py-16 text-stone-400">
                    <i class="fa-solid fa-cart-shopping text-4xl mb-3 opacity-30"></i>
                    <p class="text-sm font-semibold">Keranjang Anda masih kosong.</p>
                    <p class="text-xs mt-1">Pilih dari 4 menu lezat kami di halaman utama!</p>
                </div>
            </div>

            <!-- Rekomendasi Produk Tambahan ("Lengkapi Pesananmu") -->
            <div id="cart-recommendations-section" class="p-4 border-t border-[#ebdcc5] bg-[#fffdf7] hidden">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-[#382116] flex items-center gap-1.5 font-serif">
                        <i class="fa-solid fa-sparkles text-amber-500"></i> Lengkapi Pesananmu
                    </span>
                    <span class="text-[10.5px] text-[#7d5f4c]">Menu Rekomendasi</span>
                </div>
                <div id="cart-recommendations-list" class="space-y-2">
                    <!-- Diisi secara dinamis melalui JavaScript -->
                </div>
            </div>
        </div>

        <!-- Cart Footer / Subtotal & Navigation Buttons -->
        <div class="p-4 border-t border-[#ebdcc5] bg-[#faf5e8] space-y-3">
            <div class="flex items-center justify-between text-sm">
                <div>
                    <span class="text-[#7d5f4c] text-xs block">Total Tagihan:</span>
                    <span class="text-xs text-stone-400" id="cart-summary-text">0 produk</span>
                </div>
                <span id="cart-grand-total" class="font-extrabold text-lg sm:text-xl text-rose-600 font-serif">Rp 0</span>
            </div>

            <!-- Dua Tombol Aksi: Lanjut Belanja & Lanjut ke Checkout Sesuai Ketentuan -->
            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" onclick="toggleCartDrawer()" class="w-full py-3 px-3 rounded-xl bg-white hover:bg-stone-50 border border-[#ebdcc5] text-[#553b2a] font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-store text-xs text-[#8ea750]"></i>
                    <span>Lanjut Belanja</span>
                </button>
                <button type="button" onclick="proceedToCheckout()" id="btn-to-checkout" disabled class="w-full py-3 px-3 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-extrabold text-xs shadow-md shadow-rose-200 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-bag-shopping text-xs"></i>
                    <span>Checkout</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-24 opacity-0 transition-all duration-300 bg-[#3b281c] text-white px-4 py-3 rounded-2xl shadow-xl flex items-center gap-3 text-xs">
        <i class="fa-solid fa-circle-check text-[#a5c96b] text-base"></i>
        <span id="toast-message">Item berhasil ditambahkan ke keranjang!</span>
    </div>

    <!-- JAVASCRIPT LOGIKA LENGKAP -->
    <script>
        const productsData = <?= json_encode($products) ?>;
        let cart = [];
        let currentModalProductId = 1;
        let modalQty = 1;
        let cardQuantities = { 1: 1, 2: 1, 3: 1, 4: 1 };

        // Muat keranjang yang tersimpan dari localStorage saat halaman dibuka
        try {
            const saved = localStorage.getItem('prestige_cart');
            if (saved) {
                cart = JSON.parse(saved);
            }
        } catch(e) {
            console.error(e);
        }

        function incrementCardQty(id) {
            cardQuantities[id] = (cardQuantities[id] || 1) + 1;
            document.getElementById(`card-qty-${id}`).innerText = cardQuantities[id];
        }

        function decrementCardQty(id) {
            if (cardQuantities[id] > 1) {
                cardQuantities[id] -= 1;
                document.getElementById(`card-qty-${id}`).innerText = cardQuantities[id];
            }
        }

        // Filter Kategori (Mendukung ke-5 Tombol Kategori)
        function filterCategory(category, e) {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('bg-[#8ea750]', 'text-white', 'shadow-xs');
                btn.classList.add('bg-stone-100', 'text-[#553b2a]');
            });
            e.target.classList.remove('bg-stone-100', 'text-[#553b2a]');
            e.target.classList.add('bg-[#8ea750]', 'text-white', 'shadow-xs');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (category === 'all' || card.getAttribute('data-category') === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Modal Produk Detail
        function openProductModal(id) {
            const product = productsData.find(p => Number(p.id) === Number(id));
            if (!product) return;

            currentModalProductId = Number(id);
            modalQty = cardQuantities[Number(id)] || 1;
            document.getElementById('modal-qty-val').innerText = modalQty;

            document.getElementById('modal-image').src = product.image;
            document.getElementById('modal-image').alt = product.name;
            document.getElementById('modal-tag').innerText = product.tag;
            document.getElementById('modal-category').innerText = product.category;
            document.getElementById('modal-title').innerText = product.name;
            document.getElementById('modal-price').innerText = product.price_formatted + (product.unit || '');
            document.getElementById('modal-desc').innerText = product.full_desc;
            document.getElementById('modal-taste').innerText = product.taste_notes;

            const modal = document.getElementById('product-modal');
            const container = document.getElementById('modal-container');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                container.classList.remove('scale-95');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            const modal = document.getElementById('product-modal');
            const container = document.getElementById('modal-container');
            modal.classList.add('opacity-0');
            container.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        function incrementModalQty() {
            modalQty++;
            document.getElementById('modal-qty-val').innerText = modalQty;
        }

        function decrementModalQty() {
            if (modalQty > 1) {
                modalQty--;
                document.getElementById('modal-qty-val').innerText = modalQty;
            }
        }

        function toggleCartDrawer() {
            const drawer = document.getElementById('cart-drawer');
            const overlay = document.getElementById('cart-drawer-overlay');
            
            if (drawer.classList.contains('translate-x-full')) {
                overlay.classList.remove('hidden');
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                    drawer.classList.remove('translate-x-full');
                }, 10);
                document.body.style.overflow = 'hidden';
            } else {
                drawer.classList.add('translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            }
        }

        function addToCart(productId, qty) {
            const pId = Number(productId);
            const product = productsData.find(p => Number(p.id) === pId);
            if (!product) {
                console.warn("Produk tidak ditemukan:", productId);
                return;
            }

            const addQty = Math.max(1, parseInt(qty, 10) || 1);
            const existingIndex = cart.findIndex(item => Number(item.id) === pId);
            if (existingIndex > -1) {
                cart[existingIndex].qty += addQty;
            } else {
                cart.push({
                    id: Number(product.id),
                    name: product.name,
                    price: Number(product.price),
                    price_formatted: product.price_formatted,
                    image: product.image,
                    qty: addQty
                });
            }

            saveCartToStorage();
            updateCartUI();
            showToast(`${product.name} (${addQty}x) ditambahkan ke keranjang!`);
        }

        function addToCartFromCard(productId) {
            const pId = Number(productId);
            const qty = cardQuantities[pId] || 1;
            addToCart(pId, qty);
            toggleCartDrawer(); // Buka drawer agar pembeli langsung melihat keranjang & tombol checkout
        }

        function buyNowFromCard(productId) {
            const pId = Number(productId);
            const qty = cardQuantities[pId] || 1;
            addToCart(pId, qty);
            saveCartToStorage();
            if (!userIsLoggedIn) {
                alert("Silakan Masuk atau Buat Akun dengan Nickname terlebih dahulu untuk melanjutkan Checkout!");
                window.location.href = 'login.php?redirect=checkout.php';
                return;
            }
            window.location.href = 'checkout.php';
        }

        function addModalItemToCart() {
            addToCart(currentModalProductId, modalQty);
            closeProductModal();
            toggleCartDrawer();
        }

        const userIsLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

        function buyNowToCheckout() {
            addToCart(currentModalProductId, modalQty);
            closeProductModal();
            if (!userIsLoggedIn) {
                alert("Silakan Masuk atau Daftar Akun terlebih dahulu untuk melanjutkan Checkout!");
                window.location.href = 'login.php?redirect=checkout.php';
                return;
            }
            window.location.href = 'checkout.php';
        }

        function proceedToCheckout() {
            if (cart.length > 0) {
                saveCartToStorage();
                if (!userIsLoggedIn) {
                    alert("Silakan Masuk atau Daftar Akun terlebih dahulu untuk melanjutkan Checkout!");
                    window.location.href = 'login.php?redirect=checkout.php';
                    return;
                }
                window.location.href = 'checkout.php';
            }
        }

        function saveCartToStorage() {
            localStorage.setItem('prestige_cart', JSON.stringify(cart));
        }

        function updateCartQuantity(id, change) {
            const pId = Number(id);
            const item = cart.find(i => Number(i.id) === pId);
            if (item) {
                item.qty += change;
                if (item.qty <= 0) {
                    cart = cart.filter(i => Number(i.id) !== pId);
                }
                saveCartToStorage();
                updateCartUI();
            }
        }

        function removeCartItem(id) {
            const pId = Number(id);
            cart = cart.filter(i => Number(i.id) !== pId);
            saveCartToStorage();
            updateCartUI();
        }

        function updateCartUI() {
            const container = document.getElementById('cart-items-container');
            const counterBadge = document.getElementById('cart-counter-badge');
            const totalBadge = document.getElementById('cart-total-badge');
            const grandTotalEl = document.getElementById('cart-grand-total');
            const summaryTextEl = document.getElementById('cart-summary-text');
            const checkoutBtn = document.getElementById('btn-to-checkout');
            const recSection = document.getElementById('cart-recommendations-section');
            const recList = document.getElementById('cart-recommendations-list');

            let totalUnits = 0;
            let totalPrice = 0;

            cart.forEach(item => {
                totalUnits += item.qty;
                totalPrice += (item.price * item.qty);
            });

            const totalKinds = cart.length;

            counterBadge.innerText = totalUnits;
            totalBadge.innerText = `${totalKinds} Jenis, ${totalUnits} Cup`;
            grandTotalEl.innerText = 'Rp ' + totalPrice.toLocaleString('id-ID');
            if (summaryTextEl) {
                summaryTextEl.innerText = `${totalKinds} Jenis Produk (${totalUnits} Unit)`;
            }

            if (cart.length === 0) {
                container.innerHTML = `
                    <div id="empty-cart-state" class="text-center py-16 text-stone-400">
                        <i class="fa-solid fa-cart-shopping text-4xl mb-3 opacity-30"></i>
                        <p class="text-sm font-semibold">Keranjang Anda masih kosong.</p>
                        <p class="text-xs mt-1">Pilih dari 4 menu lezat kami di halaman utama!</p>
                    </div>
                `;
                checkoutBtn.disabled = true;
            } else {
                checkoutBtn.disabled = false;
                container.innerHTML = cart.map(item => `
                    <div class="flex items-center gap-3 bg-[#faf5e8] p-3 rounded-2xl border border-[#ebdcc5] shadow-2xs">
                        <img src="${item.image}" alt="${item.name}" class="w-14 h-14 rounded-xl object-cover border border-[#e8d7be] shrink-0">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-[#382116] truncate">${item.name}</h4>
                            <span class="text-[11px] text-stone-500 font-medium">@ Rp ${item.price.toLocaleString('id-ID')}</span>
                            <div class="flex items-center justify-between mt-1.5">
                                <div class="flex items-center gap-1.5 bg-white border border-[#ebdcc5] rounded-lg p-0.5">
                                    <button onclick="updateCartQuantity(${item.id}, -1)" class="w-5 h-5 rounded text-stone-700 hover:bg-stone-100 flex items-center justify-center text-xs font-extrabold">-</button>
                                    <span class="text-xs font-bold text-stone-900 px-1.5">${item.qty}</span>
                                    <button onclick="updateCartQuantity(${item.id}, 1)" class="w-5 h-5 rounded text-stone-700 hover:bg-stone-100 flex items-center justify-center text-xs font-extrabold">+</button>
                                </div>
                                <span class="text-xs text-rose-600 font-extrabold font-serif">Rp ${(item.price * item.qty).toLocaleString('id-ID')}</span>
                            </div>
                        </div>
                        <button onclick="removeCartItem(${item.id})" title="Hapus dari keranjang" class="text-stone-400 hover:text-rose-600 p-2 transition">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </div>
                `).join('');
            }

            // Render Rekomendasi Menu Tambahan ("You Might Also Like" / "Lengkapi Pesananmu")
            if (recSection && recList) {
                const unselectedProducts = productsData.filter(p => !cart.some(item => item.id === p.id));
                if (unselectedProducts.length > 0) {
                    recSection.classList.remove('hidden');
                    recList.innerHTML = unselectedProducts.map(p => `
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-[#ebdcc5] shadow-2xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="${p.image}" alt="${p.name}" class="w-10 h-10 rounded-lg object-cover border border-[#ebdcc5] shrink-0">
                                <div class="min-w-0">
                                    <h5 class="text-xs font-bold text-[#382116] truncate">${p.name}</h5>
                                    <span class="text-[11px] text-[#6a8738] font-extrabold">${p.price_formatted}</span>
                                </div>
                            </div>
                            <button onclick="addToCart(${p.id}, 1)" class="px-2.5 py-1.5 rounded-lg bg-[#eef6db] hover:bg-[#dceebb] text-[#557827] border border-[#cbe1a3] text-[11px] font-bold transition flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-plus text-[10px]"></i> Tambah
                            </button>
                        </div>
                    `).join('');
                } else {
                    recSection.classList.add('hidden');
                }
            }
        }

        // ================= HERO IMAGE SLIDER CONTROLLER =================
        let currentSlideIndex = 0;
        const totalSlides = <?= count($sliders) ?>;
        let sliderAutoplayInterval = null;

        function showSlide(index) {
            if (totalSlides <= 0) return;
            if (index >= totalSlides) {
                currentSlideIndex = 0;
            } else if (index < 0) {
                currentSlideIndex = totalSlides - 1;
            } else {
                currentSlideIndex = index;
            }

            const track = document.getElementById('hero-slides-track');
            if (track) {
                track.style.transform = `translateX(-${currentSlideIndex * 100}%)`;
            }

            const dots = document.querySelectorAll('.slider-dot');
            dots.forEach((dot, idx) => {
                if (idx === currentSlideIndex) {
                    dot.className = 'slider-dot transition-all duration-300 h-2.5 rounded-full w-8 bg-rose-500';
                } else {
                    dot.className = 'slider-dot transition-all duration-300 h-2.5 rounded-full w-2.5 bg-[#d4c1aa] hover:bg-rose-300';
                }
            });
        }

        function nextSlide() {
            showSlide(currentSlideIndex + 1);
        }

        function prevSlide() {
            showSlide(currentSlideIndex - 1);
        }

        function goToSlide(index) {
            showSlide(index);
            restartSliderAutoplay();
        }

        function startSliderAutoplay() {
            if (sliderAutoplayInterval) clearInterval(sliderAutoplayInterval);
            sliderAutoplayInterval = setInterval(nextSlide, 4500); // 4.5 detik
        }

        function restartSliderAutoplay() {
            startSliderAutoplay();
        }

        // Inisialisasi Event Slider (Hover Pause & Swipe Touch)
        const heroSectionEl = document.getElementById('hero-slider-section');
        if (heroSectionEl) {
            heroSectionEl.addEventListener('mouseenter', () => {
                if (sliderAutoplayInterval) clearInterval(sliderAutoplayInterval);
            });
            heroSectionEl.addEventListener('mouseleave', () => {
                startSliderAutoplay();
            });

            // Touch Swipe untuk Mobile & Tablet
            let touchStartX = 0;
            let touchEndX = 0;
            heroSectionEl.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            heroSectionEl.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 45) {
                    nextSlide();
                    restartSliderAutoplay();
                } else if (touchEndX - touchStartX > 45) {
                    prevSlide();
                    restartSliderAutoplay();
                }
            }, { passive: true });
        }

        startSliderAutoplay();

        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').innerText = msg;
            toast.classList.remove('translate-y-24', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0');
            }, 2500);
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateCartUI();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeProductModal();
                const drawer = document.getElementById('cart-drawer');
                if (!drawer.classList.contains('translate-x-full')) {
                    toggleCartDrawer();
                }
            }
        });
    </script>
</body>
</html>
