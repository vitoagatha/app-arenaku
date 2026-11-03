# Arenaku Phase 5B — Frontend UI/UX QA & Final Polish

Tanggal: 3 November 2026 (Asia/Jakarta). Baseline: `docs/qa/phase-5-audit.md`, Git `9fdb1c0`. Laporan Phase 5A sudah ada sebagai file untracked saat pekerjaan ini dimulai dan tidak diubah.

## 1. Executive summary

Polish frontend selesai untuk direview. Registrasi dan konfirmasi password kini menggunakan Courtside; halaman verifikasi email memakai shell yang sama. Label permanen, autocomplete, inline error, feedback submit, dan reveal password konsisten. Warna, tipografi, spacing, radius, dan motion primitives dipusatkan tanpa mencampur Bootstrap 5 publik dan Bootstrap 4 dashboard.

Foto hero dan logo memakai turunan WebP dari aset asli. Waktu slot/ringkasan/list management ditampilkan HH:mm; nilai input dan data tersimpan tetap utuh. Modal logout mendapat adapter management yang sama, dan DataTables hanya dimuat pada route daftar yang membutuhkannya. CSS/JS aplikasi memakai versi berdasarkan waktu modifikasi file agar browser tidak memakai stylesheet lama setelah deployment.

Tidak ada controller, model, route, middleware, dependency, schema, validasi server, izin, atau logika booking yang diubah. Tidak ada operasi database tulis. Hasil frontend ini bukan penilaian kesiapan produksi atau keamanan aplikasi.

## 2. Coverage matrix

| Area | Metode | Hasil/batas |
| --- | --- | --- |
| Homepage, header/footer, kartu, alamat/nama panjang existing | Browser screenshot lima viewport | Terbaca, gambar termuat, tidak ada overflow dokumen. |
| Search POST | Browser, query `Giri` | Satu hasil; input/filter text dipertahankan; halaman scroll menuju hasil (scrollY sekitar 483 pada desktop). Backend pagination tetap bermasalah menurut Phase 5A. |
| Venue/detail dan slot ready | Browser POST tanggal 2024-07-12, screenshot lima viewport | Tiga slot ready; tanggal Indonesia dan interval HH:mm benar. POST ini membaca jadwal, tidak membuat booking. |
| Form booking | Screenshot lima viewport + view tests | Summary dan form terbaca, kontrak POST `_token`, `jadwal_lapangan_id`, `nama`, `no_whatsapp`, `durasi` tetap tersedia. Tidak submit. |
| Booked/closed, empty state, success, validation fixtures | Inspeksi + existing view tests | Tidak mengubah status atau copy menjadi paid/confirmed. Tidak membuat booking untuk membuka success page. |
| Registrasi | Screenshot lima viewport, DOM/AX, keyboard + view tests | Lima input memiliki label terkait; old input non-password/error tampil. Tidak membuat akun. |
| Login, forgot/reset password | DOM/ukuran lima viewport; screenshot 390/1440; view tests | Render dan aset berhasil; reset memakai token dummy; tidak mengirim email atau password. |
| Konfirmasi password | View test + source | Kontrak POST dan current-password autofill dipertahankan. Browser authenticated belum tersedia. |
| Verifikasi email | Source + Blade compile | Shell diperbarui. Route `verification.resend` existing belum terdaftar; tidak diklaim berfungsi. |
| Admin/penjaga overview dan profil | Existing view tests + source | Role navigation/count fixtures terjaga; shared tokens dimuat. Bukan browser login nyata. |
| Venue/court/user forms, booking dan schedule management | Existing view tests + source | Kontrak field, CSRF, method spoofing dan ID modal terjaga. Belum CRUD browser. |
| DataTables | Dua tes Node + asset inclusion render test | Adapter scroll/label dan defaults terjaga; asset tetap ada pada booking list, tidak pada overview. Bukan plugin asli di browser auth. |
| Sidebar, upload, delete, modal keyboard | Source saja | Interaksi existing dipertahankan. Belum live authenticated; tidak dinyatakan lulus end-to-end. |

