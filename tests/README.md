# Pengujian Arenaku dengan MySQL Terisolasi

Jalankan dari root proyek:

    php tests/run.php

Alias Composer yang disediakan:

    composer test

Runner ini adalah jalur pengujian yang didukung untuk suite lengkap. Jangan menjalankan migrate:fresh, seeder, atau pengujian database terhadap koneksi development.

## Cara kerja

1. Membaca konfigurasi MySQL lokal dari .env hanya di memori; konfigurasi DB_URL atau host nonlokal ditolak.
2. Membaca snapshot operasional terbatas pada source dalam transaksi READ ONLY. Tidak menyalin data pribadi pelanggan atau kredensial akun.
3. Membuat database unik berawalan arenaku_stage2_test_ dan akun berawalan arenaku_test_, masing-masing diikuti 12 karakter heksadesimal acak, dengan password acak.
4. Memberikan hak akun uji hanya pada schema sementara; membuktikan SELECT ke schema source ditolak.
5. Memaksa APP_ENV=testing, koneksi uji, session/cache/mail in-memory, view cache terpisah, dan config cache terpisah.
6. Menjalankan migration baseline hanya di database sementara, lalu suite PHPUnit.
7. Menjalankan schema rehearsal terakhir pada salinan anonim dan memverifikasi pemulihannya.
8. Memeriksa fingerprint subset operasional source sebelum/sesudah, lalu menghapus schema dan akun sementara dalam finally.

Fingerprint mencakup ID/role, hubungan venue/court, jadwal, status, durasi, dan timestamp operasional yang disalin. Ini bukan checksum seluruh isi database atau backup produksi. Perubahan oleh pengguna lain selama suite berjalan dapat memicu pemeriksaan fingerprint gagal; jangan menimpa perubahan tersebut.

## Prasyarat

- PHP dengan pdo_mysql, dependency Composer existing, dan MySQL lokal aktif.
- Akun setup lokal harus dapat CREATE/DROP DATABASE, CREATE/DROP USER, serta memberikan hak khusus schema uji. Kredensial tidak dicetak atau ditulis ke file test.
- Akun aplikasi selama tests berbeda dari akun setup dan tidak memiliki akses ke source.
- Tidak memerlukan pdo_sqlite atau .env.testing. Jika .env.testing digunakan untuk kebutuhan lain, file tersebut diabaikan Git.
- Tidak menjalankan dependency install/update.

## Pengaman

TestDatabase::assertSafe() berjalan setelah konfigurasi dimuat dan sebelum provider aplikasi melakukan boot pada Laravel test cases. Nama schema, username, host, environment, driver, dan URL koneksi diperiksa. Prototype migration dan proses restore juga memeriksa guard; restore memverifikasi SELECT DATABASE().

tests/bootstrap.php menolak eksekusi langsung suite tanpa provisi runner. phpunit.xml mendeskripsikan suite, tetapi tidak menyimpan credential dan tidak boleh digunakan untuk melewati runner. Masalah bootstrap path pada CLI PHPUnit Windows sebelumnya dihindari dengan entry point PHP dan --no-configuration, tanpa mengedit vendor.

Normal php artisan migrate tidak menemukan migration prototype di database/staging.

Akun test dan schema dibersihkan setelah run normal, termasuk saat suite gagal. Pemutusan proses paksa/komputer mati dapat melewati finally; jika itu terjadi, inspeksi nama schema/akun yang benar-benar dibuat sebelum cleanup manual. Jangan menghapus database berdasarkan prefix secara massal.

Artefak kompilasi view test berada di tests/.runtime/ dan diabaikan Git. Tidak ada credential database yang disimpan di sana.

## Cakupan dan batas

- Test akses memakai akun sintetis di database sementara, bukan session/browser pelanggan.
- CRUD hanya dijalankan pada record uji.
- Simulasi menggunakan ID dan struktur data source dengan pengganti nama/kontak, bukan backup yang dapat dipakai memulihkan pelanggan.
- Regression view tetap dijalankan bersama suite.
- PHPUnit schema tests harus terakhir karena mengubah schema sementara dan memulihkan salinan legacy. Runner menetapkan urutan tersebut.
- Tes transaksi booking serentak, konfirmasi, dan expiry ditambahkan pada tahap 3–4 ketika service tersedia.

Tes JavaScript existing dapat dijalankan terpisah:

    node --test tests/Frontend/dashboard-tables.test.cjs
