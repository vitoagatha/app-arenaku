# Arenaku — Tahap 1: Audit Ulang Jadwal dan Booking Per Jam

Tanggal audit: 4 November 2026, Asia/Jakarta (WIB). Snapshot database: **12.31.14 WIB**.

Baseline Git: `4ba6c89`. Sebelum audit, `docs/booking-hourly-slots-plan.md` sudah ada sebagai file untracked; file tersebut dipertahankan tanpa perubahan dalam tahap ini.

**Status: audit tahap 1 selesai. Belum ada implementasi tahap 2, migrasi, atau perubahan data aplikasi.** Hasil ini bukan pernyataan aplikasi sudah aman atau siap mengaktifkan booking per jam.

Acuan kebutuhan: [kesimpulan jadwal dan booking per jam](../booking-hourly-slots-plan.md). Pembanding historis: [audit Phase 5A, 2 November 2026](phase-5-audit.md).

## 1. Ringkasan hasil

1. Database sekarang berisi **2 venue, 3 label court, 13 jadwal, dan 3 booking**. Dibandingkan audit 2 November, terdapat tambahan satu jadwal dan satu booking. Audit ini tidak membuat tambahan tersebut dan tidak menyimpulkan siapa pembuatnya.
2. Seluruh 13 jadwal telah melewati waktu mulai, termasuk jadwal tanggal audit pukul 00.00–01.00. Tidak ada jadwal atau booking dengan waktu main mendatang pada snapshot.
3. Sembilan jadwal masa lalu masih berstatus `ready`; kode publik dan backend belum memeriksa batas mulai slot.
4. Konflik durasi dan konflik status yang dilaporkan sebelumnya ternyata berada pada **booking yang sama, ID 1 / jadwal ID 16**: durasi booking 1 jam, interval jadwal 18.00–21.00, status booking pending, status jadwal closed.
5. Label Court A digunakan pada dua venue. Court C belum pernah dikaitkan dengan venue melalui jadwal. Daftar court fisik per venue harus ditetapkan sebelum pembentukan jadwal otomatis.
6. Tidak ditemukan record yatim, interval bertumpuk, interval identik ganda, atau beberapa booking pada satu jadwal dalam snapshot. Namun, kode dan constraint database belum mencegah kondisi tersebut muncul.
7. Route management booking/court belum memiliki middleware autentikasi. Booking tidak dibatasi berdasarkan venue penjaga, dan pengelolaan pengguna belum memiliki pembatasan admin. Temuan kode ini harus ditangani sebelum fitur baru diaktifkan.
8. Tidak ada scheduled task yang terdaftar. Database belum memiliki tenggat pending, sumber booking, hubungan beberapa slot, pengecualian operasional, atau riwayat pembatalan.

## 2. Metode, batas akses, dan bukti

| Pemeriksaan | Metode | Batas kesimpulan |
| --- | --- | --- |
| Controller, model, route, migration, form, JavaScript | Pembacaan source dan pencarian terarah | Perilaku tulis disimpulkan dari kode, tidak dieksekusi |
| Skema dan data aktual | PDO MySQL, SELECT dan metadata schema dalam transaksi READ ONLY | Snapshot satu waktu; bukan pengujian concurrency |
| Middleware route | `php artisan route:list --json`, hasil dipilih menurut route terkait | Tidak mengirim request HTTP atau menyamar sebagai pengguna |
| Definisi scheduler | `php artisan schedule:list` dan pembacaan console kernel | Tidak menjalankan task dan tidak mengaudit konfigurasi Task Scheduler/cron OS |
| Lingkungan database uji | Driver PDO, `phpunit.xml`, keberadaan `.env.testing`, bootstrap tests | Tidak menjalankan test yang menulis data |
| Perubahan berkas | Status Git dan hash log sebelum/sesudah pemeriksaan | Audit hanya menambah laporan ini |

Koneksi menggunakan konfigurasi lokal yang dibaca di memori; kredensial tidak dicetak. Query tidak mengambil nama pelanggan, nomor WhatsApp, email, password, session, atau cookie. Laporan menggunakan ID record serta waktu jadwal untuk rekonsiliasi; keberadaan informasi pemesan hanya diperiksa sebagai boolean kosong/tidak kosong.

Pengaman koneksi:

```sql
SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
SET SESSION TRANSACTION READ ONLY;
START TRANSACTION;
-- SELECT metadata dan data operasional yang diperlukan.
ROLLBACK;
```

MySQL melaporkan `@@session.transaction_read_only = 1`. Transaksi diakhiri dengan rollback. Tidak ada INSERT, UPDATE, DELETE, ALTER, CREATE, seeding, migrate, pengiriman form, atau eksekusi job bisnis.

Percobaan harness pertama berhenti sebelum koneksi karena helper path Laravel memerlukan instance aplikasi. Harness diperbaiki hanya di memori dengan memuat bootstrap, tanpa menjalankan request atau memodifikasi source. Koneksi dari sandbox berikutnya gagal dengan PDO 2002; pembacaan ulang di luar sandbox yang telah disetujui berhasil. Error tidak digunakan untuk menyimpulkan database rusak.

## 3. Lingkungan yang terverifikasi

| Bagian | Hasil |
| --- | --- |
| PHP CLI | 8.2.34 |
| MySQL aktual | 8.0.30 |
| Engine tabel operasional | InnoDB |
| Timezone aplikasi | Asia/Jakarta |
| Timezone session MySQL | SYSTEM; system timezone dilaporkan `SE Asia Standard Time` |
| Driver PDO tersedia | mysql; tidak ada sqlite |
| Cache konfigurasi Laravel | `bootstrap/cache/config.php` tidak ada |
| Database uji | `.env.testing` tidak ada; XML masih menunjuk SQLite `:memory:` |
| Migration tercatat | 7 migration; nama sesuai tujuh file migration yang ada |
| Scheduler aplikasi | Tidak ada scheduled task terdaftar |

Waktu audit dan klasifikasi masa lalu/mendatang menggunakan `DateTimeImmutable` dengan zona Asia/Jakarta yang dikirim sebagai parameter query. Tidak mengasumsikan timestamp lama disimpan dalam UTC atau menggeser timestamp existing. Konvensi penyimpanan timestamp harus ditetapkan dalam rancangan migrasi.

## 4. Inventaris dan perubahan sejak audit sebelumnya

| Pemeriksaan | 2 November 2026 | 4 November 2026 |
| --- | ---: | ---: |
| Pengguna | 4 | 4 |
| Admin / penjaga | 1 / 3 | 1 / 3 |
| Venue | 2 | 2 |
| Label court global | 3 | 3 |
| Jadwal | 12 | 13 |
| Booking | 2 | 3 |
| Jadwal ready / booking / closed | 9 / 1 / 2 | 9 / 2 / 2 |
| Booking pending / confirmed / canceled | 2 / 0 / 0 | 3 / 0 / 0 |
| Durasi booking tidak sesuai interval | 1 | 1 |
| Booking pending/confirmed pada jadwal closed | 1 | 1 |
| Interval bertumpuk | 0 | 0 |
| Satu jadwal memiliki beberapa booking | 0 | 0 |

Semua booking saat ini terkait venue ID 6. Penjaga dengan penugasan venue ID 7 dan penjaga tanpa penugasan tidak seharusnya memperoleh ketiga record tersebut. Query list booking saat ini tidak membatasi penugasan; tidak dilakukan request dengan akun lain untuk mengulangi pembuktian akses browser.

## 5. Pemetaan venue dan court

### 5.1. Venue dan pasangan yang benar-benar tercatat

| Venue | Penugasan saat ini | Court dari jadwal existing | Jumlah jadwal |
| --- | --- | --- | ---: |
| ID 6 — Giri Krida | Satu akun penjaga | Court A, label ID 1 | 4 |
| ID 7 — GOR Bulutangkis Semen Gresik | Satu akun penjaga | Court A, label ID 1 | 6 |
| ID 7 — GOR Bulutangkis Semen Gresik | Akun penjaga yang sama | Court B, label ID 2 | 3 |

Kandidat pemetaan `venue_courts` yang didukung record:

- `(lapangan_id=6, court_id=1)` menjadi unit Court A milik venue 6.
- `(lapangan_id=7, court_id=1)` menjadi unit Court A milik venue 7, berbeda dari unit venue 6.
- `(lapangan_id=7, court_id=2)` menjadi unit Court B milik venue 7.

Ketiganya merupakan kandidat berdasarkan pemakaian historis, bukan sertifikasi bahwa inventaris fisik venue sudah lengkap atau masih sama saat ini.