## 3. Findings dan fixes

### UI-001 / QA-025 — Medium — Auth legacy dan input tanpa label

- Lokasi: `resources/views/auth/register.blade.php:1`, `resources/views/auth/passwords/confirm.blade.php:1`, `resources/views/auth/verify.blade.php:1`.
- Reproduksi sebelum: buka registrasi 390px; lima field hanya menggunakan placeholder dalam template SB Admin biru. Confirm/verify masih meng-extend layout auth lama.
- Aktual sebelum: visual berbeda dari login/reset; nama field tidak persisten. Ekspektasi: shell Courtside, label terlihat, error terkait field, navigasi dan keyboard jelas.
- Akar masalah: tiga view tertinggal pada layout legacy.
- Fix: shell login bersama, label/id, autocomplete, inline aria error, feedback component, reveal controls dan loading status pada register/confirm. Tidak menambahkan role input atau mengubah route/method/nama field. Link verifikasi lama tetap dipertahankan.
- Verifikasi: registrasi screenshot lima viewport, 0 input tanpa label; Tab dari nama depan menuju nama belakang dengan outline solid; Space reveal mengubah type dan aria-pressed. Register/confirm render tests lulus. Confirm browser belum; verify route dependency masih terbuka.

### UI-002 / QA-027 — Medium — Hero/logo terlalu besar

- Lokasi: `resources/views/cms/index.blade.php:12`, `resources/views/components/auth/visual.blade.php:1`, tiga layout Courtside.
- Reproduksi sebelum: lihat URL gambar hero/logo di HTML lalu ukuran aset di disk. Hero JPG 1,594,775 byte; logo JPG 347,436 byte untuk frame kecil; visual auth eager meski disembunyikan di mobile.
- Ekspektasi: aset sesuai ukuran render, tidak merusak original, dimensi layout stabil. Akar masalah: satu gambar original digunakan di semua layar.
- Fix: WebP hero 640/1200 dengan srcset/sizes di homepage, logo 640 di layout, hero auth 640 lazy. Original tidak dihapus atau ditimpa. Turunan dibuat deterministik dengan Pillow thumbnail, kualitas WebP 82/method 6; rasio asli dipertahankan.
- Hasil ukuran disk: hero 1200 **60,048 byte** (~96% lebih kecil); hero 640 **21,750 byte**; logo **3,182 byte** (~99% lebih kecil). Ini ukuran file, bukan hasil pengukuran transfer/LCP.
- Verifikasi: gambar terlihat termuat di halaman yang diuji, crop dan identitas tetap sesuai. Browser dapat tetap mengambil kandidat besar setelah resize; srcset bukan jaminan setiap resize melakukan unduh baru. Venue upload dinamis belum memiliki pipeline responsive variants; ditunda.

### UI-003 / QA-030 — Low — Token duplikat dan DataTables global

- Lokasi: `public/css/arenaku-tokens.css:1`, `public/css/arenaku-public.css:1`, `public/css/arenaku-dashboard.css:1`, `resources/views/layouts/admin.blade.php:2`.
- Reproduksi sebelum: bandingkan token public/dashboard dan script overview/profil. Nilai brand disalin, DataTables selalu dimuat.
- Ekspektasi: satu sumber primitives; plugin hanya pada halaman yang memakai tabel. Akar masalah: tiap adapter membawa foundation dan asset global.
- Fix: palette, fonts, spacing, radii dan motion dipindah ke CSS bersama. Control density tetap khusus dashboard. Asset DataTables dikondisikan pada lapangan.index, courts.index, jadwal.*, booking.index, users.index; jQuery/Bootstrap/SB Admin tetap tersedia. Auth masih memakai adapter dashboard, pengurangan CSS lebih jauh ditunda agar tidak merusak controls.
- Verifikasi: view test overview tidak memuat DataTables, booking list tetap memuat CSS/JS/initializer. Browser publik/auth berhasil setelah ekstraksi. Tabel browser authenticated belum diuji.

