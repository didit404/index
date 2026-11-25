<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Normalisasi URL & Base URI agar aset (logo, favicon, foto) tidak rusak jika URL memiliki duplikasi path
$baseUri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
if (!empty($_SERVER['PATH_INFO']) || preg_match('#/admin\.php/#i', $_SERVER['REQUEST_URI'] ?? '')) {
    header("Location: " . $baseUri . "admin.php");
    exit();
}

include_once("koneksi.php");

// Konfigurasi Admin Password (Bisa diubah sewaktu-waktu)
$ADMIN_PASS = "admin123";
$authError = "";

// Handle Admin Login & Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['is_admin']);
    header("Location: " . $baseUri . "admin.php");
    exit();
}

if (isset($_POST['admin_login'])) {
    $inputPass = $_POST['admin_password'] ?? '';
    if ($inputPass === $ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        header("Location: " . $baseUri . "admin.php");
        exit();
    } else {
        $authError = "Password admin salah! Silakan coba lagi.";
    }
}

$isAdmin = !empty($_SESSION['is_admin']);

// File Penyimpanan Cadangan JSON
$ordersFile = __DIR__ . '/uploads/orders.json';
$slidersFile = __DIR__ . '/uploads/sliders.json';
$keunggulanFile = __DIR__ . '/uploads/keunggulan.json';

// Baca data sliders
$adminSliders = [];
if ($conn) {
    $res = @mysqli_query($conn, "SELECT * FROM `sliders` ORDER BY `order_index` ASC");
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $adminSliders[] = $row;
        }
    }
}
if (empty($adminSliders) && file_exists($slidersFile)) {
    $adminSliders = json_decode(file_get_contents($slidersFile), true) ?: [];
}

// Baca data keunggulan
$adminKeunggulan = [];
if ($conn) {
    $res = @mysqli_query($conn, "SELECT * FROM `keunggulan` ORDER BY `order_index` ASC");
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $adminKeunggulan[] = $row;
        }
    }
}
if (empty($adminKeunggulan) && file_exists($keunggulanFile)) {
    $adminKeunggulan = json_decode(file_get_contents($keunggulanFile), true) ?: [];
}

// Helper Baca Orders dari MySQL & JSON
function fetchAllOrders($conn, $ordersFile) {
    $ordersList = [];

    // 1. Coba baca dari MySQL
    if ($conn) {
        $res = @mysqli_query($conn, "SELECT * FROM `orders` ORDER BY `id` DESC");
        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $ordersList[] = [
                    'order_id'       => $row['order_id'],
                    'username'       => $row['username'],
                    'order_date'     => $row['order_date'],
                    'order_status'   => $row['order_status'],
                    'payment_status' => $row['payment_status'],
                    'recipient_name' => $row['recipient_name'],
                    'recipient_phone'=> $row['recipient_phone'],
                    'lokasi_sekolah' => $row['lokasi_sekolah'],
                    'catatan_lokasi' => $row['catatan_lokasi'],
                    'shipping'       => $row['shipping'],
                    'shipping_cost'  => (int)$row['shipping_cost'],
                    'payment_method' => $row['payment_method'],
                    'payment_proof'  => $row['payment_proof'],
                    'notes'          => $row['notes'],
                    'items'          => json_decode($row['items_json'] ?? '[]', true),
                    'total_amount'   => (int)$row['total_amount']
                ];
            }
            return $ordersList;
        }
    }

    // 2. Fallback baca dari file JSON
    if (file_exists($ordersFile)) {
        $content = file_get_contents($ordersFile);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $ordersList = $decoded;

            // Jika MySQL aktif tapi tabel masih kosong, auto-migrasikan data JSON ke MySQL
            if ($conn && !empty($ordersList)) {
                foreach ($ordersList as $ord) {
                    $oid = mysqli_real_escape_string($conn, $ord['order_id']);
                    $u   = mysqli_real_escape_string($conn, $ord['username']);
                    $od  = mysqli_real_escape_string($conn, $ord['order_date']);
                    $os  = mysqli_real_escape_string($conn, $ord['order_status']);
                    $ps  = mysqli_real_escape_string($conn, $ord['payment_status']);
                    $rn  = mysqli_real_escape_string($conn, $ord['recipient_name']);
                    $rp  = mysqli_real_escape_string($conn, $ord['recipient_phone']);
                    $ls  = mysqli_real_escape_string($conn, $ord['lokasi_sekolah']);
                    $cl  = mysqli_real_escape_string($conn, $ord['catatan_lokasi']);
                    $shp = mysqli_real_escape_string($conn, $ord['shipping']);
                    $sc  = (int)($ord['shipping_cost'] ?? 0);
                    $pm  = mysqli_real_escape_string($conn, $ord['payment_method']);
                    $pp  = !empty($ord['payment_proof']) ? "'" . mysqli_real_escape_string($conn, $ord['payment_proof']) . "'" : "NULL";
                    $nt  = mysqli_real_escape_string($conn, $ord['notes'] ?? '-');
                    $ij  = mysqli_real_escape_string($conn, json_encode($ord['items'] ?? []));
                    $tot = (int)($ord['total_amount'] ?? 0);

                    $q = "INSERT IGNORE INTO `orders` 
                        (`order_id`, `username`, `order_date`, `order_status`, `payment_status`, `recipient_name`, `recipient_phone`, `lokasi_sekolah`, `catatan_lokasi`, `shipping`, `shipping_cost`, `payment_method`, `payment_proof`, `notes`, `items_json`, `total_amount`)
                        VALUES 
                        ('$oid', '$u', '$od', '$os', '$ps', '$rn', '$rp', '$ls', '$cl', '$shp', $sc, '$pm', $pp, '$nt', '$ij', $tot)";
                    @mysqli_query($conn, $q);
                }
            }
        }
    }

    return $ordersList;
}

// Helper Simpan Orders ke JSON
function syncOrdersToJson($file, $orders) {
    @file_put_contents($file, json_encode(array_values($orders), JSON_PRETTY_PRINT));
}

