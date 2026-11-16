<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_seller();

$sellerId  = (int) auth_user()['id'];
$id        = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pageTitle = 'Detail Pesanan';

$allowedStatus = ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

// Pastikan pesanan ini memuat produk milik penjual
$chk = $mysqli->prepare(
    "SELECT COUNT(*) AS c FROM order_items oi JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ? AND p.seller_id = ?"
);
$chk->bind_param('ii', $id, $sellerId);
$chk->execute();
$count = (int) $chk->get_result()->fetch_assoc()['c'];
$chk->close();

if ($count === 0) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    redirect('admin/orders.php');
}

// Perbarui status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, $allowedStatus, true)) {
        $stmt = $mysqli->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Status pesanan diperbarui menjadi "' . $newStatus . '".');
    }
    redirect('admin/order-detail.php?id=' . $id);
}

// Header pesanan
$stmt = $mysqli->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Item milik penjual ini
$istmt = $mysqli->prepare(
    "SELECT oi.* FROM order_items oi JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ? AND p.seller_id = ?"
);
$istmt->bind_param('ii', $id, $sellerId);
$istmt->execute();
$items = $istmt->get_result();

require_once __DIR__ . '/includes/header.php';

$statusBadge = [
    'pending' => 'secondary', 'diproses' => 'info', 'dikirim' => 'primary',
    'selesai' => 'success', 'dibatalkan' => 'danger',
];
$sellerTotal = 0;
$rows = [];
while ($r = $items->fetch_assoc()) { $sellerTotal += $r['subtotal']; $rows[] = $r; }
$istmt->close();
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('admin/orders.php') ?>">Pesanan</a></li>
        <li class="breadcrumb-item active"><?= e($order['order_code']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><?= e($order['order_code']) ?></span>
                <span class="badge text-bg-<?= $statusBadge[$order['status']] ?? 'secondary' ?>">
                    <?= e(ucfirst($order['status'])) ?>
                </span>
            </div>
            <div class="card-body">
                <h6>Data Pengiriman</h6>
                <p class="mb-3">
                    <strong><?= e($order['recipient_name']) ?></strong><br>
                    <?= e($order['phone']) ?><br>
                    <?= nl2br(e($order['shipping_address'])) ?>
                    <?php if ($order['note']): ?><br><em class="text-muted">Catatan: <?= e($order['note']) ?></em><?php endif; ?>
                </p>

                <h6>Item Pesanan (produk Anda)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-middle">
                        <thead class="table-light">
                            <tr><th>Produk</th><th class="text-end">Harga</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= e($r['product_name']) ?></td>
                                <td class="text-end"><?= rupiah($r['price']) ?></td>
                                <td class="text-center"><?= (int) $r['quantity'] ?></td>
                                <td class="text-end"><?= rupiah($r['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Total Pesanan Anda</td>
                                <td class="text-end price-tag"><?= rupiah($sellerTotal) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Ubah Status Pesanan</h6>
                <form method="post" action="<?= url('admin/order-detail.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                    <select name="status" class="form-select mb-3">
                        <?php foreach ($allowedStatus as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                <?= ucfirst($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="d-grid">
                        <button class="btn btn-primary"><i class="bi bi-check2"></i> Simpan Status</button>
                    </div>
                </form>
                <hr>
                <div class="small text-muted">
                    <div>Tanggal pesan: <?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></div>
                    <div>Total nilai pesanan: <?= rupiah($order['total_amount']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
