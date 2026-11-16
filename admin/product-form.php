<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_seller();

$sellerId = (int) auth_user()['id'];
$id       = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit   = $id > 0;
$pageTitle = $isEdit ? 'Edit Produk' : 'Tambah Produk';
$errors = [];

// Data default form
$product = [
    'id' => 0, 'name' => '', 'slug' => '', 'description' => '',
    'price' => '', 'stock' => '', 'image' => '', 'status' => 'aktif',
];

// Mode edit: ambil data produk (pastikan milik penjual ini)
if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $mysqli->prepare('SELECT * FROM products WHERE id = ? AND seller_id = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $sellerId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$product) {
        set_flash('danger', 'Produk tidak ditemukan.');
        redirect('admin/products.php');
    }
}

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $product['name']        = trim($_POST['name'] ?? '');
    $product['slug']        = trim($_POST['slug'] ?? '');
    $product['description'] = trim($_POST['description'] ?? '');
    $product['price']       = $_POST['price'] ?? '';
    $product['stock']       = $_POST['stock'] ?? '';
    $product['image']       = trim($_POST['image'] ?? '');
    $product['status']      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
    $product['id']          = $id;

    if ($product['name'] === '')                       $errors[] = 'Nama produk wajib diisi.';
    if (!is_numeric($product['price']) || $product['price'] < 0) $errors[] = 'Harga harus berupa angka >= 0.';
    if (!is_numeric($product['stock']) || $product['stock'] < 0) $errors[] = 'Stok harus berupa angka >= 0.';

    if ($product['slug'] === '') {
        $product['slug'] = slugify($product['name']);
    }

    // === Unggah gambar (opsional) ===
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Format gambar harus JPG, PNG, GIF, atau WEBP.';
        } elseif ($_FILES['image_file']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 2 MB.';
        } else {
            $newName   = 'produk_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newName)) {
                $product['image'] = $newName;   // simpan nama file
            } else {
                $errors[] = 'Gagal mengunggah gambar.';
            }
        }
    }

    // Cek slug unik
    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id FROM products WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $product['slug'], $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Slug sudah dipakai produk lain, gunakan slug berbeda.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $price = (float) $product['price'];
        $stock = (int) $product['stock'];
        $status = $product['status'];

        if ($isEdit) {
            $stmt = $mysqli->prepare(
                "UPDATE products SET name=?, slug=?, description=?, price=?, stock=?, image=?, status=?
                 WHERE id=? AND seller_id=?"
            );
            $stmt->bind_param('sssdissii',
                $product['name'], $product['slug'], $product['description'],
                $price, $stock, $product['image'], $status, $id, $sellerId);
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO products (seller_id, name, slug, description, price, stock, image, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('isssdiss',
                $sellerId, $product['name'], $product['slug'], $product['description'],
                $price, $stock, $product['image'], $status);
        }

        if ($stmt->execute()) {
            $stmt->close();
            set_flash('success', $isEdit ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.');
            redirect('admin/products.php');
        }
        $errors[] = 'Gagal menyimpan produk: ' . $mysqli->error;
        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('admin/products.php') ?>">Produk</a></li>
        <li class="breadcrumb-item active"><?= $isEdit ? 'Edit' : 'Tambah' ?></li>
    </ol>
</nav>

<h4 class="mb-3"><?= e($pageTitle) ?></h4>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= url('admin/product-form.php' . ($isEdit ? '?id=' . $id : '')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($product['name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug URL <small class="text-muted">(opsional)</small></label>
                    <input type="text" name="slug" class="form-control" value="<?= e($product['slug']) ?>"
                           placeholder="otomatis dari nama">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" min="0" step="100"
                           value="<?= e($product['price']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Stok <span class="text-danger">*</span></label>
                    <input type="number" name="stock" class="form-control" min="0"
                           value="<?= e($product['stock']) ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="4"><?= e($product['description']) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">URL Gambar <small class="text-muted">(link gambar, opsional)</small></label>
                    <input type="text" name="image" class="form-control" value="<?= e($product['image']) ?>"
                           placeholder="https://... atau kosongkan">
                    <div class="form-text">Boleh dikosongkan bila ingin mengunggah file gambar.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unggah Gambar <small class="text-muted">(maks 2 MB)</small></label>
                    <input type="file" name="image_file" class="form-control" accept="image/*">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= $product['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $product['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <?php if (!empty($product['image'])): ?>
                    <div class="col-12">
                        <label class="form-label d-block">Pratinjau Gambar</label>
                        <img src="<?= e(product_image($product['image'])) ?>" alt="preview"
                             class="rounded border" style="max-height:150px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
