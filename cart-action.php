<?php
require_once __DIR__ . '/includes/init.php';

// Hanya menerima POST untuk aksi yang mengubah data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}

verify_csrf();
require_login();

$user   = auth_user();
$userId = (int) $user['id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty       = max(1, (int) ($_POST['quantity'] ?? 1));

    // Ambil produk & pastikan aktif serta stok cukup
    $stmt = $mysqli->prepare("SELECT id, name, stock FROM products WHERE id = ? AND status = 'aktif' LIMIT 1");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        set_flash('danger', 'Produk tidak ditemukan.');
        redirect('products.php');
    }
    if ($qty > (int) $product['stock']) {
        set_flash('warning', 'Jumlah melebihi stok yang tersedia (maksimal ' . (int) $product['stock'] . ').');
        redirect('product.php?slug=' . urlencode($_POST['slug'] ?? ''));
    }

    // Tambahkan / perbarui keranjang (UNIQUE user_id + product_id)
    $stmt = $mysqli->prepare(
        "INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
    );
    $stmt->bind_param('iii', $userId, $productId, $qty);
    $stmt->execute();
    $stmt->close();

    set_flash('success', $product['name'] . ' berhasil ditambahkan ke keranjang.');
    redirect('cart.php');

} elseif ($action === 'update') {
    $cartId = (int) ($_POST['cart_id'] ?? 0);
    $qty    = max(1, (int) ($_POST['quantity'] ?? 1));

    // Pastikan baris keranjang milik user ini dan stok mencukupi
    $stmt = $mysqli->prepare(
        "SELECT c.id, p.stock FROM carts c JOIN products p ON p.id = c.product_id
         WHERE c.id = ? AND c.user_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $cartId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        if ($qty > (int) $row['stock']) {
            $qty = (int) $row['stock'];
            set_flash('warning', 'Jumlah disesuaikan dengan stok tersedia.');
        }
        $stmt = $mysqli->prepare('UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('iii', $qty, $cartId, $userId);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Keranjang diperbarui.');
    }
    redirect('cart.php');

} elseif ($action === 'remove') {
    $cartId = (int) ($_POST['cart_id'] ?? 0);
    $stmt = $mysqli->prepare('DELETE FROM carts WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $cartId, $userId);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Produk dihapus dari keranjang.');
    redirect('cart.php');

} elseif ($action === 'clear') {
    $stmt = $mysqli->prepare('DELETE FROM carts WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Keranjang dikosongkan.');
    redirect('cart.php');
}

redirect('cart.php');
