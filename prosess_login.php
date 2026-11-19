<?php 
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

include("koneksi.php");

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? md5($_POST['password']) : '';
$redirect = !empty($_POST['redirect']) ? trim($_POST['redirect']) : 'index.php';

if (!empty($username) && !empty($password)) {
    if ($conn) {
        $u = mysqli_real_escape_string($conn, $username);
        $sql = "SELECT * FROM users WHERE username='$u' AND password='$password'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_data'] = [
                'id'             => $row['id'] ?? 1,
                'username'       => $row['username'],
                'nama_lengkap'   => $row['nama_lengkap'] ?? $row['username'],
                'no_telp'        => $row['no_telp'] ?? '',
                'lokasi_sekolah' => $row['lokasi_sekolah'] ?? ($row['alamat'] ?? 'Kelas XII RPL 1'),
                'catatan_lokasi' => $row['catatan_lokasi'] ?? 'Antar saat jam istirahat'
            ];
            header("Location: " . $redirect);
            exit();
        } else {
            echo "<script>alert('Nickname atau password salah! Silakan periksa kembali.'); window.location.href='login.php" . (!empty($redirect) ? "?redirect=" . urlencode($redirect) : "") . "';</script>";
            exit();
        }
    } else {
        // Fallback jika MySQL belum aktif
        $_SESSION['username'] = $username;
        $_SESSION['user_data'] = [
            'username'       => $username,
            'nama_lengkap'   => $username,
            'no_telp'        => '081234567890',
            'lokasi_sekolah' => 'Kelas XII RPL 1',
            'catatan_lokasi' => 'Antar saat jam istirahat sekolah'
        ];
        header("Location: " . $redirect);
        exit();
    }
} else {
    header("Location: login.php" . (!empty($redirect) ? "?redirect=" . urlencode($redirect) : ""));
    exit();
}
?>
