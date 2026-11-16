<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Daftar Produk';

$search   = trim($_GET['q'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 8;
$offset   = ($page - 1) * $perPage;

// Bangun kondisi WHERE secara dinamis (aman dengan prepared statement)
$where  = ["p.status = 'aktif'"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
$whereSql = implode(' AND ', $where);

// Hitung total data (untuk pagination)
$countSql  = "SELECT COUNT(*) AS total FROM products p WHERE $whereSql";
$countStmt = db_prepare($mysqli, $countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows  = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

// Ambil data produk
$sql       = "SELECT p.*, u.store_name
              FROM products p
              JOIN users u ON u.id = p.seller_id
              WHERE $whereSql
              ORDER BY p.created_at DESC, p.id DESC
              LIMIT ? OFFSET ?";
$stmt      = db_prepare($mysqli, $sql);
$bindTypes = $types . 'ii';
$bindVals  = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($bindTypes, ...$bindVals);
$stmt->execute();
$products = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Beranda</a></li>
        <li class="breadcrumb-item active">Produk</li>
    </ol>
</nav>

<h4 class="mb-3">Daftar Produk</h4>

<form class="row g-2 mb-4" method="get" action="<?= url('products.php') ?>">
    <div class="col-md-10">
        <input type="text" name="q" class="form-control" placeholder="Cari produk..."
               value="<?= e($search) ?>">
    </div>
    <div class="col-md-2 d-grid">
        <button class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
    </div>
</form>

<p class="text-muted small">Menampilkan <?= $totalRows ?> produk.</p>

<div class="row g-4">
    <?php if ($products->num_rows === 0): ?>
        <div class="col-12"><div class="alert alert-warning">Produk tidak ditemukan.</div></div>
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
                    <div class="d-grid gap-2">
                        <a href="<?= url('product.php?slug=' . urlencode($p['slug'])) ?>"
                           class="btn btn-sm btn-outline-primary">Detail</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php
            $q = http_build_query(array_filter(['q' => $search]));
            for ($i = 1; $i <= $totalPages; $i++):
                $link = url('products.php') . '?' . ($q ? $q . '&' : '') . 'page=' . $i;
            ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($link) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
