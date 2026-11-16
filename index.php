<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Beranda';

// Ambil 8 produk terbaru yang aktif
$sql = "SELECT p.*, u.store_name, u.name AS seller_name
        FROM products p
        JOIN users u ON u.id = p.seller_id
        WHERE p.status = 'aktif'
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT 8";
$products = db_query($mysqli, $sql);

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero p-4 p-md-5 mb-4 shadow-sm">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="fw-bold mb-2">🌷 Welcome to Florist Mellysa! 💐 <?= e(APP_NAME) ?></h1>
            <p class="lead mb-3">
                Temukan buket cantik untuk setiap momen spesialmu. Dibuat dengan cinta, dikemas dengan penuh keindahan. 🎀💗
Thank you for shopping with us! ♡ 
            </p>
            <a href="<?= url('products.php') ?>" class="btn btn-light text-primary fw-semibold">
                <i class="bi bi-flower1"></i> Pesan Bunga Sekarang
            </a>
        </div>
    </div>
</section>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Produk Terbaru</h4>
    <a href="<?= url('products.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
</div>

<div class="row g-4">
    <?php if ($products->num_rows === 0): ?>
        <div class="col-12"><div class="alert alert-info">Belum ada produk yang tersedia.</div></div>
    <?php endif; ?>
    <?php while ($p = $products->fetch_assoc()): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card product-card shadow-sm border-0">
                <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>">
                    <img src="<?= e(product_image($p['image'])) ?>" class="card-img-top product-thumb" alt="<?= e($p['name']) ?>">
                </a>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title">
                        <a class="text-dark text-decoration-none"
                           href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"><?= e($p['name']) ?></a>
                    </h6>
                    <div class="price-tag mt-auto mb-2"><?= rupiah($p['price']) ?></div>
                    <div class="small text-muted mb-2">Stok: <?= (int) $p['stock'] ?></div>
                    <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"
                       class="btn btn-sm btn-primary w-100">Lihat Detail</a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
