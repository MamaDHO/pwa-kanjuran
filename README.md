# Kantin Kejujuran — PHP Native

Web kantin kejujuran (self‑service) dengan pembayaran QRIS, dibuat dari 6 desain SVG.
Tanpa framework, tanpa database server, tanpa Composer.

## Menjalankan

Syarat: PHP 8.0+ (disarankan 8.1+).

```bash
cd kantin-kejujuran
php -S 0.0.0.0:8000
```

Buka `http://localhost:8000` (di HP: `http://IP-KOMPUTER:8000`).
Untuk hosting Apache/Nginx, cukup upload seluruh folder ke `public_html`
dan pastikan folder `data/` bisa ditulis (`chmod 775 data`).

## Alur halaman

| Halaman | File | Tombol yang berfungsi |
|---|---|---|
| 01 Selamat datang | `index.php` | Mulai Belanja |
| 02 Produk | `produk.php` | Cari (live), filter kategori, + / −, badge & bar keranjang |
| 03 Keranjang | `keranjang.php` | + / −, hapus, panah kembali, Bayar dengan QRIS |
| 04 Pembayaran QRIS | `bayar.php` | Timer 15 menit, Cek Status, Batalkan pesanan |
| 05 Berhasil | `berhasil.php` | Selesai |
| 06 Kedaluwarsa | `kedaluwarsa.php` | Buat QRIS Baru, Kembali ke Keranjang |

Lainnya: `aksi.php` (semua aksi POST + CSRF), `status.php` (polling status),
`webhook.php` (notifikasi dari payment gateway).

## Penting: QRIS di sini masih DEMO

Kode QR yang tampil dibuat lokal dan **tidak bisa dibayar sungguhan**.
Untuk pembayaran nyata:

1. Daftar ke penyedia QRIS dinamis (Midtrans, Xendit, DOKU, dll.).
2. Di `includes/bootstrap.php` fungsi `qris_payload()`, ganti isinya dengan
   `qr_string` dari API penyedia (buat transaksi saat `order_buat()` dipanggil).
3. Arahkan notifikasi penyedia ke `webhook.php` (sesuaikan verifikasi tanda tangan).
4. Set `'demo_mode' => false` di `includes/config.php` dan ganti `webhook_secret`.

Selama `demo_mode` aktif, halaman QRIS menampilkan tombol
"Simulasi: tandai sudah dibayar" agar seluruh alur bisa dicoba.

## Data

- Produk & stok: `data/products.json` (dibuat otomatis; edit untuk menambah produk
  — gambar taruh di `assets/img/`).
- Pesanan: `data/orders.json`. Stok berkurang saat pesanan berstatus *paid*.
- Keranjang disimpan di session pengunjung.
- Folder `data/` dilindungi `.htaccess` (Apache). Di Nginx / `php -S`, sebaiknya
  ubah `data_dir` di `config.php` ke folder di luar web root.

## Pengaturan

Semua ada di `includes/config.php`: nama toko, durasi QRIS, NMID, biaya layanan,
batas "Sisa N", mode demo, kunci webhook.
