<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

include_once("koneksi.php");

$isLoggedIn = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';

// Ambil riwayat pesanan dari MySQL, session, dan file persistent
$userOrders = [];

// 1. Dari MySQL Database jika aktif
if ($conn && $isLoggedIn) {
    $u = mysqli_real_escape_string($conn, $username);
    $res = @mysqli_query($conn, "SELECT * FROM `orders` WHERE `username`='$u' ORDER BY `id` DESC");
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $userOrders[$row['order_id']] = [
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
    }
}

// 2. Dari file JSON uploads/orders.json (sebagai backup / sinkron)
$ordersFile = __DIR__ . '/uploads/orders.json';
if (file_exists($ordersFile)) {
    $content = file_get_contents($ordersFile);
    $allOrders = json_decode($content, true);
    if (is_array($allOrders)) {
        foreach ($allOrders as $ord) {
            if ($isLoggedIn && isset($ord['username']) && $ord['username'] === $username) {
                $userOrders[$ord['order_id']] = $ord;
            } elseif (!$isLoggedIn) {
                // jika belum login, biarkan kosong
            }
        }
    }
}

// 2. Gabungkan dengan yang ada di session
if ($isLoggedIn && isset($_SESSION['order_history']) && is_array($_SESSION['order_history'])) {
    foreach ($_SESSION['order_history'] as $ord) {
        if (isset($ord['order_id'])) {
            $userOrders[$ord['order_id']] = $ord;
        }
    }
}

