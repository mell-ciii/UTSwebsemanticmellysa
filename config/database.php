<?php
/**
 * Konfigurasi koneksi database (MySQLi).
 *
 * File ini OTOMATIS memilih kredensial:
 *   - Saat dibuka via localhost / 127.0.0.1  -> pakai blok LOCAL (XAMPP)
 *   - Saat dibuka di hosting InfinityFree   -> pakai blok HOSTING
 *
 * Jadi cukup isi blok HOSTING di bawah sekali, lalu file ini bisa
 * dipakai untuk lokal maupun hosting tanpa perlu diubah-ubah lagi.
 */

// =====================================================================
// 1. KREDENSIAL LOCAL (XAMPP)
// =====================================================================
$__db_local = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'marketplace_db',
];

// =====================================================================
// 2. KREDENSIAL HOSTING (InfinityFree)  <-- ISI BAGIAN INI
//    Lihat di vPanel -> MySQL Databases.
// =====================================================================
$__db_hosting = [
    'host' => 'sqlXXX.infinityfree.com',            // MySQL Hostname (JANGAN 'localhost')
    'user' => 'if0_XXXXXXXX',                       // Username database
    'pass' => 'password_vpanel_anda',               // Password vPanel (bukan password email)
    'name' => 'if0_XXXXXXXX_marketplace',           // Nama database
];

// =====================================================================
// Pemilihan otomatis (tidak perlu diubah)
// =====================================================================
$__host = $_SERVER['HTTP_HOST'] ?? '';
$__host = strtolower(preg_replace('/:\d+$/', '', $__host));   // buang port, mis. :8080

if ($__host === '' || $__host === 'localhost' || $__host === '127.0.0.1') {
    $__db = $__db_local;
} else {
    $__db = $__db_hosting;
}

define('DB_HOST', $__db['host']);
define('DB_USER', $__db['user']);
define('DB_PASS', $__db['pass']);
define('DB_NAME', $__db['name']);

unset($__db, $__db_local, $__db_hosting, $__host);

// ---- Membuat koneksi MySQLi ----
// PHP 8.1+ melempar mysqli_sql_exception secara default; dimatikan agar
// kegagalan koneksi bisa ditangani sendiri (menghindari HTTP ERROR 500 polos).
mysqli_report(MYSQLI_REPORT_OFF);

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Throwable $e) {
    $mysqli = null;
    http_response_code(500);
    die('Koneksi database gagal: ' . $e->getMessage()
        . '<br>Periksa DB_HOST, DB_USER, DB_PASS, DB_NAME pada config/database.php.');
}

if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Koneksi database gagal (' . $mysqli->connect_errno . '): ' . $mysqli->connect_error
        . '<br>Periksa DB_HOST, DB_USER, DB_PASS, DB_NAME pada config/database.php.');
}

// Set charset agar mendukung utf8mb4 (aman untuk emoji & karakter khusus)
$mysqli->set_charset('utf8mb4');

// Zona waktu
date_default_timezone_set('Asia/Jakarta');
