# Arenaku — Phase 5A: Comprehensive QA & Application Audit

Tanggal: **2 November 2026 (Asia/Jakarta)**. Baseline Git: `9fdb1c0` (`feat(ui): unify dashboard components and improve responsivness`). Working tree bersih saat audit dimulai.

**Batas pekerjaan:** audit saja. Tidak ada perbaikan source, upgrade dependency, migrasi, seeding, perubahan akun, atau perubahan record aplikasi. Deliverable repository hanya file ini. Phase 5B belum dimulai.

## A. Executive summary

**Rekomendasi: jangan menganggap aplikasi siap produksi berdasarkan hasil redesign atau kelulusan tes tampilan.** Courtside sudah konsisten pada halaman publik, login, dan reset password yang diperiksa, tetapi otorisasi dan integritas reservasi masih memiliki masalah yang lebih mendesak daripada penyempurnaan visual.

Laporan mencatat **32 temuan: 3 Critical, 9 High, 17 Medium, dan 3 Low**. Jumlah ini mencakup defect terkonfirmasi dan risiko yang diberi label; bukan 32 eksploitasi yang berhasil direproduksi.

Temuan utama:

1. Penjaga dapat membuka pengelolaan pengguna melalui URL langsung. Controller juga menerima perubahan `role` tanpa pemeriksaan admin. Route court dan booking management tidak memiliki middleware autentikasi.
2. Pengujian baca-saja dengan identitas penjaga di memori memperoleh **dua booking milik venue di luar kepemilikannya**. Menu tersembunyi tidak melindungi data backend.
3. Booking hanya memvalidasi keberadaan ID jadwal, lalu membuat record dan memperbarui status dalam dua operasi terpisah. Tidak ada pemeriksaan ulang `ready`, transaksi, locking, idempotency, atau constraint unik untuk mencegah booking ganda.
4. Data saat audit memiliki **satu durasi booking yang tidak cocok dengan interval jadwal**. Tidak ditemukan slot dengan beberapa booking pada snapshot ini; hal tersebut bukan bukti perlindungan terhadap booking ganda.
5. Pencarian POST bekerja, tetapi GET dengan filter yang sama mengembalikan seluruh venue. Jalur pagination tidak menerapkan filter walaupun teks pencarian masih ditampilkan.
6. Audit Composer menemukan advisori pada versi dependency terkunci, termasuk advisori upstream berkategori Critical. Eksploitabilitas setiap advisori pada Arenaku belum dibuktikan.
7. Tersisa masalah profil, route detail yang tidak memiliki view, template registrasi lama, dan lingkungan build/test yang belum dapat direproduksi dengan perintah standar.

Hasil positif yang terbukti: **17 tes PHPUnit tanpa akses database lulus (191 assertions), 2 tes Node lulus, 62 file PHP lolos lint, dan 51 Blade template berhasil dikompilasi ke string di memori.** Enam jenis halaman publik/auth diperiksa dengan screenshot nyata pada kelima viewport yang diminta. Ini tidak mencakup pengiriman booking, login akun nyata, atau CRUD tulis.

### Metode dan batas bukti

| Label | Makna |
| --- | --- |
| **Browser** | Diamati melalui aplikasi lokal yang berjalan, DOM/AX, interaksi, dan/atau screenshot. |
| **Runtime baca-saja** | Request GET melalui kernel Laravel atau SELECT MySQL; identitas admin/penjaga hanya dipasang di guard dalam proses pengujian, bukan login browser. |
| **Tes memori** | Fixture view/model/session tanpa penyimpanan database. |
| **Kode terkonfirmasi** | Perilaku/jalur kontrol terlihat pada source; dampak operasi tulis belum dijalankan. |
| **Risiko** | Kondisi berisiko terlihat, tetapi eksploitasi, dampak produksi, atau aturan bisnis yang diharapkan masih perlu verifikasi. |

GET kernel dijalankan di dalam koneksi MySQL dengan `SET SESSION TRANSACTION READ ONLY`, `beginTransaction()`, lalu `rollBack()`. Tidak ada pemanggilan controller store/update/destroy terhadap record nyata. Hasil hanya mencetak status, jumlah, boolean, dan jadwal publik; nama, email, nomor pelanggan, hash, cookie, dan token nyata tidak dimasukkan ke laporan.

Screenshot diperiksa langsung melalui tool browser, tidak disimpan sebagai file tambahan agar perubahan repository tetap hanya dokumentasi ini. Cache view/session dapat terbentuk dari rendering normal. Append log yang dihasilkan pemeriksaan error audit dikembalikan ke byte baseline setelah diverifikasi bahwa seluruh append berasal dari pemeriksaan ini; log awal dipertahankan.

### Lingkungan yang benar-benar diperiksa

| Bagian | Hasil |
| --- | --- |
| Runtime | Laravel **11.11.1**, PHP **8.2.34**, Node **22.16.0**, Composer **2.8.5**, PHPUnit **10.5.24** |
| Browser utama | `http://app_arenaku.test`, virtual host Laragon dengan document root `public/` |
| URL pembanding | `http://localhost/app_arenaku/public/`; HTML tampil tetapi jalur aset root salah (QA-022) |
| Frontend | Bootstrap **5.3.3** publik; Bootstrap **4.6.0**, jQuery **3.6.0**, DataTables **1.10.24** dashboard |
| Konfigurasi lokal | `APP_ENV=local`, debug aktif, timezone **Asia/Jakarta**, locale **en**, session file, mail log |
| Cookie lokal | HttpOnly aktif, SameSite lax; Secure tidak dipaksa. Ini konfigurasi HTTP lokal, bukan bukti konfigurasi produksi. |
| Build | Laravel Mix 5; `node_modules` dan lockfile npm/yarn tidak ditemukan |
| Test database | XML menunjuk SQLite `:memory:`, tetapi **pdo_sqlite tidak tersedia**, tidak ada `.env.testing`, dan suite tidak menyiapkan schema |

### Snapshot integritas database (SELECT saja)

| Pemeriksaan | Hasil |
| --- | ---: |
| Pengguna | 4 (1 admin, 3 penjaga) |
| Venue / court / jadwal / booking | 2 / 3 / 12 / 2 |
| Status jadwal | 9 ready, 1 booking, 2 closed |
| Status booking | 2 pending |
| Slot dengan lebih dari satu booking | 0 |
| Status booking pada jadwal tanpa record booking | 0 |
| Durasi <= 0 / interval akhir <= awal | 0 / 0 |
| Durasi booking berbeda dari panjang interval | **1** |
| Pasangan interval bertumpuk pada venue/court/tanggal sama | 0 |
| Booking pending/confirmed dengan slot ready | 0 |
| Booking pending/confirmed dengan slot closed | **1**, perlu rekonsiliasi aturan status (QA-008) |
| Password tersimpan yang saat ini membutuhkan rehash | 0; risiko rehash tetap ada ketika biaya hash berubah (QA-009) |

## B. Test coverage matrix

