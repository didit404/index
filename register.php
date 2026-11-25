<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
include("koneksi.php");

$redirect = htmlspecialchars($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));
$message = "";
$messageType = "";

if (isset($_POST['register'])) {
    $nama_lengkap   = trim($_POST['nama_lengkap'] ?? '');
    $username       = trim($_POST['username'] ?? '');
    $raw_pass       = $_POST['password'] ?? '';
    $no_telp        = trim($_POST['no_telp'] ?? '');
    $lokasi_sekolah = trim($_POST['lokasi_sekolah'] ?? '');
    $catatan_lokasi = trim($_POST['catatan_lokasi'] ?? '');

    if (empty($username) || empty($raw_pass) || empty($nama_lengkap)) {
        $message = "Nama lengkap, username, dan password wajib diisi!";
        $messageType = "error";
    } else {
        $password = md5($raw_pass);

        if ($conn) {
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS nama_lengkap VARCHAR(100) NULL");
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS no_telp VARCHAR(30) NULL");
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS lokasi_sekolah VARCHAR(255) NULL");
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS catatan_lokasi VARCHAR(255) NULL");

            $u = mysqli_real_escape_string($conn, $username);
            $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$u'");
            if ($check && mysqli_num_rows($check) > 0) {
                $message = "Username '{$username}' sudah terdaftar! Silakan gunakan username lain.";
                $messageType = "error";
            } else {
                $nl = mysqli_real_escape_string($conn, $nama_lengkap);
                $nt = mysqli_real_escape_string($conn, $no_telp);
                $ls = mysqli_real_escape_string($conn, $lokasi_sekolah);
                $cl = mysqli_real_escape_string($conn, $catatan_lokasi);

                $query = "INSERT INTO users (username, password, nama_lengkap, no_telp, lokasi_sekolah, catatan_lokasi) 
                          VALUES ('$u', '$password', '$nl', '$nt', '$ls', '$cl')";
                
                if (@mysqli_query($conn, $query)) {
                    $messageType = "success";
                } else {
                    $fallback = "INSERT INTO users (username, password) VALUES ('$u', '$password')";
                    if (mysqli_query($conn, $fallback)) {
                        $messageType = "success";
                    } else {
                        $message = "Gagal mendaftar: " . mysqli_error($conn);
                        $messageType = "error";
                    }
                }
            }
        } else {
            $messageType = "success";
        }

        if ($messageType === "success") {
            $_SESSION['saved_profile'] = [
                'nama_lengkap'   => $nama_lengkap,
                'username'       => $username,
                'no_telp'        => $no_telp,
                'lokasi_sekolah' => $lokasi_sekolah,
                'catatan_lokasi' => $catatan_lokasi
            ];
            
            // Langsung alihkan ke halaman login sesuai permintaan user
            $_SESSION['auth_notice'] = "Pendaftaran akun berhasil! Silakan masuk dengan Nickname '{$username}' dan password Anda.";
            header("Location: login.php" . (!empty($redirect) ? "?redirect=" . urlencode($redirect) : ""));
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun - Pistachio Prestige</title>
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
    
    <div class="max-w-xl w-full bg-white/95 backdrop-blur-md border border-[#e2d5bc] rounded-3xl p-6 sm:p-9 shadow-xl relative overflow-hidden">
        
        <div class="absolute -top-16 -right-16 w-44 h-44 bg-rose-200/50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-44 h-44 bg-emerald-200/50 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center justify-between mb-4 relative z-10">
            <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#faf5e8] hover:bg-stone-200 text-[#553b2a] font-bold text-xs transition border border-[#ebdcc5] shadow-2xs">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Beranda</span>
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
                <span class="inline-flex items-center text-[11.5px] uppercase tracking-wider text-emerald-800 font-bold bg-[#e8f2d5] border border-[#c9e0a2] px-3.5 py-1.5 rounded-full shadow-2xs">
                    Pistachio Prestige
                </span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-extrabold text-[#382116] tracking-tight">
                Pendaftaran Akun
            </h1>
            <p class="text-xs text-[#7d5f4c] mt-1 max-w-sm mx-auto">
                Isi data diri untuk kemudahan pemesanan dessert di lingkungan sekolah
            </p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-5 p-3.5 rounded-2xl text-xs font-medium <?php echo $messageType === 'success' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-rose-50 border border-rose-300 text-rose-800'; ?>">
                <div class="flex items-center gap-2">
                    <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600'; ?> text-sm"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
                <?php if ($messageType === 'success'): ?>
                    <div class="mt-2.5 pt-2 border-t border-emerald-200 font-bold">
                        <a href="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="inline-flex items-center gap-1.5 text-emerald-700 hover:text-emerald-900 underline">
                            <span>Masuk Sekarang & Lanjutkan</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4 relative z-10">
            <input type="hidden" name="redirect" value="<?= $redirect ?>">

            <!-- Data Akun -->
            <div class="bg-[#fcfaf4] p-4 rounded-2xl border border-[#ece2ce]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#694b38] mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-user-circle text-rose-500"></i> Informasi Pengguna
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" required value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>"
                            placeholder="Masukkan nama lengkap"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1">No. WhatsApp / HP *</label>
                        <input type="tel" name="no_telp" required value="<?php echo htmlspecialchars($_POST['no_telp'] ?? ''); ?>"
                            placeholder="Masukkan nomor WhatsApp / HP"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1">
                            <i class="fa-solid fa-user-tag text-rose-500 mr-1"></i> Nickname Akun (Nama Login) *
                        </label>
                        <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            placeholder="Masukkan nickname akun"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1">Password *</label>
                        <input type="password" name="password" required
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>
                </div>
            </div>

            <!-- Lokasi Pengantaran di Sekolah (Sesuai Permintaan: Khusus Lingkungan Sekolah) -->
            <div class="bg-[#fcfaf4] p-4 rounded-2xl border border-[#ece2ce]">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#694b38] mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-school text-[#8ea750]"></i> Lokasi Pengantaran di Sekolah
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1">Kelas / Ruangan / Tempat COD di Sekolah *</label>
                        <input type="text" name="lokasi_sekolah" required value="<?php echo htmlspecialchars($_POST['lokasi_sekolah'] ?? ''); ?>"
                            placeholder="Masukkan kelas, ruangan, atau tempat pengantaran"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[#5c3e2e] uppercase mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="catatan_lokasi" value="<?php echo htmlspecialchars($_POST['catatan_lokasi'] ?? ''); ?>"
                            placeholder="Tuliskan catatan pengantaran (opsional)"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-white border border-[#d6c7af] text-[#3b281c] placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>
                </div>
            </div>

            <button type="submit" name="register"
                class="w-full py-3.5 px-4 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-extrabold text-sm rounded-2xl shadow-md shadow-rose-200 transition-all duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                <i class="fa-solid fa-address-card"></i>
                <span>Daftar Akun Sekarang</span>
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-[#7d5f4c] relative z-10">
            Sudah punya akun? 
            <a href="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="text-rose-600 hover:text-rose-800 font-bold underline underline-offset-4">Masuk di Sini</a>
        </div>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-xs text-stone-500 hover:text-stone-800 transition">
                ← Kembali ke Menu Utama
            </a>
        </div>
    </div>

</body>
</html>
