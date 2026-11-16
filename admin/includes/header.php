<?php
/** Header area penjual. Wajib dipanggil setelah init + require_seller(). */
$pageTitle = $pageTitle ?? 'Dashboard';
$__user = auth_user();
$__current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &middot; Dashboard <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= url('admin/index.php') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard Penjual
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small d-none d-md-inline">
                <i class="bi bi-flower1"></i> <?= e($__user['store_name'] ?: $__user['name']) ?>
            </span>
            <a href="<?= url('index.php') ?>" class="btn btn-sm btn-outline-light">
                <i class="bi bi-shop-window"></i> Lihat Toko
            </a>
            <a href="<?= url('logout.php') ?>" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar-pink border-end min-vh-100 py-3 d-none d-md-block">
            <ul class="nav flex-column nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?= $__current === 'index.php' ? 'active' : 'text-dark' ?>"
                       href="<?= url('admin/index.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($__current, ['products.php', 'product-form.php']) ? 'active' : 'text-dark' ?>"
                       href="<?= url('admin/products.php') ?>"><i class="bi bi-box-seam"></i> Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($__current, ['orders.php', 'order-detail.php']) ? 'active' : 'text-dark' ?>"
                       href="<?= url('admin/orders.php') ?>"><i class="bi bi-receipt"></i> Pesanan</a>
                </li>
            </ul>
        </aside>

        <main class="col-md-9 col-lg-10 py-4">
            <?php foreach (take_flashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <div class="d-md-none mb-3">
                <div class="btn-group w-100">
                    <a href="<?= url('admin/index.php') ?>" class="btn btn-outline-primary btn-sm">Dashboard</a>
                    <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-primary btn-sm">Produk</a>
                    <a href="<?= url('admin/orders.php') ?>" class="btn btn-outline-primary btn-sm">Pesanan</a>
                </div>
            </div>
