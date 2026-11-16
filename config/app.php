<?php
/**
 * Konfigurasi umum aplikasi.
 */

define('APP_NAME', 'Florist Mellysa');

// Base URL dideteksi otomatis, sehingga aplikasi tetap jalan
// baik di localhost (http://localhost/UTSWEBSEMANTIC) maupun di InfinityFree.
$__appRootFs = str_replace('\\', '/', dirname(__DIR__));           // folder root proyek
$__docRoot   = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

if ($__docRoot !== '' && strpos($__appRootFs, $__docRoot) === 0) {
    $__base = substr($__appRootFs, strlen($__docRoot));            // contoh: /UTSWEBSEMANTIC
} else {
    $__base = '';
}
define('BASE_URL', rtrim($__base, '/'));                            // '' atau '/UTSWEBSEMANTIC'

// Mata uang default
define('CURRENCY', 'Rp');

unset($__appRootFs, $__docRoot, $__base);
