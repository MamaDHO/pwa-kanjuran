<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$order = order_sekarang();
if (!$order) {
    redirect('keranjang.php');
}
if ($order['status'] === 'paid') {
    redirect('berhasil.php');
}
if ($order['status'] === 'pending') {
    redirect('bayar.php');
}
if ($order['status'] !== 'expired') {
    redirect('keranjang.php');
}

page_start('Waktu Pembayaran Habis', 'page-hasil');
?>
<section class="result">
    <div class="halo halo-red"><div class="halo-in"><?= icon('x', 44) ?></div></div>
    <h1>Waktu Pembayaran Habis</h1>
    <p class="muted center">QRIS sudah tidak berlaku.<br>Belum ada pembayaran yang diterima.</p>
</section>

<section class="order-box">
    <div>
        <div class="muted small">Pesanan Anda</div>
        <strong class="order-sum"><?= (int) $order['jumlah'] ?> item · <?= rupiah((int) $order['total']) ?></strong>
    </div>
    <span class="tag tag-red">Kedaluwarsa</span>
</section>

<form method="post" action="aksi.php" class="ajax">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="qris_baru">
    <button type="submit" class="btn btn-primary btn-block"><?= icon('refresh', 22) ?> Buat QRIS Baru</button>
</form>

<a class="btn btn-soft btn-block" href="keranjang.php">Kembali ke Keranjang</a>
<?php page_end();
