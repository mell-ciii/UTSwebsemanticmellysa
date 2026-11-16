<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Checkout';
require_login();

$user   = auth_user();
$userId = (int) $user['id'];

/** Mengambil isi keranjang milik user. */
function get_cart_items($mysqli, $userId)
{
    $stmt = $mysqli->prepare(
        "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock
         FROM carts c JOIN products p ON p.id = c.product_id
         WHERE c.user_id = ?"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $r['subtotal'] = $r['price'] * $r['quantity'];
        $rows[] = $r;
    }
    $stmt->close();
    return $rows;
}

$items = get_cart_items($mysqli, $userId);

if (empty($items)) {
    set_flash('warning', 'Keranjang Anda kosong.');
    redirect('cart.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $recipient = trim($_POST['recipient_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['shipping_address'] ?? '');
    $note      = trim($_POST['note'] ?? '');

    if ($recipient === '') $errors[] = 'Nama penerima wajib diisi.';
    if ($phone === '')     $errors[] = 'Nomor telepon wajib diisi.';
    if ($address === '')   $errors[] = 'Alamat pengiriman wajib diisi.';

    // Validasi ulang stok terbaru
    foreach ($items as $it) {
        if ($it['quantity'] > (int) $it['stock']) {
            $errors[] = 'Stok produk "' . $it['name'] . '" tidak mencukupi (tersisa ' . (int) $it['stock'] . ').';
        }
    }

    if (empty($errors)) {
        $total     = array_sum(array_column($items, 'subtotal'));
        $orderCode = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $mysqli->begin_transaction();
        try {
            // 1) Simpan header pesanan
            $stmt = $mysqli->prepare(
                "INSERT INTO orders (order_code, buyer_id, recipient_name, phone, shipping_address, note, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->bind_param('sissssd', $orderCode, $userId, $recipient, $phone, $address, $note, $total);
            $stmt->execute();
            $orderId = $stmt->insert_id;
            $stmt->close();

            // 2) Simpan detail item + kurangi stok
            $insItem = $mysqli->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $updStock = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($items as $it) {
                $insItem->bind_param('iisdid',
                    $orderId, $it['product_id'], $it['name'], $it['price'], $it['quantity'], $it['subtotal']);
                $insItem->execute();

                $updStock->bind_param('ii', $it['quantity'], $it['product_id']);
                $updStock->execute();
            }
            $insItem->close();
            $updStock->close();

            // 3) Kosongkan keranjang
            $del = $mysqli->prepare('DELETE FROM carts WHERE user_id = ?');
            $del->bind_param('i', $userId);
            $del->execute();
            $del->close();

            $mysqli->commit();
            set_flash('success', 'Pesanan berhasil dibuat dengan kode ' . $orderCode . '.');
            redirect('order-success.php?code=' . urlencode($orderCode));

        } catch (Throwable $ex) {
            $mysqli->rollback();
            $errors[] = 'Gagal menyimpan pesanan: ' . $ex->getMessage();
        }
    }
}

$total = array_sum(array_column($items, 'subtotal'));
require_once __DIR__ . '/includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-credit-card"></i> Checkout</h4>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('checkout.php') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Data Pengiriman</h5>
                    <div class="mb-3">
                        <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                        <input type="text" name="recipient_name" class="form-control"
                               value="<?= e($_POST['recipient_name'] ?? $user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= e($_POST['phone'] ?? ($user['phone'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Pengiriman <span class="text-danger">*</span></label>
                        <textarea name="shipping_address" class="form-control" rows="3" required><?= e($_POST['shipping_address'] ?? ($user['address'] ?? '')) ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Contoh: kirim via JNE"><?= e($_POST['note'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Ringkasan Pesanan</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($items as $it): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span><?= e($it['name']) ?> <small class="text-muted">x<?= (int) $it['quantity'] ?></small></span>
                                <span><?= rupiah($it['subtotal']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-3">
                        <span>Total</span>
                        <span class="price-tag"><?= rupiah($total) ?></span>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-success btn-lg">
                            <i class="bi bi-check2-circle"></i> Buat Pesanan
                        </button>
                    </div>
                    <a href="<?= url('cart.php') ?>" class="btn btn-link w-100 mt-2">Kembali ke Keranjang</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
