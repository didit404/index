<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$baseUri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
include_once("koneksi.php");

// 1. Strict Auth Gate: Wajib Login / Register Sebelum Bisa Checkout
if (!isset($_SESSION['username'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

$username = $_SESSION['username'];
$userProfile = $_SESSION['user_data'] ?? [
    'username'       => $username,
    'nama_lengkap'   => $username,
    'no_telp'        => '081234567890',
    'lokasi_sekolah' => 'Kelas XII RPL 1',
    'catatan_lokasi' => 'Gedung B Lantai 2, samping Lab Komputer'
];

if (empty($userProfile['lokasi_sekolah'])) {
    $userProfile['lokasi_sekolah'] = 'Kelas XII RPL 1';
}
if (empty($userProfile['catatan_lokasi'])) {
    $userProfile['catatan_lokasi'] = 'Antar saat jam istirahat sekolah';
}
if (empty($userProfile['nama_lengkap'])) {
    $userProfile['nama_lengkap'] = $username;
}
if (empty($userProfile['no_telp'])) {
    $userProfile['no_telp'] = '081234567890';
}

// 2. Authoritative Product Catalog (Digunakan untuk Validasi Harga di Sisi Server & Rekomendasi)
$catalogProducts = [
    [
        "id" => 1,
        "name" => "Strawberry Chocolate Pistachio Kunafa Drip",
        "price" => 30000,
        "price_formatted" => "Rp 30.000",
        "image" => "images/pistachio-kunafa-drip.jpg",
        "tag" => "✨ Signature Handmade"
    ],
    [
        "id" => 2,
        "name" => "Fresh Strawberry",
        "price" => 18000,
        "price_formatted" => "Rp 18.000",
        "image" => "images/fresh-strawberry.jpg",
        "tag" => "🍓 100% Segar Alami"
    ],
    [
        "id" => 3,
        "name" => "Chocolate & Notes",
        "price" => 55000,
        "price_formatted" => "Rp 55.000",
        "image" => "images/chocolate-notes.jpg",
        "tag" => "🎁 Special Gift & Birthday"
    ],
    [
        "id" => 4,
        "name" => "Strawberry Chocolate Drip",
        "price" => 32000,
        "price_formatted" => "Rp 32.000",
        "image" => "images/strawberry-chocolate-drip.jpg",
        "tag" => "🍫 Classic Favorite"
    ]
];

// Ambil harga database MySQL jika tersedia
if (!empty($conn)) {
    $catRes = @mysqli_query($conn, "SELECT id, name, price, image FROM `products` ORDER BY id ASC");
    if ($catRes && mysqli_num_rows($catRes) > 0) {
        $dbCat = [];
        while ($cRow = mysqli_fetch_assoc($catRes)) {
            $dbCat[] = [
                'id' => (int)$cRow['id'],
                'name' => $cRow['name'],
                'price' => (int)$cRow['price'],
                'price_formatted' => 'Rp ' . number_format((int)$cRow['price'], 0, ',', '.'),
                'image' => $cRow['image'],
                'tag' => 'Menu Prestige'
            ];
        }
        if (!empty($dbCat)) {
            $catalogProducts = $dbCat;
        }
    }
}

// Handle Form Submission untuk Buat Pesanan
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $paymentMethod = htmlspecialchars($_POST['payment_method'] ?? 'QRIS');
    
    // Status awal pesanan berdasarkan metode pembayaran
    if (strpos($paymentMethod, 'COD') !== false) {
        $orderStatus = 'Pesanan Dibuat (Bayar COD di Sekolah)';
        $paymentStatus = 'Belum Bayar (Bayar Tunai saat Kurir Tiba di Kelas/Ruangan)';
    } elseif (strpos($paymentMethod, 'Transfer') !== false) {
        $orderStatus = 'Menunggu Verifikasi Bukti Transfer';
        $paymentStatus = 'Bukti Terkirim (Diverifikasi Admin Dapur)';
    } else {
        $orderStatus = 'Menunggu Verifikasi QRIS';
        $paymentStatus = 'Bukti QRIS Terkirim (Diverifikasi Admin Dapur)';
    }

    // Handle Upload Bukti Pembayaran (Screenshot QRIS / Struk Transfer)
    $proofPath = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['payment_proof']['tmp_name'];
        $origName = $_FILES['payment_proof']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            $proofsDir = __DIR__ . '/uploads/proofs';
            if (!is_dir($proofsDir)) {
                @mkdir($proofsDir, 0777, true);
            }
            $fileName = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $target = $proofsDir . '/' . $fileName;
            if (move_uploaded_file($tmpName, $target)) {
                $proofPath = 'uploads/proofs/' . $fileName;
            }
        }
    }

    // ================= VALIDASI & PERHITUNGAN ULANG HARGA DI SISI SERVER (BACKEND) =================
    // Mencegah manipulasi harga atau total dari browser client
    $rawItems = json_decode($_POST['items_json'] ?? '[]', true);
    if (!is_array($rawItems) || empty($rawItems)) {
        $rawItems = [[
            'id' => 1,
            'name' => $catalogProducts[0]['name'],
            'price' => $catalogProducts[0]['price'],
            'image' => $catalogProducts[0]['image'],
            'qty' => 1
        ]];
    }

    $verifiedItems = [];
    $verifiedSubtotal = 0;
    foreach ($rawItems as $rawItem) {
        $itemId = (int)($rawItem['id'] ?? 0);
        $qty = max(1, (int)($rawItem['qty'] ?? 1));
        
        // Cocokkan dengan katalog produk resmi
        $found = null;
        foreach ($catalogProducts as $cp) {
            if ($cp['id'] === $itemId || $cp['name'] === ($rawItem['name'] ?? '')) {
                $found = $cp;
                break;
            }
        }
        if (!$found) {
            $found = $catalogProducts[0];
        }

        $officialPrice = (int)$found['price'];
        $itemSubtotal = $officialPrice * $qty;
        $verifiedSubtotal += $itemSubtotal;

        $verifiedItems[] = [
            'id' => $found['id'],
            'name' => $found['name'],
            'price' => $officialPrice,
            'price_formatted' => 'Rp ' . number_format($officialPrice, 0, ',', '.'),
            'image' => $found['image'],
            'qty' => $qty,
            'subtotal' => $itemSubtotal
        ];
    }

    // Validasi Ongkir (Hanya 0 atau 2000)
    $verifiedShippingCost = (int)($_POST['shipping_cost'] ?? 0);
    if ($verifiedShippingCost !== 2000) {
        $verifiedShippingCost = 0;
    }

    $packingFee = 1000;
    $verifiedGrandTotal = $verifiedSubtotal + $verifiedShippingCost + $packingFee;

    $orderId = 'PST-' . strtoupper(substr(uniqid(), -6));
    $orderData = [
        'order_id'       => $orderId,
        'username'       => $username,
        'order_date'     => date('d M Y, H:i') . ' WIB',
        'order_status'   => $orderStatus,
        'payment_status' => $paymentStatus,
        'recipient_name' => htmlspecialchars($_POST['recipient_name'] ?? $userProfile['nama_lengkap']),
        'recipient_phone'=> htmlspecialchars($_POST['recipient_phone'] ?? $userProfile['no_telp']),
        'lokasi_sekolah' => htmlspecialchars($_POST['lokasi_sekolah'] ?? $userProfile['lokasi_sekolah']),
        'catatan_lokasi' => htmlspecialchars($_POST['catatan_lokasi'] ?? $userProfile['catatan_lokasi']),
        'shipping'       => htmlspecialchars($_POST['shipping_option'] ?? 'Diantar Langsung ke Kelas / Ruangan (Rp 0)'),
        'shipping_cost'  => $verifiedShippingCost,
        'payment_method' => $paymentMethod,
        'payment_proof'  => $proofPath,
        'notes'          => htmlspecialchars($_POST['notes'] ?? '-'),
        'items'          => $verifiedItems,
        'total_amount'   => $verifiedGrandTotal
    ];

    // 1. Simpan ke database MySQL jika koneksi aktif
    if ($conn) {
        $oid = mysqli_real_escape_string($conn, $orderData['order_id']);
        $u   = mysqli_real_escape_string($conn, $orderData['username']);
        $od  = mysqli_real_escape_string($conn, $orderData['order_date']);
        $os  = mysqli_real_escape_string($conn, $orderData['order_status']);
        $ps  = mysqli_real_escape_string($conn, $orderData['payment_status']);
        $rn  = mysqli_real_escape_string($conn, $orderData['recipient_name']);
        $rp  = mysqli_real_escape_string($conn, $orderData['recipient_phone']);
        $ls  = mysqli_real_escape_string($conn, $orderData['lokasi_sekolah']);
        $cl  = mysqli_real_escape_string($conn, $orderData['catatan_lokasi']);
        $shp = mysqli_real_escape_string($conn, $orderData['shipping']);
        $sc  = (int)$orderData['shipping_cost'];
        $pm  = mysqli_real_escape_string($conn, $orderData['payment_method']);
        $pp  = $orderData['payment_proof'] ? "'" . mysqli_real_escape_string($conn, $orderData['payment_proof']) . "'" : "NULL";
        $nt  = mysqli_real_escape_string($conn, $orderData['notes']);
        $ij  = mysqli_real_escape_string($conn, json_encode($orderData['items']));
        $tot = (int)$orderData['total_amount'];

        $sqlInsertOrder = "INSERT INTO `orders` 
            (`order_id`, `username`, `order_date`, `order_status`, `payment_status`, `recipient_name`, `recipient_phone`, `lokasi_sekolah`, `catatan_lokasi`, `shipping`, `shipping_cost`, `payment_method`, `payment_proof`, `notes`, `items_json`, `total_amount`)
            VALUES 
            ('$oid', '$u', '$od', '$os', '$ps', '$rn', '$rp', '$ls', '$cl', '$shp', $sc, '$pm', $pp, '$nt', '$ij', $tot)";
        @mysqli_query($conn, $sqlInsertOrder);
    }

    // 2. Simpan order terakhir untuk halaman status
    $_SESSION['latest_order'] = $orderData;

    // Simpan ke Riwayat Pembelian dalam session
    if (!isset($_SESSION['order_history']) || !is_array($_SESSION['order_history'])) {
        $_SESSION['order_history'] = [];
    }
    $_SESSION['order_history'][] = $orderData;

    // Simpan ke file JSON persistent agar riwayat belanja tetap tersimpan
    $ordersFile = __DIR__ . '/uploads/orders.json';
    $allOrders = [];
    if (file_exists($ordersFile)) {
        $content = file_get_contents($ordersFile);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $allOrders = $decoded;
        }
    }
    $allOrders[] = $orderData;
    @file_put_contents($ordersFile, json_encode($allOrders, JSON_PRETTY_PRINT));

    // Redirect ke halaman status pemesanan (ending setiap pembayaran)
    header("Location: order-status.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pistachio Prestige</title>
    <base href="<?= htmlspecialchars($baseUri) ?>">
    <link rel="icon" type="image/png" href="<?= $baseUri ?>images/logo-prestige.png">
    <link rel="shortcut icon" type="image/png" href="<?= $baseUri ?>images/logo-prestige.png">
    <link rel="apple-touch-icon" href="<?= $baseUri ?>images/logo-prestige.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #faf5e8;
            color: #3b281c;
        }
        .font-serif {
            font-family: 'Playfair Display', serif;
        }
        .pastel-card {
            background-color: #ffffff;
            border: 1px solid #ebdcc5;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased selection:bg-rose-400 selection:text-white">

    <!-- HEADER PISTACHIO PRESTIGE CHECKOUT -->
    <header class="bg-white/95 backdrop-blur-md border-b border-[#ebdcc5] sticky top-0 z-40 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3 sm:gap-4">
                <a href="index.php" class="flex items-center gap-2.5 group">
                    <div class="w-11 h-11 rounded-2xl overflow-hidden bg-[#faf5e8] p-1 border border-[#ebdcc5] shadow-xs flex items-center justify-center group-hover:scale-105 transition-transform">
                        <img src="images/logo-prestige.png" alt="Pistachio Prestige Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="text-xl font-serif font-extrabold text-[#3b281c] tracking-tight block">
                            <span class="text-[#6a8738]">Pistachio</span> <span class="text-rose-500">Prestige</span>
                        </span>
                        <span class="text-[9.5px] uppercase font-bold text-[#6a8738] block -mt-1 tracking-wider">
                            Strawberry Chocolate Kunafa
                        </span>
                    </div>
                </a>

                <div class="h-7 w-px bg-stone-300 mx-1"></div>

                <h1 class="text-base sm:text-lg font-bold text-[#3b281c] font-serif">
                    Checkout Pesanan
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <a href="riwayat.php" class="text-xs font-bold text-[#557827] bg-[#eef6db] hover:bg-[#dceebb] border border-[#cbe1a3] px-3 py-1.5 rounded-xl transition flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span class="hidden sm:inline">Riwayat Beli</span>
                </a>
                <div class="text-xs text-stone-500 hidden sm:flex items-center gap-1.5">
                    <i class="fa-solid fa-user-check text-[#6a8738]"></i>
                    <span class="font-bold text-[#3b281c]"><?= htmlspecialchars($username) ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CHECKOUT CONTENT -->
    <main class="max-w-5xl w-full mx-auto px-3 sm:px-6 py-6 space-y-4">

        <!-- 1. TEMPAT PENGANTARAN DI SEKOLAH (SESUAI REQUEST USER: KHUSUS LINGKUNGAN SEKOLAH, TANPA KOTA/PROVINSI/KODE POS) -->
        <div class="pastel-card rounded-3xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2 text-sm font-bold text-rose-600">
                    <i class="fa-solid fa-school text-base text-rose-500"></i>
                    <span>Tempat Pengantaran di Sekolah (Tempat Pembeli)</span>
                </div>
                <button type="button" onclick="toggleEditAddress()" id="btn-ubah-alamat" class="text-xs text-[#8ea750] hover:text-[#6a8738] font-bold flex items-center gap-1">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Ubah Lokasi Pengantaran</span>
                </button>
            </div>

            <!-- Display Address View -->
            <div id="view-address" class="text-xs sm:text-sm text-[#5c3e2e] space-y-1.5 bg-[#fcfaf4] p-4 rounded-2xl border border-[#ebdcc5]">
                <div class="flex flex-wrap items-center gap-2 font-bold text-[#382116]">
                    <span id="display-name"><?= htmlspecialchars($userProfile['nama_lengkap']) ?></span>
                    <span class="text-stone-300">|</span>
                    <span id="display-phone" class="font-normal text-stone-600">(<?= htmlspecialchars($userProfile['no_telp']) ?>)</span>
                    <span class="px-2.5 py-0.5 rounded-lg text-[10.5px] font-bold border border-[#cbe1a3] text-[#557827] bg-[#eef6db]">
                        <i class="fa-solid fa-location-dot mr-1 text-[#6a8738]"></i> Lingkungan Sekolah
                    </span>
                </div>
                <div>
                    <span class="text-stone-400 text-xs block">Kelas / Ruangan / Tempat:</span>
                    <p id="display-lokasi" class="text-[#382116] font-bold text-sm leading-relaxed">
                        <?= htmlspecialchars($userProfile['lokasi_sekolah']) ?>
                    </p>
                </div>
                <div>
                    <span class="text-stone-400 text-xs block">Patokan / Catatan Pengantaran:</span>
                    <p id="display-catatan" class="text-[#7d5f4c] italic">
                        <?= htmlspecialchars($userProfile['catatan_lokasi']) ?>
                    </p>
                </div>
            </div>

            <!-- Edit Address Inline Form (Custom Fill-in Khusus Sekolah Sesuai Permintaan) -->
            <div id="form-edit-address" class="hidden mt-3 pt-3 border-t border-[#ebdcc5] space-y-3 bg-[#fcfaf4] p-4 rounded-2xl">
                <p class="text-xs font-bold text-[#382116] flex items-center gap-1.5">
                    <i class="fa-solid fa-pen text-rose-500"></i>
                    <span>Isi Tempat Pengantaran di Sekolah (Kelas / Ruangan):</span>
                </p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] mb-1">Nama Penerima (Siswa / Guru / Staff):</label>
                        <input type="text" id="input-name" value="<?= htmlspecialchars($userProfile['nama_lengkap']) ?>" placeholder="Nama lengkap penerima..." class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] mb-1">No. WhatsApp / Telepon Aktif:</label>
                        <input type="tel" id="input-phone" value="<?= htmlspecialchars($userProfile['no_telp']) ?>" placeholder="08xxxxxxxxxx" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-[#5c3e2e] mb-1">Kelas / Ruangan / Tempat di Sekolah:</label>
                    <input type="text" id="input-lokasi" value="<?= htmlspecialchars($userProfile['lokasi_sekolah']) ?>" placeholder="Misal: Kelas XII RPL 1 / Ruang Guru / Perpustakaan / Kantin" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 font-semibold text-[#382116]">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-[#5c3e2e] mb-1">Patokan / Catatan Pengantaran:</label>
                    <textarea id="input-catatan" rows="2" placeholder="Misal: Gedung B Lantai 2 samping lab, titip ke meja piket, antar saat jam istirahat" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400"><?= htmlspecialchars($userProfile['catatan_lokasi']) ?></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="saveAddressChanges()" class="px-4 py-2 rounded-xl bg-[#8ea750] hover:bg-[#7a933f] text-white text-xs font-bold transition shadow-xs">
                        <i class="fa-solid fa-check mr-1"></i> Simpan Tempat Pengantaran
                    </button>
                    <button type="button" onclick="toggleEditAddress()" class="px-4 py-2 rounded-xl bg-stone-200 hover:bg-stone-300 text-stone-700 text-xs font-bold transition">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. PRODUK DIPESAN -->
        <div class="pastel-card rounded-3xl shadow-xs overflow-hidden">
            
            <div class="px-5 py-3.5 bg-[#fcfaf4] border-b border-[#ebdcc5] flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs sm:text-sm font-bold text-[#382116]">Pistachio Prestige Boutique Kitchen</span>
                    <span class="text-xs text-[#7d5f4c] hidden sm:inline">• Fresh Handcrafted Daily</span>
                </div>
                <a href="https://wa.me/6285215142038" target="_blank" class="text-xs font-bold text-[#557827] hover:underline flex items-center gap-1">
                    <i class="fa-brands fa-whatsapp text-sm text-emerald-600"></i> Tanya Admin
                </a>
            </div>

            <!-- Table Header -->
            <div class="hidden sm:grid grid-cols-12 gap-3 px-5 py-2.5 text-xs text-stone-400 font-bold border-b border-stone-100 uppercase tracking-wider">
                <div class="col-span-6">Menu Dipesan</div>
                <div class="col-span-2 text-right">Harga</div>
                <div class="col-span-2 text-center">Jumlah</div>
                <div class="col-span-2 text-right">Subtotal</div>
            </div>

            <!-- Items Container -->
            <div id="checkout-items-list" class="divide-y divide-[#f5ede0]">
            </div>

            <!-- Rekomendasi Menu Tambahan ("Lengkapi Pesananmu") di Halaman Checkout -->
            <div id="checkout-recommendations-box" class="p-4 sm:p-5 bg-[#fffdf7] border-t border-[#ebdcc5] space-y-3 hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-sparkles text-amber-500"></i>
                        <h4 class="text-xs sm:text-sm font-bold text-[#382116] font-serif">Lengkapi Pesananmu</h4>
                        <span class="text-[10px] text-[#557827] bg-[#eef6db] px-2 py-0.5 rounded-full font-bold">Menu Pilihan</span>
                    </div>
                    <span class="text-[11px] text-stone-400">Tambahkan langsung ke pesanan Anda</span>
                </div>
                <div id="checkout-recommendations-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <!-- Diisi secara dinamis melalui JavaScript -->
                </div>
            </div>

            <!-- Opsi Pengantaran di Sekolah & Catatan Tambahan -->
            <div class="p-4 sm:p-5 bg-[#fcfaf4] border-t border-[#ebdcc5] space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <div class="md:col-span-6">
                        <label class="block text-xs font-bold text-[#5c3e2e] mb-1">Notes Untuk Seller:</label>
                        <input type="text" id="order-notes" placeholder="Masukan Notes Tambahan (Opsional)..."
                            class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400">
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-xs font-bold text-[#5c3e2e] mb-1">Opsi Pengantaran di Sekolah:</label>
                        <select id="shipping-select" onchange="updateShippingOption()" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 font-medium text-[#3b281c]">
                            <option value="0" data-label="Diantar Langsung ke Kelas / Ruangan (Rp 0)">Diantar Langsung ke Kelas / Ruangan - Gratis (Rp 0)</option>
                            <option value="0" data-label="Ambil Sendiri di X RPL (Rp 0)">Ambil Sendiri di X RPL - Gratis (Rp 0)</option>
                            <option value="2000" data-label="Prioritas Express Istirahat (Rp 2.000)">Prioritas Tepat Waktu Jam Istirahat - Rp 2.000</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-[#ebdcc5] text-xs text-[#7d5f4c]">
                    <span>Biaya Pengantaran Sekolah:</span>
                    <span id="shipping-display-cost" class="font-extrabold text-[#382116]">Gratis (Rp 0)</span>
                </div>
            </div>

        </div>

        <!-- 3. METODE PEMBAYARAN INTERAKTIF & FORM BUKTI PEMBAYARAN -->
        <!-- Sesuai permintaan: QRIS memunculkan QRIS, Transfer memunculkan rekening, COD langsung ke status pembuatan pesanan -->
        <div class="pastel-card rounded-3xl p-5 sm:p-6 shadow-xs space-y-5">
            
            <div class="flex items-center justify-between border-b border-[#ebdcc5] pb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-credit-card text-rose-500"></i>
                    <h3 class="text-sm sm:text-base font-bold text-[#382116] font-serif">Pilih Metode Pembayaran</h3>
                </div>
                <span class="text-xs text-stone-400">Pilih salah satu metode</span>
            </div>

            <!-- 3 Tombol Opsi Pembayaran -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                
                <!-- 1. Tombol QRIS -->
                <button type="button" onclick="selectPaymentTab('qris')" id="tab-btn-qris" 
                    class="payment-tab-btn flex items-center gap-3 p-4 rounded-2xl border-2 border-rose-500 bg-[#fff5f6] text-left transition-all">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#382116] block">1. QRIS</span>
                        <span class="text-[11px] text-stone-500 block">Scan barcode & upload bukti</span>
                    </div>
                </button>

                <!-- 2. Tombol Transfer Bank & E-Wallet -->
                <button type="button" onclick="selectPaymentTab('transfer')" id="tab-btn-transfer" 
                    class="payment-tab-btn flex items-center gap-3 p-4 rounded-2xl border border-[#ebdcc5] bg-white hover:bg-stone-50 text-left transition-all">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#382116] block">2. Transfer / DANA</span>
                        <span class="text-[11px] text-stone-500 block">BCA & DANA</span>
                    </div>
                </button>

                <!-- 3. Tombol COD di Sekolah -->
                <button type="button" onclick="selectPaymentTab('cod')" id="tab-btn-cod" 
                    class="payment-tab-btn flex items-center gap-3 p-4 rounded-2xl border border-[#ebdcc5] bg-white hover:bg-stone-50 text-left transition-all">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#382116] block">3. COD di Sekolah</span>
                        <span class="text-[11px] text-stone-500 block">Bayar tunai di kelas/ruangan</span>
                    </div>
                </button>

            </div>

            <!-- ================= KONTEN DINAMIS BERDASARKAN PEMILIHAN METODE ================= -->

            <!-- PANEL A: QRIS BOX (MUNCUL JIKA QRIS DIKLIK) -->
            <div id="panel-qris" class="payment-panel p-5 rounded-2xl bg-[#fffbf2] border border-[#ebdcc5] space-y-4">
                <div class="text-center sm:text-left flex flex-col sm:flex-row items-center gap-6">
                    
                    <!-- QRIS Visual Barcode Card -->
                    <div class="w-52 bg-white p-4 rounded-2xl border-2 border-stone-800 shadow-sm text-center shrink-0">
                        <div class="flex items-center justify-between pb-2 border-b border-stone-200 mb-2">
                            <span class="text-[10px] font-extrabold tracking-widest text-stone-800">QRIS</span>
                            <span class="text-[9px] font-bold text-rose-600">GPN</span>
                        </div>

                        <!-- Barcode QR Image -->
                        <div class="w-full aspect-square bg-white border border-stone-200 p-2 flex items-center justify-center rounded-lg">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=00020101021126580014ID.LINKAJA.WWW01189360091100223000005204581253033605802ID5916Prestige%20Kunafa6007Bandung61054012362070703A016304" 
                                 alt="QRIS Prestige" 
                                 class="w-full h-full object-contain">
                        </div>

                        <p class="text-[10px] font-bold text-[#3b281c] mt-2 leading-tight">Pistachio Prestige</p>
                        <p class="text-[8px] text-stone-400">NMID: ID1020268892110</p>
                    </div>

                    <!-- Instruksi QRIS -->
                    <div class="space-y-2.5 text-xs text-[#5c3e2e]">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                            Scan QRIS Instan
                        </span>
                        <h4 class="font-bold text-[#382116] text-sm">Cara Pembayaran QRIS:</h4>
                        <ol class="list-decimal list-inside space-y-1.5 text-stone-600 text-xs">
                            <li>Buka aplikasi m-Banking (BCA, Mandiri, BRI, BNI) atau e-Wallet (GoPay, OVO, Dana, ShopeePay).</li>
                            <li>Pilih menu <strong>Scan / Bayar QRIS</strong> dan arahkan kamera ke barcode di samping.</li>
                            <li>Periksa nama toko: <strong>Pistachio Prestige</strong>.</li>
                            <li>Setelah berhasil bayar, <strong>unggah screenshot bukti pembayaran</strong> pada form di bawah ini.</li>
                        </ol>
                    </div>

                </div>
            </div>

            <!-- PANEL B: TRANSFER BANK & DANA (MUNCUL JIKA TRANSFER DIKLIK) -->
            <div id="panel-transfer" class="payment-panel hidden p-5 rounded-2xl bg-[#f4f9ed] border border-[#cbe1a3] space-y-4">
                <div>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-1">
                        Rekening & E-Wallet Resmi
                    </span>
                    <h4 class="font-bold text-[#382116] text-sm">Silakan Transfer ke Rekening BCA atau E-Wallet DANA:</h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    
                    <!-- Rekening BCA -->
                    <div class="bg-white p-3.5 rounded-xl border border-stone-200 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-blue-700 block">Bank BCA DIGITAL</span>
                            <span class="text-sm font-extrabold text-[#382116] font-mono block tracking-wider" id="rek-bca">0086-7902-3774</span>
                            <span class="text-[10px] text-stone-400 block">a.n. Prestige Kunafa</span>
                        </div>
                        <button type="button" onclick="copyText('008679023774', 'No. Rekening BCA disalin!')" class="px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold border border-blue-200 transition">
                            <i class="fa-regular fa-copy mr-1"></i> Salin
                        </button>
                    </div>

                    <!-- E-Wallet DANA -->
                    <div class="bg-white p-3.5 rounded-xl border border-stone-200 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-[#118eea] flex items-center gap-1.5">
                                <i class="fa-solid fa-wallet text-[#118eea]"></i> DANA
                            </span>
                            <span class="text-sm font-extrabold text-[#382116] font-mono block tracking-wider" id="rek-dana">0852-1514-2038</span>
                            <span class="text-[10px] text-stone-400 block">a.n. Pistachio Prestige</span>
                        </div>
                        <button type="button" onclick="copyText('085215142038', 'Nomor DANA 085215142038 disalin!')" class="px-3 py-1.5 rounded-lg bg-sky-50 hover:bg-sky-100 text-[#118eea] text-xs font-bold border border-sky-200 transition">
                            <i class="fa-regular fa-copy mr-1"></i> Salin
                        </button>
                    </div>

                </div>

                <p class="text-[11px] text-[#557827] italic">
                    *Setelah transfer BCA atau kirim DANA, unggah foto/screenshot struk bukti transfer pada form di bawah ini agar pesanan segera dibuat.
                </p>
            </div>

            <!-- PANEL C: COD DI TEMPAT PENGIRIMAN SEKOLAH (MUNCUL JIKA COD DIKLIK) -->
            <div id="panel-cod" class="payment-panel hidden p-5 rounded-2xl bg-[#fffbf2] border border-[#ebdcc5] space-y-3">
                <div class="flex items-center gap-2 text-[#78432a]">
                    <i class="fa-solid fa-hand-holding-dollar text-xl text-amber-600"></i>
                    <h4 class="font-bold text-sm text-[#382116]">Cash on Delivery (Bayar Tunai di Kelas / Ruangan Sekolah)</h4>
                </div>
                <p class="text-xs text-[#6d513f] leading-relaxed">
                    Pesanan Anda akan langsung diproses di dapur dan diantarkan langsung ke kelas atau ruangan sekolah Anda. 
                    Anda cukup menyiapkan uang pas sebesar total pembayaran saat kurir tiba di tempat tujuan.
                </p>
                <div class="p-3 bg-white rounded-xl border border-[#ebdcc5] text-[11px] text-emerald-800 font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>COD tidak perlu upload bukti pembayaran! Langsung klik tombol di bawah untuk masuk ke Status Pembuatan Pesanan.</span>
                </div>
            </div>

            <!-- ================= FORM UNGGAH BUKTI PEMBAYARAN (SESUAI REQUEST USER) ================= -->
            <div id="box-bukti-pembayaran" class="p-4 sm:p-5 rounded-2xl bg-white border border-[#ebdcc5] space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-rose-500"></i>
                        <span class="text-xs sm:text-sm font-bold text-[#382116]">Form Unggah Bukti Pembayaran</span>
                    </div>
                    <span class="text-[11px] text-stone-400">Screenshot QRIS / Struk Transfer</span>
                </div>

                <div class="border-2 border-dashed border-[#d6c7af] rounded-2xl p-4 text-center bg-[#fcfaf4] hover:bg-[#faf5e8] transition">
                    <input type="file" name="payment_proof_file" id="proof-file-input" accept="image/*" onchange="previewProofImage(event)" class="hidden">
                    
                    <label for="proof-file-input" class="cursor-pointer block space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-[#eef6db] text-[#557827] flex items-center justify-center mx-auto text-xl shadow-xs">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-[#382116] block hover:text-rose-600">
                                Klik untuk Pilih Foto / Screenshot Bukti Bayar
                            </span>
                            <span class="text-[10px] text-stone-400 block mt-0.5">Format: JPG, PNG, JPEG, atau WebP</span>
                        </div>
                    </label>

                    <!-- Image Preview Area -->
                    <div id="proof-preview-wrapper" class="hidden mt-3 pt-3 border-t border-[#ebdcc5] flex flex-col items-center">
                        <span class="text-[11px] font-bold text-[#557827] mb-2 flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i> Bukti Siap Diunggah:
                        </span>
                        <img id="proof-preview-img" src="" alt="Pratinjau Bukti" class="max-h-48 rounded-xl border border-stone-300 shadow-sm object-contain">
                        <button type="button" onclick="clearProofImage()" class="mt-2 text-[11px] text-rose-600 hover:text-rose-800 underline font-semibold">
                            Hapus & Ganti Gambar
                        </button>
                    </div>
                </div>

                <p class="text-[10.5px] text-stone-400 italic">
                    *Catatan: Jika bukti pembayaran belum sempat diunggah sekarang, Anda juga bisa mengirimkannya langsung ke WhatsApp Admin di halaman status pemesanan.
                </p>
            </div>

        </div>

        <!-- 4. RINCIAN BIAYA & TOMBOL AKSI PEMESANAN (ENDING KE STATUS PESANAN) -->
        <div class="pastel-card rounded-3xl p-5 sm:p-6 space-y-4">
            
            <div class="space-y-2 text-xs sm:text-sm text-[#6d513f] max-w-md ml-auto">
                <div class="flex justify-between">
                    <span>Subtotal Produk</span>
                    <span id="summary-subtotal" class="font-bold text-[#382116]">Rp 0</span>
                </div>
                <div class="flex justify-between">
                    <span>Ongkos Kirim di Sekolah</span>
                    <span id="summary-shipping" class="font-bold text-[#382116]">Gratis (Rp 0)</span>
                </div>
                <div class="flex justify-between">
                    <span>Biaya Pengemasan Higienis</span>
                    <span class="font-bold text-[#382116]">Rp 1.000</span>
                </div>
                <div class="flex justify-between pt-3 border-t border-[#ebdcc5] text-sm sm:text-base font-bold text-[#382116] items-baseline">
                    <span>Total Pembayaran:</span>
                    <span id="summary-grand-total" class="text-xl sm:text-2xl font-extrabold text-rose-600">Rp 0</span>
                </div>
            </div>

            <!-- Form Multi-part Submit to checkout.php with Payment Proof -->
            <form id="checkout-form" action="checkout.php" method="POST" enctype="multipart/form-data" class="pt-4 border-t border-[#ebdcc5] flex flex-col sm:flex-row items-center justify-between gap-4">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="recipient_name" id="post-name">
                <input type="hidden" name="recipient_phone" id="post-phone">
                <input type="hidden" name="lokasi_sekolah" id="post-lokasi">
                <input type="hidden" name="catatan_lokasi" id="post-catatan">
                <input type="hidden" name="shipping_option" id="post-shipping-option">
                <input type="hidden" name="shipping_cost" id="post-shipping-cost">
                <input type="hidden" name="payment_method" id="post-payment-method" value="QRIS">
                <input type="hidden" name="notes" id="post-notes">
                <input type="hidden" name="items_json" id="post-items-json">
                <input type="hidden" name="total_amount" id="post-total-amount">
                
                <!-- Mirror file input inside the form -->
                <input type="file" name="payment_proof" id="form-proof-file" accept="image/*" class="hidden">

                <div class="text-xs text-stone-500 text-center sm:text-left">
                    <i class="fa-solid fa-circle-info text-rose-500 mr-1"></i>
                    Semua pembayaran akan langsung diarahkan ke <strong>Halaman Status Pembuatan Pesanan</strong>.
                </div>

                <!-- Tombol Dinamis Sesuai Metode Pembayaran Terpilih -->
                <button type="submit" id="btn-submit-order" class="w-full sm:w-auto px-10 py-3.5 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-sm sm:text-base shadow-md shadow-rose-200 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span id="btn-submit-label">Konfirmasi Pembayaran QRIS & Buat Pesanan</span>
                </button>
            </form>

        </div>

    </main>

    <!-- FOOTER PISTACHIO PRESTIGE -->
    <footer class="bg-white border-t border-[#ebdcc5] py-6 text-center text-xs text-stone-400 mt-10">
        <p>&copy; <?= date('Y') ?> Pistachio Prestige. Strawberry Chocolate Kunafa Artisanal Boutique.</p>
    </footer>

    <!-- JAVASCRIPT LOGIKA LENGKAP -->
    <script>
        // Data Produk Resmi untuk Rekomendasi & Validasi
        const catalogProducts = <?= json_encode($catalogProducts) ?>;

        let checkoutCart = [];
        try {
            const stored = localStorage.getItem('prestige_cart');
            if (stored) {
                checkoutCart = JSON.parse(stored);
            }
        } catch (e) {
            console.error(e);
        }

        // Pastikan format array valid dan tidak memuat order palsu jika kosong
        if (!Array.isArray(checkoutCart)) {
            checkoutCart = [];
        }

        let shippingCost = 0;
        let selectedPayment = "QRIS";
        const feeService = 1000;

        function renderCheckoutItems() {
            const container = document.getElementById('checkout-items-list');
            const btnSubmit = document.getElementById('btn-submit-order');
            const labelSubmit = document.getElementById('btn-submit-label');
            let subtotal = 0;

            if (checkoutCart.length === 0) {
                container.innerHTML = `
                    <div class="p-8 sm:p-12 text-center text-[#7d5f4c]">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-2xl border border-rose-100">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <h4 class="font-serif font-bold text-base sm:text-lg text-[#382116]">Keranjang Belanja Masih Kosong</h4>
                        <p class="text-xs text-stone-500 mt-1 max-w-sm mx-auto">
                            Anda belum menambahkan produk apa pun. Silakan tambahkan menu rekomendasi di bawah atau kembali ke katalog menu kami!
                        </p>
                        <a href="index.php#katalog-produk" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-extrabold text-xs shadow-xs transition">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Buka Katalog Menu</span>
                        </a>
                    </div>
                `;

                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-40', 'cursor-not-allowed');
                }
                if (labelSubmit) {
                    labelSubmit.innerText = "Pilih Menu Terlebih Dahulu";
                }

                updateSummaryCalculations(0);
                renderRecommendations();
                return;
            }

            // Aktifkan kembali tombol jika ada item
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-40', 'cursor-not-allowed');
            }
            updateSubmitButtonLabel();

            container.innerHTML = checkoutCart.map(item => {
                const itemSub = Number(item.price) * Number(item.qty);
                subtotal += itemSub;

                return `
                    <div class="p-4 sm:p-5 flex flex-col sm:grid sm:grid-cols-12 gap-3 items-center">
                        <div class="sm:col-span-5 flex items-center gap-3 w-full">
                            <img src="${item.image}" alt="${item.name}" class="w-14 h-14 object-cover rounded-2xl border border-[#ebdcc5] shrink-0">
                            <div class="min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-[#382116] leading-snug line-clamp-2">${item.name}</h4>
                                <span class="text-[10px] text-[#557827] bg-[#eef6db] px-2 py-0.5 rounded-lg font-bold mt-1 inline-block">Fresh Handmade</span>
                            </div>
                        </div>

                        <div class="sm:col-span-2 text-right w-full sm:w-auto text-xs sm:text-sm text-stone-600">
                            Rp ${Number(item.price).toLocaleString('id-ID')}
                        </div>

                        <!-- Selector Quantity (+ dan -) Langsung di Checkout -->
                        <div class="sm:col-span-3 flex items-center justify-center gap-2 w-full sm:w-auto">
                            <div class="flex items-center bg-white border border-[#ebdcc5] rounded-xl p-0.5 shadow-2xs">
                                <button type="button" onclick="updateCheckoutQty(${item.id}, -1)" class="w-6 h-6 rounded-lg text-stone-700 hover:bg-stone-100 flex items-center justify-center text-xs font-extrabold">-</button>
                                <span class="text-xs font-extrabold text-[#382116] px-2.5">${item.qty}</span>
                                <button type="button" onclick="updateCheckoutQty(${item.id}, 1)" class="w-6 h-6 rounded-lg text-stone-700 hover:bg-stone-100 flex items-center justify-center text-xs font-extrabold">+</button>
                            </div>
                            <button type="button" onclick="removeCheckoutItem(${item.id})" title="Hapus menu ini" class="p-1.5 text-stone-400 hover:text-rose-600 transition">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>

                        <div class="sm:col-span-2 text-right w-full sm:w-auto text-xs sm:text-sm font-extrabold text-rose-600 font-serif">
                            Rp ${itemSub.toLocaleString('id-ID')}
                        </div>
                    </div>
                `;
            }).join('');

            updateSummaryCalculations(subtotal);
            renderRecommendations();
        }

        function updateCheckoutQty(id, delta) {
            const pId = Number(id);
            const item = checkoutCart.find(i => Number(i.id) === pId);
            if (item) {
                item.qty += delta;
                if (item.qty <= 0) {
                    checkoutCart = checkoutCart.filter(i => Number(i.id) !== pId);
                }
                localStorage.setItem('prestige_cart', JSON.stringify(checkoutCart));
                renderCheckoutItems();
            }
        }

        function removeCheckoutItem(id) {
            const pId = Number(id);
            checkoutCart = checkoutCart.filter(i => Number(i.id) !== pId);
            localStorage.setItem('prestige_cart', JSON.stringify(checkoutCart));
            renderCheckoutItems();
        }

        // Render Bagian Rekomendasi Menu ("Lengkapi Pesananmu") di Halaman Checkout
        function renderRecommendations() {
            const box = document.getElementById('checkout-recommendations-box');
            const list = document.getElementById('checkout-recommendations-list');
            if (!box || !list) return;

            const unselected = catalogProducts.filter(p => !checkoutCart.some(c => Number(c.id) === Number(p.id)));
            if (unselected.length === 0) {
                box.classList.add('hidden');
            } else {
                box.classList.remove('hidden');
                list.innerHTML = unselected.map(p => `
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-white border border-[#ebdcc5] shadow-2xs hover:border-[#8ea750] transition">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img src="${p.image}" alt="${p.name}" class="w-12 h-12 rounded-xl object-cover border border-[#ebdcc5] shrink-0">
                            <div class="min-w-0">
                                <h5 class="text-xs font-bold text-[#382116] truncate">${p.name}</h5>
                                <span class="text-xs text-rose-600 font-extrabold block">${p.price_formatted}</span>
                            </div>
                        </div>
                        <button type="button" onclick="addRecommendedItem(${p.id})" class="px-3 py-1.5 rounded-xl bg-[#eef6db] hover:bg-[#dceebb] text-[#557827] border border-[#cbe1a3] text-xs font-bold transition flex items-center gap-1 shrink-0">
                            <i class="fa-solid fa-plus text-[10px]"></i> Tambah
                        </button>
                    </div>
                `).join('');
            }
        }

        function addRecommendedItem(id) {
            const pId = Number(id);
            const p = catalogProducts.find(item => Number(item.id) === pId);
            if (!p) return;

            checkoutCart.push({
                id: p.id,
                name: p.name,
                price: p.price,
                price_formatted: p.price_formatted,
                image: p.image,
                qty: 1
            });

            localStorage.setItem('prestige_cart', JSON.stringify(checkoutCart));
            renderCheckoutItems();
        }

        function updateShippingOption() {
            const select = document.getElementById('shipping-select');
            shippingCost = parseInt(select.value, 10);
            
            const costFormatted = shippingCost === 0 ? 'Gratis (Rp 0)' : 'Rp ' + shippingCost.toLocaleString('id-ID');
            document.getElementById('shipping-display-cost').innerText = costFormatted;
            document.getElementById('summary-shipping').innerText = costFormatted;

            let subtotal = 0;
            checkoutCart.forEach(i => subtotal += (Number(i.price) * Number(i.qty)));
            updateSummaryCalculations(subtotal);
        }

        function updateSummaryCalculations(subtotal) {
            const isCartEmpty = checkoutCart.length === 0;
            const currentFee = isCartEmpty ? 0 : feeService;
            const currentShipping = isCartEmpty ? 0 : shippingCost;
            const grandTotal = isCartEmpty ? 0 : (subtotal + currentShipping + currentFee);

            document.getElementById('summary-subtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('summary-grand-total').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');

            document.getElementById('post-name').value = document.getElementById('display-name').innerText;
            document.getElementById('post-phone').value = document.getElementById('display-phone').innerText.replace(/[()]/g, '');
            document.getElementById('post-lokasi').value = document.getElementById('display-lokasi').innerText.trim();
            document.getElementById('post-catatan').value = document.getElementById('display-catatan').innerText.trim();
            
            const shippingSelect = document.getElementById('shipping-select');
            const selectedOpt = shippingSelect.options[shippingSelect.selectedIndex];
            document.getElementById('post-shipping-option').value = selectedOpt.getAttribute('data-label');
            document.getElementById('post-shipping-cost').value = currentShipping;
            document.getElementById('post-payment-method').value = selectedPayment;
            document.getElementById('post-notes').value = document.getElementById('order-notes').value;
            document.getElementById('post-items-json').value = JSON.stringify(checkoutCart);
            document.getElementById('post-total-amount').value = grandTotal;
        }

        function updateSubmitButtonLabel() {
            const labelSubmit = document.getElementById('btn-submit-label');
            if (!labelSubmit) return;
            if (checkoutCart.length === 0) {
                labelSubmit.innerText = "Pilih Menu Terlebih Dahulu";
                return;
            }
            if (selectedPayment === 'QRIS') {
                labelSubmit.innerText = "Konfirmasi Pembayaran QRIS & Buat Pesanan";
            } else if (selectedPayment.indexOf('Transfer') !== -1) {
                labelSubmit.innerText = "Konfirmasi Transfer & Buat Pesanan";
            } else {
                labelSubmit.innerText = "Pesan Sekarang (COD di Sekolah)";
            }
        }

        // FUNGSI MEMILIH TAB PEMBAYARAN SESUAI PERMINTAAN USER:
        // QRIS -> Muncul Barcode QRIS & Bukti Upload
        // Transfer -> Muncul Rekening Bank & Bukti Upload
        // COD -> Bayar di Tempat Pengiriman / Sekolah, Langsung Ke Status Pemesanan
        function selectPaymentTab(type) {
            document.querySelectorAll('.payment-panel').forEach(p => p.classList.add('hidden'));
            
            document.querySelectorAll('.payment-tab-btn').forEach(btn => {
                btn.classList.remove('border-rose-500', 'bg-[#fff5f6]', 'border-emerald-600', 'bg-[#f4f9ed]', 'border-amber-600', 'bg-[#fffbf2]', 'border-2');
                btn.classList.add('border-[#ebdcc5]', 'bg-white');
            });

            const btnSubmit = document.getElementById('btn-submit-order');
            const labelSubmit = document.getElementById('btn-submit-label');
            const boxBukti = document.getElementById('box-bukti-pembayaran');

            if (type === 'qris') {
                selectedPayment = 'QRIS';
                document.getElementById('panel-qris').classList.remove('hidden');
                boxBukti.classList.remove('hidden');
                
                const tab = document.getElementById('tab-btn-qris');
                tab.classList.add('border-2', 'border-rose-500', 'bg-[#fff5f6]');
                tab.classList.remove('border-[#ebdcc5]', 'bg-white');

                btnSubmit.className = "w-full sm:w-auto px-10 py-3.5 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-sm sm:text-base shadow-md shadow-rose-200 transition-all flex items-center justify-center gap-2";
                labelSubmit.innerText = "Konfirmasi Pembayaran QRIS & Buat Pesanan";
            } 
            else if (type === 'transfer') {
                selectedPayment = 'Transfer BCA / DANA (085215142038)';
                document.getElementById('panel-transfer').classList.remove('hidden');
                boxBukti.classList.remove('hidden');

                const tab = document.getElementById('tab-btn-transfer');
                tab.classList.add('border-2', 'border-emerald-600', 'bg-[#f4f9ed]');
                tab.classList.remove('border-[#ebdcc5]', 'bg-white');

                btnSubmit.className = "w-full sm:w-auto px-10 py-3.5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-sm sm:text-base shadow-md shadow-emerald-200 transition-all flex items-center justify-center gap-2";
                labelSubmit.innerText = "Konfirmasi Transfer / DANA & Buat Pesanan";
            } 
            else if (type === 'cod') {
                selectedPayment = 'COD (Bayar di Tempat di Sekolah)';
                document.getElementById('panel-cod').classList.remove('hidden');
                boxBukti.classList.add('hidden'); // COD tidak perlu unggah bukti bayar

                const tab = document.getElementById('tab-btn-cod');
                tab.classList.add('border-2', 'border-amber-600', 'bg-[#fffbf2]');
                tab.classList.remove('border-[#ebdcc5]', 'bg-white');

                btnSubmit.className = "w-full sm:w-auto px-10 py-3.5 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-sm sm:text-base shadow-md shadow-amber-200 transition-all flex items-center justify-center gap-2";
                labelSubmit.innerText = "Pesan Sekarang (COD di Sekolah)";
            }

            document.getElementById('post-payment-method').value = selectedPayment;
        }

        // Preview Bukti Pembayaran
        function previewProofImage(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('proof-preview-img').src = e.target.result;
                    document.getElementById('proof-preview-wrapper').classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);

                // Sync file input to the form
                const formInput = document.getElementById('form-proof-file');
                formInput.files = input.files;
            }
        }

        function clearProofImage() {
            document.getElementById('proof-file-input').value = "";
            document.getElementById('form-proof-file').value = "";
            document.getElementById('proof-preview-wrapper').classList.add('hidden');
            document.getElementById('proof-preview-img').src = "";
        }

        function copyText(text, msg) {
            navigator.clipboard.writeText(text).then(() => {
                alert(msg);
            }).catch(() => {
                prompt("Salin nomor ini:", text);
            });
        }

        function toggleEditAddress() {
            const form = document.getElementById('form-edit-address');
            form.classList.toggle('hidden');
        }

        function saveAddressChanges() {
            const name = document.getElementById('input-name').value.trim();
            const phone = document.getElementById('input-phone').value.trim();
            const lokasi = document.getElementById('input-lokasi').value.trim();
            const catatan = document.getElementById('input-catatan').value.trim();

            if (!name || !lokasi) {
                alert("Mohon isi nama dan lokasi ruangan/kelas di sekolah!");
                return;
            }

            document.getElementById('display-name').innerText = name;
            document.getElementById('display-phone').innerText = `(${phone})`;
            document.getElementById('display-lokasi').innerText = lokasi;
            document.getElementById('display-catatan').innerText = catatan || '-';

            document.getElementById('post-name').value = name;
            document.getElementById('post-phone').value = phone;
            document.getElementById('post-lokasi').value = lokasi;
            document.getElementById('post-catatan').value = catatan;

            toggleEditAddress();
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderCheckoutItems();
            document.getElementById('order-notes').addEventListener('input', (e) => {
                document.getElementById('post-notes').value = e.target.value;
            });
            const form = document.getElementById('checkout-form');
            if (form) {
                form.addEventListener('submit', () => {
                    localStorage.removeItem('prestige_cart');
                });
            }
        });
    </script>
</body>
</html>
