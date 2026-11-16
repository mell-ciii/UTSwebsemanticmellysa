<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_seller();

$sellerId  = (int) auth_user()['id'];
$pageTitle = 'Kelola Produk';

$stmt = $mysqli->prepare(
    "SELECT p.*
     FROM products p
     WHERE p.seller_id = ?
     ORDER BY p.created_at DESC"
);
$stmt->bind_param('i', $sellerId);
$stmt->execute();
$products = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Kelola Produk</h4>
    <a href="<?= url('admin/product-form.php') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Tambah Produk
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:70px;">Gambar</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Status</th>
                    <th class="text-end" style="width:150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($products->num_rows === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    Belum ada produk. <a href="<?= url('admin/product-form.php') ?>">Tambah sekarang</a>.
                </td></tr>
            <?php endif; ?>
            <?php while ($p = $products->fetch_assoc()): ?>
                <tr>
                    <td><img src="<?= e(product_image($p['image'])) ?>" width="50" height="50"
                             class="rounded object-fit-cover" alt=""></td>
                    <td>
                        <div class="fw-semibold"><?= e($p['name']) ?></div>
                        <div class="small text-muted"><?= e($p['slug']) ?></div>
                    </td>
                    <td class="text-end"><?= rupiah($p['price']) ?></td>
                    <td class="text-center">
                        <?php if ((int) $p['stock'] <= 5): ?>
                            <span class="badge text-bg-danger"><?= (int) $p['stock'] ?></span>
                        <?php else: ?>
                            <?= (int) $p['stock'] ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge text-bg-<?= $p['status'] === 'aktif' ? 'success' : 'secondary' ?>">
                            <?= e(ucfirst($p['status'])) ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('admin/product-form.php?id=' . (int) $p['id']) ?>"
                           class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form action="<?= url('admin/product-delete.php') ?>" method="post" class="d-inline"
                              onsubmit="return confirm('Hapus produk ini? Tindakan tidak dapat dibatalkan.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
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
