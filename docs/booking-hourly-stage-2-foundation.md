# Arenaku — Tahap 2: Skema, Transisi, dan Lingkungan Uji

Tanggal: 4 November 2026.

Status: rancangan skema telah diuji melalui simulasi pada MySQL sementara. Perbaikan akses management sudah diterapkan pada source. Database utama belum dimigrasikan dan alur booking per jam belum diaktifkan.

Acuan: [rencana booking per jam](booking-hourly-slots-plan.md), [audit tahap 1](qa/hourly-booking-stage-1-audit.md), dan [hasil implementasi tahap 2](qa/hourly-booking-stage-2-foundation.md).

## 1. Batas tahap ini

Tahap 2 menyiapkan struktur dan pengujian sebelum pembangunan aturan slot/booking baru. Tidak ada generator slot produksi, konfirmasi booking, kedaluwarsa, pembatalan tercatat, polling, atau scheduler baru yang diaktifkan.

Prototype migration berada di database/staging/2026_11_04_000001_hourly_booking_schema.php, bukan di database/migrations. Normal php artisan migrate tidak memuatnya. Prototype memeriksa lingkungan testing, nama database acak yang dibuat runner, dan identitas akun database uji. Tidak boleh menyalinnya ke migration produksi tanpa persiapan cutover, pemeriksaan data terbaru, dan penyesuaian layanan aplikasi.

## 2. Identitas venue dan court

Tabel courts tetap menjadi label legacy. Tabel venue_courts merupakan unit fisik reservasi:

| Kolom | Ketentuan |
| --- | --- |
| id | Identitas unit court fisik |
| lapangan_id | Foreign key ke venue, delete restrict |
| legacy_court_id | Label lama jika berasal dari pemetaan; nullable untuk court baru |
| name | Nama court yang ditampilkan |
| is_active | Default false sampai inventaris diverifikasi |
| created_at, updated_at | Jejak waktu record |

Kombinasi (lapangan_id, legacy_court_id) unik untuk kandidat pemetaan lama. Court A venue 6 berbeda dari Court A venue 7. Court C tidak otomatis ditambahkan karena tidak memiliki pasangan venue yang teramati.

Pembuatan ulang kandidat tidak mengaktifkan court dan tidak menggandakan pasangan. Penentuan court sebenarnya tetap memerlukan pengelola.

## 3. Pengaturan operasional

Tambahan pada lapangan:

| Kolom | Default dan aturan |
| --- | --- |
| regular_opens_at, regular_closes_at | Keduanya nullable sebelum dikonfigurasi; jika diisi harus jam bulat dan buka < tutup |
| booking_window_days | 14; positif |
| booking_contact | Nullable sampai sumber kontak publik ditetapkan |
| hourly_booking_enabled | False; bukan otomatis aktif setelah migration |

operating_exceptions memiliki satu record per venue dan tanggal: is_closed, jam buka/tutup khusus, alasan, created_by, updated_by, timestamps. CHECK membedakan libur sehari penuh tanpa jam dan jam khusus yang valid. Perubahan tetap harus dicatat ke activity log oleh layanan yang dibangun kemudian.

court_closures menyimpan court, starts_at, ends_at, alasan, created_by, reopened_at, reopened_by, serta alasan pembukaan kembali. Interval jam bulat, positif, dan dalam satu tanggal. Penutupan venue sehari penuh dapat menggunakan operating_exceptions. Penutupan court sehari penuh direpresentasikan sebagai interval operasional court pada tanggal itu.

Jangan menghitung jam reguler dari minimum/maksimum jadwal legacy. Jangan membuat nomor kontak, alasan penutupan, atau pelaku penutupan lama secara fiktif.

## 4. Slot, riwayat, dan reservasi aktif

### schedule_slots

- Foreign key venue_court_id.
- starts_at dan ends_at berupa DATETIME, merepresentasikan waktu lokal WIB untuk sistem satu zona ini.
- Unique (venue_court_id, starts_at).
- CHECK: durasi tepat 3.600 detik, jam mulai bulat, akhir pada tanggal yang sama.
- Tidak ada status ready/closed/booking yang dapat menimpa sumber kebenaran lain. Ketersediaan dihitung dari jam operasional, penutupan, waktu server, dan reservasi aktif.

### konfirmasi_bookings

Record existing dipertahankan bersama nama kolom lama. Tambahan:

| Kelompok | Kolom |
| --- | --- |
| Transisi | is_legacy, venue_court_id |
| Identitas alur | source: website / walk_in / whatsapp, created_by |
| Jadwal | starts_at, ends_at, durasi yang konsisten untuk booking baru |
| Pending | expires_at |
| Konfirmasi | confirmed_at, confirmed_by |
| Pembatalan | canceled_at, canceled_by, cancellation_reason |
| Pengiriman ulang | idempotency_key_hash, nullable unique |
| Status | enum lama dipertahankan dan ditambah expired |

jadwal_lapangan_id menjadi nullable. Booking legacy tetap menunjuk jadwal lama; booking baru tidak mengisi foreign key legacy.

Default is_legacy = true hanya melindungi transisi. Layanan baru wajib secara eksplisit membuat booking dengan is_legacy = false. Legacy writer harus dihentikan saat cutover, bukan dibiarkan terus membuat record legacy setelah fitur baru aktif.

CHECK untuk booking nonlegacy mengharuskan court, source yang dikenal, interval jam bulat dalam satu tanggal, durasi positif yang cocok dengan interval, serta expires_at yang tidak melewati mulai jika status pending. Baris legacy tidak dipaksa memenuhi kontrak baru karena dapat memiliki konflik yang belum terselesaikan.

Indeks (is_legacy, status, expires_at) disiapkan untuk pemindaian kedaluwarsa. Service expiry nanti wajib membatasi ke booking nonlegacy.

### booking_slots

- Primary key (booking_id, slot_id).
- Foreign key gabungan memastikan booking dan slot menunjuk venue_court_id yang sama.
- Tetap disimpan ketika booking kedaluwarsa atau dibatalkan.

### active_slot_reservations

- Primary key slot_id: maksimal satu penguasaan aktif per slot.
- Foreign key gabungan ke booking_slots: penguasaan aktif harus memiliki hubungan historis yang cocok.
- Tidak menduplikasi expires_at; tenggat berada pada booking.
- Record dapat dilepas dalam transaksi tanpa menghapus booking_slots.

Constraint tidak menggantikan service yang memeriksa slot berurutan, satu tanggal, durasi keseluruhan, waktu sekarang, status booking, dan penutupan. Kondisi lintas tabel/waktu berjalan tersebut termasuk tahap 3–4.

## 5. Riwayat dan kesehatan proses

activity_logs memuat venue, actor nullable untuk proses sistem, subject_type/subject_id, action, reason, changes JSON, dan occurred_at. Service harus membatasi isi changes; jangan mencatat password, token, kredensial, atau data pelanggan yang tidak diperlukan. Larangan edit/delete log ditegakkan pada layer aplikasi yang dibangun kemudian.

operational_task_runs memuat task, started_at, finished_at, status, processed_count, dan error_code tersanitasi. Ini baru struktur untuk pemantauan; belum ada task yang menulisnya atau UI peringatan.

Pada prototype, foreign key lama users → venue → jadwal → booking dan courts → jadwal diganti menjadi RESTRICT. Hubungan baru juga memakai RESTRICT. Penghapusan bisnis harus diganti dengan penonaktifan/pembatalan pada fase terkait; penambahan constraint tidak dengan sendirinya menyediakan UI pengganti.

## 6. Waktu dan transisi legacy

- Jadwal baru menggunakan DATETIME WIB secara konsisten; tidak ditafsirkan sebagai zona waktu browser.
- Jam reguler berupa TIME lokal; tanggal pengecualian berupa DATE lokal.
- Timestamp legacy dan nilai waktu aslinya dipertahankan. Tidak ada konversi UTC otomatis.
- Penyimpanan UTC di masa depan membutuhkan migrasi eksplisit dan berada di luar keputusan tahap ini.

Salinan simulasi mempertahankan ID, hubungan, interval, durasi, status, dan timestamp operasional source. Nama pengguna/pelanggan, nomor kontak, email, password, serta alamat venue asli tidak disalin. Data pengganti hanya berada di database uji.

Hasil audit tahap 1: 13 jadwal dan 3 booking seluruhnya masa lalu. Prototype tidak mengubah pending lama menjadi expired/confirmed, tidak membuat reservasi aktif untuknya, dan tidak memaksakan booking 1 menjadi tiga jam. Pemetaan pasangan venue/court tetap sebagai kandidat nonaktif.

## 7. Kontrak transaksi untuk tahap berikutnya