### 5.2. Court yang belum dapat dipetakan

Court C, label ID 3, memiliki **0 jadwal dan 0 venue teramati**. Jangan otomatis menambahkan Court C ke setiap venue. Pengelola perlu menentukan apakah label ini belum digunakan, milik salah satu venue, atau merupakan court yang akan ditambahkan.

Tabel `courts` tidak memiliki `lapangan_id`, sementara `Lapangan::courts()` memakai `hasMany(Court::class)` yang mengasumsikan foreign key tersebut. Relasi ini tidak dapat menjadi sumber pembentukan slot dalam keadaan sekarang.

### 5.3. Pengaturan dasar yang tidak dapat diambil dari data lama

Belum ada kolom jam reguler atau rentang pemesanan. Minimum/maksimum jam pada jadwal lama bukan bukti jam buka/tutup resmi. Contoh venue 6 memiliki jadwal 00.00–01.00; jangan menggunakannya untuk menyimpulkan venue harus buka tengah malam.

Sebelum pembentukan slot baru, admin perlu menetapkan jam buka dan tutup masing-masing venue serta memverifikasi daftar court. Rentang awal 14 hari sudah disepakati dan tidak perlu ditentukan ulang.

## 6. Inventaris jadwal dan klasifikasi transisi

Seluruh waktu di bawah ditafsirkan sebagai WIB. Semua interval sudah berakhir pada saat snapshot.

| ID jadwal | Venue / court label | Tanggal | Interval | Status | Booking terkait |
| --- | --- | --- | --- | --- | --- |
| 16 | 6 / 1 | 2024-07-10 | 18.00–21.00 | closed | 1 |
| 18 | 6 / 1 | 2024-07-11 | 09.00–12.00 | closed | Tidak ada |
| 31 | 7 / 1 | 2024-07-12 | 07.00–09.00 | ready | Tidak ada |
| 32 | 7 / 1 | 2024-07-12 | 09.00–12.00 | ready | Tidak ada |
| 33 | 7 / 1 | 2024-07-12 | 12.00–15.00 | ready | Tidak ada |
| 34 | 7 / 2 | 2024-07-13 | 07.00–09.00 | ready | Tidak ada |
| 35 | 7 / 2 | 2024-07-13 | 09.00–12.00 | ready | Tidak ada |
| 36 | 7 / 1 | 2024-07-14 | 07.00–09.00 | ready | Tidak ada |
| 37 | 7 / 1 | 2024-07-14 | 09.00–12.00 | ready | Tidak ada |
| 38 | 7 / 1 | 2024-07-15 | 07.00–09.00 | ready | Tidak ada |
| 39 | 7 / 2 | 2024-07-15 | 09.00–12.00 | ready | Tidak ada |
| 40 | 6 / 1 | 2026-10-23 | 08.00–09.00 | booking | 2 |
| 41 | 6 / 1 | 2026-11-04 | 00.00–01.00 | booking | 3 |

Distribusi interval: **2 jadwal satu jam, 4 jadwal dua jam, dan 7 jadwal tiga jam**. Sebelas jadwal belum berbentuk slot satu jam meskipun seluruh batas waktu sudah berada pada jam bulat.

Klasifikasi yang disarankan:

| Kelompok | Record | Perlakuan saat merancang transisi |
| --- | --- | --- |
| Riwayat booking konsisten secara durasi | Booking 2/jadwal 40 dan booking 3/jadwal 41 | Pertahankan record serta hubungan historis; jangan membuat reservasi mendatang atau otomatis menyatakan confirmed/expired |
| Riwayat booking dengan konflik | Booking 1/jadwal 16 | Pertahankan nilai asli dan tandai perlu rekonsiliasi; jangan memecah menjadi booking tiga jam berdasarkan asumsi |
| Riwayat penutupan tanpa booking | Jadwal 18 | Pertahankan penutupan untuk tanggal aslinya; alasan dan pembuat penutupan tidak tersedia |
| Riwayat jadwal kosong | Jadwal 31–39 | Pertahankan riwayat; ready lama tidak boleh dianggap dapat dipesan sekarang |
| Booking/jadwal mendatang | Tidak ada pada snapshot | Tidak ada pemetaan reservasi masa depan yang dapat dilaksanakan dari data ini |