// Ubah ke array index numerik dan urutkan pesanan terbaru di paling atas
$ordersList = array_values($userOrders);
$ordersList = array_reverse($ordersList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pistachio Prestige</title>
    <link rel="icon" type="image/png" href="images/logo-prestige.png">
    <link rel="shortcut icon" type="image/png" href="images/logo-prestige.png">
    <link rel="apple-touch-icon" href="images/logo-prestige.png">
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

    <!-- NAVIGATION HEADER -->
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
                    Riwayat Pembelian
                </h1>
            </div>

            <!-- Nav Actions -->
            <div class="flex items-center gap-3">
                <a href="index.php" class="text-xs font-bold text-[#553b2a] hover:text-rose-600 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-house text-xs"></i>
                    <span class="hidden sm:inline">Beranda</span>
                </a>
                <a href="index.php#katalog-produk" class="text-xs font-bold text-[#553b2a] hover:text-rose-600 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group text-xs"></i>
                    <span class="hidden sm:inline">Menu Produk</span>
                </a>

                <?php if ($isLoggedIn): ?>
                    <div class="flex items-center gap-2 pl-2 border-l border-stone-200">
                        <span class="text-xs font-bold text-[#557827] bg-[#eef6db] px-2.5 py-1 rounded-xl border border-[#cbe1a3] hidden sm:inline">
                            <?= htmlspecialchars($username) ?>
                        </span>
                        <a href="logout.php" class="text-xs font-bold text-stone-500 hover:text-stone-800 underline">
                            Keluar
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php?redirect=riwayat.php" class="px-3.5 py-1.5 rounded-xl bg-rose-500 text-white text-xs font-bold shadow-xs">
                        Masuk Akun
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- MAIN RIWAYAT CONTENT -->
    <main class="max-w-5xl w-full mx-auto px-3 sm:px-6 py-8 space-y-6 flex-grow">

        <!-- Banner Info Header -->
        <div class="pastel-card rounded-3xl p-6 sm:p-7 shadow-xs bg-gradient-to-r from-white via-[#fffdfa] to-[#faf5e8] flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#eef6db] text-[#557827] border border-[#cbe1a3] text-xs font-bold mb-1">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Transaksi Pelanggan</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-serif font-extrabold text-[#382116]">
                    Daftar Pesanan Saya
                </h2>
                <p class="text-xs sm:text-sm text-[#7d5f4c]">
                    Pantau status pembuatan pesanan kunafa dan dessert Anda yang sedang diproses di dapur atau diantar ke sekolah.
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                <a href="index.php#katalog-produk" class="px-5 py-2.5 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-xs shadow-md shadow-rose-200 transition flex items-center gap-2">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Pesan Menu Baru</span>
                </a>
            </div>
        </div>

        <?php if (!$isLoggedIn): ?>
            <!-- Alert jika belum login -->
            <div class="pastel-card rounded-3xl p-8 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-2xl mx-auto">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="max-w-md mx-auto space-y-1">
                    <h3 class="font-serif font-bold text-lg text-[#382116]">Silakan Masuk Terlebih Dahulu</h3>
                    <p class="text-xs text-stone-500">Masuk dengan akun Anda untuk melihat semua daftar riwayat pesanan kunafa Anda.</p>
                </div>
                <div>
                    <a href="login.php?redirect=riwayat.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#8ea750] hover:bg-[#7a933f] text-white text-xs font-bold transition shadow-xs">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Masuk ke Akun Saya</span>
                    </a>
                </div>
            </div>

        <?php elseif (empty($ordersList)): ?>
            <!-- Empty State jika belum ada pesanan -->
            <div class="pastel-card rounded-3xl p-12 text-center space-y-4">
                <div class="w-20 h-20 rounded-full bg-[#fcf5e9] text-[#8ea750] flex items-center justify-center text-3xl mx-auto border border-[#ebdcc5]">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="max-w-md mx-auto space-y-1.5">
                    <h3 class="font-serif font-bold text-xl text-[#382116]">Belum Ada Riwayat Pesanan</h3>
                    <p class="text-xs sm:text-sm text-stone-500 leading-relaxed">
                        Anda belum pernah membuat pesanan dessert Prestige. Yuk cicipi kelezatan renyahnya Pistachio Kunafa Drip & Strawberry segar sekarang!
                    </p>
                </div>
                <div class="pt-2">
                    <a href="index.php#katalog-produk" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white text-xs sm:text-sm font-extrabold shadow-md shadow-rose-200 transition">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <span>Lihat Menu & Pesan Sekarang</span>
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- List of Order Cards -->
            <div class="space-y-4">
                <?php foreach ($ordersList as $order): ?>
                    <?php
                        $isCOD = (strpos($order['payment_method'], 'COD') !== false);
                        $isTransfer = (strpos($order['payment_method'], 'Transfer') !== false);
                        
                        // Buat pesan WhatsApp konfirmasi
                        $waMsg = "Halo Admin Prestige,%0ASaya ingin cek status pesanan " . $order['order_id'] . " atas nama " . urlencode($order['recipient_name']) . " (" . urlencode($order['lokasi_sekolah'] ?? '-') . "). Terima kasih!";
                    ?>
                    <div class="pastel-card rounded-3xl p-5 sm:p-6 shadow-xs space-y-4 transition hover:shadow-md">
                        
                        <!-- Header Kartu Pesanan -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 border-b border-[#ebdcc5] gap-2">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="px-3 py-1 rounded-xl bg-stone-100 font-mono text-xs font-extrabold text-[#382116] border border-stone-200">
                                    #<?= htmlspecialchars($order['order_id']) ?>
                                </span>
                                <span class="text-xs text-stone-400">
                                    <?= htmlspecialchars($order['order_date']) ?>
                                </span>
                            </div>

                            <div>
                                <span class="inline-block text-[11px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border <?php echo $isCOD ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-emerald-50 text-emerald-800 border-emerald-300'; ?>">
                                    <?= htmlspecialchars($order['order_status']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Info Penerima & Pengantaran Sekolah -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 text-xs">
                            
                            <!-- Kolom Kiri: Info Tempat Pengantaran di Sekolah -->
                            <div class="md:col-span-5 space-y-1.5 bg-[#fcfaf4] p-3.5 rounded-2xl border border-[#ebdcc5]">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 block">
                                    <i class="fa-solid fa-school mr-1"></i> Tempat Pengantaran di Sekolah:
                                </span>
                                <p class="font-bold text-[#382116] text-xs sm:text-sm">
                                    <?= htmlspecialchars($order['lokasi_sekolah'] ?? '-') ?>
                                </p>
                                <p class="text-[11px] text-stone-500 italic">
                                    Catatan: <?= htmlspecialchars($order['catatan_lokasi'] ?? '-') ?>
                                </p>
                                <div class="pt-1 text-[11px] text-stone-600">
                                    <span>Penerima: <strong><?= htmlspecialchars($order['recipient_name']) ?></strong> (<?= htmlspecialchars($order['recipient_phone']) ?>)</span>
                                </div>
                            </div>

                            <!-- Kolom Tengah: Menu yang Dipesan -->
                            <div class="md:col-span-4 space-y-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400 block">
                                    Menu Dipesan:
                                </span>
                                <ul class="space-y-1.5">
                                    <?php if (!empty($order['items'])): ?>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li class="flex items-center justify-between font-medium text-stone-800 text-xs">
                                                <span class="truncate max-w-[180px]"><?= htmlspecialchars($item['name']) ?></span>
                                                <span class="font-bold text-stone-600">x<?= $item['qty'] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="text-stone-400 italic">Prestige Signature Dessert</li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <!-- Kolom Kanan: Pembayaran & Bukti -->
                            <div class="md:col-span-3 space-y-2 bg-[#faf5e8] p-3.5 rounded-2xl border border-[#ebdcc5] flex flex-col justify-between">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400 block">Metode Pembayaran:</span>
                                    <span class="font-bold text-[#382116] block text-xs mt-0.5"><?= htmlspecialchars($order['payment_method']) ?></span>
                                    
                                    <?php if (!empty($order['payment_proof'])): ?>
                                        <div class="mt-2 flex items-center gap-1.5 text-[10px] text-[#557827] font-bold">
                                            <i class="fa-solid fa-paperclip text-emerald-600"></i>
                                            <a href="<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank" class="hover:underline">
                                                Lihat Bukti Bayar
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pt-2 border-t border-[#ebdcc5]">
                                    <span class="text-[10px] text-stone-400 block font-bold">Total Pembayaran:</span>
                                    <span class="text-base font-extrabold text-rose-600 font-serif">
                                        Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>

                        </div>

                        <!-- Action Bar -->
                        <div class="pt-3 border-t border-[#ebdcc5] flex flex-wrap items-center justify-between gap-3">
                            <span class="text-[11px] text-stone-500">
                                Status Bayar: <strong class="text-[#382116]"><?= htmlspecialchars($order['payment_status'] ?? 'Sedang Diproses') ?></strong>
                            </span>

                            <div class="flex items-center gap-2">
                                <a href="https://wa.me/6281234567890?text=<?= $waMsg ?>" target="_blank" 
                                    class="px-4 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold transition flex items-center gap-1.5">
                                    <i class="fa-brands fa-whatsapp text-emerald-600"></i> Tanya Admin
                                </a>
                                <form action="checkout.php" method="POST" class="inline">
                                    <!-- Jika ingin membuka kembali di status -->
                                    <a href="order-status.php" class="px-4 py-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-[#382116] text-xs font-bold transition flex items-center gap-1.5">
                                        <i class="fa-solid fa-eye text-stone-600"></i> Pantau Status
                                    </a>
                                </form>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER PISTACHIO PRESTIGE -->
    <footer class="bg-white border-t border-[#ebdcc5] py-6 text-center text-xs text-stone-400 mt-10">
        <p>&copy; <?= date('Y') ?> Pistachio Prestige. Strawberry Chocolate Kunafa Artisanal Boutique.</p>
    </footer>

</body>
</html>