| Fitur | Metode | Hasil dan batas |
| --- | --- | --- |
| Homepage, logo, navigasi, venue cards | Browser, screenshot | Render berhasil pada virtual host; dua venue nyata dan gambarnya tampil. |
| Pencarian nama | Browser POST | `Giri` menghasilkan satu venue; input dan judul mempertahankan kata pencarian. |
| GET dengan filter / pagination | Browser + kode | **Gagal:** `cms/search?search=Giri&page=1` menampilkan dua venue. Belum ada tombol halaman kedua pada data nyata karena total hanya dua. |
| Pencarian kosong | Browser POST | Kata uji yang tidak cocok menghasilkan 0 venue dan tautan pemulihan. |
| Detail venue, date picker | Browser | Detail dan tanggal terpilih tampil; tidak ada data yang diubah. |
| Slot ready / booking / closed | Browser + render tests | Ketiga label tampil sesuai data; hanya ready memiliki tautan. Status backend belum dilindungi pada submit. |
| Jadwal tanpa hasil | Browser | Tanggal 2026-11-02 pada venue yang diuji menghasilkan empty state dan tindakan ganti tanggal. |
| Form booking | Browser + render tests | Nama, telepon, ringkasan, CSRF, old input, dan error markup tersedia. Submit form kosong diblokir native validation, fokus ke `nama`; tidak ada booking terkirim. |
| Invalid ID / slot closed | Runtime baca-saja | ID tak ada: 404. ID slot closed yang ada: **200 dengan form booking**. |
| Booking success | Render test saja | Menyatakan data dicatat, tidak mengklaim lunas/approved. Pengiriman nyata dan recovery POST belum diuji. |
| Duplikasi/concurrency | Kode + SELECT snapshot | Perlindungan tidak ditemukan. Tidak menjalankan request serentak atau booking tulis. |
| Login/logout | Browser GET + kode | `/home` mengarah ke login untuk guest. Login/logout akun nyata, session rotation, remember token, dan throttling belum diuji end-to-end. |
| Reset password | Screenshot + tes memori | Request-link dan form token tampil. Form token menggunakan string dummy, tanpa submit, email, atau perubahan password. |
| Admin/penjaga overview | Tes view + runtime baca-saja | GET 200 untuk kedua role; widget controller cocok dengan count database/owner scope. Bukan verifikasi browser terautentikasi. |
| Role navigation | Tes view | Menu administrasi tidak dirender untuk penjaga; backend tetap dapat diakses langsung (QA-001). |
| Venue/court/users/booking index dan create | Tes view + runtime GET | Halaman yang tersedia render 200 untuk role fixture; pelanggaran permission dicatat terpisah. |
| Edit forms, DELETE forms, CSRF, method spoofing | Tes view | Kontrak markup tersedia, bukan bukti update/delete berhasil atau ditolak secara benar. |
| Schedule modal IDs dan status values | Tes view | ID unik pada fixture multi-jadwal, nilai status tidak diubah. Interaksi modal browser belum diverifikasi. |
| Ownership venue/jadwal | Runtime baca-saja | Penjaga yang diuji tidak dapat membuka edit venue lain (302) atau jadwal venue lain (404). Jalur pindah venue pada update masih berisiko. |
| Ownership booking | Runtime baca-saja | **Gagal:** controller mengembalikan 2 booking luar kepemilikan. |
| Profile | Tes view + kode/schema | Form/role benar; update nyata tidak dilakukan. Ditemukan kontradiksi nullable dan feedback. |
| Upload/replace/delete gambar | Kode + gambar existing | Gambar existing tampil di virtual host. Upload, MIME rejection, replacement, cleanup, dan rollback filesystem belum diuji. |
| DataTables | 2 tes Node + source | Konfigurasi, region scroll, label, dan default paging/sort/search terjaga. Tes memakai adapter jQuery kecil, **bukan plugin asli di browser**. |
| Accessibility | DOM/AX, keyboard, token contrast, kode | Cakupan parsial; bukan sertifikasi WCAG atau audit axe/screen reader penuh. |
| Dependencies | Composer advisory audit | Advisori ditemukan. Npm audit tidak dapat dilakukan tanpa lockfile. |

### Hasil GET kernel menurut role

Identitas fixture memakai record existing yang dibaca tanpa perubahan. Angka ini menguji middleware/controller/rendering, **bukan login memakai password**.

| GET path | Guest | Admin | Penjaga | Catatan |
| --- | ---: | ---: | ---: | --- |
| `/home`, `/profile` | 302 login | 200 | 200 | Pembatasan guest bekerja. |
| `/users`, `/users/create` | 302 login | 200 | **200** | Pembatasan admin tidak ada. |
| `/courts`, `/courts/create` | **500** | 200 | **200** | Guest mencapai template yang mengasumsikan user; bukan 401/redirect. |
| `/lapangan`, `/lapangan/create` | **500** | 200 | 200 | Middleware auth tidak ada; owner scope ada pada sebagian controller. |
| `/jadwal`, `/booking` | **500** | 200 | 200 | List booking tidak memiliki owner scope. |
| `/lapangan/{existing}`, `/courts/{existing}`, `/users/{existing}` | Tidak diuji | **500** | Tidak diuji | Tiga template show tidak ditemukan. |

## C. Bug report terprioritas

Langkah yang memerlukan POST/PUT/DELETE di bawah adalah **rencana reproduksi pada database uji terisolasi Phase 5B**, bukan tindakan yang dijalankan terhadap data lokal. Bukti saat ini selalu disebutkan secara eksplisit.

### QA-001 — Critical — Penjaga dapat mengelola pengguna dan mengubah role

- **Lokasi:** `routes/web.php:52`; `app/Http/Controllers/UserController.php:18`, `:40`, `:106`, `:147`; `app/Providers/AuthServiceProvider.php:16`.
- **Masalah/aktual:** resource users hanya memakai `auth`. Tidak ada policy, gate, atau pemeriksaan admin dalam controller. Field `role` menerima `admin` dan disimpan; index membaca seluruh pengguna.
- **Reproduksi:** sebagai penjaga, buka `/users` dan `/users/create`; pada database uji, kirim update pengguna sendiri dengan `role=admin`, atau ubah/hapus pengguna lain.
- **Ekspektasi:** penjaga menerima 403 untuk seluruh pengelolaan pengguna; hanya admin yang berwenang dapat mengubah role/akun.
- **Bukti/status:** **Runtime baca-saja + kode terkonfirmasi**: kedua GET memberi 200 untuk penjaga; operasi tulis/escalation tidak dieksekusi.
- **Perbaikan:** middleware/policy admin pada semua action, otorisasi record, pengamanan akun admin terakhir/self-delete, dan integration tests guest/admin/penjaga. `Auth::routes()` juga membuka registrasi publik dengan default penjaga; kaji kebijakan registrasi karena menambah jalur masuk ke masalah ini.

### QA-002 — Critical — Route mutasi court dan booking tidak memerlukan login

- **Lokasi:** `routes/web.php:53`, `:54`, `:56`, `:64`, `:65`; `app/Http/Controllers/CourtController.php:37`, `:78`, `:96`; `app/Http/Controllers/BookingController.php:56`, `:78`; `resources/views/layouts/admin.blade.php:2`.
- **Masalah/aktual:** banyak route management hanya memakai `web`. Court store/update/delete dan booking update/delete tidak memeriksa user. Error guest pada layout tidak melindungi action yang mengubah data dan langsung redirect.
- **Reproduksi:** guest membuka `/courts` atau `/booking` → 500. Pada database uji, gunakan session guest dengan CSRF token valid untuk request mutasi management.
- **Ekspektasi:** guest ditolak sebelum controller; mutasi administrasi juga memeriksa role dan kepemilikan.
- **Bukti/status:** **Runtime baca-saja + kode terkonfirmasi**. `route:list` menunjukkan hanya `web`; request mutasi tanpa login tidak dijalankan. CSRF masih aktif, tetapi guest dapat memperoleh token sendiri sehingga CSRF bukan autentikasi.
- **Perbaikan:** kelompokkan route management di bawah auth, tambahkan policy per role/record. Biarkan public booking store terpisah sesuai kebutuhan aplikasi.

### QA-003 — Critical — Booking lintas pemilik dapat dibaca dan ditargetkan

- **Lokasi:** `app/Http/Controllers/BookingController.php:14`, `:48`, `:64`, `:80`; `resources/views/dashboard/booking/index.blade.php:34`.
- **Masalah/aktual:** list mengambil semua booking; edit/update/delete mencari ID global. Informasi pelanggan, nomor WhatsApp, dan jadwal tidak dibatasi ke venue penjaga.
- **Reproduksi:** sebagai penjaga tanpa venue terkait, buka daftar booking; pada database uji ubah ID edit/update/delete ke booking venue penjaga lain.
- **Ekspektasi:** hanya booking venue yang diizinkan; admin memperoleh akses global sesuai policy.
- **Bukti/status:** **Runtime baca-saja** mengembalikan **2 booking luar kepemilikan**. Write impact terkonfirmasi dari kode, belum dieksekusi.
- **Perbaikan:** scope query melalui `jadwalLapangan.lapangan.user_id`, authorize setiap action termasuk edit, dan uji IDOR secara terisolasi. Hanya menyembunyikan baris/menu tidak cukup.

### QA-004 — High — Update jadwal dapat dipindahkan ke venue yang tidak diizinkan

- **Lokasi:** `app/Http/Controllers/JadwalController.php:64`, `:74`, `:80`; `app/Models/JadwalLapangan.php:15`.
- **Masalah/aktual:** ownership diperiksa terhadap venue jadwal **sebelum update**; `lapangan_id` baru hanya diperiksa keberadaannya, kemudian `$request->all()` memasukkannya ke model fillable.
- **Reproduksi:** pada DB uji, penjaga mengedit jadwal miliknya dan mengganti hidden `lapangan_id` ke venue penjaga lain.
- **Ekspektasi:** venue tujuan juga diotorisasi, atau field venue immutable untuk action ini.
- **Bukti/status:** **Kode terkonfirmasi**; mutasi tidak dijalankan. Berbeda dari store: store memanggil helper ownership pada venue request. Edit/read/destroy jadwal juga memiliki helper scope; tidak semua jalur jadwal terbuka lintas pemilik.
- **Perbaikan:** gunakan payload tervalidasi eksplisit; authorize sumber dan tujuan, atau abaikan perubahan foreign key dari form tersebut.

