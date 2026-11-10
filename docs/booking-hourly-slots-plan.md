# Spesifikasi booking per jam Arenaku

Diperbarui 10 November 2026 berdasarkan implementasi dan pengujian sampai 9 November. Implementasi selesai dan pilot Giri Krida Court A aktif pada database lokal. Venue lain memerlukan aktivasi tersendiri.

## Jadwal dan ketersediaan

- Satu venue memiliki beberapa court fisik. Jam reguler sama setiap hari, dalam WIB.
- Jam buka, tutup, pengecualian, serta slot memakai jam bulat. Operasional dan satu booking berada dalam satu tanggal. Operasional lintas tengah malam belum didukung.
- Rentang pemesanan default 14 hari termasuk hari ini, dapat diatur admin menjadi 1–365 hari. Rentang bergeser setiap hari.
- Slot berdurasi satu jam dibentuk dari jam buka hingga jam tutup. Pembentukan ulang bersifat idempotent dan mempertahankan reservasi serta penutupan.
- Pengecualian tanggal dapat menetapkan jam khusus atau libur sehari penuh. Penutupan khusus court memiliki interval, alasan, dan riwayat pembukaan kembali.
- Semua slot ditampilkan. Slot yang sudah mencapai waktu mulai tampil Tidak tersedia, termasuk ketika belum dipesan.
- Ketersediaan diperiksa dari waktu server, jam operasional efektif, penutupan, court aktif, dan reservasi aktif. Perubahan operasional yang bertabrakan dengan reservasi harus ditolak.

## Pemesanan publik

1. Pengunjung memilih venue dan tanggal, lalu satu atau beberapa jam berurutan pada court yang sama.
2. Form menghitung waktu dan durasi dari slot. Nama dan nomor WhatsApp diperlukan.
3. Server memvalidasi seluruh pilihan dan membuat booking beserta penahanan semua slot dalam satu transaksi.
4. Booking berstatus pending sampai pengelola mengonfirmasi. Batasnya adalah waktu pembuatan ditambah 60 menit atau jam mulai slot pertama, mana yang lebih dahulu.
5. Konfirmasi harus terjadi sebelum tenggat. Saat atau setelah tenggat, seluruh booking kedaluwarsa dan reservasi aktif dilepas.
6. Slot yang dilepas hanya tersedia kembali jika belum mulai dan memenuhi seluruh aturan operasional.

Slot yang tidak berurutan, berbeda court, atau berbeda tanggal harus dipesan terpisah. Request bersamaan tidak boleh menghasilkan booking ganda atau menyimpan sebagian pilihan. Pengiriman ulang memakai idempotency key yang terikat konteks pemesan dan payload.

Halaman pemesan tidak memerlukan login. Link status memerlukan sesi browser asal dan key yang cocok. Sesi baru, perangkat lain, atau cookie yang hilang tidak mendapat akses ke bukti tersebut. Halaman tidak menampilkan data pribadi pemesan.

Judul pending adalah Menunggu konfirmasi. Kotak informasinya berisi Menunggu konfirmasi pengelola. Form menjelaskan aturan penahanan, sementara tanggal/jam tenggat tersedia di dashboard pengelola. Tombol Perbarui status membaca kondisi terbaru. Sesuai revisi pengguna, ringkasan pilihan dan bukti pending tidak menampilkan perkiraan atau tanggal/jam tenggat.

## Booking pengelola dan pembatalan

Booking langsung atau WhatsApp dicatat sebagai booking dengan sumber walk_in atau whatsapp, nama, nomor kontak, pilihan slot, dan pelaku pencatatan. Pengelola dapat mengonfirmasi sesuai alur yang tersedia. Status confirmed menyatakan persetujuan jadwal, tanpa menyatakan pembayaran.

Pembatalan melalui pengelola berlaku untuk seluruh slot dalam booking sebelum waktu mulai. Alasan, pelaku, dan waktu dicatat. Booking dan hubungan slot tetap menjadi riwayat, sedangkan reservasi aktif dilepas. Pembatalan sebagian, pembatalan mandiri pelanggan, pembayaran, dan refund belum termasuk fitur ini.

## Hak akses

| Tindakan | Admin | Penjaga |
| --- | --- | --- |
| Jam reguler, rentang pemesanan, kontak, verifikasi court | Semua venue | Tidak tersedia |
| Pengecualian tanggal dan penutupan court | Semua venue | Venue penugasan sesuai pembatasan pelaku pada implementasi |
| Membuka kembali penutupan | Semua venue | Sesuai kepemilikan tindakan yang diizinkan |
| Booking, konfirmasi, pembatalan | Semua venue | Venue penugasan |
| Pengguna dan label court global | Tersedia | Tidak tersedia |
| Pemantauan proses otomatis | Tersedia | Tidak tersedia |

Penjaga tidak mendapat izin khusus mengubah jam reguler. Perubahan yang dibuat admin pada pengecualian atau penutupan dilindungi dari perubahan penjaga sesuai layanan. Pembatasan akses diperiksa server.

## Pembaruan tampilan dan proses otomatis

Ketersediaan publik diperbarui setiap 30 detik pada tab aktif dan saat tab kembali terlihat. Waktu server menjadi acuan penandaan slot mulai. Pilihan yang menjadi tidak sah dipertahankan untuk ditinjau pengguna, dengan pesan kegagalan jika pembaruan koneksi gagal. Server memvalidasi ulang seluruh slot saat submit.

Scheduler memeriksa pending setiap menit. Pemeriksaan pembentukan slot berjalan setiap 15 menit dalam WIB sampai berhasil pada hari itu. Penutupan, reservasi, dan histori tetap dipertahankan saat proses mengejar tanggal terlewat. Panel admin memberi peringatan bila sukses expiry lebih lama dari tiga menit, backlog ada, atau pembentukan hari itu belum berhasil.

## Transisi data lama

Jadwal dan booking legacy dipertahankan dengan nilai waktu, status, durasi, dan hubungan aslinya. Pending legacy tidak otomatis dikonfirmasi atau diberi tenggat baru. Inventaris court, jam resmi, dan kontak harus diverifikasi sebelum aktivasi. Booking atau penutupan legacy mendatang memblokir aktivasi sampai direkonsiliasi.

Interval lama yang tidak sesuai jam bulat membutuhkan peninjauan seluruh slot yang beririsan. Jangan mengubah durasi pelanggan atau melepaskan reservasi berdasarkan asumsi. Command aktivasi saat ini menolak konflik legacy yang masih berlaku.

## Acuan

- [Arsitektur dan integritas data](booking-hourly-architecture.md)
- [Operasional, aktivasi, dan pemulihan](operations.md)
- [Hasil penerimaan dan batas verifikasi](qa/acceptance.md)