Booking/jadwal lama tetap dapat disimpan sebagai riwayat legacy tanpa memaksakan normalisasi satu jam untuk data masa lalu. Detail mekanisme penyimpanan riwayat diputuskan pada tahap 2. Jika record lama dimasukkan ke struktur slot baru, pemetaan harus tetap mempertahankan interval asli dan tidak mengubah arti durasi.

Jadwal 41 adalah 00.00–01.00 pada satu tanggal, bukan interval lintas tengah malam. Waktu ini tetap tidak boleh dijadikan default jam reguler karena konteks operasionalnya belum diketahui.

## 7. Daftar rekonsiliasi booking

| Booking | Kondisi terverifikasi | Keputusan yang diperlukan |
| --- | --- | --- |
| ID 1 | Jadwal 16, 10 Juli 2024, 18.00–21.00; durasi booking 1 jam; booking pending; jadwal closed | Jika akan dinormalisasi: tentukan interval pemesanan sebenarnya dan makna penutupan. Jika bukti tidak tersedia, pertahankan sebagai riwayat ambigu dengan nilai asli |
| ID 2 | Jadwal 40, 23 Oktober 2026, 08.00–09.00; durasi 1 jam; booking pending; jadwal booking | Durasi cocok; jangan menyimpulkan sudah dibayar, dikonfirmasi, atau harus dibatalkan dari status pending lama |
| ID 3 | Jadwal 41, 4 November 2026, 00.00–01.00; durasi 1 jam; booking pending; jadwal booking | Pertahankan sebagai riwayat; konteks waktu 00.00 tidak ditafsirkan sebagai jam buka venue |

Nama dan kontak ketiga booking terisi secara tekstual. Ini bukan verifikasi nomor aktif, kepemilikan nomor, identitas pelanggan, atau pembayaran.

**Penting:** belum ada alasan untuk menjalankan aturan pending 60 menit secara retroaktif pada ketiga booking. Tidak ada `expires_at` dan tidak ada jejak konfirmasi/pembatalan lama untuk membuktikan lifecycle sebenarnya. Tenggat baru digunakan untuk alur baru setelah transisi yang jelas.

## 8. Integritas dan skema database aktual

### 8.1. Hasil pemeriksaan integritas

| Pemeriksaan | Hasil |
| --- | ---: |
| Jadwal sudah mulai | 13 |
| Jadwal mendatang | 0 |
| Jadwal ready yang sudah mulai | 9 |
| Batas jam dengan menit/detik bukan nol | 0 |
| Akhir <= awal / kandidat lintas hari | 0 |
| Interval identik ganda | 0 |
| Pasangan interval bertumpuk | 0 |
| Jadwal dengan beberapa booking | 0 |
| Venue tanpa pemilik yang valid | 0 |
| Jadwal tanpa venue/court yang valid | 0 / 0 |
| Booking tanpa jadwal yang valid | 0 |
| Jadwal booking tanpa booking pending/confirmed | 0 |
| Booking pending/confirmed pada jadwal ready | 0 |
| Booking pending/confirmed pada jadwal closed | 1 |
| Durasi booking null, nol, atau negatif | 0 |

### 8.2. Constraint yang ada dan yang belum ada

- Tabel operasional memiliki primary key ID dan indeks foreign key biasa.
- Tidak ada unique constraint untuk kombinasi venue/court/tanggal/jam mulai.
- `konfirmasi_bookings.jadwal_lapangan_id` bukan unique; relasi `hasOne` pada model tidak mencegah beberapa booking pada tingkat database.
- Status jadwal hanya `ready`, `booking`, `closed`.
- Status booking hanya `pending`, `confirmed`, `canceled`; belum mendukung expired.
- Tidak ada struktur booking beberapa slot, reservasi aktif terpisah, tenggat konfirmasi, pelaku konfirmasi/pembatalan, sumber booking, jam reguler, pengecualian, alasan penutupan, atau activity log bisnis.
- Tidak ditemukan kolom harga/tarif pada skema domain yang diperiksa. Perhitungan harga tidak boleh dimasukkan sebagai fitur existing.

Foreign key aktual memakai `ON DELETE CASCADE` pada rantai berikut:

```text
users -> lapangan -> jadwal_lapangan -> konfirmasi_bookings
courts -> jadwal_lapangan -> konfirmasi_bookings
```

