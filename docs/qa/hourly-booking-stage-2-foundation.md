# Arenaku — Hasil Tahap 2: Fondasi Skema dan Otorisasi

Tanggal: 4 November 2026. Baseline Git: 4ba6c89.

**Status: tahap 2 selesai dalam cakupan persiapan skema, lingkungan uji, simulasi, dan perbaikan akses management. Database utama tidak dimigrasikan. Tahap 3 belum dimulai.**

Rancangan terperinci dan runbook: [fondasi tahap 2](../booking-hourly-stage-2-foundation.md).
Cara menjalankan tests: [tests/README.md](../../tests/README.md).
Audit awal: [tahap 1](hourly-booking-stage-1-audit.md).

## 1. Perubahan aplikasi yang sudah berlaku

- Route management venue, jadwal, booking, court, dan pengguna memerlukan login serta role admin/penjaga yang dikenal.
- Seluruh resource pengguna dan court global memerlukan admin.
- Booking penjaga dibatasi melalui hubungan jadwal → venue → user_id untuk list, edit, update, dan delete.
- Penjaga tanpa venue mendapat daftar booking kosong.
- Update jadwal memeriksa akses terhadap venue asal dan tujuan; mengganti hidden lapangan_id tidak dapat memindahkan jadwal ke venue penjaga lain.
- Payload jadwal hanya memakai field tervalidasi. Create tidak menerima injeksi status booking dari field di luar kontrak form; update membatasi status ke enum existing.
- Public booking.store tetap public. Nama route, metode form, CSRF, dan Blade tidak diubah.
- Import exception jadwal diperbaiki agar penanganan venue tidak ditemukan memakai exception Eloquent yang benar.

Pembatasan ini memperbaiki temuan akses terkait QA-001–004/S1-01–03/S1-05 dalam cakupan yang diuji. Tidak menyatakan seluruh audit keamanan telah selesai.

## 2. Lingkungan pengujian

Runner php tests/run.php membuat schema MySQL acak dan akun sementara dengan grant hanya pada schema tersebut. Akses akun uji ke tabel users pada schema utama diuji dan ditolak oleh MySQL. Akun setup memakai konfigurasi lokal di memori, tanpa mencetak kredensial.

Snapshot source dibaca READ ONLY dan hanya menyalin struktur operasional tanpa nama/kontak pelanggan, email, password, atau session. PHPUnit menjalankan CRUD terhadap fixture sementara.

Database sementara dan akun uji yang dibuat runner dibersihkan setelah setiap run. Hash snapshot operasional utama sebelum/sesudah sama. Hash log aplikasi utama tetap:

    BF19E1358236AF876B4C59ECA67B42AD82E6D3C68B8E5510815785C156E00290

Tidak ada perubahan .env utama, migration baseline, vendor, atau dependency lockfile.

## 3. Skema simulasi

Prototype mencakup pengaturan venue, venue_courts, operating_exceptions, court_closures, schedule_slots, tambahan lifecycle booking, booking_slots, active_slot_reservations, activity_logs, dan operational_task_runs.

Pengaman dan hasil:

- Court kandidat tidak aktif; jam reguler tetap kosong; fitur hourly tetap nonaktif.
- Field legacy, ID, interval, status, dan timestamp dipertahankan.
- Pending legacy tidak diberi expires_at dan tidak dilepas/dikonfirmasi otomatis.
- Reservasi aktif legacy tetap kosong dalam simulasi snapshot seluruh jadwal masa lalu.
- Slot unik per court/waktu dan maksimal satu reservasi aktif per slot.
- Hubungan booking dan slot dibatasi ke court fisik yang sama.
- Slot harus satu jam pada jam bulat dan tidak lintas tanggal.
- Durasi booking nonlegacy harus sesuai interval.
- Foreign key mencegah penghapusan riwayat melalui parent.
- Pelepasan reservasi aktif tidak menghapus hubungan booking_slots.
- Idempotency hash memiliki unique constraint.
- Backup logis anonim sebelum simulasi berhasil dipulihkan pada schema sementara; fingerprint seluruh field tabel anonim yang dicadangkan cocok kembali.

Simulasi restore tersebut bukan backup/restore produksi penuh. Perilaku bisnis lintas transaksi belum tersedia; constraint tests tidak diklaim sebagai pengujian dua request booking bersamaan.

