<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$redirect = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));
if (isset($_SESSION['username'])) {
    header("Location: " . (!empty($redirect) ? $redirect : "index.php"));
    exit();
}

$authNotice = $_SESSION['auth_notice'] ?? (!empty($redirect) ? 'Silakan Masuk atau Daftar Akun terlebih dahulu untuk melanjutkan Checkout!' : '');
unset($_SESSION['auth_notice']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Pistachio Prestige</title>
    <link rel="icon" type="image/png" href="images/logo-prestige.png">
    <link rel="shortcut icon" type="image/png" href="images/logo-prestige.png">
    <link rel="apple-touch-icon" href="images/logo-prestige.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #faf6ea;
            color: #3b281c;
        }
        .font-serif {
            font-family: 'Playfair Display', serif;
        }
    </style>
</head>
<body class="min-h-screen py-10 px-4 flex items-center justify-center bg-gradient-to-b from-[#fbf5e6] via-[#f7eed8] to-[#f4e7cd]">
    
    <div class="max-w-md w-full bg-white/95 backdrop-blur-md border border-[#e2d5bc] rounded-3xl p-6 sm:p-9 shadow-xl relative overflow-hidden">
        
        <div class="absolute -top-14 -right-14 w-40 h-40 bg-rose-200/50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-14 -left-14 w-40 h-40 bg-emerald-200/50 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Tombol Keluar & Kembali yang Jelas dan Nyaman Dilihat -->
        <div class="flex items-center justify-between mb-4 relative z-10">
            <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#faf5e8] hover:bg-stone-200 text-[#553b2a] font-bold text-xs transition border border-[#ebdcc5] shadow-2xs">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Toko</span>
            </a>
            <a href="index.php" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-xmark"></i> Keluar
            </a>
        </div>

        <div class="text-center mb-6 relative z-10">
            <div class="flex items-center justify-center gap-3 mb-2">
                <a href="index.php" class="group shrink-0 inline-flex items-center justify-center">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 p-1 shadow-sm border border-amber-200/80 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <img src="images/logo-prestige.png" alt="Pistachio Prestige Logo" class="w-full h-full object-contain">
                    </div>
                </a>
                <span class="inline-flex items-center text-[11.5px] uppercase tracking-wider text-rose-800 font-bold bg-[#ffe6ea] border border-[#fccad3] px-3.5 py-1.5 rounded-full shadow-2xs">
                    Selamat Datang
                </span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-extrabold text-[#382116] tracking-tight">
                <span class="text-[#6a8738]">Pistachio</span> <span class="text-rose-500">Prestige</span>
            </h1>
            <p class="text-xs text-[#7d5f4c] mt-1">
                Masuk untuk kemudahan pemesanan dessert di lingkungan sekolah
            </p>
        </div>

        <?php if (!empty($authNotice)): ?>
            <div class="mb-4 p-3.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-amber-600 text-sm shrink-0"></i>
                <span><?= htmlspecialchars($authNotice) ?></span>
            </div>
        <?php endif; ?>

        <form action="prosess_login.php" method="POST" class="space-y-4 relative z-10">
            <input type="hidden" name="redirect" value="<?= $redirect ?>">

            <div>
                <label for="username" class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1 flex items-center gap-1">
                    <i class="fa-solid fa-user-tag text-rose-500"></i> Nickname Akun
                </label>
                <input type="text" id="username" name="username" required 
                    class="w-full px-4 py-3 rounded-xl bg-[#fcfaf4] border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500 transition text-xs"
                    placeholder="Masukkan Nickname Anda">
            </div>

            <div>
                <label for="password" class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1 flex items-center gap-1">
                    <i class="fa-solid fa-key text-emerald-600"></i> Password
                </label>
                <input type="password" id="password" name="password" required 
                    class="w-full px-4 py-3 rounded-xl bg-[#fcfaf4] border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500 transition text-xs"
                    placeholder="••••••••">
            </div>

            <button type="submit" 
                class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold rounded-2xl shadow-md shadow-emerald-200 transition duration-200 transform hover:-translate-y-0.5 text-xs sm:text-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>Masuk Sekarang</span>
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-[#7d5f4c] relative z-10">
            Belum memiliki akun? 
            <a href="register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="text-rose-600 hover:text-rose-800 font-bold underline underline-offset-4">Daftar Akun Baru</a>
        </div>

        <div class="mt-5 pt-4 border-t border-stone-200 text-center relative z-10">
            <a href="index.php" class="w-full py-2.5 px-4 rounded-xl bg-[#faf5e8] hover:bg-stone-200 text-[#553b2a] font-bold text-xs transition border border-[#ebdcc5] shadow-2xs flex items-center justify-center gap-2">
                <i class="fa-solid fa-house text-xs text-rose-500"></i>
                <span>Keluar & Kembali ke Menu Utama</span>
            </a>
        </div>
    </div>
</body>
</html>