### UI-004 / QA-032 — Low — Heading auth dan waktu sulit dipindai

- Lokasi: `resources/views/components/auth/visual.blade.php:1`, `resources/views/components/arenaku/booking-summary.blade.php:5`, `resources/views/cms/cek-booking.blade.php`, list jadwal/booking dashboard.
- Reproduksi sebelum: heading h2 promosi mendahului h1 tugas; jam menampilkan detik dan membungkus pada ringkasan tablet.
- Ekspektasi: heading tugas utama jelas; jam mudah dibandingkan. Akar masalah: caption memakai heading dan jam mentah dipakai untuk display.
- Fix: caption menjadi paragraph display; HH:mm pada teks/accessible label publik dan teks list. Input time, submitted values, tanggal, duration dan sorting/date contracts tetap utuh.
- Verifikasi: AX auth memiliki h1 tugas tanpa h2 dekoratif sebelumnya; booking 768px menampilkan `07:00 – 09:00` satu baris. Jadwal lima viewport dan view tests lulus. Management hanya render fixtures, bukan browser.

### UI-005 — Low — Nama tombol reveal ambigu

- Lokasi: `public/js/arenaku-login.js:11`.
- Reproduksi sebelum: reset memiliki dua tombol bernama Tampilkan; pembaca layar tidak membedakan target dari nama tombol saja.
- Ekspektasi: nama mencakup field dan berubah mengikuti state. Akar masalah: hanya textContent generik.
- Fix: nama diambil dari label input dan diperbarui saat toggle/pageshow; aria-controls/aria-pressed dipertahankan.
- Verifikasi: keyboard Space pada registrasi menghasilkan `Sembunyikan kata sandi`, type=text, aria-pressed=true. Tidak memasukkan credential.

### UI-006 — Low — Modal logout tidak memakai adapter modal management

- Lokasi: `resources/views/layouts/admin.blade.php`, `public/css/arenaku-dashboard.css`.
- Reproduksi: bandingkan kelas logoutModal dan schedule modal. Logout tidak memenuhi selector `.akd-management.modal`, sehingga radius/close touch size/overscroll berbeda.
- Ekspektasi: modal shared memakai styling yang sama. Akar masalah: class adapter hilang.
- Fix: tambahkan class adapter pada logout modal; tambahkan overflow-wrap untuk judul/task panjang dashboard.
- Verifikasi: inspeksi selector dan rendered layout lulus. Visual serta focus/Escape modal browser authenticated belum diuji; tidak diklaim resolved end-to-end.

### UI-007 — Low — Browser mempertahankan CSS lama setelah polish

- Lokasi: tiga layout Courtside, URL CSS/JS aplikasi.
- Reproduksi: setelah caption diubah menjadi paragraph, tab dengan CSS lama menampilkan caption kecil; navigation/reload biasa masih memakai aset cache.
- Ekspektasi: markup dan adapter yang sesuai dimuat bersamaan. Akar masalah: URL stylesheet tetap saat isi berubah.
- Fix: tambahkan query versi filemtime untuk CSS/JS aplikasi pada layout, tanpa menjalankan Mix atau mengubah vendor.
- Verifikasi: setelah reload, computed caption menjadi 32px; screenshot login/reset desktop konsisten. Deployment perlu menyertakan semua file dan timestamp; CDN/offloaded asset workflow belum diuji.

## 4. Responsive results

Browser lokal `http://app_arenaku.test`, viewport height 900, desktop resize; bukan device touch fisik. Screenshot diperiksa langsung melalui tool; tidak menyimpan screenshot yang berisi akun nyata. Tidak ada akun nyata dibuka.