### QA-005 — High — Booking slot unavailable dan booking ganda tidak dicegah backend

- **Lokasi:** `app/Http/Controllers/CMSController.php:56`; `app/Http/Controllers/BookingController.php:20`, `:27`, `:35`, `:43`; `database/migrations/2024_07_06_153426_create_konfirmasi_bookings_table.php:17`.
- **Masalah/aktual:** `exists` tidak memeriksa status; create booking dan save jadwal terpisah. Tidak ada lock, transaction, unique reservation rule, atau idempotency. Success page langsung merender respons POST sehingga reload/retry juga dapat mengulang pencatatan.
- **Reproduksi:** GET confirm untuk ID closed yang ada; pada DB uji submit slot closed/booked atau dua request serentak untuk slot ready yang sama, lalu uji retry POST.
- **Ekspektasi:** slot diverifikasi atomik; hanya satu reservasi aktif yang berhasil dan retry aman.
- **Bukti/status:** **Runtime baca-saja:** `/cms/16/confirm-booking` memberi 200 dan form walau slot closed. **Kode terkonfirmasi** untuk jalur save tanpa pemeriksaan. Race/duplicate writes belum diuji; 0 duplikat pada data existing tidak membuktikan proteksi.
- **Perbaikan:** tetapkan aturan reservasi aktif, transaksi + row lock/conditional update, constraint yang sesuai, respons konflik, idempotency, serta Post/Redirect/Get dengan akses receipt yang aman.

### QA-006 — High — Durasi tidak terikat interval jadwal

- **Lokasi:** `app/Http/Controllers/BookingController.php:24`, `:61`; `resources/views/cms/konfirmasi-booking.blade.php:36`.
- **Masalah/aktual:** durasi hanya `required|integer`; nol, negatif, atau durasi lebih panjang dari slot tidak dibatasi. Hanya satu jadwal ditandai booking, berapa pun durasi yang dikirim.
- **Reproduksi:** pada DB uji, pilih slot dua jam lalu kirim durasi 0, -1, atau 5. SELECT existing membandingkan `durasi * 3600` dengan `TIME_TO_SEC(TIMEDIFF(jam_akhir,jam_awal))`.
- **Ekspektasi:** durasi merepresentasikan interval yang benar-benar dipesan; jika multi-slot didukung, semua interval terkait dipesan atomik.
- **Bukti/status:** **Kode + data terkonfirmasi:** **1 dari 2 booking tidak cocok** dengan interval. Request invalid tidak dikirim.
- **Perbaikan:** putuskan kontrak fixed-slot atau multi-slot, hitung durasi di server dan validasi konsistensi. Jangan hanya menambahkan atribut `min` di UI.

### QA-007 — High — Validasi interval, overlap, dan status jadwal tidak lengkap

- **Lokasi:** `app/Http/Controllers/JadwalController.php:47`, `:57`, `:64`, `:80`.
- **Masalah/aktual:** jam hanya `required`; akhir tidak harus setelah awal; tidak ada pemeriksaan overlap. Status update hanya `required`, bukan enum. Store tidak memvalidasi status tetapi `$request->all()` menerima status karena fillable.
- **Reproduksi:** pada DB uji buat akhir <= awal, interval bertumpuk, format jam salah, atau status di luar ready/booking/closed; tambahkan status pada POST create.
- **Ekspektasi:** validasi server eksplisit dan feedback field; konflik interval ditolak menurut model court/venue yang disepakati.
- **Bukti/status:** **Kode terkonfirmasi**; snapshot memiliki 0 interval invalid dan 0 overlap. Belum menguji penolakan MySQL/HTTP untuk payload tersebut.
- **Perbaikan:** format waktu, urutan interval, enum status, payload tervalidasi, dan pemeriksaan konflik yang aman terhadap concurrency. Putuskan apakah court adalah unit fisik per venue atau label global sebelum menambah aturan kepemilikan court.

### QA-008 — High — Perubahan/hapus jadwal dapat mengubah atau menghilangkan reservasi existing

- **Lokasi:** `app/Http/Controllers/JadwalController.php:80`, `:94`; `database/migrations/2024_07_06_153426_create_konfirmasi_bookings_table.php:17`; `database/migrations/2024_07_06_120925_create_jadwal_lapangan.php:17`; `resources/views/components/dashboard/delete-action.blade.php:1`.
- **Masalah/aktual:** jadwal yang dirujuk booking dapat diganti jam/tanggal/court/status tanpa guard; booking membaca relasi live sehingga detail reservasi ikut berganti. Penghapusan jadwal memiliki cascade ke booking; venue/court/user deletion juga dapat berantai. Dialog generik tidak menjelaskan ketergantungan ini.
- **Reproduksi:** pada DB uji, ubah waktu/status atau hapus jadwal dengan booking aktif; tinjau perubahan booking dan cascade. Jangan lakukan pada data development.
- **Ekspektasi:** kebijakan perubahan/cancel/archive jelas, otorisasi dan konfirmasi menjelaskan dampak, histori reservasi tidak hilang tanpa keputusan bisnis yang eksplisit.
- **Bukti/status:** **Kode/schema terkonfirmasi; dampak tulis belum diuji.** Snapshot menemukan 1 booking pending pada slot closed; ini sinyal rekonsiliasi, bukan bukti kapan/siapa mengubahnya atau bahwa closed selalu terlarang.
- **Perbaikan:** sepakati lifecycle booking/jadwal, lindungi reservasi aktif, simpan histori bila diperlukan, dan uji cascade di database disposable.

### QA-009 — High — Password mutator tidak kompatibel dengan automatic rehash

- **Lokasi:** `app/Models/User.php:61`, `:71`; `vendor/laravel/framework/src/Illuminate/Auth/EloquentUserProvider.php:166`; `vendor/laravel/framework/src/Illuminate/Auth/SessionGuard.php:491` (referensi baca-saja, bukan target edit).
- **Masalah/aktual:** mutator selalu `bcrypt($value)`. Provider rehash Laravel memasukkan **hash yang sudah dibuat** lewat `forceFill()`, sehingga mutator akan meng-hash hash itu lagi saat rehash dibutuhkan.
- **Reproduksi:** tanpa save, buat User in-memory, assign plaintext lalu cek Hash::check; kemudian assign `Hash::make(plaintext)` dan cek plaintext yang sama. Untuk end-to-end, gunakan akun disposable dengan hash cost lama.
- **Ekspektasi:** rehash tetap menghasilkan hash yang cocok dengan password asli dan login berikutnya berhasil.
- **Bukti/status:** **Tes memori terkonfirmasi:** raw assignment=true, prehashed assignment=false. Tidak ada akun existing yang membutuhkan rehash saat audit; kerusakan akun nyata **tidak** direproduksi.
- **Perbaikan:** satu strategi hashing yang kompatibel dengan provider Laravel, dengan tes login rehash dan reset. Reset controller saat ini **sudah** override `setUserPassword` untuk assign plaintext; jalur reset tersebut lolos pemeriksaan Hash::check in-memory dan tidak boleh keliru dilaporkan sebagai double-hash pada semua reset.

### QA-010 — High — Password dapat ikut tersimpan pada old input session

- **Lokasi:** `app/Exceptions/Handler.php:24`; `app/Http/Controllers/UserController.php:52`, `:118`; `app/Http/Controllers/ProfileController.php:44`; `vendor/laravel/framework/src/Illuminate/Http/RedirectResponse.php:74`.
- **Masalah/aktual:** `withInput()` manual tidak menyaring password. Handler hanya mengecualikan `password`/`password_confirmation`, bukan `current_password`/`new_password` pada validation exception profil. Tidak merender kembali password di HTML tidak mencegah penyimpanannya di session.
- **Reproduksi:** gunakan session array dan password dummy, lalu `redirect()->withInput()`; periksa keberadaan key saja. Pada test terisolasi, uji validation failure user dan profil.
- **Ekspektasi:** semua field credential dikeluarkan dari flashed input dan tidak disimpan untuk recovery form.
- **Bukti/status:** **Kode + tes memori:** `hasOldInput('password')=true` untuk redirect manual. Tidak membaca atau menampilkan password/session akun nyata.
- **Perbaikan:** allowlist input non-sensitif pada redirect dan perluas pengecualian credential pada Handler; tambah tes yang memeriksa session, tidak hanya HTML.

### QA-011 — High — SVG upload aktif pada origin aplikasi

