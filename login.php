<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(is_seller() ? 'admin/index.php' : 'index.php');
}

$pageTitle = 'Login';
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email dan password wajib diisi.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role, phone, address, store_name FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);           // jangan simpan hash di session
            $_SESSION['user'] = $user;
            set_flash('success', 'Selamat datang, ' . $user['name'] . '!');
            redirect($user['role'] === 'penjual' ? 'admin/index.php' : 'index.php');
        }
        $errors[] = 'Email atau password salah.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center"><i class="bi bi-box-arrow-in-right"></i> Login</h4>

                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul></div>
                <?php endif; ?>

                <form method="post" action="<?= url('login.php') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg">Masuk</button>
                    </div>
                </form>

                <p class="text-center mt-3 mb-0">
                    Belum punya akun? <a href="<?= url('register.php') ?>">Daftar di sini</a>
                </p>

                
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