| Halaman | 360 | 390 | 768 | 1024 | 1440 |
| --- | --- | --- | --- | --- | --- |
| Registrasi | Screenshot | Screenshot | Screenshot | Screenshot | Screenshot |
| Homepage/full footer | Screenshot | Screenshot | Screenshot | Screenshot | Screenshot |
| Venue + tiga slot ready | Screenshot | Screenshot | Screenshot | Screenshot | Screenshot |
| Booking form/summary | Screenshot | Screenshot | Screenshot | Screenshot | Screenshot |
| Login | DOM/ukuran | Screenshot | DOM/ukuran | DOM/ukuran | Screenshot |
| Forgot password | DOM/ukuran | Screenshot | DOM/ukuran | DOM/ukuran | Screenshot |
| Reset password dummy token | DOM/ukuran | Screenshot | DOM/ukuran | DOM/ukuran | Screenshot |
| Dashboard/profil/management | Belum live | Belum live | Belum live | Belum live | Belum live |

Tidak ada scrollWidth dokumen yang melebihi innerWidth pada halaman tersebut. Nama venue dan URL alamat panjang existing membungkus; navbar ringkas tetap muat; tidak perlu menambahkan mobile menu baru. Tanggal native date input mengikuti locale browser (pada browser ini MM/DD/YYYY), sedangkan heading hasil memakai tanggal Indonesia; value tetap ISO. Teks hari/tanggal dan kolom summary tetap terbaca pada tablet. Screenshot registrasi awal menangkap caption cached; pemeriksaan setelah versi asset membuktikan font caption sudah benar.

## 5. Accessibility

Review menggunakan skill frontend-design, redesign-existing-projects, ui-ux-pro-max (query UX `persistent form labels`) dan [Web Interface Guidelines](https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md). Target WCAG 2.2 AA.

Label auth dan nama reveal ditingkatkan; password tidak diisi ulang dari old input. Inline errors terhubung aria-describedby/aria-invalid dan summary fokus tersedia lewat shared feedback. Skip link, alt/dimensi image, focus outline dan reduced-motion rules existing dipertahankan; focus-visible publik kini juga mencakup select/textarea.

Keyboard nyata diuji pada register (Tab, Space reveal); tidak ada pengiriman akun. Palette tidak berubah: rasio token Phase 5A tetap berlaku karena warna identik; belum menjalankan scan semua pixel/state ulang. Belum ada axe, screen reader, forced-colors, zoom tinggi, keyboard modal/dashboard, atau reduced-motion OS emulation. Tidak mengklaim seluruh aplikasi memenuhi WCAG.

## 6. Performance

WebP/ukuran responsif dan lazy visual auth mengurangi kebutuhan aset statis. DataTables tidak dimuat pada overview/profil/form edit/create yang tidak memakainya. Shared tokens mengurangi drift; ukuran CSS total tidak otomatis lebih kecil karena satu request CSS tambahan. Bootstrap 4/5 tetap terpisah dan tidak di-upgrade. Font preconnect/display=swap existing dipertahankan.

Belum mengukur Core Web Vitals, bandwidth throttling, cold cache, atau query performance. Venue upload dinamis, auth CSS bundle penuh, dan legacy layout yang tidak aktif tetap menjadi peluang optimasi setelah pemetaan penggunaan lengkap.

## 7. Regression checks

