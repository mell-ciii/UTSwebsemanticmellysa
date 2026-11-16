<?php
/**
 * Kumpulan fungsi bantu (helper) aplikasi.
 */

/* ------------------- QUERY DB AMAN (PHP 8+) ------------------------ */

/**
 * Menjalankan query SQL biasa. Jika gagal, lempar exception dengan pesan
 * error MySQL asli (supaya terlihat di halaman, bukan 500 polos).
 */
function db_query($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if ($result === false) {
        throw new RuntimeException('Query database gagal: ' . $mysqli->error
            . ' | SQL: ' . $sql);
    }
    return $result;
}

/**
 * Membuat prepared statement. Jika gagal, lempar exception.
 */
function db_prepare($mysqli, $sql)
{
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Prepare statement gagal: ' . $mysqli->error
            . ' | SQL: ' . $sql);
    }
    return $stmt;
}

/** Escape output HTML (mencegah XSS). */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Membuat URL lengkap berdasarkan BASE_URL. */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Redirect lalu hentikan eksekusi. */
function redirect($path = '')
{
    header('Location: ' . url($path));
    exit;
}

/** Format angka menjadi Rupiah. */
function rupiah($number)
{
    return CURRENCY . ' ' . number_format((float) $number, 0, ',', '.');
}

/** Membuat slug dari teks (untuk URL produk/kategori). */
function slugify($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/* ---------------------- SESSION / FLASH MESSAGE ---------------------- */

/** Menyimpan pesan flash (notifikasi sekali tampil). */
function set_flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Mengambil & menghapus seluruh pesan flash. */
function take_flashes()
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ------------------------- AUTENTIKASI ------------------------------ */

function is_logged_in()
{
    return isset($_SESSION['user']);
}

/** Mengembalikan data user yang sedang login (array) atau null. */
function auth_user()
{
    return $_SESSION['user'] ?? null;
}

function is_seller()
{
    return is_logged_in() && auth_user()['role'] === 'penjual';
}

/** Wajib login; jika belum, arahkan ke halaman login. */
function require_login()
{
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

/** Wajib login sebagai penjual (untuk halaman admin). */
function require_seller()
{
    require_login();
    if (!is_seller()) {
        set_flash('danger', 'Akses ditolak. Halaman khusus penjual.');
        redirect('index.php');
    }
}

/* ---------------------------- CSRF ---------------------------------- */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Input hidden berisi token CSRF. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Memvalidasi token CSRF dari request POST. */
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.');
    }
}

/* --------------------------- LAIN-LAIN ------------------------------ */

/** Menentukan URL gambar produk (mendukung URL penuh atau file upload lokal). */
function product_image($image)
{
    if (!$image) {
        return url('assets/img/no-image.svg');
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return url('uploads/' . $image);
}

/** Jumlah total item pada keranjang user. */
function cart_count($mysqli, $userId)
{
    if (!$userId) {
        return 0;
    }
    $stmt = $mysqli->prepare('SELECT COALESCE(SUM(quantity),0) AS total FROM carts WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['total'] ?? 0);
}

/** Mengambil kembali nilai input lama (agar form tidak kosong saat error). */
function old($key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function set_old($data)
{
    $_SESSION['old'] = $data;
}

function clear_old()
{
    unset($_SESSION['old']);
}
