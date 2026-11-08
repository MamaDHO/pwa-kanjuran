<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$order = order_sekarang();
if (!$order || $order['status'] === 'cancelled') {
    redirect('keranjang.php');
}
if ($order['status'] === 'paid') {
    redirect('berhasil.php');
}
if ($order['status'] === 'expired') {
    redirect('kedaluwarsa.php');
}

$sisa = max(0, (int) $order['kedaluwarsa'] - time());
$mm = str_pad((string) intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
$ss = str_pad((string) ($sisa % 60), 2, '0', STR_PAD_LEFT);

page_start('Pembayaran QRIS', 'page-bayar');
?>
<header class="appbar">
    <a class="back" href="keranjang.php" aria-label="Kembali ke keranjang"><?= icon('back', 26) ?></a>
    <h1>Pembayaran QRIS</h1>
</header>

<section class="pay-head">
    <div class="muted">Total Pembayaran</div>
    <div class="pay-total"><?= rupiah((int) $order['total']) ?></div>
    <div class="pill-warn"><?= icon('clock', 20) ?><span>Bayar dalam <b id="timer" data-sisa="<?= $sisa ?>"><?= $mm ?>:<?= $ss ?></b></span></div>
</section>

<section class="qris">
    <div class="qris-head"><span class="qris-logo">QRIS</span><span class="qris-merchant"><?= e(cfg('nama_toko')) ?></span></div>
    <div class="qris-card">
        <div class="qr-box"><?= QR::svg(qris_payload($order)) ?></div>
        <div class="nmid">NMID: <?= e(cfg('nmid')) ?></div>
        <p class="qris-scan">Scan dengan e-wallet atau mobile banking</p>
        <p class="qris-wallets">GoPay · OVO · DANA · ShopeePay · BCA · BRI</p>
    </div>
</section>

<div class="waiting" id="waiting"><?= icon('refresh', 22, 'spin') ?><span>Menunggu pembayaran...</span></div>

<form method="post" action="aksi.php" class="ajax">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="cek">
    <button type="submit" class="btn btn-outline btn-block">Cek Status Pembayaran</button>
</form>

<form method="post" action="aksi.php" class="ajax" data-confirm="Batalkan pesanan ini?">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="batal">
    <button type="submit" class="btn-text btn-danger">Batalkan pesanan</button>
</form>

<?php if (cfg('demo_mode')): ?>
    <form method="post" action="aksi.php" class="ajax demo-box">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simulasi">
        <p><b>Mode demo</b> — belum terhubung ke payment gateway. Gunakan tombol ini untuk mensimulasikan pelanggan yang sudah membayar.</p>
        <button type="submit" class="btn btn-soft btn-block">Simulasi: tandai sudah dibayar</button>
    </form>
<?php endif; ?>
<?php page_end();
