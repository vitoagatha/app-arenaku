# Arenaku tahap 7 — Scheduler, pemantauan, dan cutover

6 November 2026. Implementasi dan simulasi selesai pada lingkungan uji. Penerapan database utama memerlukan verifikasi data venue dan otorisasi cutover.

## Proses otomatis

| Proses | Jadwal | Hasil |
| --- | --- | --- |
| Kedaluwarsa | Setiap menit | Menyelesaikan pending nonlegacy yang melewati tenggat, termasuk pada venue yang sudah dinonaktifkan. Riwayat slot tetap tersimpan. |
| Pembentukan slot | Pemeriksaan setiap 15 menit dalam WIB | Menjalankan pembentukan sampai pertama kali berhasil hari itu. Mengisi rentang pemesanan terkini, termasuk jadwal yang terlewat. |
| Pemantauan | Saat admin membuka ringkasan | Menampilkan eksekusi terakhir yang berhasil, jumlah record diproses, kegagalan terakhir, dan pending lewat tenggat. Peringatan muncul setelah lebih dari tiga menit tanpa sukses kedaluwarsa atau ketika backlog ada. |

Perintah internal memakai service yang sama dengan alur interaktif. Pemeriksaan sinkron saat booking dan konfirmasi tetap berlaku saat scheduler tertunda. Eksekusi terjadwal memakai mutex Laravel untuk mengurangi tumpang tindih. Proses memisahkan kegagalan per venue agar venue lain tetap diproses. Hasil parsial tercatat gagal sehingga pembentukan slot dicoba kembali. Log kegagalan mencatat jenis kesalahan dan ID venue.

## Hasil verifikasi

- Suite MySQL terisolasi lulus **40 tes, 659 assertions**. Cakupan termasuk schema cutover yang sama, simulasi pemulihan data legacy anonim, pemeriksaan aktivasi, penolakan penutupan legacy yang masih berlaku, alarm backlog, kegagalan pembentukan, percobaan ulang, dan pemulihan jadwal setelah dua hari terlewat.
- Tes lifecycle sebelumnya tetap lulus, termasuk bentrok slot antarproses, retry, dan konfirmasi saat tenggat.
- Eksekusi nyata php artisan schedule:run pada aplikasi fixture menghasilkan catatan sukses kedaluwarsa. Cron/Task Scheduler sistem untuk aplikasi utama masih perlu dipasang saat penerapan.
- Browser memakai aplikasi lokal terisolasi dengan login biasa. Pengunjung mengirim booking dua jam, admin melihat panel dan mengonfirmasi, penjaga melihat booking venue penugasannya dan membatalkan dengan alasan. Riwayat tetap tercatat dan dua slot kembali tersedia. Panel pemantauan hanya terlihat untuk admin.
- Uji browser memakai akun dan data sintetis. Fixture, akun database uji, dan server sementara telah dibersihkan. Snapshot operasional source sama sebelum dan sesudah pengujian.
- Lint PHP dan git diff --check lulus.

## Urutan cutover untuk ditinjau

1. Tetapkan venue pilot, jam buka/tutup reguler, kontak pengelola yang boleh tampil publik, dan daftar court fisik yang telah diverifikasi. Periksa ulang booking dan penutupan legacy aktif pada waktu penerapan.
2. Catat revisi aplikasi dan ambil backup lengkap database ke lokasi berakses terbatas. Hentikan penulisan selama pengambilan backup cutover, termasuk worker dan penulis dari luar aplikasi. Simpan checksum dan uji restore ke database terpisah. Simulasi dalam suite memakai snapshot anonim, sehingga restore dump penuh database utama tetap perlu diverifikasi di lingkungan penerapan.
3. Masuk maintenance mode dan hentikan scheduler saat perubahan schema berlangsung. Terapkan migration melalui jalur eksplisit di bawah. Jalur migration standar tidak memuat file ini.
4. Cocokkan jumlah record, hubungan, timestamp, dan riwayat legacy terhadap baseline. Isi pengaturan venue dan verifikasi court melalui dashboard admin dengan akses maintenance yang disiapkan operator. Gunakan data yang disetujui pengelola.
5. Jalankan preflight aktivasi per venue. Jika ada penutupan, booking, atau status legacy yang masih menghalangi aktivasi, rekonsiliasi dengan pengelola dan ulangi pemeriksaan. Command sengaja memblokir aktivasi alih-alih menebak pemetaan.
6. Setelah hasil preflight dan penerapan disetujui, jalankan aktivasi dengan --apply dalam maintenance mode. Flag dan slot awal disimpan dalam satu transaksi dengan kunci venue. Endpoint pemesanan lama memeriksa flag yang sama sebelum menyimpan.
7. Pasang satu runner sistem yang menjalankan php artisan schedule:run setiap menit dari folder proyek dengan PHP dan environment yang sama. Pada Windows gunakan Task Scheduler dengan direktori kerja proyek. Pada Linux gunakan cron dengan path PHP dan proyek yang eksplisit. Arahkan keluaran ke log operasional yang sesuai.
8. Periksa konfigurasi, keluar dari maintenance, lalu verifikasi heartbeat kedaluwarsa, hasil pembentukan, ketersediaan publik, dan scope dashboard. Pantau venue pilot sebelum memperluas aktivasi.

Perintah penerapan berikut adalah referensi untuk operator setelah backup dan otorisasi.

    php artisan down
    php artisan migrate --path=database/cutover --force
    php artisan hourly:activate ID_VENUE
    php artisan hourly:activate ID_VENUE --apply
    php artisan schedule:list
    php artisan up

Pemeriksaan tanpa --apply hanya memvalidasi kesiapan. Aktivasi memerlukan schema, jam reguler, kontak yang berisi nomor, minimal satu court terverifikasi, dan hasil rekonsiliasi legacy yang memenuhi syarat. Untuk memulihkan proses yang tertinggal, operator dapat menjalankan perintah berikut dari environment aplikasi.

    php artisan hourly:maintain expiry
    php artisan hourly:maintain slots

## Pemulihan

- Jika schema gagal diterapkan atau verifikasi gagal sebelum penulisan baru dibuka, pertahankan maintenance mode dan gunakan backup terverifikasi dengan revisi aplikasi yang sesuai. MySQL DDL dapat menyisakan perubahan parsial. Hindari menjalankan ulang migration di atas schema parsial tanpa inspeksi.
- Setelah booking baru diterima, hentikan penulisan dan amankan perubahan sejak backup. Rekonsiliasi perubahan tersebut sebelum memutuskan pemulihan. Restore langsung ke snapshot lama dapat menghilangkan booking baru.
- Rollback otomatis schema sengaja dibatasi. Verifikasi jumlah record dan riwayat setelah pemulihan, kemudian periksa lagi status scheduler.

## File implementasi

- app/Console/Kernel.php dan routes/console.php untuk jadwal dan perintah pemeliharaan
- app/Console/Commands/ActivateHourlyVenue.php untuk preflight dan aktivasi venue
- app/Services/HourlyMaintenance.php untuk proses dan ringkasan kesehatan
- app/Http/Controllers/HomeController.php dan resources/views/home.blade.php untuk pemantauan admin
- database/cutover/2026_11_04_000001_hourly_booking_schema.php untuk migration eksplisit
- database/staging/2026_11_04_000001_hourly_booking_schema.php untuk wrapper schema yang sama dengan guard uji
- tests/Database/HourlyMaintenanceTest.php dan tests/run.php untuk verifikasi

Pesan commit yang disarankan

    feat(booking): schedule hourly maintenance and prepare venue cutover
