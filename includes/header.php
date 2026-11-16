<?php
/** Header publik (navbar + flash message). Variabel opsional: $pageTitle */
$pageTitle = $pageTitle ?? 'Beranda';
$__user = auth_user();
$__cartCount = is_logged_in() ? cart_count($mysqli, $__user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &middot; <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>">
            <i class="bi bi-flower1"></i> <?= e(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('products.php') ?>">Produk</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= url('cart.php') ?>">
                        <i class="bi bi-cart3 fs-5"></i> Keranjang
                        <?php if ($__cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= (int) $__cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                    <?php if (is_seller()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('admin/index.php') ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= url('orders.php') ?>">
                            <i class="bi bi-receipt"></i> Pesanan Saya</a></li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($__user['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">
                                Login sebagai <?= e(ucfirst($__user['role'])) ?>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Login</a></li>
                    <li class="nav-item">
                        <a class="btn btn-light btn-sm ms-lg-2 mt-1 mt-lg-0 text-primary fw-semibold"
                           href="<?= url('register.php') ?>">Daftar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4">
    <div class="container">
        <?php foreach (take_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
