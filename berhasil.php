<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$order = order_sekarang();
if (!$order) {
    redirect('index.php');
}
if ($order['status'] === 'pending') {
    redirect('bayar.php');
}
if ($order['status'] === 'expired') {
    redirect('kedaluwarsa.php');
}
if ($order['status'] !== 'paid') {
    redirect('keranjang.php');
}

// Pembayaran selesai: keranjang dikosongkan.
cart_set([]);

page_start('Pembayaran Berhasil', 'page-hasil');
?>
<section class="result">
    <div class="halo halo-green"><div class="halo-in"><?= icon('check', 44) ?></div></div>
    <h1>Pembayaran Berhasil</h1>
    <p class="muted center">Terima kasih sudah berbelanja di <?= e(cfg('nama_toko')) ?>.</p>
</section>

<section class="card receipt">
    <div class="receipt-top">
        <div class="receipt-total"><?= rupiah((int) $order['total']) ?></div>
        <div class="muted small">Dibayar via QRIS</div>
    </div>
    <hr class="dash">
    <div class="line"><span class="muted">No. Transaksi</span><strong><?= e($order['trx']) ?></strong></div>
    <div class="line"><span class="muted">Waktu</span><strong><?= e(tanggal_id((int) ($order['dibayar'] ?? $order['dibuat']))) ?></strong></div>
    <div class="line"><span class="muted">Metode</span><strong>QRIS</strong></div>
    <hr class="dash">
    <?php foreach ($order['items'] as $it): ?>
        <div class="line"><span><?= e($it['nama']) ?> ×<?= (int) $it['qty'] ?></span><strong><?= rupiah((int) $it['harga'] * (int) $it['qty']) ?></strong></div>
    <?php endforeach; ?>
    <?php if ((int) $order['biaya'] > 0): ?>
        <div class="line"><span class="muted">Biaya layanan</span><strong><?= rupiah((int) $order['biaya']) ?></strong></div>
    <?php endif; ?>
    <hr class="dash">
    <div class="line"><strong>Total</strong><strong><?= rupiah((int) $order['total']) ?></strong></div>
</section>

<p class="muted center small hint">Silakan ambil barang Anda. Screenshot layar ini jika perlu menyimpan bukti pembayaran.</p>

<form method="post" action="aksi.php" class="ajax bottom-action">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="selesai">
    <button type="submit" class="btn btn-primary btn-block">Selesai</button>
</form>
<?php page_end();
