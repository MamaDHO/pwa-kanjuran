<?php
/**
 * Endpoint notifikasi pembayaran dari payment gateway (contoh).
 *
 * Panggil dengan:
 *   POST /webhook.php
 *   Header: X-Webhook-Secret: <webhook_secret di includes/config.php>
 *   Body JSON: {"trx": "TRX040", "status": "paid"}
 *
 * Sesuaikan format body & verifikasi tanda tangan dengan dokumentasi
 * penyedia QRIS Anda (Midtrans, Xendit, DOKU, dsb.).
 */
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'POST only']));
}

$secret = (string) ($_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '');
if (!hash_equals((string) cfg('webhook_secret'), $secret)) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'forbidden']));
}

$body = json_decode((string) file_get_contents('php://input'), true);
$trx  = is_array($body) ? (string) ($body['trx'] ?? '') : '';
$stat = is_array($body) ? (string) ($body['status'] ?? '') : '';

if (!preg_match('/^#?TRX(\d+)$/', $trx, $m)) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'error' => 'trx tidak valid']));
}

$id = (int) $m[1];
if ($stat === 'paid') {
    $o = order_set_status($id, 'paid');
} elseif ($stat === 'expired') {
    $o = order_set_status($id, 'expired');
} else {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'error' => 'status tidak dikenal']));
}

echo json_encode(['ok' => (bool) $o, 'status' => $o['status'] ?? null]);
