<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Keranjang Belanja';
require_login();

$user   = auth_user();
$userId = (int) $user['id'];

// Ambil isi keranjang
$stmt = $mysqli->prepare(
    "SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.slug, p.price, p.stock, p.image
     FROM carts c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$items = $stmt->get_result();

$rows  = [];
$total = 0;
while ($row = $items->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $rows[] = $row;
}
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-cart3"></i> Keranjang Belanja</h4>

<?php if (empty($rows)): ?>
    <div class="alert alert-info">
        Keranjang Anda masih kosong. <a href="<?= url('products.php') ?>">Mulai belanja</a>.
    </div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-middle mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center" style="width:160px;">Jumlah</th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e(product_image($r['image'])) ?>"
                                             alt="<?= e($r['name']) ?>" width="60" height="60"
                                             class="rounded object-fit-cover">
                                        <div>
                                            <a class="text-dark text-decoration-none fw-semibold"
                                               href="<?= url('product.php?slug=' . urlencode($r['slug'])) ?>">
                                                <?= e($r['name']) ?>
                                            </a>
                                            <div class="small text-muted">Stok: <?= (int) $r['stock'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?= rupiah($r['price']) ?></td>
                                <td>
                                    <form action="<?= url('cart-action.php') ?>" method="post" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?= (int) $r['cart_id'] ?>">
                                        <input type="number" name="quantity" class="form-control form-control-sm"
                                               value="<?= (int) $r['quantity'] ?>" min="1" max="<?= (int) $r['stock'] ?>">
                                        <button class="btn btn-sm btn-outline-secondary" title="Perbarui">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end fw-semibold"><?= rupiah($r['subtotal']) ?></td>
                                <td class="text-end">
                                    <form action="<?= url('cart-action.php') ?>" method="post"
                                          onsubmit="return confirm('Hapus produk ini dari keranjang?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?= (int) $r['cart_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <form action="<?= url('cart-action.php') ?>" method="post" class="mt-2"
                  onsubmit="return confirm('Kosongkan seluruh keranjang?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Kosongkan Keranjang</button>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Ringkasan Belanja</h5>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Item</span>
                        <span><?= (int) array_sum(array_column($rows, 'quantity')) ?> barang</span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>Total</span>
                        <span class="price-tag"><?= rupiah($total) ?></span>
                    </div>
                    <div class="d-grid mt-3">
                        <a href="<?= url('checkout.php') ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-credit-card"></i> Lanjut ke Checkout
                        </a>
                    </div>
                    <a href="<?= url('products.php') ?>" class="btn btn-link w-100 mt-2">Lanjut Belanja</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
