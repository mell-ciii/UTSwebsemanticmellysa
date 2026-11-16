<?php
/**
 * Bootstrap aplikasi: memuat konfigurasi, koneksi DB, dan helper.
 * Cukup panggil file ini di setiap halaman:
 *   require_once __DIR__ . '/includes/init.php';            (halaman root)
 *   require_once dirname(__DIR__) . '/includes/init.php';   (halaman admin/)
 */

// Tampilkan error di halaman agar mudah di-debug di hosting.
// Ubah ke 0 setelah situs selesai dan stabil.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Penangan error global: kalau ada error fatal/exception,
// tampilkan pesan jelas (bukan "HTTP ERROR 500" polos).
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;'
        . 'border:2px solid #dc3545;background:#fff5f5;border-radius:8px;">';
    echo '<h2 style="color:#dc3545;margin-top:0;">Terjadi Kesalahan</h2>';
    echo '<p><b>Pesan:</b> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><b>File:</b> ' . htmlspecialchars($e->getFile())
        . ' (baris ' . $e->getLine() . ')</p>';
    echo '<pre style="background:#222;color:#0f0;padding:12px;border-radius:6px;overflow:auto;">'
        . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
});
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/functions.php';
