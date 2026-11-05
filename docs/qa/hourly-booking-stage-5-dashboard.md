# Arenaku — Tahap 5: Integrasi Dashboard Operasional

Tanggal: 5 November 2026.

Status: implementasi route, controller, dan Blade dashboard selesai serta diuji pada database terisolasi. Database utama belum dimigrasikan. Acceptance browser dengan sesi admin/penjaga nyata belum selesai.

## Hasil implementasi

- Link Operasional per jam pada daftar venue di Atur jadwal.
- Halaman operasional per venue: filter tanggal dan Hari ini, semua court/slot, pemilihan beberapa jam, ringkasan durasi, informasi pelanggan dan sumber, serta konfirmasi langsung yang eksplisit.
- Admin dapat menyimpan jam reguler, rentang pemesanan, dan kontak publik, serta menambahkan court yang telah diverifikasi atau memverifikasi kandidat lama.
- Penjaga hanya mengakses venue penugasannya dan tidak mendapat form pengaturan admin.
- Form pengecualian tanggal/libur, penutupan satu atau beberapa jam court, dan pembukaan kembali dengan alasan. Menutup seluruh jam operasional mewakili penutupan court sehari penuh.
- Daftar booking baru mendukung status, tanggal, pencarian nama/kontak/venue/court, pengurutan field yang diizinkan, dan pagination server 20 record dengan query filter dipertahankan.
- Pilihan Menunggu hanya mengambil booking nonlegacy yang benar-benar menunggu; riwayat pending lama tidak dihitung sebagai permintaan baru.
- Detail booking menampilkan pelanggan, venue/court, waktu, sumber, semua slot, tenggat, status, alasan pembatalan dan riwayat tindakan.
- Konfirmasi dan pembatalan dipanggil melalui service tahap 4. Booking baru tidak dapat diedit/dihapus melalui endpoint legacy.
- Booking legacy tetap ditampilkan dengan nilai jadwal aslinya dan keterangan riwayat. UI baru tidak memberikan konfirmasi/pembatalan baru pada record lama.
- Pintasan Menunggu konfirmasi ditambahkan ke ringkasan menggunakan komponen task-link existing.
- Status Kedaluwarsa memakai komponen badge existing.
- Schema yang belum siap menampilkan pesan kesiapan venue, bukan menjalankan migration atau menimbulkan query tabel hilang.

## Rancangan tampilan dan komponen

Courtside tetap memakai token, font, warna, komponen feedback/field/task/status dan Bootstrap 4 yang sudah ada. Tidak ada framework/dependency baru atau perubahan vendor.

Komponen hour-select menyediakan pilihan jam bulat dengan label WIB, nilai sebelumnya saat validasi gagal, dan hubungan error ke kontrol. Grid operasional berpindah dari satu kolom ke dua kolom pada desktop. Checkbox slot memiliki seluruh label sebagai target, status tekstual, fokus terlihat, dan pembeda pilihan selain warna.

Penutupan dan pembatalan ditempatkan dalam disclosure kontekstual. Pembatalan meminta konfirmasi native serta alasan; proses tidak menghapus record.

Daftar booking baru memakai pencarian/pengurutan/pagination server agar bekerja pada seluruh data yang diizinkan. Tidak memasang DataTables di atas subset halaman server sehingga tidak terjadi pagination ganda atau pencarian hanya satu halaman. DataTables di halaman lama lainnya dipertahankan.

## Pembaruan dan validasi

- Slot diperbarui setiap 30 detik pada tab aktif dan ketika kembali ke tab. Checkbox yang sudah dipilih namun menjadi tidak tersedia tidak dihapus diam-diam; ringkasan meminta pengguna memperbaiki pilihan.
- Client menolak pilihan lintas court/tidak berurutan sebelum submit; service tetap menjadi pemeriksaan final.
- Pengecekan daftar pending hanya menampilkan pemberitahuan bahwa data berubah dan menyediakan Perbarui daftar. Filter, halaman tabel, fokus, serta formulir tidak diganti otomatis.
- Kegagalan koneksi menampilkan pesan; tidak memberi kesan ketersediaan pasti mutakhir.
- Scope idempotency dibuat dari sesi server dan akun yang login. Pemanggil tidak dapat mengirim actor sendiri.
- Semua route baru berada dalam auth dan gate management. Venue dan booking diperiksa kembali terhadap penugasan.
- Respons JSON jadwal tidak mengirim pelanggan; respons perubahan daftar hanya pending/revision.
- Jam reguler/rentang tetap khusus admin. Tidak ada delegasi izin khusus penjaga.
- Endpoint pengaturan tidak menerima flag aktivasi hourly dari request. Migrasi dan cutover tetap proses terpisah.
- Booking dikonfirmasi tidak dinyatakan lunas.

## Pengujian backend dan regresi

Run akhir lengkap sebelum penyempurnaan kecil markup field/task: **38 tests, 577 assertions lulus** melalui php tests/run.php.

Test baru DashboardHourlyTest memeriksa route/form yang sebenarnya pada fixture database sementara:

- Guest ditolak, venue asing 404, pengaturan admin ditolak untuk penjaga.
- Admin menyimpan kontak dan memverifikasi kandidat court.
- Form menghasilkan validasi dan detail booking menyimpan dua slot.
- Daftar pending, filter, pencarian kosong, pengurutan, penolakan kolom sort tidak dikenal.
- Pagination halaman kedua dari 21 fixture dengan query dipertahankan.
- Konfirmasi/pembatalan, alasan wajib, riwayat, serta status expired saat tenggat.
- Endpoint edit/delete legacy menolak booking baru.
- JSON availability dan notifikasi pending.
- Penutupan, pembukaan kembali, dan pengecualian.
- Fallback halaman saat schema baru belum ada tidak menjalankan migration.

