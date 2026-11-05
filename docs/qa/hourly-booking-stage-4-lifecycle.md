# Arenaku — Tahap 4: Lifecycle Booking Beberapa Slot

Tanggal: 5 November 2026.

Status: service booking baru diimplementasikan dan diuji pada database MySQL sementara. Belum dihubungkan ke route publik/dashboard dan belum memigrasikan database utama.

## Perubahan

Service app/Services/HourlyBooking.php menyediakan reserve, confirm, cancel, dan expireForVenue.

- Booking baru memilih satu atau beberapa slot berurutan pada satu court dan tanggal.
- Server menghitung durasi dari slot, memeriksa jam operasional, pengecualian, penutupan, rentang hari, dan waktu mulai.
- Booking, hubungan historis, reservasi aktif, serta event dibuat dalam satu transaksi.
- Semua writer mengambil kunci venue terlebih dahulu, konsisten dengan CourtOperations; transaksi memiliki retry deadlock terbatas.
- Pending berakhir pada waktu yang lebih dahulu antara 60 menit sejak pembuatan dan waktu mulai slot pertama.
- Pengelola venue atau admin dapat mengonfirmasi sebelum tenggat. Konfirmasi bukan bukti pembayaran.
- Catatan langsung/WhatsApp dapat dikonfirmasi secara eksplisit oleh pengelola ketika dibuat.
- Pembatalan memerlukan alasan, menyimpan pelaku/waktu, mempertahankan booking_slots, dan melepas seluruh reservasi aktif.
- Pembatalan booking yang sudah mulai ditolak.
- Kedaluwarsa hanya menyentuh booking nonlegacy pending dengan expires_at yang telah tiba. Seluruh slot dilepas bersama dan riwayat tetap ada.
- Konfirmasi yang terlambat tetap ditolak jika scheduler belum berjalan; status kedaluwarsa disimpan sebelum respons penolakan.
- Booking confirmed tidak kedaluwarsa akibat tenggat pending.
- Permintaan dengan key dan scope yang sama mengembalikan record yang sama, termasuk status canceled/expired, tanpa mengaktifkannya kembali.
- Menggunakan key yang sama untuk payload berbeda ditolak.

## Integrasi dengan aturan operasional

CourtOperations membersihkan penahanan kedaluwarsa ketika membaca availability atau memeriksa konflik reservasi, dengan mutex venue yang sama. Dengan demikian, pembacaan dan permintaan baru tidak harus menunggu scheduler untuk melepas pending lama.

Pembatalan tidak mengubah penutupan atau jam operasional. Slot masa lalu tetap tidak tersedia; slot mendatang hanya tersedia jika aturan lainnya mengizinkan.

Penolakan permintaan baru dapat me-rollback perubahan dalam transaksinya, termasuk cleanup expiry yang dilakukan di dalamnya. Pemeriksaan berikutnya tetap mengevaluasi ulang tenggat. Konfirmasi/pembatalan yang ditolak karena status tidak aktif menggunakan hasil transaksi terlebih dahulu agar expiry yang ditemukan tetap tersimpan.

## Kontrak pemanggil untuk integrasi berikutnya

- Route/controller baru belum dibuat. Service tidak mengganti BookingController lama secara diam-diam.
- scope harus ditentukan server dari konteks sesi/permintaan yang tidak dapat ditebak; jangan menerima identitas scope mentah dari pelanggan.
- key harus stabil pada retry permintaan yang sama. Hash disimpan, bukan token receipt publik.
- Payload retry dibandingkan dengan nama, kontak, sumber, dan seluruh ID slot historis. Replay tidak menjalankan perubahan status tambahan.
- Jika actor disediakan, pemanggil harus memakai authenticated User dari server. Pengunjung tidak boleh memilih actor, sumber offline, atau status confirmed.
- Akses receipt/status pelanggan memerlukan rancangan token atau sesi tersendiri pada integrasi publik. ID booking saja tidak boleh memberikan akses data pribadi.
- Service expiry merupakan operasi internal. Jangan mengeksposnya sebagai endpoint mutasi publik tanpa otorisasi.
- Tidak ada pembayaran, refund, atau harga yang ditambahkan.

