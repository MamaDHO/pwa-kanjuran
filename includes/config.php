<?php
/**
 * Pengaturan aplikasi — ubah sesuai kebutuhan.
 */
return [
    'nama_toko'      => 'Kantin Kejujuran',
    'tagline'        => 'Pilih barang, bayar dengan QRIS',
    'timezone'       => 'Asia/Jakarta',

    // Lama waktu pembayaran QRIS (detik). Desain: 15 menit.
    'qris_durasi'    => 15 * 60,
    'nmid'           => 'ID1023456789012',
    'biaya_layanan'  => 0,

    // Batas "Sisa N" ditampilkan bila stok <= nilai ini
    'batas_stok_tipis' => 5,

    /**
     * MODE DEMO
     * true  : tombol "Simulasi: tandai sudah dibayar" muncul di halaman QRIS
     *         (karena belum terhubung ke payment gateway sungguhan).
     * false : pembayaran hanya bisa dikonfirmasi lewat webhook.php.
     */
    'demo_mode'      => true,

    // Kunci rahasia untuk webhook.php (GANTI sebelum dipakai sungguhan)
    'webhook_secret' => 'ganti-dengan-kunci-rahasia',

    // Folder data (JSON). Idealnya di luar folder publik.
    'data_dir'       => __DIR__ . '/../data',
];
