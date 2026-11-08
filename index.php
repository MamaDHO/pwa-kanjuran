<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

page_start('Selamat Datang', 'page-welcome');
?>
<section class="welcome">
    <div class="welcome-logo"><?= icon('store', 80) ?></div>
    <h1 class="welcome-title"><?= e(cfg('nama_toko')) ?></h1>
    <p class="welcome-sub">Selamat Datang!</p>

    <h2 class="welcome-slogan">Ambil, Bayar, Jujur</h2>
    <p class="welcome-desc">Pilih barang, bayar lewat QRIS, selesai.<br>Tanpa akun dan tanpa login.</p>

    <div class="pill-info"><?= icon('qr', 20) ?><span>Terhubung lewat scan QR</span></div>

    <a class="btn btn-primary btn-block" href="produk.php">Mulai Belanja</a>
</section>

<svg class="wave" viewBox="0 0 360 130" preserveAspectRatio="none" aria-hidden="true">
    <path d="M0 70C60 30 120 10 190 22C250 32 300 52 360 8V130H0Z" fill="#A9D2FF"/>
    <path d="M0 98C70 70 150 60 220 72C280 82 320 76 360 58V130H0Z" fill="#6FB1FA"/>
</svg>
<?php page_end();