Regression Node DataTables: 2/2 lulus. PHP lint dan JavaScript syntax check lulus. Sepuluh template Blade terkait dikompilasi di memori setelah penyempurnaan markup terakhir. Diff diperiksa untuk perubahan di luar cakupan.

Fingerprint operasional source sebelum/sesudah sama dan akun/schema uji dibersihkan. Hash log utama tidak berubah dari tahap sebelumnya. Tidak ada schema/data utama, .env, vendor, migration baseline, atau dependency lockfile yang diubah.

Build Mix tidak dijalankan: CSS/JavaScript aplikasi disajikan langsung sebagai aset existing; konfigurasi Mix legacy yang tidak lengkap tidak disentuh.

## Pemeriksaan browser dan aksesibilitas

Browser terhubung awalnya tidak memiliki tab/session login. Pratinjau statis diekspor dari render HTTP fixture anonim melalui opsi ARENAKU_EXPORT_PREVIEWS=1. Form pratinjau tidak dapat submit; token sesi/CSRF dihapus. Server sementara hanya menyajikan folder preview, dan aset disalin dari public ke folder runtime test yang diabaikan Git.

Setelah kegagalan koneksi sandbox, server pratinjau lokal di luar sandbox dapat diakses. Aset awal yang gagal di host virtual disediakan dari salinan lokal; pemeriksaan akhir tidak menunjukkan gambar rusak.

| Halaman pratinjau | 360 | 390 | 768 | 1024 | 1440 |
| --- | --- | --- | --- | --- | --- |
| Operasional admin | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label |
| Operasional penjaga | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label |
| Daftar booking | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label |
| Detail booking | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label | PASS geometri/label |

Pada 20 kombinasi: viewport aktual sesuai target, tidak ada overflow dokumen, label form hilang, ID ganda, atau gambar rusak. Ini pemeriksaan DOM, bukan screenshot penuh semua halaman.

Screenshot langsung diperiksa untuk admin desktop 1440, penjaga mobile 360, detail desktop 1440, dan daftar tablet 768. Tabel awal memecah teks menjadi huruf per baris; diperbaiki dengan minimum 960px dan overflow pada region tabel. Screenshot tablet sesudah perbaikan menunjukkan teks terbaca dan scrollbar terlokalisasi.

Interaksi pratinjau: memilih dua slot berurutan menghasilkan ringkasan 2 jam. Sidebar mobile dapat dibuka dan Escape mengembalikan fokus ke tombol navigasi. Tidak ada submit CRUD browser dilakukan.

Review menggunakan frontend-design, redesign-existing-projects, ui-ux-pro-max (pencarian error summary validation), dan [Web Interface Guidelines](https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md). Perbaikan mencakup label, fokus, status tekstual, penanganan tabel sempit, target klik dan error summary.

Batas bukti: browser memeriksa fixture anonim, bukan sesi aplikasi terautentikasi. Penambahan kontrol search/sort server terakhir diverifikasi melalui tests/render dan review source; keseluruhan screenshot final dengan kontrol tersebut belum diulang. Polling terhadap endpoint aktif, konfirmasi dialog, keyboard seluruh form, validasi browser, console/aset seluruh halaman, dan CRUD lewat sesi nyata masih NOT TESTED. Jangan menganggap acceptance authenticated selesai.

Viewport dikembalikan ke default dan server preview dihentikan. Preview hanya artefak lokal yang diabaikan Git.

## File dibuat/diubah

Dibuat:

- app/Http/Controllers/DashboardOperationsController.php
- resources/views/dashboard/hourly/unavailable.blade.php
- resources/views/dashboard/hourly/operations.blade.php
- resources/views/dashboard/hourly/bookings.blade.php
- resources/views/dashboard/hourly/detail.blade.php
- resources/views/components/dashboard/hour-select.blade.php
- public/js/arenaku-hourly-dashboard.js
- tests/Database/DashboardHourlyTest.php
- docs/qa/hourly-booking-stage-5-dashboard.md

Diubah:

- app/Http/Controllers/BookingController.php
- routes/web.php
- resources/views/dashboard/jadwal/index.blade.php
- resources/views/home.blade.php
- resources/views/layouts/admin.blade.php
- resources/views/components/dashboard/status.blade.php
- resources/views/components/dashboard/task-link.blade.php
- public/css/arenaku-dashboard.css
- tests/Database/ManagementAuthorizationTest.php
- tests/run.php

## Batas aktivasi dan pekerjaan berikutnya

- Database utama belum memiliki schema hourly; aplikasi nyata menampilkan kesiapan fitur sampai migration/cutover dilakukan.
- Tidak ada aktivasi venue otomatis atau asumsi jam resmi/court/kontak.
- Alur public legacy masih berjalan; writer baru tidak boleh diaktifkan bersamaan pada venue yang sama sebelum cutover tahap berikutnya siap.
- Scheduler, pemantauan proses, backup/restore produksi, dan penerapan schema utama belum dijalankan.
- Existing endpoint legacy untuk record lama belum diubah menjadi lifecycle pembatalan baru; kebijakan histori legacy tetap mengikuti rekonsiliasi.
- Tahap 6 halaman booking publik belum dimulai.
- Tidak ada commit/push dibuat.

Rekomendasi commit:

    feat(dashboard): integrate hourly court operations and booking management