## 4. Hasil verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| PHPUnit lengkap melalui runner terisolasi | PASS — 34 tests, 408 assertions |
| Akun database uji tidak dapat membaca source | PASS |
| Snapshot operasional source sebelum/sesudah | PASS — sama |
| Guest GET/POST/PUT/DELETE management | PASS — diarahkan ke login |
| Penjaga seluruh action users/global courts | PASS — 403 |
| Booking list sesuai penugasan dan kosong tanpa penugasan | PASS |
| Booking foreign ID read/update/delete | PASS — 404, data tetap |
| Update jadwal: venue asal dan tujuan | PASS — akses lintas penugasan ditolak |
| Aksi yang sah: admin management, edit booking, jadwal own CRUD | PASS pada fixture |
| Role tidak dikenal | PASS — management ditolak |
| Public booking submit | PASS pada fixture; alur lama tetap public |
| Guard database salah nama/user/host/URL/driver | PASS — ditolak |
| Bootstrap test tanpa provisi runner | PASS — ditolak sebelum menjalankan suite |
| Constraint skema dan restore anonim | PASS |
| Node DataTables tests | PASS — 2/2 |
| PHP lint file PHP dibuat/diubah | PASS — 13 file |
| Runtime route:list | PASS — middleware baru terdaftar, booking.store tetap web |
| git diff --check | PASS |
| Validasi XSD phpunit.xml dan parsing composer.json | PASS |
| Browser acceptance/session nyata | NOT TESTED pada tahap ini |
| Build asset | Tidak dijalankan; tidak ada perubahan frontend, konfigurasi Mix legacy tidak disentuh |
| Migrasi database utama dan backup/restore produksi | NOT EXECUTED |

Run pertama sebelum schema rehearsal: 33 tests / 373 assertions lulus. Saat rehearsal ditambahkan, satu assertion mengharapkan kode foreign key MySQL 1452, tetapi akun terbatas menerima varian 1216. Assertion diperbaiki untuk menerima kode constraint setara 1216/1452 dan 1217/1451 tanpa mengabaikan kegagalan constraint. Run akhir lulus seluruhnya.

## 5. File dibuat dan diubah

### Source aplikasi

- Diubah: routes/web.php
- Diubah: app/Providers/AuthServiceProvider.php
- Diubah: app/Http/Controllers/BookingController.php
- Diubah: app/Http/Controllers/JadwalController.php

### Test dan prototype

- Dibuat: database/staging/2026_11_04_000001_hourly_booking_schema.php
- Dibuat: tests/run.php
- Dibuat: tests/bootstrap.php
- Dibuat: tests/Support/IsolatedMysql.php
- Dibuat: tests/Support/TestDatabase.php
- Dibuat: tests/Database/ManagementAuthorizationTest.php
- Dibuat: tests/Database/TestDatabaseGuardTest.php
- Dibuat: tests/Database/HourlySchemaRehearsalTest.php
- Diubah: tests/CreatesApplication.php
- Diubah: phpunit.xml
- Diubah: composer.json — hanya alias test
- Diubah: .gitignore — runtime tests dan .env.testing

### Dokumentasi

- Dibuat: docs/booking-hourly-stage-2-foundation.md
- Dibuat: tests/README.md
- Dibuat: docs/qa/hourly-booking-stage-2-foundation.md

Dokumen rencana booking dan audit tahap 1 sudah untracked saat tahap ini dimulai, dan tidak diubah. Belum membuat commit atau push.

## 6. Risiko dan pekerjaan yang belum dilakukan

1. **Public booking lama belum memiliki proteksi availability, transaksi, locking, dan lifecycle baru.** Test public submit membuktikan akses public tetap bekerja, bukan bahwa risiko booking ganda sudah diperbaiki.
2. **Hard delete dan cascade pada database utama masih berlaku.** Perubahan RESTRICT hanya terbukti pada prototype sementara. Alur pembatalan/penonaktifan belum dibangun.
3. Update jadwal yang sah masih dapat mengubah interval/status existing; aturan bentrok, booking immutable, dan jam operasional dibangun pada tahap 3–4.
4. Jam reguler/rentang hanya admin merupakan kontrak skema baru; endpoint pengaturan tersebut belum dibuat. Tidak ada izin khusus penjaga yang ditambahkan.
5. Belum ada konfirmasi, expiry, pembentukan slot, scheduler, polling, atau monitoring aktif.
6. Tidak mengubah password hashing, registrasi, pembayaran, upload, atau seluruh temuan audit lain.
7. Belum menyediakan UI penugasan ulang venue oleh admin; mekanisme existing user_id tetap digunakan.
8. Skema harus diperiksa kembali terhadap snapshot terbaru sebelum migrasi produksi. Input jam resmi, daftar court, dan kontak publik masih diperlukan sebelum aktivasi.
9. Normal phpunit/artisan test tidak dipakai sebagai jalur eksekusi langsung; gunakan runner yang memprovisi database. Bootstrap test sengaja menolak bypass. Tidak ada pdo_sqlite yang dipasang.
10. Runner memerlukan hak setup MySQL lokal untuk membuat schema/akun test. Proses paksa yang dihentikan dapat meninggalkan resource sementara; cleanup harus menargetkan resource yang diverifikasi, bukan prefix massal.

## 7. Rekomendasi commit

    feat(booking): secure management access and rehearse hourly booking schema

Tahap berikutnya setelah instruksi pengguna: tahap 3, pengaturan operasional dan pembentukan slot. Tidak ada langkah tahap 3 dijalankan otomatis.
