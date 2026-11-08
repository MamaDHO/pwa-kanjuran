<?php
declare(strict_types=1);

require_once __DIR__ . '/qr.php';
require_once __DIR__ . '/icons.php';

const BASE_DIR = __DIR__ . '/..';

function cfg(?string $key = null)
{
    static $c = null;
    if ($c === null) {
        $c = require __DIR__ . '/config.php';
    }
    return $key === null ? $c : ($c[$key] ?? null);
}

date_default_timezone_set((string) cfg('timezone'));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/* ------------------------------------------------------------------ */
/* Helper umum                                                         */
/* ------------------------------------------------------------------ */

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function tanggal_id(int $ts): string
{
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts) - 1] . ' ' . date('Y, H:i', $ts);
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function redirect(string $url): void
{
    if (is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['redirect' => $url]);
    } else {
        header('Location: ' . $url);
    }
    exit;
}

function flash(?string $msg = null, string $type = 'info')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/* ------------------------------------------------------------------ */
/* Penyimpanan JSON (dengan file lock)                                 */
/* ------------------------------------------------------------------ */

function data_path(string $file): string
{
    $dir = (string) cfg('data_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir . '/' . $file;
}

/**
 * Baca-ubah-tulis JSON secara atomik.
 * $fn menerima array lama dan mengembalikan array baru.
 */
function json_update(string $file, callable $fn, array $default = []): array
{
    $path = data_path($file);
    $fh = fopen($path, 'c+');
    if (!$fh) {
        throw new RuntimeException('Tidak bisa membuka ' . $file . ' (cek izin folder data).');
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = $raw !== '' && $raw !== false ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = $default;
    }
    $new = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $new;
}

function json_read(string $file, array $default = []): array
{
    $path = data_path($file);
    if (!is_file($path)) {
        return $default;
    }
    $fh = fopen($path, 'r');
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $data = json_decode((string) $raw, true);
    return is_array($data) ? $data : $default;
}

/* ------------------------------------------------------------------ */
/* Produk                                                              */
/* ------------------------------------------------------------------ */

function produk_default(): array
{
    return [
        'es-teh'       => ['id' => 'es-teh',       'nama' => 'Es Teh Manis',    'harga' => 2000, 'kategori' => 'Minuman', 'stok' => 5,  'gambar' => 'es-teh.png'],
        'mie-instan'   => ['id' => 'mie-instan',   'nama' => 'Mie Instan',      'harga' => 4000, 'kategori' => 'Makanan', 'stok' => 12, 'gambar' => 'mie-instan.png'],
        'keripik'      => ['id' => 'keripik',      'nama' => 'Keripik Singkong', 'harga' => 4000, 'kategori' => 'Camilan', 'stok' => 2,  'gambar' => 'keripik.png'],
        'air-mineral'  => ['id' => 'air-mineral',  'nama' => 'Air Mineral',     'harga' => 3000, 'kategori' => 'Minuman', 'stok' => 20, 'gambar' => 'air-mineral.png'],
        'roti-cokelat' => ['id' => 'roti-cokelat', 'nama' => 'Roti Cokelat',    'harga' => 3000, 'kategori' => 'Camilan', 'stok' => 3,  'gambar' => 'roti-cokelat.png'],
    ];
}

function semua_produk(): array
{
    $p = json_read('products.json');
    if (!$p) {
        $p = produk_default();
        json_update('products.json', fn() => $p);
    }
    return $p;
}

function kategori_list(): array
{
    return ['Semua', 'Makanan', 'Minuman', 'Camilan'];
}

/* ------------------------------------------------------------------ */
/* Keranjang (disimpan di session: [id_produk => qty])                  */
/* ------------------------------------------------------------------ */

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_set(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cart_qty(string $id): int
{
    return (int) (cart_raw()[$id] ?? 0);
}

/** Item keranjang yang sudah divalidasi terhadap stok terkini. */
function cart_items(): array
{
    $produk = semua_produk();
    $items = [];
    $clean = [];
    foreach (cart_raw() as $id => $qty) {
        if (!isset($produk[$id])) {
            continue;
        }
        $qty = min((int) $qty, (int) $produk[$id]['stok']);
        if ($qty < 1) {
            continue;
        }
        $clean[$id] = $qty;
        $items[] = [
            'produk'   => $produk[$id],
            'qty'      => $qty,
            'subtotal' => $qty * (int) $produk[$id]['harga'],
        ];
    }
    if ($clean !== cart_raw()) {
        cart_set($clean);
    }
    return $items;
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'qty'));
}

