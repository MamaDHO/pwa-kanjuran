<?php
/**
 * Kerangka halaman. Pemakaian:
 *   page_start('Judul', 'kelas-halaman');  ...isi...  page_end();
 */
function page_start(string $title, string $bodyClass = '', array $opts = []): void
{
    $nama = cfg('nama_toko');
    $flash = flash();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1677F8">
    <title><?= e($title) ?> · <?= e($nama) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">
<div class="app">
    <main class="screen" id="screen">
        <?php if ($flash): ?>
            <div class="toast toast-<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div>
        <?php endif; ?>
<?php
}

function page_end(array $opts = []): void
{
    ?>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php
}

/** Tombol kecil +/- (form POST ke aksi.php). */
function qty_form(string $aksi, string $id, string $kembali, string $icon, string $label, bool $filled, bool $disabled = false): string
{
    $cls = 'qbtn ' . ($filled ? 'qbtn-fill' : 'qbtn-line');
    return '<form method="post" action="aksi.php" class="ajax inline">'
        . csrf_field()
        . '<input type="hidden" name="aksi" value="' . e($aksi) . '">'
        . '<input type="hidden" name="id" value="' . e($id) . '">'
        . '<input type="hidden" name="kembali" value="' . e($kembali) . '">'
        . '<button type="submit" class="' . $cls . '" aria-label="' . e($label) . '"' . ($disabled ? ' disabled' : '') . '>'
        . icon($icon, 16) . '</button></form>';
}

/** Kontrol jumlah: [-] n [+]. */
function qty_control(array $p, int $qty, string $kembali): string
{
    $maks = $qty >= (int) $p['stok'];
    return '<div class="qty">'
        . qty_form('kurang', $p['id'], $kembali, 'minus', 'Kurangi ' . $p['nama'], false)
        . '<span class="qty-n" aria-live="polite">' . $qty . '</span>'
        . qty_form('tambah', $p['id'], $kembali, 'plus', 'Tambah ' . $p['nama'], true, $maks)
        . '</div>';
}