- **Lokasi:** `app/Http/Controllers/LapanganController.php:48`, `:56`, `:100`; `resources/views/components/dashboard/image-upload.blade.php:3`; `config/filesystems.php:57`.
- **Masalah/aktual:** rule image+mimes pada versi Laravel terpasang menerima SVG; file disimpan di public storage tanpa sanitization/rasterization. SVG berisi konten aktif berpotensi berjalan ketika dibuka sebagai dokumen pada origin aplikasi, walaupun tag img tidak menjalankan script SVG.
- **Reproduksi:** hanya pada lingkungan disposable, upload SVG dengan penanda script aman dan buka URL filenya secara langsung; periksa CSP/content-disposition dan MIME.
- **Ekspektasi:** upload gambar tidak menghasilkan dokumen aktif yang berbahaya pada origin ber-session.
- **Bukti/status:** **Risiko berdasarkan kode**; tidak mengunggah payload, tidak membuktikan eksekusi script. Validator vendor yang dibaca memang memasukkan svg (`vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:1348`).
- **Perbaikan:** batasi raster yang dibutuhkan, atau sanitasi/rasterisasi SVG dengan mekanisme teruji dan isolasi origin/header. Review advisori dependency secara terpisah; kasus wildcard file validation tidak otomatis membuktikan bypass pada field tunggal ini.

### QA-012 — High — Dependency terkunci memiliki advisori keamanan

- **Lokasi:** `composer.lock` pada package/version yang dirinci di bagian D; `composer.json:9`; `routes/api.php:18`; `config/auth.php:46`.
- **Masalah/aktual:** Composer audit berhasil memperoleh advisori untuk dependency runtime dan development. Ada advisori Laravel upstream Critical pada TokenGuard; Arenaku memiliki guard token/MySQL, tetapi schema token juga tidak lengkap (QA-021).
- **Reproduksi:** jalankan `composer audit --locked --no-dev --format=json` dan audit tanpa `--no-dev`; bandingkan versi serta prasyarat advisori dengan penggunaan aplikasi.
- **Ekspektasi:** dependency deployment tidak memiliki advisori relevan yang belum ditangani atau keputusan risiko yang belum didokumentasikan.
- **Bukti/status:** **Scanner terkonfirmasi; eksploitabilitas per advisori belum diverifikasi.** Severity issue ini High sebagai pekerjaan remediasi, bukan pernyataan bahwa semua advisori dapat dieksploitasi pada Arenaku.
- **Perbaikan:** rencanakan update terkontrol ke versi yang masih menerima patch, review kompatibilitas Laravel/PHP/Mix, tambah regression tests, audit ulang; jangan edit vendor atau upgrade tanpa fase implementasi disetujui.

### QA-013 — Medium — Search filter hilang pada GET/pagination

- **Lokasi:** `routes/web.php:28`, `:29`; `app/Http/Controllers/CMSController.php:12`, `:18`; `resources/views/cms/index.blade.php:27`, `:40`.
- **Reproduksi:** POST pencarian `Giri` → 1 venue. Buka `/cms/search?search=Giri&page=1` → 2 venue, termasuk yang tidak cocok, sedangkan UI masih menulis `Pencarian: Giri`.
- **Ekspektasi:** daftar, total, input, URL, dan pagination memakai filter yang sama.
- **Aktual/bukti/status:** **Browser terkonfirmasi.** GET diarahkan ke index yang selalu `Lapangan::paginate(3)`. Tombol page 2 belum diuji karena hanya dua venue existing.
- **Perbaikan:** satu alur query GET untuk pencarian/pagination atau normalisasi POST ke GET; validasi search sebagai string dengan batas panjang, termasuk penanganan input array/invalid shape. Tambah tes >3 hasil pada DB uji.

### QA-014 — Medium — Paginator menambahkan CSRF token POST ke query URL

- **Lokasi:** `resources/views/cms/index.blade.php:15`, `:16`, `:40`.
- **Masalah/aktual:** `request()->except('page')` mencakup input POST `_token`, bukan hanya filter pencarian. Saat link pagination dirender, token dapat ikut URL, history, dan log akses.
- **Reproduksi:** pada fixture in-memory atau DB uji dengan >3 hasil, render POST search yang memiliki `_token`, lalu periksa query link pagination tanpa mencetak token nyata.
- **Ekspektasi:** URL hanya membawa parameter publik yang diperlukan seperti `search` dan `page`.
- **Bukti/status:** **Kode terkonfirmasi; link dengan token nyata tidak dihasilkan/dibagikan dalam audit** karena data existing tidak memerlukan pagination.
- **Perbaikan:** allowlist `search`; jangan append semua request body. Perbaiki bersama QA-013.

### QA-015 — Medium — Hapus booking tidak merekonsiliasi status slot

- **Lokasi:** `app/Http/Controllers/BookingController.php:78`; `app/Models/JadwalLapangan.php:37`.
- **Masalah/aktual:** destroy hanya menghapus booking, tidak meninjau status jadwal. Slot dapat tetap booking meskipun reservasinya tidak ada.
- **Reproduksi:** pada DB uji, hapus satu-satunya booking untuk slot berstatus booking lalu cek ketersediaan publik.
- **Ekspektasi:** status jadwal ditentukan ulang menurut kebijakan cancel/delete dan reservasi aktif yang tersisa; jangan membabi buta membuka slot jika masih ada booking lain.
- **Bukti/status:** **Kode terkonfirmasi**, tidak dilakukan delete; saat audit 0 slot booking tanpa record terkait.
- **Perbaikan:** tetapkan lifecycle cancel/delete dan lakukan rekonsiliasi atomik serta regression test.

### QA-016 — Medium — Profil mengizinkan nama belakang kosong, database menolaknya

- **Lokasi:** `app/Http/Controllers/ProfileController.php:26`, `:37`; `resources/views/profile.blade.php:14`; `database/migrations/0001_01_01_000000_create_users_table.php:18`.
- **Masalah/aktual:** UI dan validator menjadikan `last_name` opsional; nilai kosong menjadi null dan di-assign, sedangkan kolom existing non-nullable.
- **Reproduksi:** pada akun disposable, kosongkan nama belakang lalu simpan profil.
- **Ekspektasi:** kontrak UI/validasi/schema selaras; pengguna mendapat validasi yang jelas atau nilai kosong sah tersimpan.
- **Bukti/status:** **Kode + schema aktual terkonfirmasi** (`last_name nullable=false`); update tidak dijalankan, error submit masih inferensi kuat dari constraint.
- **Perbaikan:** pilih kebijakan nama belakang, selaraskan validator/UI/schema melalui perubahan yang disetujui dan tes update.

### QA-017 — Medium — Nomor handphone lama tidak dapat dikosongkan

- **Lokasi:** `app/Http/Controllers/ProfileController.php:47`; `app/Http/Controllers/UserController.php:129`.
- **Reproduksi:** pada DB uji, akun yang memiliki nomor mengosongkan field telepon pada profil atau edit pengguna.
- **Ekspektasi:** field opsional dapat dibersihkan sesuai label/validasi.
- **Aktual:** assignment hanya terjadi bila `filled()`, sehingga nilai lama dipertahankan tanpa penjelasan.
- **Bukti/status:** **Kode terkonfirmasi**, perubahan record tidak dijalankan.
- **Perbaikan:** bedakan input tidak disertakan dan input sengaja kosong; assign null yang tervalidasi dan uji kedua jalur.

### QA-018 — Medium — Salah password lama tidak menghasilkan feedback

- **Lokasi:** `app/Http/Controllers/ProfileController.php:40`, `:44`; `resources/views/profile.blade.php:19`.
- **Reproduksi:** pada akun disposable, kirim password lama salah dengan password baru/konfirmasi yang memenuhi rule.
- **Ekspektasi:** error `current_password`, fokus dan pesan yang menjelaskan koreksi, tanpa mengubah profil/password.
- **Aktual:** redirect back dengan input saja, tanpa error atau status. Pengguna tidak tahu penyebab gagal. Jalur ini juga terkait password flash QA-010.
- **Bukti/status:** **Kode terkonfirmasi**, bukan percobaan credential terhadap akun nyata.
- **Perbaikan:** validator current-password atau error field eksplisit; jangan flash credentials. Kebijakan maksimum 12 karakter pada profil juga perlu diselaraskan dengan register/reset/user management yang tidak memiliki maksimum tersebut.

### QA-019 — Medium — Route show untuk tiga resource selalu gagal render

- **Lokasi:** `app/Http/Controllers/LapanganController.php:72`; `app/Http/Controllers/CourtController.php:57`; `app/Http/Controllers/UserController.php:77`; `routes/web.php:51`.
- **Reproduksi:** sebagai admin fixture, GET `/lapangan/{id}`, `/courts/{id}`, `/users/{id}` menggunakan ID existing.
- **Ekspektasi:** halaman detail yang valid, redirect yang disengaja, atau route tidak diekspos jika tidak didukung.
- **Aktual/bukti/status:** **Runtime baca-saja terkonfirmasi:** ketiganya 500; `view()->exists()` false untuk ketiga template show.
- **Perbaikan:** batasi resource route ke action yang didukung atau implementasikan detail yang diperlukan, dengan auth/policy dan HTTP tests.