## Pengujian terisolasi

Test baru: tests/Database/HourlyBookingTest.php.
Worker concurrency: tests/Support/booking-worker.php.
Jalankan: php tests/run.php.

Worker PHP menggunakan akun database uji yang sama, guard lingkungan testing, dan waktu WIB fixture. Argumen worker hanya berisi ID fixture, waktu uji, dan key sintetis; tidak membawa kredensial atau data pelanggan asli.

Skenario yang diperiksa:

- Durasi dua slot dan seluruh reservasinya.
- Retry urutan ID terbalik mengembalikan booking sama; payload berbeda ditolak.
- Pilihan tidak berurutan, slot dimulai, slot ditutup, dan konflik sebagian ditolak tanpa booking sebagian.
- Konfirmasi penjaga luar venue ditolak.
- Konfirmasi tepat waktu tetap confirmed setelah tenggat pending.
- Pembatalan menjaga hubungan historis dan melepas reservasi.
- Penutupan sesudah pembatalan tetap memblokir pemesanan.
- Pending dekat waktu mulai memiliki tenggat lebih pendek.
- Konfirmasi tepat pada tenggat ditolak dan status menjadi expired.
- Slot pertama yang telah mulai tetap unavailable; slot berikutnya yang dilepas dapat tersedia.
- Replay booking expired tetap expired.
- Booking WhatsApp pengelola dapat langsung confirmed.
- Pembatalan setelah mulai ditolak.
- Dua proses memesan slot sama dengan key berbeda: satu berhasil dan satu mendapat konflik.
- Dua proses mengirim ulang key/payload sama: keduanya memperoleh satu ID booking yang sama.
- Dua proses konfirmasi dan expiry pada tenggat: hasil expired, reservasi dilepas, satu event expiry.

Pengujian race dijalankan melalui proc_open dengan dua koneksi MySQL dan waktu mulai bersama. Ini bukti skenario simultan yang diuji, bukan load test untuk seluruh kombinasi transaksi atau jaminan tidak ada deadlock pada beban produksi.

## Batas tahap ini

Hasil verifikasi akhir: **36 tests, 501 assertions lulus**, termasuk tiga pasangan proses bersamaan. Lint lima file PHP terkait dan git diff --check lulus. Snapshot operasional utama sebelum/sesudah sama; schema dan akun sementara dibersihkan. Hash log aplikasi utama tetap sama seperti tahap sebelumnya. Tidak ada build asset karena tidak ada perubahan frontend.

- Schema masih prototype tahap 2 yang dijalankan hanya pada database uji. Database utama tidak dimigrasikan.
- Public booking existing masih memakai alur lama. Perlindungan baru berlaku pada service baru sampai integrasi tahap 5–6 dilakukan.
- Daftar pending/tombol konfirmasi/dashboard dan formulir publik baru belum dibuat.
- expireForVenue dapat dipanggil oleh proses otomatis; pemasangan scheduler setiap menit dan pemantauannya tetap tahap 7. Belum ada klaim background expiry aktif pada aplikasi utama.
- Slot generation, aktivasi court/venue, dan data resmi tetap mengikuti tahap 3 dan hasil audit.
- Tidak menormalisasi atau menandai kedaluwarsa pending legacy berdasarkan asumsi.
- Browser testing tidak dilakukan karena tidak ada perubahan antarmuka atau route baru.
- Backup/restore produksi dan deployment tetap menunggu prosedur cutover.

## File tahap ini

- Dibuat: app/Services/HourlyBooking.php
- Diubah: app/Services/CourtOperations.php
- Dibuat: tests/Database/HourlyBookingTest.php
- Dibuat: tests/Support/booking-worker.php
- Diubah: tests/run.php
- Dibuat: docs/qa/hourly-booking-stage-4-lifecycle.md

Tahap 5 belum dimulai.

Rekomendasi commit:

    feat(booking): implement atomic multi-slot booking lifecycle
