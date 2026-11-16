<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Pesanan Berhasil';
require_login();

$user = auth_user();
$code = trim($_GET['code'] ?? '');

$stmt = $mysqli->prepare("SELECT * FROM orders WHERE order_code = ? AND buyer_id = ? LIMIT 1");
$stmt->bind_param('si', $code, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    redirect('orders.php');
}

$istmt = $mysqli->prepare("SELECT * FROM order_items WHERE order_id = ?");
$istmt->bind_param('i', $order['id']);
$istmt->execute();
$orderItems = $istmt->get_result();
$istmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="text-center mb-4">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem;"></i>
    <h3 class="mt-2">Terima Kasih!</h3>
    <p class="text-muted">Pesanan Anda telah berhasil disimpan.</p>
</div>

<div class="card border-0 shadow-sm mx-auto" style="max-width:720px;">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap mb-3">
            <div>
                <div class="small text-muted">Kode Pesanan</div>
                <div class="fw-bold fs-5"><?= e($order['order_code']) ?></div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Tanggal</div>
                <div><?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></div>
            </div>
        </div>

        <span class="badge text-bg-warning text-uppercase mb-3"><?= e($order['status']) ?></span>

        <h6>Data Pengiriman</h6>
        <p class="mb-3">
            <strong><?= e($order['recipient_name']) ?></strong><br>
            <?= e($order['phone']) ?><br>
            <?= nl2br(e($order['shipping_address'])) ?>
            <?php if ($order['note']): ?><br><em class="text-muted">Catatan: <?= e($order['note']) ?></em><?php endif; ?>
        </p>

        <h6>Rincian Item</h6>
        <div class="table-responsive">
            <table class="table table-sm table-middle">
                <thead class="table-light">
                    <tr><th>Produk</th><th class="text-end">Harga</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr>
                </thead>
                <tbody>
                <?php while ($it = $orderItems->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($it['product_name']) ?></td>
                        <td class="text-end"><?= rupiah($it['price']) ?></td>
                        <td class="text-center"><?= (int) $it['quantity'] ?></td>
                        <td class="text-end"><?= rupiah($it['subtotal']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="text-end price-tag"><?= rupiah($order['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= url('orders.php') ?>" class="btn btn-outline-primary"><i class="bi bi-receipt"></i> Pesanan Saya</a>
            <a href="<?= url('products.php') ?>" class="btn btn-primary">Lanjut Belanja</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