### QA-020 — Medium — Hasil jadwal hilang saat kembali dari form booking

- **Lokasi:** `resources/views/cms/konfirmasi-booking.blade.php:6`; `app/Http/Controllers/CMSController.php:27`; `resources/views/cms/cek-booking.blade.php:25`.
- **Reproduksi:** cari jadwal tanggal 2024-07-12, pilih slot, klik `Kembali ke pilihan tanggal`.
- **Ekspektasi:** konteks tanggal dan daftar slot tetap tersedia sehingga pengguna bisa memilih slot lain tanpa pencarian ulang.
- **Aktual/bukti/status:** **Browser terkonfirmasi:** query tanggal mengisi date picker, tetapi GET `cekBooking()` tidak memuat jadwal/`$tanggal`; hasil hanya muncul setelah POST lagi. Label link memang menyebut pilihan tanggal, jadi ini friction state, bukan kehilangan record.
- **Perbaikan:** date filtering dapat diakses melalui URL GET dan direstorasi saat kembali; pertahankan validasi dan status nyata.

### QA-021 — Medium — Guard API mengandalkan kolom token yang tidak tersedia

- **Lokasi:** `routes/api.php:18`; `config/auth.php:46`; `database/migrations/0001_01_01_000000_create_users_table.php:14`.
- **Reproduksi:** request lokal GET `/api/user?api_token=qa-invalid-fixture` dengan Accept JSON melalui harness baca-saja (bukan token pengguna).
- **Ekspektasi:** token invalid → 401; atau route API dinonaktifkan bila tidak digunakan.
- **Aktual/bukti/status:** **Runtime + schema terkonfirmasi:** 500, `Unknown column` untuk `api_token`; schema users tidak memiliki kolom tersebut. Tidak menguji credential berbentuk array/advisory bypass.
- **Perbaikan:** tentukan apakah API dibutuhkan, lalu selaraskan guard/schema/route memakai pendekatan yang didukung. Kaji advisori TokenGuard QA-012 sebelum membuka API.

### QA-022 — Medium — Aset publik/dashboard rusak pada hosting subfolder

- **Lokasi:** `config/app.php:57`; `resources/views/layouts/public-courtside.blade.php:8`; `resources/views/layouts/admin.blade.php:13`; `resources/views/layouts/login.blade.php:9`.
- **Reproduksi:** buka `http://localhost/app_arenaku/public/` dan bandingkan dengan `http://app_arenaku.test/`.
- **Ekspektasi:** base URL/aset konsisten untuk cara hosting yang didukung, atau README secara tegas mensyaratkan virtual host public-root.
- **Aktual/bukti/status:** **Browser terkonfirmasi:** pada subfolder, CSS/gambar mengarah ke `/css`, `/assets`, `/storage` di host root dan gagal termuat. Virtual host memuat semua gambar homepage. Login memakai `url()` sementara halaman lain `asset()` sehingga perilaku berbeda.
- **Perbaikan:** tetapkan satu kontrak deployment/base URL dan pengaturan aset yang tepat; hindari perbaikan URL parsial per layout. Jangan menganggap defect ini terjadi pada virtual host yang sudah berhasil diuji.

### QA-023 — Medium — Pipeline build tidak tersedia dan berisiko menimpa aset legacy

- **Lokasi:** `package.json:4`, `:10`; `webpack.mix.js:15`; `resources/sass/app.scss:1`; `resources/js/app.js:1`.
- **Reproduksi:** jalankan `npm.cmd run production`; inspeksi mapping Mix dan sumber SCSS.
- **Ekspektasi:** clean checkout memiliki lockfile, dependencies yang dapat direproduksi, dan sumber lengkap untuk setiap output yang dibangun.
- **Aktual/bukti/status:** **Command terkonfirmasi:** gagal karena `cross-env` tidak dikenali, `node_modules` absen. SCSS hanya komentar tetapi target build adalah `public/css/app.css` existing. `npm audit --offline --json` gagal ENOLOCK.
- **Perbaikan:** dokumentasikan jalur aset aplikasi/vendor, pulihkan sumber build yang tepat, pilih dependency/lockfile dengan approval. Sebelum rebuild, pastikan compiled legacy asset tidak terganti output kosong. Tidak ada install/build output yang ditulis audit ini.

### QA-024 — Medium — Runner dan database test standar belum siap

- **Lokasi:** `phpunit.xml:4`, `:26`; `tests/Feature/ExampleTest.php:14`; `tests/CreatesApplication.php:16`; `composer.json:24`.
- **Reproduksi:** `php artisan test --do-not-cache-result` dan `php vendor/bin/phpunit --do-not-cache-result`; cek `extension_loaded('pdo_sqlite')` dan setup schema test.
- **Ekspektasi:** satu perintah standar berjalan pada database terisolasi dengan fixture/schema yang jelas.
- **Aktual/bukti/status:** **Command terkonfirmasi:** kedua runner gagal `Cannot open bootstrap script ... vendor/autoload.php`, walaupun direct PHP require bekerja. pdo_sqlite=false; `.env.testing` tidak ada; test homepage melakukan query tanpa setup database. XML juga masih memakai elemen coverage legacy `filter/whitelist`.
- **Perbaikan:** benahi path/config PHPUnit yang cocok dengan versi terpasang; siapkan SQLite atau MySQL test khusus, guard terhadap DB development, dan integration tests otorisasi/booking. Direct runner yang melewati XML bukan perbaikan pipeline.

### QA-025 — Medium — Registrasi masih template lama dan tanpa label input

- **Lokasi:** `resources/views/auth/register.blade.php:1`, `:32`; `resources/views/layouts/auth.blade.php:26`; `resources/views/auth/passwords/confirm.blade.php:1`.
- **Reproduksi:** buka `/register` pada 390px dan telusuri form dengan AX/keyboard. Untuk password-confirm diperlukan session sah; belum dibuka dalam browser audit.
- **Ekspektasi:** halaman auth yang masih didukung mengikuti Courtside, memiliki label terlihat, autocomplete, fokus/error yang konsisten.
- **Aktual/bukti/status:** **Browser + screenshot + DOM:** registrasi tetap latar biru gradient/pill inputs dan lima field tanpa label terkait. Password-confirm juga memakai layout lama menurut source, tetapi belum diverifikasi visual.
- **Perbaikan:** putuskan kebijakan registrasi terlebih dahulu; bila tetap aktif, selaraskan komponen auth dan semantic labels. Jangan mengganti kebijakan akses hanya demi konsistensi tampilan.

### QA-026 — Medium — Daftar management dan modal memuat seluruh data sekaligus

- **Lokasi:** `app/Http/Controllers/BookingController.php:14`; `app/Http/Controllers/UserController.php:20`; `app/Http/Controllers/LapanganController.php:23`; `app/Http/Controllers/JadwalController.php:36`; `resources/views/dashboard/jadwal/jadwal.blade.php:18`.
- **Masalah/aktual:** `get()/all()` mengirim seluruh record, sementara DataTables hanya membagi tampilan client. Satu modal lengkap dirender untuk setiap jadwal.
- **Reproduksi:** pada DB uji berukuran realistis, ukur query time, response size, DOM nodes dan interaction delay untuk ribuan booking/jadwal, bukan menambahkan data ke DB development.
- **Ekspektasi:** biaya render/query tumbuh terkendali dan pengguna tetap bisa mencari/sort/paging.
- **Bukti/status:** **Risiko skala berdasarkan kode**, bukan bottleneck terukur pada snapshot kecil (12 jadwal/2 booking). Query booking index sudah eager-load relasi sehingga tidak tepat disebut N+1 pada list tersebut.
- **Perbaikan:** setelah permission aman, pertimbangkan server-side pagination/search/sort serta satu modal reusable; ukur sebelum mengubah interaksi/plugin.

### QA-027 — Medium — Gambar besar dipakai tanpa varian responsif

- **Lokasi:** `resources/views/cms/index.blade.php:12`; `resources/views/components/auth/visual.blade.php:1`; `resources/views/layouts/public-courtside.blade.php:20`; `public/assets/img/hero-bg.jpg`; `public/assets/img/logo_arenaku.jpg`.
- **Reproduksi:** periksa ukuran file dan markup image, lalu ukur mobile cold load pada jaringan terkontrol.
- **Ekspektasi:** gambar dikirim sesuai kebutuhan ukuran layar; visual desktop yang disembunyikan di mobile tidak menambah transfer yang tidak diperlukan.
- **Aktual/bukti/status:** **File/kode terkonfirmasi:** hero 1,594,775 byte, logo 347,436 byte. Logo memakai canvas 3656×2680 yang dicrop CSS untuk frame kecil. Tidak ada `srcset`; image auth berada pada elemen display:none di mobile tetapi img tetap eager. Dampak LCP/transfer jaringan nyata belum diukur.
- **Perbaikan:** buat turunan ukuran tepat/format efisien dari aset asli tanpa mengganti identitas; responsive image/source strategy dan ukur LCP. Lazy loading sudah ada pada venue cards, ukuran gambar eksplisit juga sudah tersedia.