- **20 tes PHP / 220 assertions lulus**, direct PHPUnit `--no-configuration --do-not-cache-result`, dengan APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, session/cache/mail array. Suite yang dipilih: Unit, DashboardFoundationViewsTest, ManagementViewsTest, PublicBookingViewsTest, LoginViewTest, PasswordResetViewsTest, AuthPolishViewsTest. Semuanya fixture in-memory; tidak membuka SQLite atau database development.
- **2/2 tes Node lulus**: `node --test tests/Frontend/dashboard-tables.test.cjs`; adapter jQuery mock, bukan real plugin.
- `node --check` untuk public/dashboard/login/datatables initializer: lulus.
- Blade compileString seluruh **51 template**: lulus di memori. Ini tidak menjamin setiap route render (contoh verify tergantung route backend).
- Dua assertion lama awalnya gagal karena mengharapkan logo JPG dan DataTables global; diperbarui mengikuti output baru dan ditambah positive test bahwa tabel tetap memperoleh dependencies. Run akhir lulus.
- Browser: public/auth images berhasil, console error collection kosong pada sesi yang diperiksa; POST pencarian dan availability tetap bekerja. Tidak submit booking/register/reset/CRUD.
- Mix diperiksa: source SCSS hanya komentar dan menargetkan legacy compiled app.css. Build **sengaja tidak dijalankan** agar tidak menimpa aset incomplete. Phase 5A sudah menunjukkan cross-env/node_modules tidak tersedia. Tidak install dependencies atau menjalankan npm update/audit baru.
- Scope/diff diperiksa: perubahan terbatas Blade/CSS/JS/aset/test frontend/dokumentasi; original QA report, vendor dan backend tidak diedit.

## 8. File inventory

Modified:

- `public/css/arenaku-dashboard.css`, `arenaku-login.css`, `arenaku-public.css`
- `public/js/arenaku-login.js`
- `resources/views/layouts/admin.blade.php`, `login.blade.php`, `public-courtside.blade.php`
- `resources/views/auth/register.blade.php`, `verify.blade.php`, `passwords/confirm.blade.php`
- `resources/views/components/auth/visual.blade.php`, `components/arenaku/booking-summary.blade.php`
- `resources/views/cms/index.blade.php`, `cms/cek-booking.blade.php`
- `resources/views/dashboard/booking/index.blade.php`, `dashboard/jadwal/jadwal.blade.php`
- `tests/Feature/DashboardFoundationViewsTest.php`, `tests/Feature/LoginViewTest.php`

Created:

- `public/css/arenaku-tokens.css`
- `public/assets/img/hero-bg-640.webp`, `hero-bg-1200.webp`, `logo_arenaku-640.webp`
- `tests/Feature/AuthPolishViewsTest.php`
- `docs/qa/phase-5-ui-qa.md`

## 9. Deferred dependencies

Seluruh temuan backend Phase 5A tetap terbuka: QA-001–012 (authorization, ownership, atomic booking, duration/status, password/session/upload/dependencies), QA-013–021 (search/pagination/CSRF URL, lifecycle booking, profil, show routes, booking back-state, API), QA-022 (subfolder asset configuration), QA-023–024 (build/test infrastructure), QA-026 (server-side dataset scale), QA-028–029 (deployment/log/file lifecycle), QA-031 (model court).

QA-025: presentasi register/confirm diperbaiki; kebijakan registrasi dan route verifikasi tidak diubah. `Auth::routes()` belum mengaktifkan verification routes; link resend existing pada template akan tetap memerlukan keputusan backend. QA-027: static hero/logo dioptimalkan, bukan pipeline upload venue. QA-030: token dan pemuatan DataTables ditangani, pengurangan CSS/vendor lebih jauh ditunda. QA-032: tampilan/hierarchy diperbaiki tanpa mengubah jam tersimpan. Tidak ada backend finding yang ditutup oleh pekerjaan frontend ini.

## 10. Limitations dan handoff

Session browser admin/penjaga dan database disposable belum tersedia. UI dashboard hanya diverifikasi dengan source/render tests; sidebar mobile, focus trap, DataTables asli, upload preview, native delete dialog, Bootstrap modal keyboard, validation recovery, toast/session expiry, CRUD dan login/logout masih perlu sesi test berizin. Confirm password tidak dibuka lewat browser authenticated; verify route belum aktif. Tidak ada concurrency/booking write/security retest.

UI QA dan polish dalam batas frontend selesai; authenticated visual acceptance tetap outstanding. Berhenti untuk review, tidak melanjutkan remediasi backend.

Recommended commit: `fix(ui): polish Courtside auth accessibility and shared presentation`

Alternatif pemisahan: `perf(ui): optimize Courtside images and scope table assets`, lalu `docs(qa): record frontend polish verification and limitations`.
