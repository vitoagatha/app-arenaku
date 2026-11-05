# Arenaku tahap 6 — Booking publik per jam

5 November 2026. Implementasi terhubung ke service tahap 3–4 dan diuji pada database terisolasi. Aktivasi venue mengikuti cutover setelah tahap 7.

## Alur pelanggan

- Halaman jadwal existing membuka pilihan per jam untuk venue yang aktif. Rentang tanggal mengikuti pengaturan venue dan dihitung dalam WIB, termasuk hari ini.
- Semua court aktif dan slot pada tanggal terpilih tampil dengan status tekstual. Pilihan beberapa jam harus berurutan pada satu court.
- Ringkasan pilihan menghitung durasi dan perkiraan batas konfirmasi. Setelah pengiriman, receipt menampilkan tenggat sebenarnya yang ditetapkan server.
- Form memakai nama dan nomor WhatsApp. Service menghitung durasi dari slot dan memeriksa seluruh pilihan dalam satu transaksi.
- POST berhasil mengarah ke receipt dengan HTTP 303. UUID pengiriman dan scope sesi yang ditetapkan server menjaga retry pada booking yang sama.
- Receipt menampilkan jadwal, status, tenggat, dan kontak pengelola yang dikonfigurasi. Akses terikat ke sesi pembuat dan UUID. Sesi lain mendapat 404. Respons memakai no-store dan no-referrer.
- Receipt tetap dapat dibuka dari sesi pembuat setelah penerimaan booking venue dinonaktifkan, selama schema tersedia.
- Pembaruan slot berlangsung setiap 30 detik dan saat tab aktif kembali. Jam server menjadi acuan timer lokal untuk menandai slot saat waktu mulai tiba.
- Pilihan yang kedaluwarsa atau dipesan orang lain tetap terlihat sebagai pilihan bermasalah sampai pengguna menghapusnya. Dengan demikian, formulir tidak diam-diam mengirim sebagian dari pilihan.
- Kegagalan pembaruan menampilkan pesan dan mempertahankan isian. Server selalu mengevaluasi kembali jadwal ketika permintaan dikirim.

## Peralihan dari alur lama

Form legacy mengikuti jalur existing untuk venue legacy. Pada venue hourly, URL konfirmasi lama kembali ke pilihan jadwal, dan POST legacy ditolak setelah venue dikunci dan flag dibaca ulang. Penulis lama dan baru menggunakan kunci venue yang sama untuk pemeriksaan tersebut.

Schema utama dan aktivasi venue tetap menjadi pekerjaan cutover. Scheduler dan monitoring berada pada tahap 7.

## Verifikasi

- Suite MySQL terisolasi lulus 39 tes dan 625 assertions. Termasuk tanggal di luar rentang, booking dua slot, retry, penolakan data aktor/status yang dikirim pengunjung, akses lintas sesi, konflik tanpa booking sebagian, expiry, dan penolakan POST legacy pada venue hourly.
- Suite awal menemukan regresi parse Blade pada komponen pilihan jam dashboard dari tahap 5. Direktif yang berdempetan dipisahkan, lalu tes render dashboard kembali lulus.
- Lint PHP dan pemeriksaan sintaks JavaScript lulus. Snapshot operasional source sama sebelum dan sesudah suite. Akun dan schema sementara dibersihkan.
- Pratinjau browser memakai hasil render fixture anonim dengan submit dinonaktifkan. Jadwal dan receipt diperiksa pada lebar 360, 390, 768, 1024, dan 1440 piksel. Tidak ditemukan overflow dokumen, label input yang hilang, atau gambar rusak pada fixture tersebut.
- Keyboard dan checkbox menghasilkan ringkasan dua jam. Respons ketersediaan simulasi mengubah slot menjadi Dipesan, dan simulasi jam server menandai slot tepat saat mulai. Respons rusak menampilkan pesan pembaruan gagal sambil mempertahankan pilihan dan isian nama.
- Submit nyata diverifikasi lewat tes HTTP terisolasi. Pratinjau browser menguji tampilan dan interaksi klien, tanpa submit melalui database utama.

## File terkait

- app/Http/Controllers/PublicHourlyBookingController.php
- app/Http/Controllers/CMSController.php
- app/Http/Controllers/BookingController.php
- routes/web.php
- resources/views/cms/hourly-booking.blade.php
- resources/views/cms/hourly-receipt.blade.php
- resources/views/components/arenaku/form-errors.blade.php
- resources/views/components/dashboard/hour-select.blade.php
- public/css/arenaku-public.css
- public/js/arenaku-hourly-public.js
- tests/Database/PublicHourlyBookingTest.php
- tests/run.php

Pesan commit yang disarankan

    feat(booking): connect public hourly reservations and session receipts
