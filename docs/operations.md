# Operasional Arenaku

Acuan per 10 November 2026. Pilot Giri Krida ID 6, Court A sudah aktif di database lokal sejak 6 November. Jam 08.00–22.00 WIB, rentang 14 hari termasuk hari ini. Venue lain masih legacy. Backup penuh cutover lokal telah diuji restore ke schema sementara dan data lama cocok setelah migration. Status historis ini tidak menggantikan backup baru untuk penerapan berikutnya.

## Scheduler

Satu runner menjalankan php artisan schedule:run setiap menit dari direktori proyek menggunakan PHP dan environment aplikasi yang sama.

| Proses | Jadwal dan hasil |
| --- | --- |
| hourly:maintain expiry | Setiap menit, menyelesaikan pending nonlegacy lewat tenggat termasuk venue nonaktif |
| hourly:maintain slots | Pemeriksaan setiap 15 menit WIB, mengisi rentang sampai pertama kali berhasil pada hari itu |
| Panel admin | Menampilkan sukses terakhir, kegagalan, jumlah diproses, backlog, dan peringatan keterlambatan |

Task Windows lokal bernama Arenaku Hourly Scheduler. Action memakai wscript.exe dengan storage/app/arenaku-operations/schedule-hidden.vbs untuk menjalankan PHP tanpa terminal. Task memakai sesi pengguna interaktif. Komputer, sesi tersebut, serta MySQL harus aktif. Task Windows dan peluncur lokal tidak otomatis tersedia dari Git di mesin lain.

Periksa hasil task sistem sekaligus operational_task_runs dan panel admin. Exit code Windows 0 tidak menjamin child command Laravel berhasil. Kegagalan MySQL pada 9 November menghambat proses hingga 11.33, lalu expiry pulih 11.34.19 dan pembentukan slot pulih 11.45.19 dengan 42 slot tambahan.

Jangan menjalankan runner ganda. Saat perubahan schema, hentikan penulisan dan scheduler lebih dahulu. Mutex withoutOverlapping membatasi tumpang tindih; expiry 5 menit dan pembentukan 30 menit. Kegagalan per venue dicatat dan hasil parsial dicoba kembali.

## Penerapan pada lingkungan baru atau venue berikutnya

1. Tetapkan venue pilot, jam buka/tutup reguler, kontak pengelola yang boleh tampil publik, dan daftar court fisik yang telah diverifikasi. Periksa ulang booking dan penutupan legacy aktif pada waktu penerapan.
2. Catat revisi aplikasi dan ambil backup lengkap database ke lokasi berakses terbatas. Hentikan penulisan selama pengambilan backup cutover, termasuk worker dan penulis dari luar aplikasi. Simpan checksum dan uji restore ke database terpisah. Simulasi dalam suite memakai snapshot anonim, sehingga restore dump penuh database utama tetap perlu diverifikasi di lingkungan penerapan.
3. Pastikan maintenance aktif dan hentikan scheduler saat perubahan schema berlangsung. Terapkan migration melalui jalur eksplisit di bawah. Jalur migration standar tidak memuat file ini.
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


## Verifikasi setelah penerapan

Periksa aktivasi hanya pada venue yang dituju, court terverifikasi, jumlah slot sesuai jam/rentang, booking legacy tetap utuh, heartbeat expiry kurang dari tiga menit, dan pembentukan berhasil hari itu. Uji receipt, konfirmasi, pembatalan, expiry, serta scope penjaga menggunakan booking khusus pengujian.

[Spesifikasi](booking-hourly-slots-plan.md), [arsitektur](booking-hourly-architecture.md) dan [penerimaan](qa/acceptance.md).