// Handle Aksi Admin (Update Status, Hapus, dan Reset Halaman)
$flashMessage = "";
$flashType = "";
$activeTab = $_GET['tab'] ?? 'orders';

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. FITUR RESET SEMUA DATA PESANAN
    if (isset($_POST['reset_all_orders'])) {
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($confirmPassword === $ADMIN_PASS) {
            // Hapus di MySQL
            if ($conn) {
                @mysqli_query($conn, "TRUNCATE TABLE `orders`");
            }
            // Kosongkan file JSON
            syncOrdersToJson($ordersFile, []);

            // Kosongkan session riwayat pesanan lokal
            unset($_SESSION['order_history']);
            unset($_SESSION['latest_order']);

            $flashMessage = "<b>RESET BERHASIL:</b> Seluruh data antrean pesanan di MySQL dan sistem telah dikosongkan (0 pesanan). Halaman admin kembali bersih!";
            $flashType = "success";
        } else {
            $flashMessage = "<b>Gagal Reset:</b> Kata sandi konfirmasi salah. Tindakan reset dibatalkan demi keamanan.";
            $flashType = "error";
        }
    }

    // 2. UPDATE STATUS PESANAN
    if (isset($_POST['update_status'])) {
        $targetId = trim($_POST['order_id'] ?? '');
        $newStatus = trim($_POST['new_status'] ?? '');
        $newPaymentStatus = 'Pembayaran Terverifikasi';
        if ($newStatus === 'Selesai (Dinikmati)') {
            $newPaymentStatus = 'Lunas / Selesai';
        } elseif (strpos($newStatus, 'Menunggu') !== false) {
            $newPaymentStatus = 'Bukti Terkirim (Diverifikasi Admin Dapur)';
        } elseif (strpos($newStatus, 'Batal') !== false) {
            $newPaymentStatus = 'Pesanan Dibatalkan';
        }

        // Update di MySQL
        if ($conn) {
            $tid = mysqli_real_escape_string($conn, $targetId);
            $nst = mysqli_real_escape_string($conn, $newStatus);
            $nps = mysqli_real_escape_string($conn, $newPaymentStatus);
            @mysqli_query($conn, "UPDATE `orders` SET `order_status`='$nst', `payment_status`='$nps' WHERE `order_id`='$tid'");
        }

        // Update di JSON
        $jsonOrders = fetchAllOrders($conn, $ordersFile);
        foreach ($jsonOrders as &$ord) {
            if ($ord['order_id'] === $targetId) {
                $ord['order_status'] = $newStatus;
                $ord['payment_status'] = $newPaymentStatus;
                break;
            }
        }
        unset($ord);
        syncOrdersToJson($ordersFile, $jsonOrders);

        $flashMessage = "Status pesanan <b>$targetId</b> berhasil diperbarui menjadi: <b>$newStatus</b>.";
        $flashType = "success";
    }

    // 3. HAPUS PESANAN SATUAN
    if (isset($_POST['delete_order'])) {
        $targetId = trim($_POST['order_id'] ?? '');

        // Hapus di MySQL
        if ($conn) {
            $tid = mysqli_real_escape_string($conn, $targetId);
            @mysqli_query($conn, "DELETE FROM `orders` WHERE `order_id`='$tid'");
        }

        // Hapus di JSON
        $jsonOrders = fetchAllOrders($conn, $ordersFile);
        $jsonOrders = array_filter($jsonOrders, function($ord) use ($targetId) {
            return ($ord['order_id'] !== $targetId);
        });
        syncOrdersToJson($ordersFile, $jsonOrders);

        $flashMessage = "Pesanan <b>$targetId</b> berhasil dihapus dari sistem.";
        $flashType = "warning";
    }

    // 4. KELOLA & CUSTOMIZE 5 SLIDER HERO (SESUAI REQUEST USER)
    if (isset($_POST['update_slider'])) {
        $activeTab = 'sliders';
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ctaText = trim($_POST['cta_text'] ?? '');
        $ctaLink = trim($_POST['cta_link'] ?? '');

        // Upload Gambar Slider
        $uploadedImagePath = null;
        if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['slider_image']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $dir = __DIR__ . '/uploads/sliders';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $newName = 'slide_' . $slideId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp, $dir . '/' . $newName)) {
                    $uploadedImagePath = 'uploads/sliders/' . $newName;
                }
            }
        }

        // Update di JSON
        foreach ($adminSliders as &$sl) {
            if ((int)$sl['id'] === $slideId) {
                $sl['title'] = $title;
                $sl['badge'] = $badge;
                $sl['subtitle'] = $subtitle ?: $badge;
                $sl['description'] = $description;
                $sl['cta_text'] = $ctaText;
                $sl['cta_link'] = $ctaLink;
                if ($uploadedImagePath) {
                    $sl['image'] = $uploadedImagePath;
                }
                break;
            }
        }
        unset($sl);
        @file_put_contents($slidersFile, json_encode($adminSliders, JSON_PRETTY_PRINT));

        // Update di MySQL
        if ($conn) {
            $t  = mysqli_real_escape_string($conn, $title);
            $b  = mysqli_real_escape_string($conn, $badge);
            $st = mysqli_real_escape_string($conn, $subtitle ?: $badge);
            $d  = mysqli_real_escape_string($conn, $description);
            $ct = mysqli_real_escape_string($conn, $ctaText);
            $cl = mysqli_real_escape_string($conn, $ctaLink);
            $imgSql = $uploadedImagePath ? ", `image`='" . mysqli_real_escape_string($conn, $uploadedImagePath) . "'" : "";
            @mysqli_query($conn, "UPDATE `sliders` SET `title`='$t', `badge`='$b', `subtitle`='$st', `description`='$d', `cta_text`='$ct', `cta_link`='$cl' $imgSql WHERE `id`=$slideId");
        }

        $flashMessage = "Slide #$slideId berhasil diperbarui!";
        $flashType = "success";
    }

    // 5. KELOLA & CUSTOMIZE 4 KEUNGGULAN BERBASIS FOTO (SESUAI REQUEST USER)
    if (isset($_POST['update_keunggulan'])) {
        $activeTab = 'keunggulan';
        $kId = (int)($_POST['keunggulan_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Upload Gambar Keunggulan
        $uploadedImagePath = null;
        if (isset($_FILES['keunggulan_image']) && $_FILES['keunggulan_image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['keunggulan_image']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['keunggulan_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $dir = __DIR__ . '/uploads/keunggulan';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $newName = 'keung_' . $kId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp, $dir . '/' . $newName)) {
                    $uploadedImagePath = 'uploads/keunggulan/' . $newName;
                }
            }
        }

        // Update di JSON
        foreach ($adminKeunggulan as &$kg) {
            if ((int)$kg['id'] === $kId) {
                $kg['title'] = $title;
                $kg['tag'] = $tag;
                $kg['description'] = $description;
                if ($uploadedImagePath) {
                    $kg['image'] = $uploadedImagePath;
                }
                break;
            }
        }
        unset($kg);
        @file_put_contents($keunggulanFile, json_encode($adminKeunggulan, JSON_PRETTY_PRINT));

        // Update di MySQL
        if ($conn) {
            $t  = mysqli_real_escape_string($conn, $title);
            $tg = mysqli_real_escape_string($conn, $tag);
            $d  = mysqli_real_escape_string($conn, $description);
            $imgSql = $uploadedImagePath ? ", `image`='" . mysqli_real_escape_string($conn, $uploadedImagePath) . "'" : "";
            @mysqli_query($conn, "UPDATE `keunggulan` SET `title`='$t', `tag`='$tg', `description`='$d' $imgSql WHERE `id`=$kId");
        }

        $flashMessage = "Kartu Keunggulan #$kId berhasil diperbarui!";
        $flashType = "success";
    }
}