### QA-028 — Medium — Konfigurasi debug dan log versioned perlu pengamanan sebelum deployment

- **Lokasi:** `config/app.php:42`; `.gitignore:1`; `storage/logs/laravel.log`; `config/mail.php:16`.
- **Masalah/aktual:** debug lokal aktif dan log runtime dilacak Git. Error aplikasi dapat memuat stack, path, SQL, atau data request; reset mail memakai log di lingkungan ini. Ini meningkatkan risiko jika file/config yang sama dipublikasikan.
- **Reproduksi:** `git ls-files storage/logs`, inspeksi hanya flag konfigurasi non-rahasia, dan uji error terkontrol di staging. Jangan mencetak isi log/token email.
- **Ekspektasi:** produksi mematikan debug, runtime log tidak masuk repository/distribusi publik, pengiriman reset mail dan retensi log dikonfigurasi dengan benar.
- **Bukti/status:** **Konfigurasi/file tracking terkonfirmasi; exposure produksi tidak diuji.** Debug=true dan mail=log wajar untuk lokal, bukan bukti deployment publik sudah bocor. `.env` tidak ter-track.
- **Perbaikan:** deployment checklist/automated guard, pengecualian log dari version control, review histori secara privat jika perlu, dan mail transport staging/production teruji. Tidak ada secret yang dicetak atau dirotasi dalam audit.

### QA-029 — Medium — Lifecycle file upload tidak mencakup semua kegagalan/cascade

- **Lokasi:** `app/Http/Controllers/LapanganController.php:55`, `:111`, `:118`, `:135`; `app/Http/Controllers/UserController.php:150`; `config/filesystems.php:61`.
- **Masalah/aktual:** gambar baru ditulis sebelum save DB; kegagalan DB bisa meninggalkan file orphan. Penghapusan venue langsung menghapus file sebelum record; kegagalan delete DB bisa meninggalkan record tanpa file. Cascade penghapusan user tidak menjalankan helper cleanup venue.
- **Reproduksi:** pada DB/storage disposable, simulasi kegagalan save/delete dan hapus user dengan venue bergambar; cek record/file tersisa.
- **Ekspektasi:** operasi mempunyai kompensasi filesystem dan cleanup dependensi yang terukur.
- **Bukti/status:** **Risiko berdasarkan kode/schema**; tidak menghapus, mengganti, atau mengunggah file nyata. Path file user tidak dipakai langsung sebagai upload filename; penyimpanan menggunakan `store()`.
- **Perbaikan:** service lifecycle/after-commit cleanup dan compensating cleanup untuk file baru gagal save; jangan mengandalkan transaksi DB untuk otomatis membatalkan perubahan filesystem.

### QA-030 — Low — Token dan fondasi CSS diduplikasi; dependency dashboard dimuat global

- **Lokasi:** `public/css/arenaku-public.css:2`; `public/css/arenaku-dashboard.css:3`; `resources/views/layouts/admin.blade.php:74`; `resources/views/layouts/login.blade.php:13`; `resources/views/layouts/cms.blade.php:3`.
- **Reproduksi:** bandingkan deklarasi token dan daftar asset pada overview/profil versus halaman table.
- **Ekspektasi:** sumber token bersama yang mudah dipelihara, adapter Bootstrap terpisah, asset fitur dimuat bila diperlukan.
- **Aktual/bukti/status:** **Kode terkonfirmasi:** nilai brand cocok tetapi disalin dua kali. Overview/profil tetap memuat DataTables; auth memuat seluruh dashboard CSS/SB Admin. Cabang template publik lama masih ada. Ini risiko drift/overhead, bukan bukti konflik Bootstrap 4/5 pada halaman Courtside yang aktif.
- **Perbaikan:** pada fase tersendiri, ekstrak token tanpa mencampur framework; kondisikan asset table setelah dependensi SB Admin diperiksa. Jangan otomatis menghapus vendor/template yang belum dipetakan pemakaiannya.

### QA-031 — Low — Relasi venue.courts tidak sesuai schema court

- **Lokasi:** `app/Models/Lapangan.php:36`; `app/Models/Court.php:14`; `database/migrations/2024_06_26_110158_create_courts_table.php:14`.
- **Masalah/aktual:** `Lapangan::courts()` menggunakan hasMany konvensional yang mengharapkan `courts.lapangan_id`, tetapi kolom itu tidak ada pada migration maupun database aktual. Court saat ini adalah daftar global yang dikaitkan melalui jadwal.
- **Reproduksi:** pada harness baca-saja, akses relasi `$lapangan->courts`; periksa SQL/schema. Akses tersebut tidak diperlukan oleh halaman aktif yang diperiksa, sehingga tidak sengaja dipanggil untuk menimbulkan error tambahan.
- **Ekspektasi:** model domain/relationship sesuai schema dan arti unit court.
- **Bukti/status:** **Kode + schema terkonfirmasi**, failure relasi latent belum dieksekusi dan tidak diklaim sebagai failure homepage.
- **Perbaikan:** tentukan model court global/per-venue sebelum perubahan schema/relasi; tambah relation test. Jangan sekadar memfilter `Court::all()` berdasarkan kolom yang belum ada.

### QA-032 — Low — Heading auth dan tampilan waktu masih dapat diperjelas

- **Lokasi:** `resources/views/components/auth/visual.blade.php:1`; `resources/views/auth/login.blade.php:6`; `resources/views/cms/cek-booking.blade.php:39`; `resources/views/components/arenaku/booking-summary.blade.php:5`.
- **Reproduksi:** periksa urutan heading auth desktop dan screenshot jadwal/form booking 360/390/768px.
- **Ekspektasi:** struktur heading dimulai dari judul tugas utama; waktu mudah dipindai sesuai presisi operasional yang dibutuhkan.
- **Aktual/bukti/status:** **Browser + kode:** heading visual h2 mendahului h1 tugas di DOM desktop; waktu selalu menampilkan detik, dan ringkasan waktu membungkus di panel 768px. Tetap terbaca dan tidak membuktikan kegagalan AA secara otomatis.
- **Perbaikan:** jadikan caption visual teks biasa atau atur struktur heading; format HH:mm bila detik tidak relevan. Pertahankan nilai jam asli untuk submit/aturan backend.

## D. Security findings dan dependency audit

Prioritas keamanan: **QA-001–004**, lalu **QA-005**, **QA-009–012**, **QA-014**, dan **QA-028**. Controller inheritance berbeda (`App` controller vs `Illuminate\Routing\Controller`) tidak menyediakan otorisasi otomatis pada salah satu jalur. Policies kosong dan tidak ditemukan pemanggilan authorize/gate pada controller aplikasi yang diperiksa.

Proteksi yang ada: middleware CSRF aktif tanpa exclusion aplikasi, form tulis menggunakan CSRF/method spoofing, field output yang ditinjau memakai escaping Blade, password/remember token disembunyikan dari serialisasi User, HttpOnly/SameSite tersedia, dan auth standar dipakai untuk home/profile/users. Tidak ditemukan interpolasi raw SQL dari input pada query pencarian yang ditinjau. Ini hasil inspeksi, bukan bukti bebas XSS/SQLi untuk seluruh sistem.

### Hasil advisory Composer

Audit tanpa dev menghasilkan exit **1** karena advisori, bukan keberhasilan bersih. Audit lengkap juga menghasilkan advisori; shell pengolah JSON mengembalikan exit 0 karena langkah formatting, sehingga exit wrapper tersebut **tidak** berarti dependency aman.

Keluaran lengkap memuat **53 entri advisory pada 15 package**; **52 entri/14 package** adalah dependency runtime, satu tambahan pada PHPUnit. Ini hitungan entri scanner, **bukan jumlah CVE unik**: advisori CRLF email Laravel muncul dengan dua ID sumber untuk GHSA yang sama. Tidak ada package abandoned yang dilaporkan. Versi framework terpasang diverifikasi cocok dengan lock.

