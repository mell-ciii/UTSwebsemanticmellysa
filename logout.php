<?php
require_once __DIR__ . '/includes/init.php';

// Hapus data autentikasi dan perbarui ID session (mencegah session fixation)
unset($_SESSION['user']);
session_regenerate_id(true);

set_flash('success', 'Anda telah logout.');
redirect('index.php');