// Ambil Data Pesanan Terkini
$orders = fetchAllOrders($conn, $ordersFile);

// Filter & Search
$searchQuery = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$filteredOrders = array_filter($orders, function($ord) use ($searchQuery, $statusFilter) {
    if ($statusFilter !== 'all') {
        if ($statusFilter === 'Menunggu' && strpos($ord['order_status'], 'Menunggu') === false) return false;
        if ($statusFilter === 'Dapur' && strpos($ord['order_status'], 'Dapur') === false) return false;
        if ($statusFilter === 'Antar' && strpos($ord['order_status'], 'Antar') === false) return false;
        if ($statusFilter === 'Selesai' && strpos($ord['order_status'], 'Selesai') === false) return false;
        if ($statusFilter === 'Batal' && strpos($ord['order_status'], 'Batal') === false) return false;
    }

    if (!empty($searchQuery)) {
        $haystack = strtolower(
            ($ord['order_id'] ?? '') . ' ' .
            ($ord['recipient_name'] ?? '') . ' ' .
            ($ord['username'] ?? '') . ' ' .
            ($ord['lokasi_sekolah'] ?? '') . ' ' .
            ($ord['recipient_phone'] ?? '')
        );
        if (strpos($haystack, strtolower($searchQuery)) === false) {
            return false;
        }
    }
    return true;
});

// Hitung Statistik
$totalRevenue = 0;
$countTotal = count($orders);
$countPending = 0;
$countCooking = 0;
$countCompleted = 0;

