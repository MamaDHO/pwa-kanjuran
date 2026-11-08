<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$q   = trim((string) ($_GET['q'] ?? ''));
$kat = (string) ($_GET['kat'] ?? 'Semua');
if (!in_array($kat, kategori_list(), true)) {
    $kat = 'Semua';
}

function url_produk(string $q, string $kat): string
{
    $qs = [];
    if ($q !== '') {
        $qs['q'] = $q;
    }
    if ($kat !== 'Semua') {
        $qs['kat'] = $kat;
    }
    return 'produk.php' . ($qs ? '?' . http_build_query($qs) : '');
}

$kembali = url_produk($q, $kat);

$daftar = array_filter(semua_produk(), function (array $p) use ($q, $kat) {
    if ($kat !== 'Semua' && $p['kategori'] !== $kat) {
        return false;
    }
    return $q === '' || mb_stripos($p['nama'], $q) !== false;
});

$jumlah = cart_count();
$total  = cart_subtotal();

page_start('Produk', 'page-produk');
?>
<header class="topbar">
    <div class="brand">
        <span class="brand-icon"><?= icon('store', 32) ?></span>
        <div>
            <div class="brand-name"><?= e(cfg('nama_toko')) ?></div>
            <div class="brand-tag"><?= e(cfg('tagline')) ?></div>
        </div>
    </div>
    <a class="cart-link" href="keranjang.php" aria-label="Keranjang, <?= $jumlah ?> item">
        <?= icon('cart', 30) ?>
        <?php if ($jumlah > 0): ?><span class="badge"><?= $jumlah ?></span><?php endif; ?>
    </a>
</header>

<form class="search" method="get" action="produk.php" role="search">
    <?= icon('search', 22) ?>
    <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Cari produk..." autocomplete="off" aria-label="Cari produk">
    <?php if ($kat !== 'Semua'): ?><input type="hidden" name="kat" value="<?= e($kat) ?>"><?php endif; ?>
</form>

<nav class="chips" id="chips" aria-label="Kategori">
    <?php foreach (kategori_list() as $k): ?>
        <a class="chip<?= $k === $kat ? ' active' : '' ?>" data-nav href="<?= e(url_produk($q, $k)) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
</nav>

<section class="list" id="daftar-produk">
    <?php if (!$daftar): ?>
        <div class="empty">
            <p class="empty-title">Produk tidak ditemukan</p>
            <p class="muted">Coba kata kunci atau kategori lain.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($daftar as $p):
        $qty  = cart_qty($p['id']);
        $habis = (int) $p['stok'] < 1;
        ?>
        <article class="row" id="p-<?= e($p['id']) ?>">
            <div class="thumb"><img src="assets/img/<?= e($p['gambar']) ?>" alt="" width="48" height="48"></div>
            <div class="row-info">
                <h3 class="row-name"><?= e($p['nama']) ?></h3>
                <div class="price"><?= rupiah((int) $p['harga']) ?></div>
                <?php if ($habis): ?>
                    <div class="stock stock-out">Habis</div>
                <?php elseif ((int) $p['stok'] <= (int) cfg('batas_stok_tipis')): ?>
                    <div class="stock">Sisa <?= (int) $p['stok'] ?></div>
                <?php endif; ?>
            </div>
            <div class="row-action">
                <?php if ($habis): ?>
                    <span class="muted small">Stok habis</span>
                <?php elseif ($qty > 0): ?>
                    <?= qty_control($p, $qty, $kembali) ?>
                <?php else: ?>
                    <?= qty_form('tambah', $p['id'], $kembali, 'plus', 'Tambah ' . $p['nama'] . ' ke keranjang', false) ?>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php if ($jumlah > 0): ?>
    <a class="cartbar" href="keranjang.php">
        <span class="cartbar-ico"><?= icon('cart', 22) ?></span>
        <span class="cartbar-text">
            <small><?= $jumlah ?> item</small>
            <strong><?= rupiah($total) ?></strong>
        </span>
        <span class="cartbar-cta">Lihat Keranjang <?= icon('chevron', 18) ?></span>
    </a>
<?php endif; ?>
<?php page_end();