function cart_subtotal(): int
{
    return array_sum(array_column(cart_items(), 'subtotal'));
}

function cart_total(): int
{
    return cart_subtotal() + (cart_items() ? (int) cfg('biaya_layanan') : 0);
}

/* ------------------------------------------------------------------ */
/* Pesanan (orders.json)                                               */
/* ------------------------------------------------------------------ */

function format_trx(int $n): string
{
    return '#TRX' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
}

/** Buat pesanan baru dari array item [{id,nama,harga,qty}]. */
function order_buat(array $items): array
{
    $order = null;
    json_update('orders.json', function (array $db) use ($items, &$order) {
        $db['seq'] = (int) ($db['seq'] ?? 0) + 1;
        $total = 0;
        $jumlah = 0;
        foreach ($items as $it) {
            $total += $it['harga'] * $it['qty'];
            $jumlah += $it['qty'];
        }
        $total += (int) cfg('biaya_layanan');
        $now = time();
        $order = [
            'id'        => $db['seq'],
            'trx'       => format_trx($db['seq']),
            'items'     => array_values($items),
            'jumlah'    => $jumlah,
            'biaya'     => (int) cfg('biaya_layanan'),
            'total'     => $total,
            'status'    => 'pending',
            'dibuat'    => $now,
            'kedaluwarsa' => $now + (int) cfg('qris_durasi'),
            'dibayar'   => null,
        ];
        $db['orders'][(string) $order['id']] = $order;
        return $db;
    }, ['seq' => 0, 'orders' => []]);
    return $order;
}

function order_ambil(int $id): ?array
{
    $db = json_read('orders.json', ['seq' => 0, 'orders' => []]);
    $o = $db['orders'][(string) $id] ?? null;
    if ($o && $o['status'] === 'pending' && time() >= (int) $o['kedaluwarsa']) {
        $o = order_set_status($id, 'expired') ?? $o;
    }
    return $o;
}

/**
 * Ubah status pesanan. Perubahan hanya sah dari 'pending'
 * (pesanan yang sudah paid/expired/cancelled tidak berubah lagi).
 * Saat 'paid', stok produk dikurangi.
 */
function order_set_status(int $id, string $status): ?array
{
    $hasil = null;
    json_update('orders.json', function (array $db) use ($id, $status, &$hasil) {
        $k = (string) $id;
        if (!isset($db['orders'][$k])) {
            return $db;
        }
        $o = &$db['orders'][$k];
        if ($o['status'] === 'pending') {
            if ($status === 'paid') {
                $o['status'] = 'paid';
                $o['dibayar'] = time();
                kurangi_stok($o['items']);
            } elseif (in_array($status, ['expired', 'cancelled'], true)) {
                $o['status'] = $status;
            }
        }
        $hasil = $o;
        unset($o);
        return $db;
    }, ['seq' => 0, 'orders' => []]);
    return $hasil;
}

function kurangi_stok(array $items): void
{
    json_update('products.json', function (array $p) use ($items) {
        foreach ($items as $it) {
            if (isset($p[$it['id']])) {
                $p[$it['id']]['stok'] = max(0, (int) $p[$it['id']]['stok'] - (int) $it['qty']);
            }
        }
        return $p;
    }, produk_default());
}

function order_sekarang(): ?array
{
    $id = (int) ($_SESSION['order_id'] ?? 0);
    return $id > 0 ? order_ambil($id) : null;
}

/** Ubah isi keranjang menjadi snapshot item untuk pesanan. */
function cart_snapshot(): array
{
    $out = [];
    foreach (cart_items() as $it) {
        $out[] = [
            'id'    => $it['produk']['id'],
            'nama'  => $it['produk']['nama'],
            'harga' => (int) $it['produk']['harga'],
            'qty'   => $it['qty'],
        ];
    }
    return $out;
}

/** String payload QR. Ganti dengan qr_string dari payment gateway bila sudah terhubung. */
function qris_payload(array $order): string
{
    return implode('|', ['QRIS-DEMO', cfg('nmid'), ltrim($order['trx'], '#'), $order['total'], $order['kedaluwarsa']]);
}
