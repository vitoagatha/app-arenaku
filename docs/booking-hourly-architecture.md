# Arsitektur booking per jam

Acuan implementasi per 10 November 2026. Skema utama berada di database/cutover/2026_11_04_000001_hourly_booking_schema.php. Wrapper database/staging memakai skema yang sama dengan pengaman lingkungan uji. Migration standar tidak menerapkan skema cutover secara otomatis.

## Identitas venue dan court

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

## Pengaturan operasional

Tambahan pada lapangan:

| Kolom | Default dan aturan |
| --- | --- |
| regular_opens_at, regular_closes_at | Keduanya nullable sebelum dikonfigurasi; jika diisi harus jam bulat dan buka < tutup |
| booking_window_days | 14; positif |
| booking_contact | Nullable sampai sumber kontak publik ditetapkan |
| hourly_booking_enabled | False; bukan otomatis aktif setelah migration |

operating_exceptions memiliki satu record per venue dan tanggal: is_closed, jam buka/tutup khusus, alasan, created_by, updated_by, timestamps. CHECK membedakan libur sehari penuh tanpa jam dan jam khusus yang valid. Perubahan dicatat oleh CourtOperations ke activity_logs.

court_closures menyimpan court, starts_at, ends_at, alasan, created_by, reopened_at, reopened_by, serta alasan pembukaan kembali. Interval jam bulat, positif, dan dalam satu tanggal. Penutupan venue sehari penuh dapat menggunakan operating_exceptions. Penutupan court sehari penuh direpresentasikan sebagai interval operasional court pada tanggal itu.

Jangan menghitung jam reguler dari minimum/maksimum jadwal legacy. Jangan membuat nomor kontak, alasan penutupan, atau pelaku penutupan lama secara fiktif.

## Slot, riwayat, dan reservasi aktif

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

Default is_legacy = true hanya melindungi transisi. Layanan baru wajib secara eksplisit membuat booking dengan is_legacy = false. Writer legacy menolak venue hourly aktif di bawah kunci venue yang sama.

CHECK untuk booking nonlegacy mengharuskan court, source yang dikenal, interval jam bulat dalam satu tanggal, durasi positif yang cocok dengan interval, serta expires_at yang tidak melewati mulai jika status pending. Baris legacy tidak dipaksa memenuhi kontrak baru karena dapat memiliki konflik yang belum terselesaikan.

Indeks (is_legacy, status, expires_at) disiapkan untuk pemindaian kedaluwarsa. Service expiry membatasi ke booking nonlegacy.

### booking_slots

- Primary key (booking_id, slot_id).
- Foreign key gabungan memastikan booking dan slot menunjuk venue_court_id yang sama.
- Tetap disimpan ketika booking kedaluwarsa atau dibatalkan.

### active_slot_reservations

- Primary key slot_id: maksimal satu penguasaan aktif per slot.
- Foreign key gabungan ke booking_slots: penguasaan aktif harus memiliki hubungan historis yang cocok.
- Tidak menduplikasi expires_at; tenggat berada pada booking.
- Record dapat dilepas dalam transaksi tanpa menghapus booking_slots.

Constraint tidak menggantikan service yang memeriksa slot berurutan, satu tanggal, durasi keseluruhan, waktu sekarang, status booking, dan penutupan. Kondisi lintas tabel dan waktu berjalan diperiksa layanan.

## Riwayat dan kesehatan proses

activity_logs memuat venue, actor nullable untuk proses sistem, subject_type/subject_id, action, reason, changes JSON, dan occurred_at. Service harus membatasi isi changes; jangan mencatat password, token, kredensial, atau data pelanggan yang tidak diperlukan. Alur aplikasi hanya menambahkan riwayat tindakan.

operational_task_runs memuat task, started_at, finished_at, status, processed_count, dan error_code tersanitasi. HourlyMaintenance menulis hasil proses dan panel admin menampilkan peringatan kesehatan.

Pada skema cutover, foreign key lama users → venue → jadwal → booking dan courts → jadwal diganti menjadi RESTRICT. Hubungan baru juga memakai RESTRICT. Penghapusan bisnis harus diganti dengan penonaktifan/pembatalan pada fase terkait; penambahan constraint tidak dengan sendirinya menyediakan UI pengganti.


## Layanan dan transaksi

CourtOperations menangani konfigurasi, pengecualian, penutupan, pembukaan kembali, pembentukan slot, dan ketersediaan. HourlyBooking menangani reserve, confirm, cancel, serta expiry. HourlyMaintenance menjalankan proses dan merekam kesehatan.

Seluruh writer mengunci baris venue lebih dahulu dalam transaksi, kemudian mengevaluasi reservasi dan aturan. Penahanan lewat tenggat diselesaikan sebelum pemeriksaan konflik. Pemilihan satu court/tanggal, keberurutan, waktu mulai, durasi, penutupan, dan ketersediaan diperiksa server. Booking, booking_slots, reservasi aktif, dan riwayat dibuat atomik.

Konfirmasi dan expiry memakai kunci yang sama dan memeriksa waktu server. Unique slot_id pada active_slot_reservations melindungi dari penguasaan ganda. Hash idempotency terikat venue, konteks server, dan key; retry dengan payload berbeda ditolak.

PublicHourlyBookingController mengikat receipt ke hash sesi browser asal dan key. Respons status memakai private/no-store dan no-referrer. Jangan mencatat cookie, token, password, atau kontak pelanggan dalam log operasional.

## Waktu dan data lama

DATETIME slot/booking baru merepresentasikan WIB, TIME untuk jam reguler, DATE untuk pengecualian. Nilai legacy dipertahankan tanpa konversi otomatis UTC. Court global tetap menjadi label legacy, sementara court fisik baru berada di venue_courts.

Audit 4 November menemukan 13 jadwal dan 3 booking, seluruhnya masa lalu pada snapshot. Booking legacy #1 memiliki interval tiga jam tetapi durasi satu jam dan status yang bertentangan; nilainya dipertahankan. Kandidat court tidak boleh diaktifkan hanya berdasarkan label global. Aktivasi pilot hanya memverifikasi Court A venue 6.

## Pengujian dan penerapan

Gunakan [runner MySQL terisolasi](../tests/README.md). Simulasi memakai database acak dan akun terbatas, snapshot operasional anonim, serta pemeriksaan source sebelum/sesudah. Ini berbeda dari backup penuh untuk pemulihan deployment.

Lihat [operasional](operations.md) dan [penerimaan](qa/acceptance.md).
