<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_seller();

$sellerId  = (int) auth_user()['id'];
$pageTitle = 'Dashboard';

// Statistik
$totalProducts = db_query($mysqli, "SELECT COUNT(*) AS c FROM products WHERE seller_id = $sellerId")->fetch_assoc()['c'];

$stmt = db_prepare($mysqli, "SELECT COUNT(DISTINCT oi.order_id) AS c FROM order_items oi
                          JOIN products p ON p.id = oi.product_id WHERE p.seller_id = ?");
$stmt->bind_param('i', $sellerId);
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = db_prepare($mysqli, "SELECT COALESCE(SUM(oi.subtotal),0) AS total FROM order_items oi
                          JOIN products p ON p.id = oi.product_id
                          JOIN orders o ON o.id = oi.order_id
                          WHERE p.seller_id = ? AND o.status <> 'dibatalkan'");
$stmt->bind_param('i', $sellerId);
$stmt->execute();
$revenue = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$lowStock = db_query($mysqli, "SELECT COUNT(*) AS c FROM products WHERE seller_id = $sellerId AND stock <= 5")->fetch_assoc()['c'];

// 5 pesanan terbaru yang memuat produk penjual ini
$stmt = db_prepare($mysqli,
    "SELECT o.id, o.order_code, o.recipient_name, o.status, o.created_at,
            SUM(oi.subtotal) AS seller_total
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON p.id = oi.product_id
     WHERE p.seller_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$stmt->bind_param('i', $sellerId);
$stmt->execute();
$recentOrders = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<h4 class="mb-4">Ringkasan Toko</h4>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card"><div class="card-body">
            <div class="text-muted small">Total Produk</div>
            <div class="stat-value text-primary"><?= (int) $totalProducts ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card"><div class="card-body">
            <div class="text-muted small">Pesanan Masuk</div>
            <div class="stat-value text-success"><?= (int) $totalOrders ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card"><div class="card-body">
            <div class="text-muted small">Total Penjualan</div>
            <div class="stat-value text-dark fs-5"><?= rupiah($revenue) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card"><div class="card-body">
            <div class="text-muted small">Stok Menipis (&le;5)</div>
            <div class="stat-value text-danger"><?= (int) $lowStock ?></div>
        </div></div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Pesanan Terbaru</h5>
    <a href="<?= url('admin/orders.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-middle mb-0">
            <thead class="table-light">
                <tr><th>Kode</th><th>Penerima</th><th>Tanggal</th><th class="text-end">Nilai</th><th class="text-center">Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ($recentOrders->num_rows === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan.</td></tr>
            <?php endif; ?>
            <?php while ($o = $recentOrders->fetch_assoc()): ?>
                <tr>
                    <td class="fw-semibold"><?= e($o['order_code']) ?></td>
                    <td><?= e($o['recipient_name']) ?></td>
                    <td><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
                    <td class="text-end"><?= rupiah($o['seller_total']) ?></td>
                    <td class="text-center"><span class="badge text-bg-secondary"><?= e(ucfirst($o['status'])) ?></span></td>
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