| Package terkunci | Versi | Entri | Catatan |
| --- | --- | ---: | --- |
| guzzlehttp/guzzle | 7.8.1 | 9 | Host/cookie/header/redirect; relevansi jalur HTTP perlu pemetaan. |
| guzzlehttp/psr7 | 2.6.2 | 4 | Parsing/serialization URI/header. |
| laravel/framework | 11.11.1 | 9 | Termasuk TokenGuard Critical, debug-page XSS, dan validasi. |
| league/commonmark | 2.4.2 | 15 | Parser/extensions; tidak ditemukan editor Markdown publik pada alur yang diaudit. |
| league/flysystem | 3.28.0 | 1 | Normalisasi path. |
| nesbot/carbon | 3.6.0 | 1 | Locale input; view yang ditinjau menggunakan locale tetap `id`. |
| psy/psysh | 0.12.4 | 1 | Tool development tetapi terpasang via dependency runtime tinker. |
| symfony/http-client | 6.4.8 | 1 | NoPrivateNetworkHttpClient. |
| symfony/http-foundation | 7.1.1 | 4 | Header parsing, PATH_INFO, URL/IP handling. |
| symfony/mailer | 7.1.1 | 1 | Sendmail path, relevansi bergantung transport. |
| symfony/mime | 7.1.1 | 2 | Email/header handling. |
| symfony/polyfill-intl-idn | 1.30.0 | 1 | Domain normalization. |
| symfony/process | 7.1.1 | 1 | Windows process execution environment. |
| symfony/routing | 7.1.1 | 2 | URL generation. |
| phpunit/phpunit (dev) | 10.5.24 | 1 | PHPT coverage handling; tidak diasumsikan exposed pada web production. |

Rujukan primer untuk triage:

- [Laravel TokenGuard advisory GHSA-f9x3-7vq9-cph6](https://github.com/laravel/framework/security/advisories/GHSA-f9x3-7vq9-cph6): prasyarat token guard dan database perlu dicek; audit tidak menjalankan bypass. Schema token yang hilang pada snapshot ini juga membatasi kesimpulan exploitability.
- [Laravel file validation GHSA-78fx-h6xr-vch4](https://github.com/laravel/framework/security/advisories/GHSA-78fx-h6xr-vch4): berkaitan dengan wildcard array file validation; upload Arenaku yang ditinjau adalah field tunggal, sehingga jangan menyamakan hasil scanner dengan reproduksi bypass upload.
- [Symfony HeaderUtils ReDoS](https://symfony.com/cve-2026-108082) dan [Symfony PATH_INFO advisory](https://symfony.com/blog/cve-2025-64500-incorrect-parsing-of-path-info-can-lead-to-limited-authorization-bypass): periksa exposure request path/header pada deployment target.
- [PHPUnit PHPT advisory](https://github.com/sebastianbergmann/phpunit/security/advisories/GHSA-vvj3-c3rp-c85p): pisahkan risiko tool development dari aplikasi HTTP.

Frontend vendor tidak tercakup Composer. Npm audit diblokir ENOLOCK, dan salinan vendor Bootstrap/jQuery/DataTables di `public/` juga tidak direpresentasikan sebagai locked npm tree. Tidak mengklaim frontend dependency bebas vulnerability atau melabeli versi tua rentan tanpa advisori yang cocok.

## E. UI/UX, responsive, dan accessibility

Review mengikuti [Web Interface Guidelines](https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md), skill lokal `web-design-guidelines` dan pencarian UX `keyboard focus modal` dari `ui-ux-pro-max`. Target aksesibilitas adalah [WCAG 2.2 AA](https://www.w3.org/WAI/WCAG22/quickref/); panduan kenyamanan touch 44px bukan otomatis ukuran minimum universal AA.

### Matriks screenshot nyata

Tinggi viewport pengujian 900px; full-page screenshot digunakan untuk melihat bagian bawah. Browser desktop dengan resize viewport, **bukan perangkat iOS/Android fisik atau emulasi touch/keyboard virtual**.

| Halaman | 360 | 390 | 768 | 1024 | 1440 |
| --- | --- | --- | --- | --- | --- |
| Homepage dan seluruh venue cards/footer | Dilihat | Dilihat | Dilihat | Dilihat | Dilihat |
| Detail + hasil jadwal ready | Dilihat | Dilihat | Dilihat | Dilihat | Dilihat |
| Form booking tanpa isi | Dilihat | Dilihat | Dilihat | Dilihat | Dilihat |
| Login | Dilihat | Dilihat | Dilihat | Dilihat | Dilihat |
| Permintaan reset password | Dilihat | Dilihat | Dilihat | Dilihat | Dilihat |
| Form password baru, token dummy | Dilihat | Dilihat | Dilihat | Dilihat | Dilihat |
| Registrasi legacy | — | Dilihat, QA-025 | — | — | — |
| Overview admin/penjaga, profil, management | Belum | Belum | Belum | Belum | Belum |

Pada enam halaman utama yang dilihat, tidak terdeteksi overflow dokumen melebihi viewport. Layout berpindah dari satu kolom mobile ke susunan desktop; jadwal, tombol, label dan kartu tetap terbaca. Brand green/mist, Barlow Semi Condensed/Source Sans 3, CTA dan status konsisten. Foto venue existing tidak diganti mock. Caption/auth heading dan presisi jam masih dapat dipoles (QA-032). Alamat venue memuat URL Maps sebagai teks biasa yang membungkus; tidak dianggap broken link karena memang bukan anchor.

### Aksesibilitas: yang diperiksa dan yang belum

| Pemeriksaan | Bukti/hasil |
| --- | --- |
| Form labels | Input booking dan reset baru yang terlihat memiliki label; registrasi legacy memiliki 5 input tanpa label terkait. |
| Fokus keyboard login | Tab email → password → tombol reveal; outline solid terlihat pada computed style, Space mengubah reveal dan `aria-pressed`. Tidak memasukkan credential. |
| Required field booking | Klik submit kosong memfokuskan `nama`; request tidak dikirim. |
| Struktur/landmark | Main, nav labels, skip links, headings dan aria-current tersedia dari source/AX; urutan heading auth perlu penyempurnaan. |
| Status/error | Text labels tersedia selain warna; error summary dan inline errors diuji pada rendered fixtures, bukan semua validasi live. |
| Images | Alt dan dimensi tersedia untuk gambar utama yang diperiksa; fallback dan lazy loading venue ada dalam kode. |
| Reduced motion | CSS public/dashboard memiliki override prefers-reduced-motion; tidak menjalankan emulasi preferensi OS. |
| Sidebar/mobile focus trap | Source memuat Escape, focus restore, inert workspace dan Tab wrapping; belum diuji langsung pada browser auth. |
| Modal focus/Escape/backdrop | Bootstrap 4 markup dan ID unik lolos render test; perilaku keyboard nyata belum diuji. |
| Screen reader / 200–400% zoom / forced-colors | Belum diuji. DOM/AX bukan pengganti pengujian assistive technology. |
| Axe/Lighthouse | Tidak dijalankan; tidak ada skor otomatis yang diklaim. |

Perhitungan kontras statis memakai formula luminance sRGB, **bukan scan seluruh pixel/state**:

| Pasangan warna | Rasio |
| --- | ---: |
| Court Green `#245D4C` / putih | 7.65:1 |
| Muted Green `#53675E` / putih | 6.05:1 |
| Muted Green / Court Mist `#F3F6F3` | 5.56:1 |
| Orange teks `#A54728` / putih | 5.95:1 |
| Input border `#788B80` / putih | 3.62:1 |
| Heading `#263E35` / Court Mist | 10.58:1 |

Pasangan teks tersebut memenuhi ambang teks normal AA pada background yang disebutkan. Kontras foto overlay, seluruh state vendor, disabled controls, focus ring pada tiap background, dan semua kombinasi table/modal belum diverifikasi menyeluruh. Tidak menyatakan aplikasi WCAG-conformant.

## F. Performance dan maintainability

Temuan dengan ID: skala table/modal **QA-026**, gambar **QA-027**, token/dependency layout **QA-030**, relasi court **QA-031**, dan pipeline asset **QA-023**.

Observasi tambahan yang membatasi kesimpulan:

- Booking index menggunakan eager loading `jadwalLapangan.lapangan` dan `jadwalLapangan.court`; availability/jadwal menggunakan `with('court')`. Tidak ada bukti N+1 pada loop utama tersebut. Detail/confirmation melakukan lazy relation lookup pada satu record; belum diprofilkan.
- Public query dibatasi 3 venue per halaman. Search `LIKE %term%` berpotensi mahal pada dataset besar, tetapi belum diukur dengan EXPLAIN/load test. Statistik memakai COUNT dan cocok pada snapshot; HomeController tetap menghitung users untuk penjaga walaupun tidak ditampilkan.
- Ukuran file lokal: `sb-admin-2.min.css` 177,176 byte, `arenaku-dashboard.css` 17,491, `arenaku-public.css` 15,583, `arenaku-login.css` 3,200. Ukuran ini uncompressed di disk, bukan network transfer.
- Google Fonts memakai preconnect dan `display=swap`. CSS dasar tetap render-blocking. Layout publik aktif tidak memuat AOS/Swiper dari cabang CMS lama. Tidak ada framework baru yang ditemukan pada redesign aktif.
- Tidak mengukur LCP, INP, CLS, TTFB, query count/time, bandwidth throttling, atau cache hit rate. Tidak mengklaim skor Core Web Vitals lulus/gagal dari screenshot.
- File lama/layout alternatif perlu inventory penggunaan sebelum dihapus. Nama input, route, CSRF, controller, dan status yang ada tetap dipertahankan oleh audit ini.

## G. Rencana perbaikan Phase 5B

Urutan berikut adalah **rekomendasi untuk direview**, bukan perubahan yang sudah dikerjakan.

| Urutan | Paket perbaikan | Issue | Bukti kelulusan yang diperlukan |
| --- | --- | --- | --- |
| 1 | Siapkan jalur test terisolasi dan baseline yang reproducible | QA-024, QA-023 | Runner standar lulus; test DB terpisah dan guard menolak DB development; build tidak menimpa legacy output. |
| 2 | Tutup akses management dan lindungi data pelanggan | QA-001–004 | Matrix guest/admin/penjaga untuk GET/POST/PUT/DELETE; 403/redirect sesuai policy; test IDOR sumber dan target venue. |
| 3 | Triage advisori dan hardening credential/upload | QA-009–012, QA-028 | Login rehash/reset teruji pada akun disposable; tidak ada password di session; upload aman; audit dependency ulang dengan keputusan reachability. |
| 4 | Definisikan lifecycle reservasi dan implementasi atomik | QA-005–008, QA-015 | Dua request simultan satu slot → tepat satu sukses; retry tidak menggandakan; ready/booked/closed, durasi, overlap, cancel/delete dan rollback teruji di MySQL test. |
| 5 | Perbaiki workflow/form dan route contract | QA-013–022 | Search/pagination >3 hasil konsisten; URL bebas CSRF; profil clear/error sesuai schema; show/API status benar; base URL didokumentasikan. |
| 6 | Konsistensi auth, file cleanup dan accessibility | QA-025, QA-029, QA-032 | Auth layout konsisten jika route tetap didukung; label/error/keyboard benar; failure/cascade upload cleanup diuji tanpa merusak histori. |
| 7 | Optimasi setelah correctness aman | QA-026, QA-027, QA-030, QA-031 | Ukur dataset realistis dan mobile cold load; hilangkan drift tanpa mencampur Bootstrap 4/5; model court disepakati. |
| 8 | Acceptance regression end-to-end | Seluruh area | Browser admin dan penjaga, lima viewport, sidebar, DataTables asli, modal validation recovery, upload, CRUD authorized, login/logout, dan reset email pada staging. |

Keputusan bisnis sebelum implementasi: fixed-slot vs multi-slot booking; definisi reservasi aktif; arti cancel/delete/closed; court fisik per venue vs label global; registrasi pengelola publik atau hanya dibuat admin; dan kebutuhan reassignment venue. UI saat ini tidak menyediakan reassignment `user_id` oleh admin, sementara venue baru selalu dimiliki pembuatnya; jangan mengasumsikan workflow penugasan sudah tersedia.

## H. Command log, batas pengujian, dan status akhir

### Perintah dan hasil

| Perintah/check | Hasil |
| --- | --- |
| `git status --short`, baseline commit | Bersih saat mulai; diverifikasi kembali saat selesai. |
| `php artisan route:list --json` | Berhasil; middleware aktual dicatat. |
| `php artisan test --do-not-cache-result` | Gagal sebelum tests: bootstrap autoload tidak dapat dibuka. |
| `php vendor/bin/phpunit --do-not-cache-result` | Gagal dengan pesan bootstrap yang sama. |
| Direct PHPUnit, file test non-DB dipilih eksplisit | **17 tests, 191 assertions lulus**; tidak mencakup Feature/ExampleTest homepage query. |
| `node --test tests/Frontend/dashboard-tables.test.cjs` | **2/2 lulus**. |
| `node --check` empat JS aplikasi | Lulus: public, dashboard, login, dan demo/datatables initializer. |
| `php -l` untuk app/routes/config/migrations | **62 file, 0 failure**. |
| Blade `compileString()` seluruh resources/views | **51 template, 0 failure**; bukan full HTTP render semua view. |
| GET kernel + SELECT dengan transaksi READ ONLY | Berhasil; status/ownership/integritas dirinci di atas. Inisialisasi harness pertama gagal karena request belum dibind, kemudian diperbaiki hanya di proses pengujian. |
| Password assignment/reset/flash di memori | Raw/reset hashing benar; prehashed assignment gagal cocok; withInput menyimpan key password. |
| `npm.cmd run production` | Gagal pada `cross-env` yang tidak ada; tidak menghasilkan asset. |
| `npm.cmd audit --offline --json` | ENOLOCK; tidak membuat lockfile/dependency tree. |
| `composer audit --locked --no-dev --format=json --no-interaction` | Awalnya DNS terhalang sandbox; berhasil setelah akses jaringan disetujui, advisori ditemukan. |
| `composer audit --locked --format=json --no-interaction` | Advisori runtime + development diperiksa, hasil diringkas di D. |
| Screenshot browser | Enam jenis halaman × lima lebar; registrasi tambahan 390px. |

Reproduksi lane test tanpa database (PowerShell; environment hanya proses command, bukan edit `.env`):

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:SESSION_DRIVER='array'
$env:CACHE_DRIVER='array'
$env:MAIL_MAILER='array'
@'
<?php
require 'vendor/autoload.php';
exit((new PHPUnit\TextUI\Application)->run([
    'phpunit', '--no-configuration', '--do-not-cache-result',
    'tests/Unit',
    'tests/Feature/DashboardFoundationViewsTest.php',
    'tests/Feature/ManagementViewsTest.php',
    'tests/Feature/PublicBookingViewsTest.php',
    'tests/Feature/LoginViewTest.php',
    'tests/Feature/PasswordResetViewsTest.php',
]));
'@ | php
```

Lane tersebut memakai fixture in-memory dan tidak membuka koneksi SQLite. Karena pdo_sqlite tidak ada, kelulusannya **tidak** membuktikan database test sudah bekerja. Jangan menjalankan suite penuh dengan `--no-configuration` tanpa environment eksplisit: default `.env` dapat mengarah ke database development. Tes homepage digantikan observasi HTTP GET baca-saja dalam audit ini, bukan dilaporkan sebagai PHPUnit test yang lulus.

### Yang tetap belum diverifikasi

- Tidak ada session browser admin/penjaga atau kredensial test yang disediakan. `/home` mengarah ke login. Role fixture kernel tidak membuktikan login/logout atau pengalaman browser authenticated.
- Tidak ada database MySQL disposable yang dikonfigurasi; SQLite XML belum dapat dipakai. Tidak ada migrate:fresh, seeder, INSERT/UPDATE/DELETE test, concurrent booking, cancellation, upload replacement, atau reset token/email creation.
- Tidak menguji kredensial, brute force, exploit dependency, script upload, atau sistem eksternal. Composer hanya mengirim metadata dependency ke registry untuk audit.
- Tidak ada pengujian full suite database, browser CRUD asli, sidebar/mobile focus trap, actual DataTables sorting/filter/pagination, modal keyboard/focus recovery, session expiry/419 recovery, dan file upload validation end-to-end.
- Pagination klik halaman 2 belum tersedia pada data nyata. Defect filter GET tetap direproduksi dengan query `page=1`, dan cabang controller menunjukkan masalah yang sama pada halaman berikutnya.
- Tidak ada pengiriman reset email atau perubahan password; `MAIL_MAILER=log` berarti delivery email lokal tidak diasumsikan bekerja. Token dummy hanya untuk melihat form.
- Tidak ada audit screen reader penuh, zoom tinggi, keyboard virtual mobile, Safari/Firefox, reduced-motion emulation, network/load test, atau audit otomatis accessibility lengkap.
- Snapshot database kecil dan point-in-time. Tidak ada duplikat/overlap sekarang bukan jaminan tidak dapat muncul. Tidak mengubah data inconsistent yang ditemukan.

**Status Phase 5A:** inspeksi, safe checks, klasifikasi temuan, dan rencana remediasi selesai. Acceptance produksi/keamanan belum lulus. **STOP: tunggu persetujuan eksplisit sebelum Phase 5B.**

Rekomendasi commit dokumentasi: `docs(qa): add Phase 5A application audit and remediation plan`