Artinya, penghapusan akun/venue/court/jadwal dapat menghapus riwayat booking melalui database. Tidak ada penghapusan yang diuji pada audit ini. Rancangan tahap 2 harus melindungi riwayat sebelum menawarkan penonaktifan atau perubahan lifecycle baru.

## 9. Audit akses dan perilaku backend

Status di bagian ini merupakan **bukti source + metadata route aktual**, bukan pengujian eksploitasi atau request mutasi.

| ID | Prioritas | Temuan | Bukti utama | Dampak pada rencana |
| --- | --- | --- | --- | --- |
| S1-01 | Critical | Route management booking/court hanya middleware web; tidak ada autentikasi atau pemeriksaan role di controller terkait | `routes/web.php`; `BookingController`; `CourtController`; output route:list | Pisahkan public booking dari seluruh tindakan management yang wajib autentikasi/otorisasi |
| S1-02 | Critical | Booking index mengambil seluruh record; edit/update/delete berdasarkan ID global tanpa scope venue | `BookingController.php:14`, `:48`, `:64`, `:80` | Terapkan akses venue pada setiap pembacaan dan mutasi, bukan hanya menu |
| S1-03 | Critical | Resource users hanya auth; controller menerima role admin/penjaga tanpa pemeriksaan admin | `routes/web.php:52`; `UserController.php:40`, `:106`, `:147`; `AuthServiceProvider` | Pembatasan role harus dibangun sebelum mengandalkan pemisahan admin/penjaga |
| S1-04 | High | Booking menerima ID jadwal existing tanpa validasi waktu/status; create booking dan update jadwal terpisah | `BookingController.php:18`; `CMSController.php:56` | Wajib transaksi, penguncian, pemeriksaan ketersediaan, dan pengaman pengiriman ulang |
| S1-05 | High | Update jadwal memeriksa venue asal, tetapi menerima lapangan_id tujuan dan payload request all | `JadwalController.php:62` | Jangan izinkan pemindahan ke venue lain tanpa otorisasi tujuan; identitas slot baru perlu dilindungi |
| S1-06 | High | Durasi booking input bebas integer; jadwal tanpa validasi urutan jam, interval satu jam, overlap, atau status yang ketat | `BookingController.php:24`, `:61`; `JadwalController.php:45`, `:62` | Hitung durasi dari slot; validasi server sesuai kontrak baru |
| S1-07 | High | Penghapusan booking langsung; jadwal/venue/court dapat menghapus riwayat melalui cascade | `BookingController.php:78`; foreign key aktual | Ganti alur bisnis dengan pembatalan tercatat dan perlindungan histori pada tahap implementasi |
| S1-08 | Medium | Court global dan relasi Lapangan::courts tidak cocok schema | `Court.php`; `Lapangan.php:36`; metadata columns | Tetapkan unit court per venue sebelum membuat slot |

Matriks route aktual:

| Kelompok | Middleware | Pemeriksaan tambahan di kode |
| --- | --- | --- |
| Home dan profile | web + auth | Home memakai role dan penugasan untuk count venue |
| Users resource | web + auth | Tidak ada pembatasan admin |
| Courts resource | web | Tidak ada pembatasan role/venue |
| Booking management | web | Tidak ada auth/role/venue scope |
| Public booking store | web | Public sesuai konsep, tetapi belum ada validasi ketersediaan/transaksi |
| Lapangan resource | web | Sebagian aksi memakai pencarian venue berdasarkan user; middleware auth tetap tidak ada |
| Jadwal management | web | Helper membatasi venue asal bagi penjaga; update tujuan belum dilindungi |

CSRF terdapat pada middleware web dan form. CSRF bukan pengganti autentikasi/otorisasi. Aksi guest yang kebetulan gagal karena mengakses user null juga bukan perlindungan akses yang benar.

Tidak ada policy mapping aktif pada AuthServiceProvider. Route registrasi publik masih ada melalui Auth::routes(); kebijakan pembuatan akun pengelola perlu diperhatikan saat memperbaiki akses, tanpa mengubah autentikasi dalam audit ini.

Penugasan venue saat ini memakai satu `lapangan.user_id`; venue baru otomatis dimiliki akun pembuat. Belum ada workflow reassignment dalam controller venue yang diperiksa. Jangan mengasumsikan admin sudah dapat menetapkan penjaga melalui UI existing.