foreach ($orders as $ord) {
    $st = $ord['order_status'] ?? '';
    if (strpos($st, 'Batal') === false) {
        $totalRevenue += (int)($ord['total_amount'] ?? 0);
    }
    if (strpos($st, 'Menunggu') !== false) {
        $countPending++;
    } elseif (strpos($st, 'Dapur') !== false || strpos($st, 'Antar') !== false) {
        $countCooking++;
    } elseif (strpos($st, 'Selesai') !== false) {
        $countCompleted++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
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
                        choco: {
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #faf5e8;
            color: #3b281c;
        }
        .font-serif { font-family: 'Playfair Display', serif; }
        .nav-pastel {
            background: rgba(255, 253, 247, 0.95);
            backdrop-filter: blur(14px);
            border-bottom: 1.5px solid #ebdcc5;
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; color: black !important; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#faf5e8] selection:bg-rose-400 selection:text-white">

    <!-- Top Admin Header -->
    <header class="sticky top-0 z-40 nav-pastel shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3 group" title="Kembali ke Halaman Toko">
                        <div class="w-12 h-12 rounded-2xl overflow-hidden bg-white border border-[#e8d7be] p-1 shadow-sm group-hover:scale-105 transition-transform shrink-0 flex items-center justify-center">
                            <img src="<?= $baseUri ?>images/logo-prestige.png" alt="Logo" class="w-10 h-10 object-contain">
                        </div>
                        <div>
                            <span class="text-xl sm:text-2xl font-serif font-extrabold tracking-tight text-[#3b281c] block leading-tight">
                                <span class="text-[#6a8738]">Pistachio</span> <span class="text-rose-500">Prestige</span>
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] uppercase font-bold tracking-wider text-white bg-[#8ea750] px-2 py-0.5 rounded-md -mt-0.5">
                                <i class="fa-solid fa-database text-[9px]"></i> Full MySQL & Admin Dapur
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Right Action Buttons -->
                <div class="flex items-center space-x-2.5">
                    <a href="index.php" class="px-3.5 py-2 rounded-xl bg-white hover:bg-stone-50 text-[#553b2a] border border-[#e8d7be] text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        <span class="hidden sm:inline">Buka Toko</span>
                    </a>

                    <?php if ($isAdmin): ?>
                        <!-- Tombol Reset Data Pesanan -->
                        <button onclick="openResetModal()" class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                            <span class="hidden md:inline">Reset Pesanan</span>
                        </button>

                        <a href="admin.php?action=logout" class="px-3.5 py-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold transition flex items-center gap-1.5">
                            <i class="fa-solid fa-right-from-bracket text-xs"></i>
                            <span class="hidden sm:inline">Keluar</span>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </header>

    <!-- CONTENT WRAPPER -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if (!$isAdmin): ?>
            <!-- ================= FORM LOGIN ADMIN ================= -->
            <div class="max-w-md mx-auto my-12">
                <div class="bg-white rounded-3xl p-8 border border-[#e8d7be] shadow-xl text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-[#eef6db] text-[#557827] flex items-center justify-center text-2xl border border-[#cbe1a3]">
                        <i class="fa-solid fa-lock"></i>
                    </div>

                    <h2 class="text-2xl font-serif font-bold text-[#382116]">Akses Dapur & Admin</h2>
                    <p class="text-xs text-[#7d5f4c] mt-1 mb-6">
                        Masukkan kata sandi pengelola untuk memantau pesanan masuk dan mengubah status pemrosesan.
                    </p>

                    <?php if (!empty($authError)): ?>
                        <div class="mb-5 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation text-rose-500 shrink-0"></i>
                            <span><?= htmlspecialchars($authError) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="admin.php" class="space-y-4">
                        <div class="text-left">
                            <label class="block text-xs font-bold text-[#553b2a] mb-1.5 uppercase tracking-wider">
                                Kata Sandi Admin
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       name="admin_password" 
                                       required 
                                       placeholder="Ketik password admin..." 
                                       class="w-full px-4 py-3 rounded-2xl bg-[#faf5e8] border border-[#ebdcc5] text-[#382116] text-sm focus:outline-hidden focus:ring-2 focus:ring-[#8ea750] transition">
                            </div>
                        </div>

                        <button type="submit" 
                                name="admin_login" 
                                class="w-full py-3.5 rounded-2xl bg-[#8ea750] hover:bg-[#7a933f] text-white font-extrabold text-sm shadow-md shadow-emerald-100 transition transform hover:-translate-y-0.5">
                            Masuk ke Dashboard
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- ================= DASHBOARD ADMIN UTAMA ================= -->

            <!-- Database Status Indicator -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-[#ebdcc5] text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full <?= $conn ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' ?>"></span>
                    <span class="font-bold text-[#382116]">
                        Status Basis Data: 
                        <span class="<?= $conn ? 'text-emerald-700' : 'text-amber-700' ?>">
                            <?= $conn ? 'MySQL Terhubung (Database: login)' : 'Fallback Mode (File JSON)' ?>
                        </span>
                    </span>
                </div>
                <div class="flex items-center gap-2 text-stone-500">
                    <span>Tabel aktif: <b>users</b>, <b>products</b>, <b>orders</b></span>
                </div>
            </div>

            <!-- Flash Alert -->
            <?php if (!empty($flashMessage)): ?>
                <div class="mb-6 p-4 rounded-2xl <?= $flashType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($flashType === 'error' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-amber-50 border-amber-200 text-amber-800') ?> border text-xs sm:text-sm flex items-center justify-between shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-amber-600' ?> text-base"></i>
                        <div><?= $flashMessage ?></div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-stone-400 hover:text-stone-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- ================= NAVIGASI TAB ADMIN UTAMA ================= -->
            <div class="mb-8 flex flex-wrap items-center gap-2.5 border-b border-[#ebdcc5] pb-4">
                <button type="button" onclick="switchAdminTab('orders')" id="tab-btn-orders" class="admin-tab-btn px-4 py-2.5 rounded-2xl <?= $activeTab === 'orders' ? 'bg-[#8ea750] text-white text-xs font-extrabold shadow-xs' : 'bg-white hover:bg-stone-50 border border-[#ebdcc5] text-[#553b2a] text-xs font-bold' ?> transition flex items-center gap-2">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Antrean Pesanan (<?= $countTotal ?>)</span>
                </button>
                <button type="button" onclick="switchAdminTab('sliders')" id="tab-btn-sliders" class="admin-tab-btn px-4 py-2.5 rounded-2xl <?= $activeTab === 'sliders' ? 'bg-[#8ea750] text-white text-xs font-extrabold shadow-xs' : 'bg-white hover:bg-stone-50 border border-[#ebdcc5] text-[#553b2a] text-xs font-bold' ?> transition flex items-center gap-2">
                    <i class="fa-solid fa-images text-rose-500"></i>
                    <span>Kelola 5 Slider Hero</span>
                </button>
                <button type="button" onclick="switchAdminTab('keunggulan')" id="tab-btn-keunggulan" class="admin-tab-btn px-4 py-2.5 rounded-2xl <?= $activeTab === 'keunggulan' ? 'bg-[#8ea750] text-white text-xs font-extrabold shadow-xs' : 'bg-white hover:bg-stone-50 border border-[#ebdcc5] text-[#553b2a] text-xs font-bold' ?> transition flex items-center gap-2">
                    <i class="fa-solid fa-award text-amber-500"></i>
                    <span>Kelola 4 Keunggulan Foto</span>
                </button>
            </div>

            <!-- ================= PANEL 1: KELOLA PESANAN MASUK ================= -->
            <div id="admin-panel-orders" class="admin-tab-panel <?= $activeTab !== 'orders' ? 'hidden' : '' ?>">

            <!-- STATISTIK KARTU (KPI CARDS) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                
                <!-- Total Pendapatan -->
                <div class="bg-white rounded-3xl p-5 border border-[#e8d7be] shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#eef6db] text-[#557827] flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wider font-bold text-[#7d5f4c]">Total Omset</p>
                        <h3 class="text-lg sm:text-2xl font-extrabold text-[#382116]">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></h3>
                    </div>
                </div>

                <!-- Total Pesanan -->
                <div class="bg-white rounded-3xl p-5 border border-[#e8d7be] shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#ffebee] text-rose-600 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wider font-bold text-[#7d5f4c]">Total Pesanan</p>
                        <h3 class="text-lg sm:text-2xl font-extrabold text-rose-600"><?= $countTotal ?> Pesanan</h3>
                    </div>
                </div>

                <!-- Perlu Verifikasi -->
                <div class="bg-white rounded-3xl p-5 border border-[#e8d7be] shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wider font-bold text-[#7d5f4c]">Perlu Cek / Dapur</p>
                        <h3 class="text-lg sm:text-2xl font-extrabold text-amber-600"><?= ($countPending + $countCooking) ?> Antrean</h3>
                    </div>
                </div>

                <!-- Selesai -->
                <div class="bg-white rounded-3xl p-5 border border-[#e8d7be] shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wider font-bold text-[#7d5f4c]">Pesanan Selesai</p>
                        <h3 class="text-lg sm:text-2xl font-extrabold text-sky-700"><?= $countCompleted ?> Cup</h3>
                    </div>
                </div>

            </div>

            <!-- FILTER, SEARCH & ACTION BAR -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-[#e8d7be] shadow-xs mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Quick Filter Status -->
                <div class="flex items-center gap-1.5 flex-wrap w-full md:w-auto">
                    <a href="admin.php?status=all<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'all' ? 'bg-[#8ea750] text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' ?>">
                        Semua (<?= $countTotal ?>)
                    </a>
                    <a href="admin.php?status=Menunggu<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Menunggu' ? 'bg-amber-500 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' ?>">
                        Menunggu (<?= $countPending ?>)
                    </a>
                    <a href="admin.php?status=Dapur<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Dapur' ? 'bg-blue-600 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' ?>">
                        Dapur / Diantar
                    </a>
                    <a href="admin.php?status=Selesai<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' ?>">
                        Selesai (<?= $countCompleted ?>)
                    </a>
                </div>

                <!-- Form Search & Action Reset -->
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <form method="GET" action="admin.php" class="flex items-center gap-2 w-full md:w-auto">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <div class="relative w-full md:w-60">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-stone-400 text-xs"></i>
                            <input type="text" 
                                   name="q" 
                                   value="<?= htmlspecialchars($searchQuery) ?>" 
                                   placeholder="Cari ID, Nama, Kelas..." 
                                   class="w-full pl-9 pr-3 py-2 rounded-xl bg-[#faf5e8] border border-[#ebdcc5] text-xs focus:outline-hidden focus:ring-1 focus:ring-[#8ea750]">
                        </div>
                        <?php if (!empty($searchQuery)): ?>
                            <a href="admin.php?status=<?= htmlspecialchars($statusFilter) ?>" class="p-2 text-stone-400 hover:text-stone-600 text-xs" title="Reset Pencarian">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#381e14] text-white text-xs font-bold hover:bg-[#5c3324] transition">
                            Cari
                        </button>
                    </form>

                    <!-- Tombol Cepat Reset Halaman -->
                    <button onclick="openResetModal()" class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition shrink-0" title="Bersihkan & Reset Seluruh Data Pesanan">
                        <i class="fa-solid fa-trash-can mr-1"></i> Reset Data
                    </button>
                </div>

            </div>

            <!-- DAFTAR PESANAN -->
            <?php if (empty($filteredOrders)): ?>
                <div class="bg-white rounded-3xl p-12 border border-[#e8d7be] text-center shadow-xs">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center text-2xl">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <h3 class="text-base font-bold text-[#382116]">Tidak Ada Pesanan Ditemukan</h3>
                    <p class="text-xs text-[#7d5f4c] mt-1">
                        <?= !empty($searchQuery) ? 'Tidak ada hasil untuk kata kunci pencarian Anda.' : 'Belum ada antrean pesanan pada kategori status ini (atau data baru saja di-reset).' ?>
                    </p>
                </div>
            <?php else: ?>

                <div class="space-y-6">
                    <?php foreach ($filteredOrders as $ord): 
                        $statusText = $ord['order_status'] ?? 'Menunggu Verifikasi';
                        $statusBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                        if (strpos($statusText, 'Dapur') !== false) {
                            $statusBadgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                        } elseif (strpos($statusText, 'Antar') !== false) {
                            $statusBadgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
                        } elseif (strpos($statusText, 'Selesai') !== false) {
                            $statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        } elseif (strpos($statusText, 'Batal') !== false) {
                            $statusBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                        }

                        // WA Phone Format
                        $rawPhone = preg_replace('/[^0-9]/', '', $ord['recipient_phone'] ?? '');
                        if (substr($rawPhone, 0, 1) === '0') {
                            $waPhone = '62' . substr($rawPhone, 1);
                        } else {
                            $waPhone = $rawPhone;
                        }
                    ?>
                        <div class="bg-white rounded-3xl border border-[#e8d7be] shadow-xs hover:shadow-md transition-shadow overflow-hidden">
                            
                            <!-- Card Header -->
                            <div class="p-4 sm:p-5 bg-gradient-to-r from-[#faf5e8] via-white to-white border-b border-[#ebdcc5] flex flex-wrap items-center justify-between gap-3">
                                
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-xl bg-[#381e14] text-white font-mono text-xs font-bold tracking-wider">
                                        <?= htmlspecialchars($ord['order_id']) ?>
                                    </span>
                                    <span class="text-xs text-stone-500 font-medium">
                                        <i class="fa-regular fa-clock mr-1"></i> <?= htmlspecialchars($ord['order_date'] ?? '') ?>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusBadgeClass ?>">
                                        <i class="fa-solid fa-circle-dot text-[9px] mr-1"></i>
                                        <?= htmlspecialchars($statusText) ?>
                                    </span>
                                    
                                    <!-- Tombol Print Nota Satuan -->
                                    <button onclick="printOrderSlip('<?= htmlspecialchars($ord['order_id']) ?>')" 
                                            class="p-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs transition" 
                                            title="Cetak Struk Pesanan">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </div>

                            </div>

                            <!-- Card Body: Grid Detail -->
                            <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                                
                                <!-- Kolom 1: Informasi Pemesan & Lokasi Sekolah (4 Cols) -->
                                <div class="md:col-span-4 space-y-3.5 border-b md:border-b-0 md:border-r border-stone-100 pb-4 md:pb-0 md:pr-4">
                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-stone-400 tracking-wider block">Pemesan</span>
                                        <h4 class="text-sm font-bold text-[#382116] mt-0.5">
                                            <?= htmlspecialchars($ord['recipient_name'] ?? $ord['username']) ?>
                                            <span class="text-xs font-normal text-stone-500">(<?= htmlspecialchars($ord['username'] ?? '') ?>)</span>
                                        </h4>
                                    </div>

                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-stone-400 tracking-wider block">Tujuan Antar Sekolah</span>
                                        <div class="mt-1 p-2.5 rounded-xl bg-[#eef6db] border border-[#cbe1a3] text-xs text-[#557827] font-bold flex items-start gap-2">
                                            <i class="fa-solid fa-school mt-0.5 text-[#6a8738]"></i>
                                            <div>
                                                <p><?= htmlspecialchars($ord['lokasi_sekolah'] ?? 'Kelas / Ruangan') ?></p>
                                                <?php if (!empty($ord['catatan_lokasi'])): ?>
                                                    <p class="text-[11px] font-normal text-stone-600 mt-0.5 italic">
                                                        "<?= htmlspecialchars($ord['catatan_lokasi']) ?>"
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <span class="text-[10px] uppercase font-bold text-stone-400 tracking-wider block">Kontak WhatsApp</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="text-xs font-semibold text-stone-700"><?= htmlspecialchars($ord['recipient_phone'] ?? '-') ?></span>
                                            <?php if (!empty($waPhone) && strlen($waPhone) >= 9): ?>
                                                <a href="https://wa.me/<?= $waPhone ?>?text=Halo%20kak%20<?= urlencode($ord['recipient_name'] ?? '') ?>%20dari%20Pistachio%20Prestige%20terkait%20pesanan%20<?= urlencode($ord['order_id']) ?>" 
                                                   target="_blank" 
                                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition">
                                                    <i class="fa-brands fa-whatsapp text-xs"></i> Hubungi WA
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom 2: Menu yang Dipesan & Pembayaran (5 Cols) -->
                                <div class="md:col-span-5 space-y-3.5 border-b md:border-b-0 md:border-r border-stone-100 pb-4 md:pb-0 md:pr-4">
                                    <span class="text-[10px] uppercase font-bold text-stone-400 tracking-wider block">Menu Dipesan</span>
                                    
                                    <div class="space-y-2">
                                        <?php if (!empty($ord['items']) && is_array($ord['items'])): ?>
                                            <?php foreach ($ord['items'] as $it): ?>
                                                <div class="flex items-center justify-between text-xs bg-[#faf5e8] p-2 rounded-xl border border-[#ebdcc5]">
                                                    <div class="flex items-center gap-2.5">
                                                        <?php if (!empty($it['image'])): ?>
                                                            <img src="<?= htmlspecialchars($it['image']) ?>" alt="" class="w-8 h-8 rounded-lg object-cover">
                                                        <?php endif; ?>
                                                        <div>
                                                            <p class="font-bold text-[#382116]"><?= htmlspecialchars($it['name']) ?></p>
                                                            <p class="text-[10.5px] text-stone-500"><?= (int)$it['qty'] ?>x @ Rp <?= number_format($it['price'], 0, ',', '.') ?></p>
                                                        </div>
                                                    </div>
                                                    <span class="font-bold text-rose-600">
                                                        Rp <?= number_format((int)$it['qty'] * (int)$it['price'], 0, ',', '.') ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-xs text-stone-400 italic">Detail item tidak tersedia</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Total & Metode Bayar -->
                                    <div class="pt-2 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="text-[10.5px] text-stone-500 block">Metode Pembayaran:</span>
                                            <span class="font-bold text-[#382116]"><?= htmlspecialchars($ord['payment_method'] ?? '-') ?></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[10.5px] text-stone-500 block">Total Akhir:</span>
                                            <span class="text-base font-extrabold text-rose-600">Rp <?= number_format($ord['total_amount'] ?? 0, 0, ',', '.') ?></span>
                                        </div>
                                    </div>

                                    <!-- Bukti Pembayaran -->
                                    <div class="pt-2">
                                        <span class="text-[10px] uppercase font-bold text-stone-400 tracking-wider block mb-1">Bukti Pembayaran</span>
                                        <?php if (!empty($ord['payment_proof']) && file_exists(__DIR__ . '/' . $ord['payment_proof'])): ?>
                                            <div class="flex items-center gap-2">
                                                <a href="<?= htmlspecialchars($ord['payment_proof']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition">
                                                    <i class="fa-solid fa-image text-xs"></i> Lihat Struk / QRIS
                                                </a>
                                                <span class="text-[11px] text-emerald-600 font-semibold">
                                                    <i class="fa-solid fa-circle-check"></i> Bukti Terunggah
                                                </span>
                                            </div>
                                        <?php elseif (strpos($ord['payment_method'] ?? '', 'COD') !== false): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-stone-100 text-stone-600 text-xs font-semibold">
                                                <i class="fa-solid fa-hand-holding-dollar"></i> Bayar Tunai di Tempat (COD)
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Belum ada bukti transfer
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Kolom 3: Aksi Update Status Pesanan (3 Cols) -->
                                <div class="md:col-span-3 space-y-3 bg-[#faf5e8] p-4 rounded-2xl border border-[#ebdcc5]">
                                    <span class="text-[11px] uppercase font-bold text-[#6a8738] tracking-wider block">
                                        <i class="fa-solid fa-sliders mr-1"></i> Update Status Pesanan
                                    </span>

                                    <form method="POST" action="admin.php" class="space-y-2">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($ord['order_id']) ?>">
                                        <select name="new_status" class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#ebdcc5] text-[#382116] font-semibold focus:outline-hidden focus:ring-1 focus:ring-[#8ea750]">
                                            <option value="Menunggu Verifikasi" <?= strpos($statusText, 'Menunggu') !== false ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                            <option value="Dapur Aktif (Sedang Dibuat)" <?= strpos($statusText, 'Dapur') !== false ? 'selected' : '' ?>>Dapur Aktif (Sedang Dibuat)</option>
                                            <option value="Diantar ke Kelas / Ruangan" <?= strpos($statusText, 'Antar') !== false ? 'selected' : '' ?>>Diantar ke Kelas / Ruangan</option>
                                            <option value="Selesai (Dinikmati)" <?= strpos($statusText, 'Selesai') !== false ? 'selected' : '' ?>>Selesai (Dinikmati)</option>
                                            <option value="Dibatalkan" <?= strpos($statusText, 'Batal') !== false ? 'selected' : '' ?>>Dibatalkan</option>
                                        </select>

                                        <button type="submit" 
                                                name="update_status" 
                                                class="w-full py-2 rounded-xl bg-[#8ea750] hover:bg-[#7a933f] text-white text-xs font-extrabold shadow-2xs transition">
                                            Simpan Status
                                        </button>
                                    </form>

                                    <!-- Tombol Hapus Pesanan -->
                                    <form method="POST" action="admin.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesanan <?= htmlspecialchars($ord['order_id']) ?>? Tindakan ini tidak bisa dibatalkan.');" class="pt-2 border-t border-stone-200">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($ord['order_id']) ?>">
                                        <button type="submit" 
                                                name="delete_order" 
                                                class="w-full py-1.5 text-[11px] text-stone-400 hover:text-rose-600 transition flex items-center justify-center gap-1 font-semibold">
                                            <i class="fa-regular fa-trash-can"></i> Hapus Pesanan
                                        </button>
                                    </form>
                                </div>

                            </div>

                            <!-- Area Cetak Struk Tersembunyi (Khusus Print) -->
                            <div id="print-slip-<?= htmlspecialchars($ord['order_id']) ?>" class="hidden print-only p-6 font-mono text-xs">
                                <div class="text-center border-b pb-3 mb-3">
                                    <h2 class="text-base font-bold">PISTACHIO PRESTIGE</h2>
                                    <p class="text-[10px]">Strawberry Chocolate Kunafa Artisanal</p>
                                    <p class="text-[10px] mt-1">ID Pesanan: <?= htmlspecialchars($ord['order_id']) ?></p>
                                    <p class="text-[10px]"><?= htmlspecialchars($ord['order_date'] ?? '') ?></p>
                                </div>
                                <div class="mb-3 space-y-1">
                                    <p><b>Penerima:</b> <?= htmlspecialchars($ord['recipient_name'] ?? '') ?> (<?= htmlspecialchars($ord['recipient_phone'] ?? '') ?>)</p>
                                    <p><b>Tujuan:</b> <?= htmlspecialchars($ord['lokasi_sekolah'] ?? '') ?></p>
                                    <?php if (!empty($ord['catatan_lokasi'])): ?>
                                        <p><b>Catatan:</b> <?= htmlspecialchars($ord['catatan_lokasi']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="border-t border-b py-2 mb-3">
                                    <?php if (!empty($ord['items'])): ?>
                                        <?php foreach ($ord['items'] as $it): ?>
                                            <div class="flex justify-between">
                                                <span><?= (int)$it['qty'] ?>x <?= htmlspecialchars($it['name']) ?></span>
                                                <span>Rp <?= number_format((int)$it['qty'] * (int)$it['price'], 0, ',', '.') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-between font-bold text-sm mb-3">
                                    <span>TOTAL:</span>
                                    <span>Rp <?= number_format($ord['total_amount'] ?? 0, 0, ',', '.') ?></span>
                                </div>
                                <div class="text-center border-t pt-3 text-[10px]">
                                    <p>Metode Bayar: <?= htmlspecialchars($ord['payment_method'] ?? '-') ?></p>
                                    <p class="mt-1">Terima kasih atas pesanan Anda!</p>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
            </div> <!-- End of #admin-panel-orders -->

            <!-- ================= PANEL 2: KELOLA 5 SLIDER HERO ================= -->
            <div id="admin-panel-sliders" class="admin-tab-panel <?= $activeTab !== 'sliders' ? 'hidden' : '' ?> space-y-6">
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#ebdcc5] shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#ebdcc5] pb-4 mb-6">
                        <div>
                            <h3 class="text-base sm:text-lg font-serif font-bold text-[#382116] flex items-center gap-2">
                                <i class="fa-solid fa-images text-rose-500"></i>
                                <span>Pengaturan 5 Slide Hero Section</span>
                            </h3>
                            <p class="text-xs text-[#7d5f4c] mt-0.5">
                                Kustomisasi gambar, judul, badge, deskripsi, dan tombol CTA untuk setiap slide beranda tanpa perlu edit kode.
                            </p>
                        </div>
                        <a href="index.php" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-[#382116] text-xs font-bold transition flex items-center gap-1.5 w-fit">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            <span>Pratinjau Slider Toko</span>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($adminSliders as $sIdx => $slider): ?>
                            <div class="p-5 rounded-2xl bg-[#fcfaf4] border border-[#ebdcc5] space-y-4">
                                <div class="flex items-center justify-between border-b border-[#ebdcc5] pb-3">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#8ea750] text-white">
                                        <i class="fa-solid fa-image"></i> Slide <?= ($sIdx + 1) ?>: <?= htmlspecialchars($slider['title']) ?>
                                    </span>
                                    <span class="text-xs text-stone-400 font-semibold">ID: <?= $slider['id'] ?></span>
                                </div>

                                <form method="POST" action="admin.php" enctype="multipart/form-data" class="space-y-4">
                                    <input type="hidden" name="update_slider" value="1">
                                    <input type="hidden" name="slide_id" value="<?= $slider['id'] ?>">

                                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                                        <!-- Image Preview & Upload Input -->
                                        <div class="lg:col-span-4 space-y-2">
                                            <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider">
                                                Foto / Gambar Slide:
                                            </label>
                                            <div class="aspect-video w-full rounded-2xl overflow-hidden bg-white border border-[#ebdcc5] flex items-center justify-center p-2 shadow-2xs">
                                                <img id="admin-slide-preview-<?= $sIdx ?>" src="<?= htmlspecialchars($slider['image']) ?>" alt="Slide Image" class="w-full h-full object-contain">
                                            </div>
                                            <input type="file" name="slider_image" accept="image/*" onchange="previewAdminSlideImg(this, <?= $sIdx ?>)" class="w-full text-xs text-stone-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#eef6db] file:text-[#557827] hover:file:bg-[#dceebb] cursor-pointer">
                                            <span class="text-[10px] text-stone-400 block">*Pilih foto baru jika ingin mengganti gambar slide ini.</span>
                                        </div>

                                        <!-- Text Inputs -->
                                        <div class="lg:col-span-8 space-y-3">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                        Judul Badge / Pill:
                                                    </label>
                                                    <input type="text" name="badge" value="<?= htmlspecialchars($slider['badge'] ?? $slider['subtitle'] ?? '') ?>" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 font-semibold text-[#382116]">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                        Subtitle / Tema:
                                                    </label>
                                                    <input type="text" name="subtitle" value="<?= htmlspecialchars($slider['subtitle'] ?? '') ?>" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 text-[#382116]">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                    Judul Utama Slide:
                                                </label>
                                                <input type="text" name="title" value="<?= htmlspecialchars($slider['title']) ?>" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 font-bold text-[#382116]">
                                            </div>

                                            <div>
                                                <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                    Deskripsi Singkat:
                                                </label>
                                                <textarea name="description" rows="2" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 text-[#382116] leading-relaxed"><?= htmlspecialchars($slider['description']) ?></textarea>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                        Teks Tombol CTA:
                                                    </label>
                                                    <input type="text" name="cta_text" value="<?= htmlspecialchars($slider['cta_text'] ?? 'Pilih Menu') ?>" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 text-[#382116]">
                                                </div>
                                                <div>
                                                    <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                        Tautan Tombol CTA:
                                                    </label>
                                                    <input type="text" name="cta_link" value="<?= htmlspecialchars($slider['cta_link'] ?? '#katalog-produk') ?>" class="w-full px-3.5 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 text-[#382116]">
                                                </div>
                                            </div>

                                            <div class="pt-2 text-right">
                                                <button type="submit" class="px-5 py-2 rounded-xl bg-[#8ea750] hover:bg-[#7a933f] text-white text-xs font-bold transition shadow-xs">
                                                    <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan Slide <?= ($sIdx + 1) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ================= PANEL 3: KELOLA 4 KEUNGGULAN FOTO ================= -->
            <div id="admin-panel-keunggulan" class="admin-tab-panel <?= $activeTab !== 'keunggulan' ? 'hidden' : '' ?> space-y-6">
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#ebdcc5] shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#ebdcc5] pb-4 mb-6">
                        <div>
                            <h3 class="text-base sm:text-lg font-serif font-bold text-[#382116] flex items-center gap-2">
                                <i class="fa-solid fa-award text-amber-500"></i>
                                <span>Pengaturan 4 Kartu Keunggulan Foto</span>
                            </h3>
                            <p class="text-xs text-[#7d5f4c] mt-0.5">
                                Unggah foto baru dan sesuaikan judul serta teks deskripsi untuk 4 pilar keunggulan toko Anda.
                            </p>
                        </div>
                        <a href="index.php#keunggulan" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-[#382116] text-xs font-bold transition flex items-center gap-1.5 w-fit">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            <span>Pratinjau Keunggulan</span>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($adminKeunggulan as $kIdx => $keung): ?>
                            <div class="p-5 rounded-2xl bg-[#fcfaf4] border border-[#ebdcc5] space-y-4">
                                <div class="flex items-center justify-between border-b border-[#ebdcc5] pb-2.5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#8ea750] text-white">
                                        <i class="fa-solid fa-check"></i> Keunggulan <?= ($kIdx + 1) ?>: <?= htmlspecialchars($keung['title']) ?>
                                    </span>
                                    <span class="text-xs text-stone-400 font-semibold">ID: <?= $keung['id'] ?></span>
                                </div>

                                <form method="POST" action="admin.php" enctype="multipart/form-data" class="space-y-3">
                                    <input type="hidden" name="update_keunggulan" value="1">
                                    <input type="hidden" name="keunggulan_id" value="<?= $keung['id'] ?>">

                                    <div class="flex items-center gap-4">
                                        <div class="w-24 h-24 rounded-2xl bg-white border border-[#ebdcc5] overflow-hidden p-2 shrink-0 flex items-center justify-center">
                                            <img src="<?= htmlspecialchars($keung['image']) ?>" alt="Keunggulan" class="w-full h-full object-contain">
                                        </div>
                                        <div class="flex-1 space-y-1">
                                            <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider">
                                                Ganti Foto Keunggulan:
                                            </label>
                                            <input type="file" name="keunggulan_image" accept="image/*" class="w-full text-xs text-stone-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#eef6db] file:text-[#557827] hover:file:bg-[#dceebb] cursor-pointer">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                        <div>
                                            <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                Tag / Badge:
                                            </label>
                                            <input type="text" name="tag" value="<?= htmlspecialchars($keung['tag'] ?? 'Keunggulan') ?>" class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 text-[#382116]">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                                Judul Keunggulan:
                                            </label>
                                            <input type="text" name="title" value="<?= htmlspecialchars($keung['title']) ?>" class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 font-bold text-[#382116]">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                            Deskripsi Keunggulan:
                                        </label>
                                        <textarea name="description" rows="2" class="w-full px-3 py-2 text-xs rounded-xl bg-white border border-[#d6c7af] focus:outline-none focus:ring-1 focus:ring-rose-400 text-[#382116]"><?= htmlspecialchars($keung['description']) ?></textarea>
                                    </div>

                                    <div class="pt-1 text-right">
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-[#8ea750] hover:bg-[#7a933f] text-white text-xs font-bold transition shadow-xs">
                                            <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Keunggulan <?= ($kIdx + 1) ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ================= MODAL KONFIRMASI RESET HALAMAN ADMIN ================= -->
            <div id="reset-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs hidden">
                <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-rose-200 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl border border-rose-200">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <h3 class="text-lg font-serif font-bold text-[#382116]">Reset Semua Data Pesanan?</h3>
                    <p class="text-xs text-[#7d5f4c] mt-2 mb-5 leading-relaxed">
                        Tindakan ini akan <b>mengosongkan seluruh antrean pesanan</b> di tabel MySQL dan file JSON. Cocok digunakan saat pergantian sesi jualan/hari baru.
                    </p>

                    <form method="POST" action="admin.php" class="space-y-4">
                        <div class="text-left">
                            <label class="block text-[11px] font-bold text-[#553b2a] uppercase tracking-wider mb-1">
                                Konfirmasi Password Admin
                            </label>
                            <input type="password" 
                                   name="confirm_password" 
                                   required 
                                   placeholder="Ketik password admin..." 
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-[#faf5e8] border border-[#ebdcc5] text-xs focus:ring-2 focus:ring-rose-500 focus:outline-hidden">
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button type="button" 
                                    onclick="closeResetModal()" 
                                    class="w-1/2 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold transition">
                                Batal
                            </button>
                            <button type="submit" 
                                    name="reset_all_orders" 
                                    class="w-1/2 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-md shadow-rose-200 transition">
                                Ya, Reset Bersih
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <!-- FOOTER ADMIN & PANDUAN RESET -->
    <footer class="mt-12 py-8 bg-[#381e14] text-[#c2ab95] text-xs border-t border-[#4d2f21]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
            <p class="font-bold text-white">Pistachio Prestige — Panel Operasional Pesanan Ekskul Sekolah</p>
            <p class="text-[11px] text-[#9e836e]">
                💡 <b>Panduan Reset:</b> Untuk reset semua antrean pesanan klik tombol <span class="text-rose-400 font-bold">Reset Pesanan</span> di atas. Untuk reset sesi login admin klik <span class="text-stone-300 font-bold">Keluar</span>. Untuk mengubah password admin, edit variabel <code>$ADMIN_PASS</code> pada file <code>admin.php</code>.
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        function switchAdminTab(tabName) {
            document.querySelectorAll('.admin-tab-panel').forEach(p => p.classList.add('hidden'));
            document.querySelectorAll('.admin-tab-btn').forEach(b => {
                b.className = 'admin-tab-btn px-4 py-2.5 rounded-2xl bg-white hover:bg-stone-50 border border-[#ebdcc5] text-[#553b2a] text-xs font-bold transition flex items-center gap-2';
            });

            const activePanel = document.getElementById('admin-panel-' + tabName);
            const activeBtn = document.getElementById('tab-btn-' + tabName);
            if (activePanel) activePanel.classList.remove('hidden');
            if (activeBtn) activeBtn.className = 'admin-tab-btn px-4 py-2.5 rounded-2xl bg-[#8ea750] text-white text-xs font-extrabold shadow-xs transition flex items-center gap-2';
        }

        // Auto tab switch jika ada parameter di URL (contoh: admin.php?tab=sliders)
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('tab')) {
                switchAdminTab(urlParams.get('tab'));
            }
        });

        // Pratinjau gambar saat admin memilih foto baru
        function previewAdminSlideImg(input, idx) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('admin-slide-preview-' + idx);
                    if (preview) preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openResetModal() {
            document.getElementById('reset-modal').classList.remove('hidden');
        }
        function closeResetModal() {
            document.getElementById('reset-modal').classList.add('hidden');
        }

        function printOrderSlip(orderId) {
            const slipContent = document.getElementById('print-slip-' + orderId);
            if (!slipContent) return;

            const printWindow = window.open('', '', 'width=450,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Struk Pesanan - ${orderId}</title>
                    <style>
                        body { font-family: monospace; padding: 20px; font-size: 12px; }
                        .text-center { text-align: center; }
                        .flex { display: flex; justify-content: space-between; }
                        .border-b { border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px; }
                        .border-t { border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px; }
                        .font-bold { font-weight: bold; }
                    </style>
                </head>
                <body>
                    ${slipContent.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        }
    </script>

</body>
</html>
