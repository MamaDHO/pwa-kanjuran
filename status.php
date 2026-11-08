<?php
// Dipanggil berkala oleh halaman QRIS untuk mengecek status pesanan.
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$o = order_sekarang();
echo json_encode([
    'status' => $o['status'] ?? 'none',
    'sisa'   => $o ? max(0, (int) $o['kedaluwarsa'] - time()) : 0,
]);