## 10. Kesiapan frontend dan proses otomatis

| Area | Kondisi sekarang | Kebutuhan tahap berikutnya |
| --- | --- | --- |
| Pemilihan tanggal | POST, validasi required/date | Batas 14 hari dan WIB di server |
| Tampilan court | Group by court_id dari jadwal yang ditemukan | Daftar court fisik per venue, termasuk court yang belum punya jadwal lama |
| Slot dapat dipilih | Hanya mengecek status ready | Ketersediaan efektif berdasarkan waktu, penutupan, dan reservasi |
| Form booking | Satu jadwal_lapangan_id, nama, WhatsApp, durasi manual | Pilihan beberapa slot berurutan, durasi hasil perhitungan |
| Booking berhasil | View langsung dari respons POST | Pengaman kirim ulang dan akses ringkasan yang aman; status pending/tenggat/kontak pengelola |
| Status di modal jadwal | Pengelola bisa memilih ready/booking/closed | Pisahkan penutupan dari tindakan booking yang menghasilkan record pemesan |
| Pembaruan ketersediaan | Tidak ada polling atau timer batas slot pada JS yang diperiksa | Pembaruan 30 detik pada tab aktif, saat kembali, serta validasi final di server |
| Konfirmasi booking | Tidak ada route/action yang menyelesaikan pending | Tindakan terotorisasi sebelum expires_at |
| Kedaluwarsa | Tidak ada task/tenggat | Proses setiap menit dan pemeriksaan sinkron dalam transaksi |
| Pemantauan | Tidak ada catatan kesehatan proses bisnis | Keberhasilan terakhir, penumpukan, kegagalan, dan peringatan sesuai rancangan |

Form publik hanya memiliki pencegahan klik ulang pada browser. Itu membantu UI tetapi tidak melindungi request ulang atau dua pengguna bersamaan di server.

Kontak pengelola pada alur baru perlu berasal dari data yang dipilih secara eksplisit. Tabel venue belum memiliki kolom kontak khusus; jangan mengambil nomor akun atau membuat nomor WhatsApp tanpa menetapkan sumber dan kelayakan nomor untuk ditampilkan publik.

Tidak menjalankan scheduler, menambahkan cron/Task Scheduler, membuat queue job, atau mengubah frontend selama audit.

## 11. Keputusan dan pekerjaan persiapan untuk tahap 2

### Keputusan yang sudah final dari diskusi

- Interval satu jam; jam reguler sama setiap hari, dengan pengecualian tanggal dan penutupan per court.
- Rentang pemesanan awal 14 hari termasuk hari ini; acuan Asia/Jakarta, WIB.
- Jam reguler/rentang hanya admin; tidak ada izin khusus jam reguler bagi penjaga pada tahap awal.
- Booking beberapa slot harus berurutan pada satu court/tanggal.
- Pending maksimal 60 menit atau sampai waktu mulai booking, mana lebih dahulu.
- Konfirmasi pengelola, kedaluwarsa otomatis, pembatalan tercatat, dan booking offline memiliki record pemesan.

### Input operasional yang masih diperlukan

1. Jam buka/tutup resmi masing-masing venue. Jadwal lama bukan sumber yang cukup untuk mengisinya otomatis.
2. Verifikasi kandidat Court A venue 6, Court A/B venue 7, serta penempatan/status Court C dan court lain yang belum tercatat.
3. Penentuan sumber nomor kontak pengelola yang boleh ditampilkan publik untuk konfirmasi.
4. Bila booking 1 akan dinormalisasi, bukti interval dan makna status closed. Jika bukti tidak ada, usulan aman adalah mempertahankan nilai asli sebagai riwayat ambigu, tanpa menciptakan reservasi aktif.

Input tersebut tidak menghalangi penulisan rancangan skema tahap 2, tetapi menghalangi pengisian pengaturan nyata dan aktivasi jadwal baru jika belum tersedia.

### Rekomendasi urutan tahap 2

