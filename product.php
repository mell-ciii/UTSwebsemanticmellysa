<?php
require_once __DIR__ . '/includes/init.php';

$slug = trim($_GET['slug'] ?? '');

$stmt = $mysqli->prepare(
    "SELECT p.*, u.store_name, u.name AS seller_name, u.phone AS seller_phone
     FROM products p
     JOIN users u ON u.id = p.seller_id
     WHERE p.slug = ? AND p.status = 'aktif' LIMIT 1"
);
$stmt->bind_param('s', $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    set_flash('danger', 'Produk tidak ditemukan.');
    redirect('products.php');
}

$pageTitle = $product['name'];

// Produk lainnya
$rstmt = $mysqli->prepare(
    "SELECT p.* FROM products p
     WHERE p.id <> ? AND p.status = 'aktif'
     ORDER BY p.created_at DESC, p.id DESC LIMIT 4"
);
$rstmt->bind_param('i', $product['id']);
$rstmt->execute();
$related = $rstmt->get_result();
$rstmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Beranda</a></li>
        <li class="breadcrumb-item"><a href="<?= url('products.php') ?>">Produk</a></li>
        <li class="breadcrumb-item active"><?= e($product['name']) ?></li>
    </ol>
</nav>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-5">
                <img src="<?= e(product_image($product['image'])) ?>" class="img-fluid rounded product-detail-img"
                     alt="<?= e($product['name']) ?>">
            </div>
            <div class="col-md-7">
                <h3 class="mt-2"><?= e($product['name']) ?></h3>
                <div class="price-tag fs-3 mb-3"><?= rupiah($product['price']) ?></div>

                <ul class="list-unstyled text-muted small mb-3">
                    <li><i class="bi bi-shop"></i> Toko: <strong><?= e($product['store_name'] ?: $product['seller_name']) ?></strong></li>
                    <li><i class="bi bi-box"></i> Stok tersedia: <strong><?= (int) $product['stock'] ?></strong></li>
                    <li><i class="bi bi-telephone"></i> Kontak penjual: <?= e($product['seller_phone'] ?: '-') ?></li>
                </ul>

                <p><?= nl2br(e($product['description'])) ?></p>

                <hr>

                <?php if ($product['stock'] > 0): ?>
                    <form action="<?= url('cart-action.php') ?>" method="post" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <div class="col-auto">
                            <label class="form-label small mb-1">Jumlah</label>
                            <input type="number" name="quantity" class="form-control qty-input"
                                   value="1" min="1" max="<?= (int) $product['stock'] ?>">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">Stok produk habis.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($related && $related->num_rows > 0): ?>
    <h5 class="mb-3">Produk Lainnya</h5>
    <div class="row g-4">
        <?php while ($r = $related->fetch_assoc()): ?>
            <div class="col-6 col-md-3">
                <div class="card product-card shadow-sm border-0">
                    <a href="<?= url('product.php?slug=' . urlencode($r['slug'])) ?>">
                        <img src="<?= e(product_image($r['image'])) ?>" class="card-img-top product-thumb" alt="<?= e($r['name']) ?>">
                    </a>
                    <div class="card-body">
                        <h6 class="card-title small">
                            <a class="text-dark text-decoration-none"
                               href="<?= url('product.php?slug=' . urlencode($r['slug'])) ?>"><?= e($r['name']) ?></a>
                        </h6>
                        <div class="price-tag"><?= rupiah($r['price']) ?></div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
