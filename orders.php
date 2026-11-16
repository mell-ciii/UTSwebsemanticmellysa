<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Pesanan Saya';
require_login();

$user = auth_user();

$stmt = $mysqli->prepare(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS total_items,
            (SELECT GROUP_CONCAT(oi.product_name SEPARATOR ', ') FROM order_items oi WHERE oi.order_id = o.id) AS item_names
     FROM orders o
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$orders = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';

$statusBadge = [
    'pending'    => 'secondary',
    'diproses'   => 'info',
    'dikirim'    => 'primary',
    'selesai'    => 'success',
    'dibatalkan' => 'danger',
];
?>

<h4 class="mb-3"><i class="bi bi-receipt"></i> Pesanan Saya</h4>

<?php if ($orders->num_rows === 0): ?>
    <div class="alert alert-info">Belum ada pesanan. <a href="<?= url('products.php') ?>">Mulai belanja</a>.</div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th><th>Tanggal</th><th>Item</th>
                        <th class="text-end">Total</th><th class="text-center">Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($o['order_code']) ?></td>
                        <td><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
                        <td class="small">
                            <?= e($o['item_names'] ?? '-') ?>
                            <span class="text-muted">(<?= (int) $o['total_items'] ?> item)</span>
                        </td>
                        <td class="text-end fw-semibold"><?= rupiah($o['total_amount']) ?></td>
                        <td class="text-center">
                            <span class="badge text-bg-<?= $statusBadge[$o['status']] ?? 'secondary' ?>">
                                <?= e(ucfirst($o['status'])) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('order-success.php?code=' . urlencode($o['order_code'])) ?>"
                               class="btn btn-sm btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