1. Siapkan database MySQL uji terisolasi dengan pengaman agar test tidak mengarah ke database development.
2. Rancang skema/indeks/relasi berdasarkan unit court fisik, histori, dan satu reservasi aktif per slot.
3. Tentukan strategi mempertahankan data legacy, ID dan timestamp, serta penghentian alur lama saat transisi.
4. Rancang dan uji otorisasi management sesuai role/venue, termasuk mutasi langsung dengan ID venue lain.
5. Rancang backup, simulasi migrasi, pemeriksaan hasil, dan pemulihan sebelum menyentuh data utama.

Tidak ada migration final, deployment, atau perbaikan source yang dilakukan sebagai bagian tahap 1.

## 12. Pemeriksaan yang dijalankan dan keterbatasan

| Pemeriksaan | Hasil |
| --- | --- |
| Pembacaan skema aktual dan snapshot konsisten READ ONLY | PASS; selesai dengan rollback |
| Pemeriksaan duplikasi, overlap, record yatim | PASS untuk snapshot; bukan jaminan pencegahan |
| Konsistensi durasi/status booking | FAIL; booking 1 memiliki dua konflik |
| Ketersediaan berbasis waktu pada kode existing | FAIL; ready tidak mempertimbangkan waktu mulai |
| Pembatasan akses management sesuai role/venue | FAIL pada pemeriksaan kode/route; tidak menjalankan eksploitasi |
| `php artisan route:list --json` | PASS; middleware aktual diperoleh |
| `php artisan schedule:list` | PASS sebagai perintah inspeksi; hasil tidak ada scheduled task |
| Database test siap untuk regression bisnis | NOT READY; PDO SQLite tidak tersedia, `.env.testing` tidak ada; database MySQL uji belum disiapkan |
| Tes tulis/concurrency/konfirmasi/kedaluwarsa | NOT TESTED pada tahap baca-saja ini |
| Login/browser/CRUD aktual | NOT TESTED; tidak diperlukan untuk audit data tahap 1 |
| Build asset dan regression visual yang telah lulus | Tidak diulang; tidak ada perubahan aplikasi |
| Kesehatan cron/Task Scheduler/worker OS | NOT TESTED; tidak menganggap proses OS aktif atau mati dari schedule:list saja |

Hash SHA-256 `storage/logs/laravel.log` sebelum dan sesudah pemeriksaan runtime tetap `BF19E1358236AF876B4C59ECA67B42AD82E6D3C68B8E5510815785C156E00290`. Tidak ada pembersihan atau pengubahan log.

Contoh query inti yang digunakan untuk rekonsiliasi tanpa data pribadi:

```sql
-- Jalankan hanya dalam transaksi READ ONLY seperti pada bagian 2.
SELECT b.id AS booking_id, b.jadwal_lapangan_id AS schedule_id,
       b.durasi AS duration_hours, b.status AS booking_status,
       j.lapangan_id AS venue_id, j.court_id, j.tanggal,
       j.jam_awal, j.jam_akhir, j.status AS schedule_status,
       TIME_TO_SEC(TIMEDIFF(j.jam_akhir, j.jam_awal)) / 3600 AS interval_hours
FROM konfirmasi_bookings b
LEFT JOIN jadwal_lapangan j ON j.id = b.jadwal_lapangan_id
ORDER BY b.id;

SELECT a.id AS schedule_a, b.id AS schedule_b
FROM jadwal_lapangan a
JOIN jadwal_lapangan b
  ON a.id < b.id
 AND a.lapangan_id = b.lapangan_id AND a.court_id = b.court_id
 AND a.tanggal = b.tanggal
 AND a.jam_awal < b.jam_akhir AND b.jam_awal < a.jam_akhir;

SELECT lapangan_id, court_id, COUNT(*) AS schedule_count
FROM jadwal_lapangan
GROUP BY lapangan_id, court_id;
```

## 13. Deliverable dan status akhir

File dibuat: `docs/qa/hourly-booking-stage-1-audit.md`.

Dokumen rencana yang sudah ada, source aplikasi, vendor, migration, konfigurasi, dan data database tidak diubah.

**Tahap 1 selesai dengan temuan dan kebutuhan rekonsiliasi yang tercatat. Tahap 2 belum dimulai.** Persiapan desain tahap 2 dapat menggunakan laporan ini; migrasi dan aktivasi belum layak dijalankan sebelum input operasional, perlindungan akses, lingkungan uji, dan rencana transisi disiapkan.

Rekomendasi commit dokumentasi:

```text
docs(booking): audit existing data and access before hourly slot migration
```
