<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$order = $_SESSION['latest_order'] ?? null;
if (!$order) {
    header("Location: index.php");
    exit();
}

$isCOD = (strpos($order['payment_method'], 'COD') !== false);
$isTransfer = (strpos($order['payment_method'], 'Transfer') !== false);
$isQRIS = (!$isCOD && !$isTransfer);

$waMessage = "Halo Admin Dapur Pistachio Prestige,%0A%0ASaya ingin konfirmasi pesanan saya!%0A*No. Pesanan:* " . $order['order_id'] . "%0A*Penerima:* " . urlencode($order['recipient_name']) . " (" . $order['recipient_phone'] . ")%0A*Lokasi Sekolah:* " . urlencode($order['lokasi_sekolah'] ?? '-') . "%0A*Catatan Lokasi:* " . urlencode($order['catatan_lokasi'] ?? '-') . "%0A*Metode Pembayaran:* " . urlencode($order['payment_method']) . "%0A*Pengantaran:* " . urlencode($order['shipping']) . "%0A%0A*Daftar Menu:*%0A";

if (!empty($order['items'])) {
    foreach ($order['items'] as $idx => $it) {
        $waMessage .= ($idx + 1) . ". " . urlencode($it['name']) . " (" . $it['qty'] . "x) - Rp " . number_format($it['price'] * $it['qty'], 0, ',', '.') . "%0A";
    }
}
$waMessage .= "%0A*Total: Rp " . number_format($order['total_amount'], 0, ',', '.') . "*%0A*Catatan Dapur:* " . urlencode($order['notes'] ?? '-') . "%0A%0AMohon segera disiapkan dan diantar ke kelas/ruangan ya min, terima kasih!";
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
    </style>
