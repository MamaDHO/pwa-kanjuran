<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

if (!csrf_valid()) {
    flash('Sesi sudah habis, silakan coba lagi.', 'error');
    redirect('produk.php');
}

/** Hanya izinkan redirect balik ke halaman internal yang dikenal. */
function aman_kembali(string $url, string $default): string
{
    return preg_match('/^(produk|keranjang)\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/', $url) ? $url : $default;
}

$aksi    = (string) ($_POST['aksi'] ?? '');
$id      = (string) ($_POST['id'] ?? '');
$kembali = aman_kembali((string) ($_POST['kembali'] ?? ''), 'produk.php');

switch ($aksi) {
    case 'tambah':
        $produk = semua_produk();
        if (!isset($produk[$id])) {
            flash('Produk tidak ditemukan.', 'error');
            break;
        }
        $cart = cart_raw();
        $qty = (int) ($cart[$id] ?? 0) + 1;
        if ($qty > (int) $produk[$id]['stok']) {
            flash('Stok ' . $produk[$id]['nama'] . ' hanya ' . (int) $produk[$id]['stok'] . '.', 'error');
            break;
        }
        $cart[$id] = $qty;
        cart_set($cart);
        break;

    case 'kurang':
        $cart = cart_raw();
        if (isset($cart[$id])) {
            $cart[$id] = (int) $cart[$id] - 1;
            if ($cart[$id] < 1) {
                unset($cart[$id]);
            }
            cart_set($cart);
        }
        break;

    case 'hapus':
        $cart = cart_raw();
        unset($cart[$id]);
        cart_set($cart);
        break;

    case 'checkout':
        $items = cart_snapshot();
        if (!$items) {
            flash('Keranjang masih kosong.', 'error');
            redirect('keranjang.php');
        }
        // Pakai ulang pesanan yang masih aktif bila isinya sama (timer tidak ter-reset).
        $lama = order_sekarang();
        if ($lama && $lama['status'] === 'pending' && $lama['items'] == $items) {
            redirect('bayar.php');
        }
        if ($lama && $lama['status'] === 'pending') {
            order_set_status((int) $lama['id'], 'cancelled');
        }
        $baru = order_buat($items);
        $_SESSION['order_id'] = $baru['id'];
        redirect('bayar.php');

    case 'cek':
        $o = order_sekarang();
        if (!$o) {
            redirect('keranjang.php');
        }
        if ($o['status'] === 'paid') {
            redirect('berhasil.php');
        }
        if ($o['status'] === 'expired') {
            redirect('kedaluwarsa.php');
        }
        flash('Pembayaran belum diterima. Coba cek lagi sebentar lagi.', 'info');
        redirect('bayar.php');

    case 'batal':
        $o = order_sekarang();
        if ($o && $o['status'] === 'pending') {
            order_set_status((int) $o['id'], 'cancelled');
        }
        unset($_SESSION['order_id']);
        flash('Pesanan dibatalkan. Keranjang Anda masih tersimpan.', 'info');
        redirect('keranjang.php');

    case 'simulasi':
        $o = order_sekarang();
        if (!cfg('demo_mode') || !$o) {
            redirect('keranjang.php');
        }
        if ($o['status'] === 'pending') {
            order_set_status((int) $o['id'], 'paid');
        }
        redirect('berhasil.php');

    case 'qris_baru':
        $o = order_sekarang();
        if (!$o || $o['status'] === 'paid') {
            redirect($o ? 'berhasil.php' : 'keranjang.php');
        }
        $items = cart_snapshot() ?: $o['items'];
        // Pulihkan keranjang bila sudah kosong.
        if (!cart_raw()) {
            $cart = [];
            foreach ($o['items'] as $it) {
                $cart[$it['id']] = (int) $it['qty'];
            }
            cart_set($cart);
            $items = cart_snapshot();
        }
        if (!$items) {
            flash('Stok barang pesanan ini sudah habis. Silakan pilih barang lagi.', 'error');
            redirect('produk.php');
        }
        $baru = order_buat($items);
        $_SESSION['order_id'] = $baru['id'];
        redirect('bayar.php');

    case 'selesai':
        unset($_SESSION['order_id']);
        cart_set([]);
        redirect('index.php');

    default:
        flash('Aksi tidak dikenal.', 'error');
}

redirect($kembali);
