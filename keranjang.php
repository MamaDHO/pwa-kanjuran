<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$items    = cart_items();
$jumlah   = cart_count();
$subtotal = cart_subtotal();
$biaya    = $items ? (int) cfg('biaya_layanan') : 0;
$total    = cart_total();

page_start('Keranjang', 'page-keranjang');
?>
<header class="appbar">
    <a class="back" href="produk.php" aria-label="Kembali ke produk"><?= icon('back', 26) ?></a>
    <h1>Keranjang</h1>
</header>

<?php if (!$items): ?>
    <section class="empty empty-big">
        <div class="empty-ico"><?= icon('cart', 44) ?></div>
        <p class="empty-title">Keranjang masih kosong</p>
        <p class="muted">Pilih barang dulu, lalu bayar lewat QRIS.</p>
        <a class="btn btn-primary" href="produk.php">Lihat Produk</a>
    </section>
<?php else: ?>
    <section class="cards">
        <?php foreach ($items as $it): $p = $it['produk']; ?>
            <article class="card item">
                <div class="thumb thumb-lg"><img src="assets/img/<?= e($p['gambar']) ?>" alt="" width="48" height="48"></div>
                <div class="item-body">
                    <h3 class="row-name"><?= e($p['nama']) ?></h3>
                    <div class="price"><?= rupiah((int) $p['harga']) ?></div>
                    <div class="item-foot">
                        <?= qty_control($p, $it['qty'], 'keranjang.php') ?>
                        <strong class="item-sub"><?= rupiah($it['subtotal']) ?></strong>
                    </div>
                </div>
                <form method="post" action="aksi.php" class="ajax trash">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                    <input type="hidden" name="kembali" value="keranjang.php">
                    <button type="submit" aria-label="Hapus <?= e($p['nama']) ?>"><?= icon('trash', 22) ?></button>
                </form>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card summary">
        <h2>Ringkasan Pesanan</h2>
        <div class="line"><span class="muted">Subtotal (<?= $jumlah ?> item)</span><strong><?= rupiah($subtotal) ?></strong></div>
        <div class="line"><span class="muted">Biaya layanan</span><strong><?= rupiah($biaya) ?></strong></div>
        <hr class="dash">
        <div class="line total"><strong>Total</strong><strong class="total-n"><?= rupiah($total) ?></strong></div>
    </section>

    <section class="note note-blue">
        <?= icon('qr', 36) ?>
        <div>
            <strong>Pembayaran hanya lewat QRIS</strong>
            <p>Scan dengan e-wallet atau m-banking.</p>
        </div>
    </section>

    <form method="post" action="aksi.php" class="ajax bottom-action">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="checkout">
        <button type="submit" class="btn btn-primary btn-block"><?= icon('qr', 22) ?> Bayar dengan QRIS</button>
    </form>
<?php endif; ?>
<?php page_end();
