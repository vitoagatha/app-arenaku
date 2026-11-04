# Arenaku — Tahap 3: Aturan Operasional dan Slot Per Jam

Tanggal: 4 November 2026.

Status: service backend tahap 3 diimplementasikan dan diuji pada schema sementara. Database utama belum dimigrasikan atau diaktifkan. UI dashboard/publik dan lifecycle booking baru belum dihubungkan.

## Implementasi

Service app/Services/CourtOperations.php menyediakan:

- configure: admin mengatur jam reguler bulat, jam buka lebih awal daripada tutup, rentang 1–365 hari, dan alasan perubahan. Default schema tetap 14 hari. Tidak otomatis mengaktifkan venue.
- exception: admin atau penjaga venue terkait mengatur jam khusus atau libur tanggal tertentu. Penjaga tidak dapat mengganti pengecualian milik admin atau yang terakhir diubah admin.
- closeCourt: menutup interval satu court dengan alasan, tanpa menimpa booking aktif.
- reopen: membuka kembali penutupan milik pengelola yang berwenang. Penutupan lain, jam operasional, waktu yang sudah mulai, dan reservasi tetap menentukan ketersediaan.
- generate: membuat slot satu jam untuk court aktif pada venue yang diaktifkan, mencakup hari ini sampai akhir rentang WIB. Aman diulang, tidak mengubah slot lama, penutupan, atau reservasi.
- availability: menyediakan seluruh slot tersimpan untuk tanggal yang sah, nama court, label, dan alasan operasional tanpa data pelanggan. Slot tidak tersedia tepat saat mulai.

Hari libur tetap memiliki grid berdasarkan jam reguler, dengan seluruh slot Tidak tersedia. Jam khusus dapat menambah slot ketika jam buka diperpanjang; slot lama di luar jam yang berlaku dipertahankan dan tidak dapat dipilih.

Semua operasi menggunakan transaksi dan kunci baris venue. Service booking tahap 4 harus mengikuti protokol kunci venue yang sama sebelum mutasi reservasi; transaksi service ini sendiri belum membuktikan keamanan writer booking lama.

Perubahan jam/pengecualian yang membuat reservasi aktif berada di luar operasional ditolak dan di-rollback. Penutupan yang beririsan dengan reservasi ditolak. Jejak pengaturan, pengecualian, serta tutup/buka court disimpan dalam activity_logs.

Pembacaan ketersediaan mengambil penutupan dan reservasi secara batch, bukan query terpisah untuk setiap slot.

## Pengujian

Jalankan php tests/run.php. Test baru berada di tests/Database/CourtOperationsTest.php dan dijalankan setelah rehearsal schema tahap 2. Seluruh operasi menggunakan fixture di MySQL sementara dengan akun yang tidak memiliki akses ke schema utama.

Skenario meliputi:

- Venue nonaktif tidak membentuk slot; konfigurasi tidak mengaktifkannya otomatis.
- Penjaga tidak mengubah jam reguler dan tidak mengakses venue lain.
- Dua court aktif membentuk 112 slot pada rentang 14 hari dengan jam 08.00–12.00; court nonaktif tidak ikut.
- Pengulangan menghasilkan nol duplikasi.
- Batas hari pertama/terakhir, WIB, dan pergantian hari.
- Slot tepat pukul 10.00 tidak tersedia sejak 10.00.
- Penutupan bertumpuk tetap berlaku setelah salah satunya dibuka.
- Penjaga tidak membuka penutupan/pengecualian admin.
- Hari libur, jam khusus, serta grid hari libur yang baru masuk rentang tetap terlihat.
- Perubahan reguler/libur/penutupan yang berbenturan dengan reservasi ditolak.
- Reservasi tidak hilang saat pembentukan ulang.
- Format menit bebas, jam terbalik, rentang nol, dan tanggal tidak sah ditolak.
- Audit log tersimpan.

## Batas dan tindak lanjut

Hasil akhir verifikasi: **35 tests, 450 assertions lulus** melalui runner MySQL terisolasi. Lint tiga file PHP terkait dan git diff --check lulus. Fingerprint operasional database utama sebelum/sesudah sama; database dan akun sementara dibersihkan. Hash log aplikasi utama tetap sama seperti tahap 2. Build asset dan browser testing tidak dijalankan karena tahap ini tidak mengubah tampilan atau menghubungkan endpoint baru.

- Service memakai schema prototype tahap 2. Belum ada migration produksi atau endpoint publik baru. Integrasi dashboard berada pada tahap 5; integrasi publik pada tahap 6.
- Jam resmi tiap venue, inventaris court termasuk Court C, dan kontak pengelola tetap menunggu verifikasi. Tidak membuat nilai operasional fiktif.
- Pending yang masih memiliki reservasi aktif diblokir secara konservatif, termasuk jika record tersebut sudah melewati tenggat. Pelepasan atomik, konfirmasi, dan kedaluwarsa menjadi tugas tahap 4; jangan menganggap expiry sudah aktif.
- Aktivasi court/venue belum memiliki endpoint; fixture hanya mengaktifkan record sintetis dalam database uji.
- Pembentukan slot dapat dipanggil berulang dan mengejar tanggal yang belum terbentuk. Penjadwalan otomatis beserta pemantauan proses dipasang pada tahap 7.
- Belum ada simulasi dua writer booking serentak; pengujian itu dilakukan saat service booking baru tersedia.
- Public booking lama masih menggunakan alur lama. Jangan mengaktifkan schema baru pada database utama sebelum cutover dan integrasi disiapkan.
- Tidak ada perubahan Blade, CSS, JavaScript, dependency, tarif, pembayaran, atau data utama pada tahap ini.

## File

- Dibuat: app/Services/CourtOperations.php
- Dibuat: tests/Database/CourtOperationsTest.php
- Diubah: tests/run.php untuk menyertakan test tahap 3
- Dibuat: docs/qa/hourly-booking-stage-3-operations.md

Tahap 4 belum dimulai.

Rekomendasi commit:

    feat(booking): add court operating rules and hourly slot generation
