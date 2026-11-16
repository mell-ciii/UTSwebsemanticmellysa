<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_seller();

$sellerId  = (int) auth_user()['id'];
$pageTitle = 'Pesanan Masuk';

$status = trim($_GET['status'] ?? '');
$sql = "SELECT o.id, o.order_code, o.recipient_name, o.phone, o.status, o.created_at,
               SUM(oi.subtotal) AS seller_total, COUNT(oi.id) AS total_items
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE p.seller_id = ?";
if ($status !== '') {
    $sql .= " AND o.status = ?";
}
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $mysqli->prepare($sql);
if ($status !== '') {
    $stmt->bind_param('is', $sellerId, $status);
} else {
    $stmt->bind_param('i', $sellerId);
}
$stmt->execute();
$orders = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';

$statusBadge = [
    'pending' => 'secondary', 'diproses' => 'info', 'dikirim' => 'primary',
    'selesai' => 'success', 'dibatalkan' => 'danger',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Pesanan Masuk</h4>
    <form class="d-flex" method="get" action="<?= url('admin/orders.php') ?>">
        <select name="status" class="form-select form-select-sm me-2" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <?php foreach (array_keys($statusBadge) as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th><th>Penerima</th><th>Tanggal</th>
                    <th class="text-center">Item</th><th class="text-end">Nilai</th>
                    <th class="text-center">Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($orders->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pesanan.</td></tr>
            <?php endif; ?>
            <?php while ($o = $orders->fetch_assoc()): ?>
                <tr>
                    <td class="fw-semibold"><?= e($o['order_code']) ?></td>
                    <td><?= e($o['recipient_name']) ?><div class="small text-muted"><?= e($o['phone']) ?></div></td>
                    <td><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
                    <td class="text-center"><?= (int) $o['total_items'] ?></td>
                    <td class="text-end fw-semibold"><?= rupiah($o['seller_total']) ?></td>
                    <td class="text-center">
                        <span class="badge text-bg-<?= $statusBadge[$o['status']] ?? 'secondary' ?>">
                            <?= e(ucfirst($o['status'])) ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('admin/order-detail.php?id=' . (int) $o['id']) ?>"
                           class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
