<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Daftar Akun';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['password_confirm'] ?? '';
    $role      = ($_POST['role'] ?? 'pembeli') === 'penjual' ? 'penjual' : 'pembeli';
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $storeName = trim($_POST['store_name'] ?? '');

    if ($name === '')                          $errors[] = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 6)                 $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirm)                $errors[] = 'Konfirmasi password tidak cocok.';
    if ($role === 'penjual' && $storeName === '') $errors[] = 'Nama toko wajib diisi untuk penjual.';

    if (empty($errors)) {
        // Cek email sudah terpakai
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'Email sudah terdaftar.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare(
            'INSERT INTO users (name, email, password, role, phone, address, store_name) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $storeVal = $role === 'penjual' ? $storeName : null;
        $stmt->bind_param('sssssss', $name, $email, $hash, $role, $phone, $address, $storeVal);
        if ($stmt->execute()) {
            $stmt->close();
            set_flash('success', 'Registrasi berhasil. Silakan login.');
            redirect('login.php');
        }
        $errors[] = 'Registrasi gagal: ' . $mysqli->error;
        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center"><i class="bi bi-person-plus"></i> Daftar Akun</h4>

                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul></div>
                <?php endif; ?>

                <form method="post" action="<?= url('register.php') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Daftar Sebagai</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role" value="pembeli" id="rolePembeli"
                                       <?= (($_POST['role'] ?? 'pembeli') === 'pembeli') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rolePembeli">Pembeli</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role" value="penjual" id="rolePenjual"
                                       <?= (($_POST['role'] ?? '') === 'penjual') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rolePenjual">Penjual</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Toko <small class="text-muted">(khusus penjual)</small></label>
                        <input type="text" name="store_name" class="form-control" value="<?= e($_POST['store_name'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alamat</label>
                            <input type="text" name="address" class="form-control" value="<?= e($_POST['address'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg">Daftar</button>
                    </div>
                </form>

                <p class="text-center mt-3 mb-0">
                    Sudah punya akun? <a href="<?= url('login.php') ?>">Login di sini</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