Semua operasi yang dapat berebut slot harus memakai satu urutan penguncian yang terdokumentasi. Implementasi transaksi belum dibuat pada tahap 2.

Rancangan alur:

1. Identifikasi dan otorisasi venue/court.
2. Kunci sumber aturan operasional dan record reservasi terkait dalam urutan tetap.
3. Evaluasi waktu server dan selesaikan penahanan yang benar-benar kedaluwarsa beserta seluruh slot booking terkait.
4. Periksa seluruh pilihan: court/tanggal sama, berurutan, jam sesuai, tidak ditutup, tidak dipesan.
5. Simpan booking, booking_slots, active_slot_reservations, serta catatan perubahan dalam satu transaksi.
6. Tangani bentrok unique key/deadlock sebagai kegagalan/retry terkendali; jangan menyimpan booking sebagian.
7. Idempotency key harus terikat ke konteks permintaan/pemesan dan payload. Jangan mengembalikan data pelanggan hanya karena seseorang mengetahui key yang digunakan pengguna lain.

Konfirmasi dan expiry harus menggunakan pemeriksaan status/tenggat serta penguncian yang sama. CHECK database tidak dapat membuktikan aturan waktu real-time atau hasil dua request bersamaan; pengujian itu dilakukan setelah service dibuat.

## 8. Prosedur penerapan dan pemulihan yang direncanakan

Ini runbook untuk penerapan nanti, bukan perintah yang telah dijalankan pada database utama.

### Sebelum cutover

1. Audit ulang source, termasuk record yang masuk sejak snapshot terakhir.
2. Selesaikan jam operasional, inventaris court, kontak publik, dan perlakuan data legacy ambigu.
3. Pastikan service baru, otorisasi, serta rollback operasional sudah diuji.
4. Hentikan sementara seluruh penulisan jadwal/booking lama dan worker terkait. Public read dapat dipertahankan jika tidak memberi kesan booking masih dapat dikirim.
5. Buat backup penuh database dalam lokasi akses terbatas, termasuk objek database yang digunakan. Jangan menyimpan backup berisi data pelanggan di Git.
6. Catat versi source/schema dan checksum backup; uji pemulihan backup ke database terpisah. Backup yang belum diuji restore bukan bukti pemulihan siap.
7. Catat jumlah record dan fingerprint operasional sebagai baseline cutover.

### Saat cutover

1. Jalankan migration produksi yang telah direview, bukan prototype testing secara langsung.
2. Peta record legacy dan kandidat court sesuai keputusan pengelola.
3. Verifikasi foreign key, jumlah record, status/timestamp lama, serta konflik yang memang dipertahankan.
4. Isi pengaturan resmi dan aktifkan hanya venue/court yang sudah diverifikasi.
5. Beralih ke satu writer baru; jangan menjalankan alur lama dan baru bersamaan pada venue yang sama.
6. Jalankan smoke test yang aman, pastikan scheduler dan pemantauannya aktif, lalu buka kembali penulisan.

### Jika gagal

- Sebelum penulisan baru dibuka: pertahankan penutupan penulisan, pulihkan backup terverifikasi dan versi aplikasi yang sesuai, lalu verifikasi data.
- Setelah menerima booking baru: jangan langsung memulihkan backup lama karena dapat menghapus booking setelah cutover. Hentikan penulisan, amankan perubahan terbaru, lalu lakukan rekonsiliasi dan keputusan pemulihan.
- Prototype down() sengaja menolak rollback destruktif terhadap tabel booking. Pemulihan simulasi menggunakan backup logis yang dibuat sebelum perubahan.
- MySQL DDL tidak dianggap dapat di-rollback bersama transaksi data biasa. Checkpoint dan backup diperlukan.

## 9. Yang sudah diuji dan yang masih menjadi syarat

Sudah diuji: schema rehearsal pada MySQL 8.0.30, retensi field legacy, kandidat court idempotent/nonaktif, CHECK jam/durasi, unique slot/reservasi/key, foreign key court yang sesuai, perlindungan penghapusan, retensi histori setelah melepas reservasi, dan restore backup logis anonim.

Belum diuji: backup/restore database utama penuh, migrasi produksi, service booking serentak, generator, expiry, scheduler, UI baru, dan beban produksi. Tidak ada harga/tarif atau pembayaran yang ditambahkan.

Tahap 3 dapat mulai membangun aturan operasional berdasarkan struktur ini setelah diinstruksikan. Aktivasi venue nyata tetap menunggu input operasional yang belum tersedia.