</head>
<body class="min-h-screen py-8 px-3 sm:px-6 flex items-center justify-center antialiased selection:bg-rose-400 selection:text-white">

    <div class="max-w-xl w-full bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-[#ebdcc5] text-center space-y-6">
        
        <!-- Header Logo -->
        <div class="flex items-center justify-center gap-2">
            <div class="w-10 h-10 rounded-2xl overflow-hidden bg-[#faf5e8] p-1 border border-[#ebdcc5] shadow-xs flex items-center justify-center">
                <img src="images/logo-prestige.png" alt="Pistachio Prestige Logo" class="w-full h-full object-contain">
            </div>
            <div class="text-left">
                <span class="text-base font-serif font-extrabold text-[#3b281c] tracking-tight block leading-tight">
                    <span class="text-[#6a8738]">Pistachio</span> <span class="text-rose-500">Prestige</span>
                </span>
                <span class="text-[9px] uppercase font-bold text-[#6a8738] block tracking-wider">
                    Strawberry Chocolate Kunafa
                </span>
            </div>
        </div>

        <!-- Status Icon -->
        <div class="w-20 h-20 rounded-full <?php echo $isCOD ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'; ?> flex items-center justify-center text-3xl mx-auto shadow-xs animate-pulse">
            <i class="fa-solid <?php echo $isCOD ? 'fa-truck-fast' : 'fa-circle-check'; ?>"></i>
        </div>

        <div>
            <!-- Status Badge Dinamis Sesuai Metode Pembayaran -->
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider px-3.5 py-1 rounded-full border <?php echo $isCOD ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-emerald-50 text-emerald-800 border-emerald-300'; ?>">
                <?= htmlspecialchars($order['order_status']) ?>
            </span>

            <h1 class="font-serif text-2xl sm:text-3xl font-extrabold text-[#382116] mt-2.5">
                Status Pembuatan Pesanan
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">
                Nomor Pesanan: <strong class="text-rose-600 text-base font-mono"><?= htmlspecialchars($order['order_id']) ?></strong>
            </p>
        </div>

        <!-- TIMELINE / PROGRESS STEP PEMESANAN DI SEKOLAH -->
        <div class="bg-[#fcfaf4] p-4 rounded-2xl border border-[#ebdcc5] text-left">
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-stone-400 mb-3 text-center sm:text-left">
                <i class="fa-solid fa-list-check text-[#8ea750] mr-1"></i> Tahapan Pembuatan Pesanan:
            </h4>
            
            <div class="grid grid-cols-4 gap-2 text-center text-[10px] sm:text-xs">
                <!-- Step 1 -->
                <div class="space-y-1">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto text-xs font-bold shadow-xs">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="font-bold text-emerald-800 block">Diterima</span>
                </div>

                <!-- Step 2 -->
                <div class="space-y-1">
                    <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center mx-auto text-xs font-bold animate-bounce shadow-xs">
                        <i class="fa-solid fa-kitchen-set"></i>
                    </div>
                    <span class="font-bold text-amber-700 block">Dapur Aktif</span>
                </div>

                <!-- Step 3 -->
                <div class="space-y-1">
                    <div class="w-8 h-8 rounded-full bg-stone-200 text-stone-500 flex items-center justify-center mx-auto text-xs font-bold">
                        <i class="fa-solid fa-school"></i>
                    </div>
                    <span class="text-stone-400 block">Antar Kelas</span>
                </div>

                <!-- Step 4 -->
                <div class="space-y-1">
                    <div class="w-8 h-8 rounded-full bg-stone-200 text-stone-500 flex items-center justify-center mx-auto text-xs font-bold">
                        <i class="fa-solid fa-face-smile-beam"></i>
                    </div>
                    <span class="text-stone-400 block">Dinikmati</span>
                </div>
            </div>
        </div>

        <!-- BUKTI PEMBAYARAN JIKA TERSEDIA (SESUAI REQUEST USER) -->
        <?php if (!empty($order['payment_proof'])): ?>
            <div class="bg-[#f4f9ed] p-4 rounded-2xl border border-[#cbe1a3] text-left space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#557827] flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Bukti Pembayaran Terunggah
                    </span>
                    <span class="text-[10px] text-stone-400 font-medium">Tersimpan di Sistem</span>
                </div>
                <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-stone-200">
                    <a href="<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank" class="block shrink-0 group">
                        <img src="<?= htmlspecialchars($order['payment_proof']) ?>" alt="Bukti Pembayaran" class="w-16 h-16 object-cover rounded-lg border border-stone-300 group-hover:opacity-80 transition">
                    </a>
                    <div class="text-xs text-[#5c3e2e] space-y-0.5">
                        <p class="font-bold text-[#382116]">Struk Pembayaran Berhasil Diunggah</p>
                        <p class="text-[11px] text-stone-500">Sedang diverifikasi oleh admin dapur kami.</p>
                        <a href="<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank" class="inline-block text-[11px] text-[#557827] hover:underline font-bold mt-1">
                            <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Buka Gambar Ukuran Penuh
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif (!$isCOD): ?>
            <div class="bg-[#fff9eb] p-3.5 rounded-2xl border border-amber-200 text-left text-xs text-[#78432a] flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info text-amber-500 text-sm mt-0.5 shrink-0"></i>
                <div class="space-y-1">
                    <span class="font-bold block">Belum Unggah Bukti Pembayaran?</span>
                    <span class="text-stone-600 block text-[11px]">Anda dapat mengirimkan foto/screenshot transfer ke WhatsApp Admin melalui tombol hijau di bawah.</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detail Invoice Card Khusus Lingkungan Sekolah -->
        <div class="bg-[#faf5e8] p-4 sm:p-5 rounded-2xl border border-[#ebdcc5] text-left text-xs space-y-3">
            <div class="flex justify-between border-b border-[#ebdcc5] pb-2 font-bold text-[#382116]">
                <span class="flex items-center gap-1.5">
                    <i class="fa-solid fa-user text-rose-500"></i> Penerima & Lokasi Pengantaran
                </span>
                <span class="text-stone-500 font-normal"><?= htmlspecialchars($order['order_date']) ?></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[#5c3e2e]">
                <div>
                    <span class="text-stone-400 text-[11px] block">Nama Penerima & WhatsApp:</span>
                    <span class="font-bold text-[#382116]"><?= htmlspecialchars($order['recipient_name']) ?> (<?= htmlspecialchars($order['recipient_phone']) ?>)</span>
                </div>
                <div>
                    <span class="text-stone-400 text-[11px] block">Tempat Pengantaran di Sekolah:</span>
                    <span class="font-bold text-[#382116]"><?= htmlspecialchars($order['lokasi_sekolah'] ?? '-') ?></span>
                </div>
            </div>

            <?php if (!empty($order['catatan_lokasi']) && $order['catatan_lokasi'] !== '-'): ?>
                <div>
                    <span class="text-stone-400 text-[11px] block">Patokan / Catatan Ruangan:</span>
                    <span class="font-medium text-stone-700 italic"><?= htmlspecialchars($order['catatan_lokasi']) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#ebdcc5]">
                <div>
                    <span class="text-stone-400 text-[11px] block">Metode Pembayaran:</span>
                    <span class="font-bold text-[#382116]"><?= htmlspecialchars($order['payment_method']) ?></span>
                </div>
                <div>
                    <span class="text-stone-400 text-[11px] block">Status Pembayaran:</span>
                    <span class="font-bold text-rose-600"><?= htmlspecialchars($order['payment_status']) ?></span>
                </div>
            </div>

            <!-- List Menu Dipesan -->
            <div class="pt-2 border-t border-[#ebdcc5]">
                <span class="text-stone-400 text-[11px] block mb-1 font-bold uppercase tracking-wider">Menu yang Dipesan:</span>
                <ul class="space-y-1.5">
                    <?php if (!empty($order['items'])): ?>
                        <?php foreach ($order['items'] as $it): ?>
                            <li class="flex justify-between font-medium text-stone-800">
                                <span><?= htmlspecialchars($it['name']) ?> <span class="font-bold text-[#557827]">(x<?= $it['qty'] ?>)</span></span>
                                <span class="font-bold">Rp <?= number_format($it['price'] * $it['qty'], 0, ',', '.') ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="pt-2.5 border-t border-[#ebdcc5] flex justify-between items-baseline font-bold text-sm text-[#382116]">
                <span>Total Pembayaran:</span>
                <span class="text-xl font-extrabold text-rose-600">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3 pt-1">
            <a href="https://wa.me/6281234567890?text=<?= $waMessage ?>" target="_blank"
                class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white font-extrabold text-sm shadow-md shadow-emerald-200 transition-all flex items-center justify-center gap-2">
                <i class="fa-brands fa-whatsapp text-lg"></i>
                <span>Konfirmasi Status ke WhatsApp Admin</span>
            </a>

            <div class="flex items-center justify-center gap-4 text-xs font-bold pt-2">
                <a href="riwayat.php" class="text-[#557827] hover:underline flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Lihat Riwayat Beli</span>
                </a>
                <span class="text-stone-300">•</span>
                <a href="index.php" class="text-stone-500 hover:text-stone-800 underline">
                    Kembali ke Beranda
                </a>
            </div>
        </div>

    </div>

    <!-- Bersihkan keranjang belanja setelah sukses membuat pesanan -->
    <script>
        try {
            localStorage.removeItem('prestige_cart');
        } catch(e) {}
    </script>
</body>
</html>
