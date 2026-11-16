<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_seller();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/products.php');
}

verify_csrf();

$sellerId = (int) auth_user()['id'];
$id       = (int) ($_POST['id'] ?? 0);

// Hapus hanya produk milik penjual ini
$stmt = $mysqli->prepare('DELETE FROM products WHERE id = ? AND seller_id = ?');
$stmt->bind_param('ii', $id, $sellerId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    set_flash('success', 'Produk berhasil dihapus.');
} else {
    set_flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
}
$stmt->close();

redirect('admin/products.php');
