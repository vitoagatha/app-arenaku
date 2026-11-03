# Arenaku — Phase 5C: Authenticated Dashboard UI Acceptance

Tanggal: 3 November 2026 (Asia/Jakarta). Baseline Git: `9fdb1c0` dengan perubahan frontend Phase 5B yang masih ada di working tree. Referensi: [Phase 5A](phase-5-audit.md) dan [Phase 5B UI QA](phase-5-ui-qa.md).

**Status terkini: PARTIAL — admin dan penjaga sudah diuji langsung; acceptance menyeluruh belum lengkap.** Kedua sesi diperoleh lewat login manual pengguna. Hasil terbaru ada di bagian 11–12, termasuk perbaikan frontend. Bagian 1–10 menyimpan checkpoint awal ketika sesi belum tersedia; matriks NOT TESTED di sana adalah hasil historis. Beberapa exact-width/visual checks admin dan skenario data-dependent masih outstanding. Tidak membaca password/cookie atau melewati autentikasi.

## 1. Dashboard acceptance matrix

PASS = perilaku diuji dan sesuai pada metode yang disebutkan. FAIL = perilaku diuji dan tidak sesuai. NOT TESTED = pengujian belum dapat dilakukan; bukan PASS atau FAIL. Kelulusan fixture tidak dihitung sebagai acceptance browser. Tidak ada screenshot dashboard atau viewport dashboard yang diverifikasi pada Phase 5C.

| Halaman / role yang harus diuji | 360px | 390px | 768px | 1024px | 1440px |
| --- | --- | --- | --- | --- | --- |
| Admin overview | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Penjaga overview | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Profil — admin dan penjaga | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Venue list/create/edit — admin dan penjaga sesuai akses | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Court list/create/edit — admin | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Schedule list/detail/modal — admin dan penjaga sesuai akses | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Booking list/edit — admin dan penjaga sesuai akses | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| User list/create/edit — admin | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |
| Sidebar/header/logout modal — kedua role | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED |

Court/user management tidak diharapkan muncul di navigasi penjaga. Visibilitas tersebut hanya diperiksa pada fixture; authorization URL backend tetap merupakan temuan Phase 5A dan tidak ditutup di sini.

## 2. Browser dan keyboard results

Browser terhubung yang ditemukan: ChatGPT in-app browser, tanpa tab existing saat pemeriksaan. Tidak ada sesi Arenaku terautentikasi yang tersedia melalui browser tersebut. Window Brave ada pada desktop, tetapi session/tab browser itu tidak tersedia lewat koneksi browser yang diperiksa; tidak mengasumsikan kondisi login di browser lain.

| Check | Status | Bukti / batas |
| --- | --- | --- |
| Aplikasi lokal dapat dibuka | PASS | Navigasi `/home` berhasil mencapai login; bukan bukti dashboard render. |
| Guest `/home` menuju login | PASS | URL akhir `/login`, AX berisi heading Masuk pengelola dan input email/password. |
| Sesi browser admin tersedia | NOT TESTED | Tidak ada session/kredensial yang dapat dipakai; role tidak diketahui sebelum login. |
| Sesi browser penjaga tersedia | NOT TESTED | Blocker yang sama. |
| Sidebar mobile open/close, backdrop dan active item | NOT TESTED | Dashboard tidak terbuka. |
| Tab/Shift+Tab, focus trap, Escape, focus restore sidebar | NOT TESTED | Kode handler tersedia; tidak dijalankan di browser authenticated. |
| Bootstrap modal open/close, Escape, focus return | NOT TESTED | Tidak membuka logout/schedule modal. |
| DataTables asli: search, sort, page size, pagination | NOT TESTED | Node tests memakai mock; tidak menjalankan plugin asli. |
| Responsiveness/table overflow/form alignment/button layout | NOT TESTED | Tidak mengambil screenshot dashboard pada kelima viewport. |
| Dashboard console errors dan missing assets | NOT TESTED | Tidak ada dokumen dashboard yang dimuat. Login yang dapat dibuka tidak membuktikan aset dashboard lengkap. |
| Validation recovery, upload preview, delete confirmation | NOT TESTED | Memerlukan halaman authenticated; tidak mengirim CRUD atau mengubah data. |

Tidak ada FAIL visual baru yang terkonfirmasi pada dashboard. Ini tidak berarti dashboard bebas defect: cakupan browser terblokir. Pemeriksaan keyboard publik/auth Phase 5B tidak dipindahkan menjadi hasil keyboard dashboard.

## 3. Defect dan blocker

### DASH-001 — Blocker pengujian — Sesi authenticated tidak tersedia

- Area: semua halaman dan interaksi dashboard, kedua role, semua viewport.
- Reproduksi: pilih browser terhubung; buka `/home` melalui navigasi normal.
- Aktual: dialihkan ke `/login`; hanya shell auth yang terlihat.
- Kebutuhan pengujian: sesi admin dan penjaga yang sah pada browser yang dapat dikontrol, atau akun test yang memang diizinkan. Redirect guest sendiri merupakan perilaku yang sesuai, **bukan defect frontend**.
- Dampak: acceptance visual, keyboard, DataTables, modal dan role navigation di browser belum dapat ditandatangani.
- Status: NOT TESTED untuk dashboard, BLOCKED untuk acceptance keseluruhan.
- Tindakan: pengguna dapat login sendiri di browser yang akan dipakai untuk QA; setelah sesi siap, lanjutkan pengujian tanpa mengekspor cookie atau mencantumkan kredensial di laporan. Untuk dua role, gunakan sesi/profil terpisah atau pergantian login yang diizinkan.

### Temuan sebelumnya yang tetap terbuka

UI-006 Phase 5B (logout modal) masih menunggu verifikasi visual/keyboard browser. Sidebar focus handling, DataTables asli, error recovery modal dan upload preview juga tetap belum terverifikasi. Temuan backend Phase 5A, khususnya QA-001–004 mengenai permission/ownership dan QA-005–008 mengenai reservasi, tidak dites ulang atau diperbaiki. Tidak menggunakan kelemahan route tersebut untuk mendapatkan akses dashboard.

## 4. Frontend fixes

Tidak ada perbaikan source pada Phase 5C. Tidak cukup bukti browser untuk menyatakan defect presentasi baru atau mengubah interaksi dashboard. Tokens, form contracts, vendor dan backend dipertahankan. Tidak menjalankan operasi booking, CRUD, reset password, migrasi atau seeding.

## 5. Files modified

Hanya file baru `docs/qa/phase-5-dashboard-acceptance.md` pada Phase 5C. Perubahan Blade/CSS/JS/aset/test dari Phase 5B serta dua laporan sebelumnya sudah ada ketika Phase 5C dimulai; bukan perubahan baru dalam checkpoint ini. Tidak melakukan stage atau commit.

## 6. Regression results

| Check yang dijalankan ulang pada Phase 5C | Status | Hasil |
| --- | --- | --- |
| Direct PHPUnit lane non-DB | PASS | **20 tests, 220 assertions**. |
| `node --test tests/Frontend/dashboard-tables.test.cjs` | PASS | **2/2 tests**. |
| `node --check public/js/arenaku-dashboard.js` | PASS | Syntax valid; bukan bukti interaksi DOM. |
| `node --check public/js/demo/datatables-demo.js` | PASS | Syntax valid; bukan plugin browser test. |

PHPUnit memakai `--no-configuration --do-not-cache-result` dan environment proses eksplisit: APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, SESSION_DRIVER=array, CACHE_DRIVER=array, MAIL_MAILER=array. Suite: Unit, DashboardFoundationViewsTest, ManagementViewsTest, PublicBookingViewsTest, LoginViewTest, PasswordResetViewsTest, AuthPolishViewsTest. Tidak membuka koneksi database; label sqlite tidak berarti driver/database test sudah tersedia.

Fixture dashboard mencakup admin menu/count values, penjaga operational shortcuts tanpa admin menu, profil role/old input/error dan kontrak PUT. Management fixtures memeriksa tabel/region/headers, CSRF/method spoofing/field names, status pending tidak menjadi lunas, empty table structure, validation markup dan ID modal jadwal unik. Nilai count pada fixture adalah input tes, bukan verifikasi ulang database aktual. `actingAs()` hanya dipakai oleh test view di memori; tidak membuat akses ke aplikasi/browser yang berjalan.

Tes Node menguji adapter scroll/label menggunakan mock jQuery. Tidak membuktikan sorting/search/paging DataTables asli. Runner PHPUnit standar dan database integration tetap memiliki keterbatasan Phase 5A; lane yang lulus tidak menutupnya. Build tidak diperlukan untuk dokumentasi dan tidak dijalankan; tidak menginstal/upgrade dependency.

## 7. Skenario lanjutan setelah sesi tersedia

1. Untuk setiap role, buka overview dan profil; periksa nama role, title, active nav, shortcut, label dan konten panjang tanpa merekam PII.
2. Pada 360/390px, buka sidebar lewat keyboard dan pointer; uji Tab wrap, Shift+Tab, Escape, backdrop, focus restore, scroll lock, lalu resize saat terbuka. Pada 768/1024/1440px periksa transisi ke sidebar desktop dan content width.
3. Buka list management yang memang diizinkan melalui menu; uji DataTables filter/sort/page size dan paging jika jumlah baris cukup. Periksa caption, overflow region, controls tetap terbaca dan document tidak ikut melebar.
4. Buka create/edit form tanpa submit; periksa label, focus, upload preview lokal bila diizinkan, tombol dan field state. Jangan mengubah atau menghapus record development.
5. Buka/tutup schedule create/edit dan logout modal; uji Escape, Tab containment, close button dan focus return. Batalkan/logout modal tanpa mengakhiri sesi jika masih dipakai QA.
6. Empty state dan validation recovery hanya diuji lewat kondisi existing yang aman atau database disposable; jangan memicu server mutation untuk membuat state visual. Catat NOT TESTED jika state tidak tersedia.
7. Periksa screenshot tiap halaman/viewport serta console/assets pada dokumen dashboard. Gunakan akun test tanpa data sensitif atau tutup PII saat membagikan bukti.

## 8. Final frontend acceptance

**BELUM DITERIMA / BLOCKED.** Regresi fixture PASS; acceptance browser authenticated **NOT TESTED**. Interaksi utama sidebar, tabel dan modal belum terbukti untuk kedua role, sehingga Phase 5C tidak dinyatakan complete dari sisi acceptance. Tidak ada klaim production-ready, aman, atau WCAG-conformant.

Pekerjaan berhenti pada pelaporan blocker ini sesuai execution boundary. Lanjutkan acceptance setelah sesi authorized tersedia; backend remediation belum dimulai.

## 9. Recommended commit

`docs(qa): record blocked authenticated dashboard acceptance checkpoint`

## 10. Resume — manual login handoff

Pada permintaan resume, browser capabilities diperiksa kembali. In-app browser mendukung tab interaktif yang dapat ditampilkan kepada pengguna. Halaman `http://app_arenaku.test/login` dibuka dengan `visible: true`; AX mengonfirmasi form email/password dan tombol Masuk ke dashboard. Tab ditandai handoff agar tetap terbuka untuk login manual.

**PASS:** browser interaktif dan halaman login tersedia untuk handoff. **NOT TESTED:** sesi admin/penjaga serta seluruh acceptance dashboard; menunggu pengguna menyelesaikan login sah. Status keseluruhan tetap BLOCKED, belum complete. Pengguna diminta login akun test admin terlebih dahulu dan memberi tahu setelah dashboard terbuka, tanpa mengirim password/cookie di chat. Pengujian role penjaga menyusul melalui login manual yang sama setelah pengujian admin selesai.

Tes regresi yang sebelumnya PASS tidak diulang karena belum ada perubahan source. Checklist manual pada bagian 7 tetap berlaku bila login interaktif tidak dapat diselesaikan. Perubahan resume ini hanya memperbarui laporan; tidak ada manipulasi session/authorization, submit CRUD, atau perubahan backend.

## 11. Resume — sesi admin authorized tersedia

Pengguna menyatakan telah login admin. Inventory tab mengonfirmasi `/home`, judul Ringkasan; pembacaan AX menunjukkan role Admin dan menu administrasi. Binding browser sempat gagal setelah handoff; reset runtime tool berhasil memulihkan akses ke tab yang sama tanpa logout, injeksi session atau perubahan aplikasi. Nama akun, email dan data pelanggan tidak dicatat dalam laporan. Tidak membagikan screenshot profil/user/booking berisi data pribadi.

### 11.1 Matrix admin terbaru

P = PASS dalam metode terbatas yang disebutkan; NT = NOT TESTED untuk acceptance visual penuh. Tidak ada penilaian ini yang mencakup submit CRUD.

| Admin page/component | 360 | 390 | 768 | 1024 | 1440 | Metode dan batas |
| --- | --- | --- | --- | --- | --- | --- |
| Overview | P | P | P | NT | P | Screenshot dan DOM; run kolom 1024 pertama terbaca 1025 CSS px, tidak dianggap exact-width PASS. |
| Profil | P (DOM) | NT | P (DOM) | P (DOM) | P (DOM) | Label, form bounds, document overflow; tidak screenshot data pribadi. Pembacaan 390 pernah kembali 360. |
| Venue list | P setelah fix | NT setelah fix | NT setelah fix | NT | NT setelah fix | Screenshot sebelum fix tersedia; visual setelah fix hanya dicek ulang pada 360. |
| Court list | P | P | P | NT | P | Screenshot; tidak ada overflow dokumen, tabel memakai scroll internal. |
| Pilih venue untuk jadwal | NT setelah fix | P (interaksi) | NT setelah fix | NT | NT setelah fix | Shared description fix diverifikasi keyboard/filter; screenshot tersedia sebelum fix, visual setelah fix belum diulang menyeluruh. |
| Booking list | P (DOM) | P (DOM) | P (DOM) | NT | P (DOM) | Plugin, label dan overflow; 1024 readback stale 360. Tidak screenshot customer data. |
| User list | P (DOM) | P (DOM) | P (DOM) | NT | NT | Plugin/labels; run 1024 memberi 1025 dan 1440 memberi stale 768. |
| Venue create | P (DOM) | P (DOM) | P (DOM) | P (DOM) | P (DOM) | Field labels/geometry: semua controls berada dalam viewport. |
| Court create | P (DOM) | NT | NT | P (DOM) | P (DOM) | Beberapa resize readback stale; tidak dipaksa PASS. |
| User create | NT | P (DOM) | NT | P (DOM) | P (DOM) | Beberapa resize readback stale. |
| Booking edit dan user edit | P (DOM) | P (DOM) | P (DOM) | P (DOM) | P (DOM) | URL edit diambil dari link existing; label dan input bounds saja. Tidak submit atau mengubah nilai. |
| Schedule create modal | P | P | P | P | P | Screenshot, exact CSS width 360/390/768/1024/1440, dialog muat di layar. |
| Seluruh halaman role penjaga | NT | NT | NT | NT | NT | Belum login penjaga. |

Catatan ukuran: browser memiliki zoom 90% (devicePixelRatio sekitar 0.9). Run awal memakai ukuran override mentah menghasilkan innerWidth 400/434/853/1138/1600, sehingga tidak dipakai untuk exact-width acceptance. Run berikutnya menyesuaikan override dan mencatat innerWidth. Pembacaan resize kadang tertunda; kandidat ukuran yang tidak cocok ditandai NT. Percobaan konfirmasi ulang screenshot juga sempat gagal capture. Ini keterbatasan tool, bukan defect responsive aplikasi. Viewport override sudah di-reset di akhir.

### 11.2 Interaksi nyata

| Check | Status | Bukti |
| --- | --- | --- |
| Menu admin/active navigation | PASS | Overview, profil, venue, court, jadwal, booking dan users menampilkan active state sesuai halaman. |
| Sidebar mobile open dan Escape | PASS | aria-expanded berubah, workspace tidak muncul di AX saat sidebar terbuka, Escape menutup dan fokus kembali ke Buka navigasi. |
| Sidebar Shift+Tab boundary | PASS terbatas | Shift+Tab dari brand pertama menuju Lihat halaman publik terakhir. Forward full-cycle, backdrop click dan semua resize-while-open belum diuji lengkap. |
| Logout modal open/Escape/focus restore | PASS | Dialog terlihat, fokus berada di dalam setelah transisi, Escape menghapus show dan fokus kembali ke Keluar dari akun. Tidak menekan Keluar. |
| Schedule create/edit modal open dan Escape | PASS | Create tampil dengan field wajib; edit memuat waktu/status existing. Ditutup tanpa save. |
| Modal layout create lima viewport | PASS | Content width sekitar 344/374/500/500/500 CSS px; tidak melebihi layar. |
| Schedule modal full Tab cycle/focus return | NOT TESTED penuh | Fokus masuk teramati; AX segera setelah Escape masih transisi. Tidak menyatakan focus-return schedule lulus. |
| DataTables real filtering/zero results | PASS | Lapangan, court, pilih venue jadwal, booking, users: query dummy menghasilkan pesan Tidak ada hasil; clear memulihkan data. |
| DataTables sorting | PASS | Klik header No pada kelima list mengubah aria-sort menjadi descending. |
| DataTables page size | PASS | Jadwal venue kedua menerima 25 dan kembali 10; info menunjukkan 9 baris. |
| Pagination next-page | NOT TESTED | Dataset terbesar yang dibuka hanya 9 jadwal; tidak ada page 2 pada ukuran minimum 10. |
| Console errors | PASS pada sesi yang diamati | Koleksi error tool kosong setelah list dan form checks; bukan klaim setiap route/error path bersih. |
| Broken visible images | PASS pada halaman yang dipindai | 0 visible completed images dengan naturalWidth=0. Bukan network trace seluruh asset/font. |
| Server validation, upload preview, delete dialog | NOT TESTED | Tidak submit form atau memicu mutation; tidak klik Hapus. |
| Venue/court edit | NOT TESTED browser | Create dibuka; edit hanya user/booking dan schedule modal pada resume ini. |

### 11.3 DASH-002 — Medium — Deskripsi panjang membuat baris tabel sangat tinggi

- Lokasi: `resources/views/dashboard/lapangan/index.blade.php`, `resources/views/dashboard/jadwal/index.blade.php`.
- Reproduksi: buka dua daftar tersebut dengan venue existing pada 360/390px atau desktop; deskripsi lengkap dipaksa ke kolom sempit. Satu baris menghabiskan beberapa layar dan tindakan berada jauh dari heading/control tabel.
- Root cause: `.akd-cell-description` selalu menampilkan seluruh deskripsi panjang pada cell tanpa disclosure.
- Ekspektasi: overview tabel ringkas, teks lengkap tetap tersedia dan searchable.
- Fix minimal: shared `components/dashboard/venue-description.blade.php` memakai native details/summary dengan nama venue sebagai konteks pembaca layar, ditambah adapter CSS/focus. Full text tetap escaped dalam DOM, nama field/route/data tidak berubah.
- Verifikasi setelah fix: pada 360px, dua row venue menjadi sekitar **168px dan 147px**, default tertutup; Enter membuka/menutup disclosure. Search `pencahayaan` tetap memperoleh satu hasil walaupun detail tertutup. Tidak menyimpan perubahan record.
- Status: **FAIL sebelum → PASS untuk perilaku yang diverifikasi setelah fix**. Shared markup dipakai pada kedua daftar. Tidak mengubah DataTables sorting/filtering atau menghapus isi deskripsi.

### 11.4 File changes dan regresi resume admin

Perubahan baru: `resources/views/components/dashboard/venue-description.blade.php` (created), `resources/views/dashboard/lapangan/index.blade.php`, `resources/views/dashboard/jadwal/index.blade.php`, `public/css/arenaku-dashboard.css`, dan laporan ini. Perubahan Phase 5B sebelumnya tetap dipertahankan. Tidak ada backend/vendor/dependency yang diedit.

Karena ada fix Blade/CSS, regresi relevan dijalankan ulang: **8 tests / 131 assertions PASS** (DashboardFoundationViewsTest + ManagementViewsTest, direct non-DB lane dengan environment terisolasi seperti bagian 6); **2/2 Node table adapter tests PASS**; `git diff --check` PASS. Suite lain yang sudah PASS tidak diulang. Tidak menjalankan build Mix yang berisiko menimpa output legacy.

### 11.5 Handoff role penjaga dan status akhir terkini

**PARTIAL, belum acceptance lengkap.** Admin sudah terverifikasi pada cakupan di atas dan satu defect tabel diperbaiki. Role penjaga, exact-width checks yang ditandai NT, seluruh visual profil/user/booking, modal full keyboard cycle, validation recovery, pagination page 2 dan beberapa edit pages masih outstanding. Tidak menutup temuan backend Phase 5A.

Tab dikembalikan ke overview admin dan ditandai handoff; pengguna diminta logout lalu login manual akun test penjaga pada tab yang sama dan memberi tahu setelah selesai. Tidak meminta password/cookie di chat. Backend remediation belum dimulai.

Recommended commit setelah resume ini: `fix(ui): keep venue management rows compact with accessible details`.

## 12. Resume — sesi penjaga authorized dan regresi interaksi shared

Pengguna menyatakan telah login penjaga. AX mengonfirmasi role Penjaga, satu venue ditugaskan, shortcut operasional dan tidak ada menu Administrasi/Data court/Pengguna. Nama akun dan data pelanggan tidak dicantumkan. Binding browser kembali memerlukan reset runtime tool untuk membaca tab yang sama; ini tidak mengubah sesi aplikasi.

### 12.1 Acceptance matrix penjaga

PASS (DOM) berarti pengukuran browser nyata untuk label, bounds dan overflow; bukan tanda seluruh presentasi halaman telah direview melalui screenshot. Court/user management tidak dibuka sebagai penjaga; akses backend tidak diuji atau diakali.

| Penjaga page/component | 360 | 390 | 768 | 1024 | 1440 | Bukti / batas |
| --- | --- | --- | --- | --- | --- | --- |
| Overview operasional | PASS | PASS | PASS (DOM) | PASS | PASS | Main-content screenshot; crop 768 kurang lengkap, tidak dianggap full visual verification. |
| Profil | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | 0 input tanpa label; 0 field melewati layar; tidak submit/screenshot data akun. |
| Venue list setelah overflow fix | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | Exact width dan scrollWidth diukur ulang; screenshot daftar sebelum fix diperiksa. Visual final lengkap masih parsial. |
| Venue edit milik penjaga | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | Dibuka dari link Edit existing, semua field berlabel dan bounds sesuai. Tidak mengubah field/save. |
| Pilih venue untuk jadwal setelah fix | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | Exact-width geometry ulang; teks deskripsi tetap bisa dibuka dan dicari. |
| Schedule detail venue authorized | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | Tidak ada overflow dokumen; region bernama; seluruh field modal berlabel. |
| Booking list | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | PASS (DOM) | Plugin/filter/sort berfungsi; 0 label hilang dan gambar visible rusak. Data pelanggan tidak dibagikan. |
| Court/user management | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | NOT TESTED | Bukan menu operasional penjaga; menu absent sudah PASS, tidak menyatakan server authorization benar. |

Readback innerWidth berhasil tepat **360/390/768/1024/1440** setelah kalibrasi. Nilai devicePixelRatio kali ini 1.125 tidak sama dengan rasio viewport override; kalibrasi awal menghasilkan 451 dan dikeluarkan dari hasil acceptance. Pengujian berikutnya menavigasi ulang setelah resize dan memeriksa innerWidth. Viewport dikembalikan ke default di akhir.

### 12.2 Browser dan keyboard interaction

| Check | Status | Observasi |
| --- | --- | --- |
| Role/menu penjaga | PASS | 0 tautan users/courts dalam nav pada seluruh halaman yang dipindai. |
| Sidebar open/focus/inert | PASS | Fokus menuju tombol tutup; workspace inert dan aria-expanded=true. |
| Sidebar Tab/Shift+Tab wrap | PASS | Shift+Tab dari brand menuju tautan terakhir; Tab dari tautan terakhir kembali ke brand. |
| Sidebar Escape/restore | PASS | aria-expanded=false; fokus kembali Buka navigasi. |
| DataTables filter/no-match/sort | PASS | Venue, jadwal index, booking: pesan no-match, clear kembali, header No descending. Tidak mengubah record. |
| Deskripsi keyboard/search | PASS | Enter membuka/menutup; query pencahayaan tetap menemukan venue saat detail tertutup. |
| Schedule create/edit modal open/Escape | PASS | Dibuka pada venue dari tautan UI; ditutup tanpa submit. |
| Modal Tab/Shift+Tab containment setelah fix | PASS | Create: Simpan jadwal → Tab → Tutup formulir; Shift+Tab kembali Simpan jadwal. Edit: Tab terakhir menuju Tutup formulir. |
| Schedule Escape/focus return | PASS (create) | Setelah transisi selesai, fokus kembali Tambah jadwal. Edit focus return belum dicatat terpisah. |
| Logout modal Tab/Shift+Tab/Escape | PASS | Keluar → Tab → Tutup; Shift+Tab kembali Keluar; Escape mengembalikan fokus ke Keluar dari akun. Tidak melakukan logout. |
| Console errors | PASS untuk sesi diamati | Koleksi error kosong setelah daftar, fix dan schedule checks. |
| Server validation recovery, save/delete feedback | NOT TESTED | Tidak submit form atau mengubah development records. Fixture hasil sebelumnya tetap bukti terpisah. |
| Backdrop click, upload preview, pagination page 2 | NOT TESTED | Tidak diuji pada resume ini; dataset tidak menghasilkan halaman kedua pada page size minimum. |

### 12.3 DASH-003 — Medium — Teks aksesibel tabel menyebabkan document overflow mobile

- Reproduksi: sebagai penjaga, buka venue list/pilih venue jadwal di 360/390px. Sebelum fix, document scrollWidth **512** meskipun viewport 360/390; scroll horizontal seharusnya terbatas pada region tabel.
- Root cause terkonfirmasi: span sr-only pada summary deskripsi memakai absolute positioning. Region `.akd-table-scroll` belum menjadi containing block, sehingga teks aksesibel di kolom yang offscreen dapat memengaruhi overflow ancestor. Menyembunyikan isi details tertutup saja tidak menghilangkan overflow; pemeriksaan tersebut bukan fix yang dianggap berhasil sendiri.
- Fix: `position: relative` pada `.akd-table-scroll` membatasi positioning ke region yang di-scroll. Tambahkan explicit display:none untuk body deskripsi ketika details tertutup agar perilaku closed state konsisten. Teks tetap ada di DOM dan dapat dicari DataTables.
- Setelah fix: venue dan jadwal index scrollWidth **343/373/768/1024/1440** pada innerWidth **360/390/768/1024/1440**; tidak melebihi viewport. Membuka deskripsi di 360 juga tetap scrollWidth 343. Tinggi row satu venue sekitar 168px di mobile dan 117px pada 1440. Semua kolom tetap dapat diakses lewat scroll tabel.
- Status: **FAIL sebelum → PASS setelah fix untuk overflow yang diuji**. Fix berada di adapter shared; tidak menutupi overflow seluruh body atau membuang data.

### 12.4 DASH-004 — Medium — Tab dapat meninggalkan modal dashboard

- Reproduksi: buka create schedule; tunggu transisi, fokus Simpan jadwal, tekan Tab. Pemeriksaan terpisah setelah transisi tetap menunjukkan activeElement BODY di luar modal yang masih show. Ini dikonfirmasi dua kali, bukan hanya pembacaan segera setelah animasi.
- Root cause: mengandalkan focus enforcement Bootstrap 4 saja tidak menahan boundary Tab pada dokumen dalam browser pengujian ini. Sidebar sudah memiliki trap sendiri, tetapi modal belum.
- Fix minimal: shared keydown handler untuk `.modal.show`; hanya menangani Tab, melewati event yang sudah defaultPrevented, mengambil kontrol visible/enabled/tabbable, dan wrap batas pertama/terakhir. Tidak mengubah submit, Escape, Bootstrap data attributes, server validation atau booking.
- Verifikasi setelah reload: create dan edit schedule menahan Tab; create menahan Shift+Tab. Logout modal juga menahan kedua arah. Escape dan focus restore yang diamati tetap bekerja. Field nilai schedule tidak berubah dan tidak ada save.
- Status: **FAIL sebelum → PASS setelah fix pada skenario keyboard di atas**. Efek shared pada role admin belum diuji ulang lewat sesi admin; tidak mengklaim ulang seluruh matrix admin PASS.

### 12.5 Perubahan dan regresi terbaru

File baru yang diubah pada resume penjaga: `public/css/arenaku-dashboard.css`, `public/js/arenaku-dashboard.js`, dan laporan ini. Component/Blade dari resume admin tetap dipertahankan. Tidak ada controller/model/route/middleware/auth/database/dependency yang diedit.

Karena ada fix CSS/JS, verifikasi relevan diulang: **8 tests / 131 assertions PASS** (DashboardFoundationViewsTest + ManagementViewsTest dalam lane non-DB yang sama); **2/2 Node table adapter tests PASS**; `node --check public/js/arenaku-dashboard.js` PASS; `git diff --check` PASS. Browser keyboard dan overflow checks di atas merupakan verifikasi utama fix interaksi. Suite lain yang telah PASS tidak diulang; tidak menjalankan Mix.

### 12.6 Final frontend acceptance status

**PARTIAL — kedua role sudah memperoleh pengujian browser, tetapi acceptance lengkap belum ditandatangani.** Blocker login awal sudah teratasi. Defect row, overflow dan modal focus yang ditemukan sudah diperbaiki pada skenario terverifikasi. Major interactions yang masih belum diverifikasi menyeluruh: retest shared fixes melalui sesi admin, exact-width/visual admin bertanda NT pada bagian 11, visual penuh profil/user/booking, backdrop click, upload preview dan modal validation recovery. Pagination page 2 tidak tersedia pada dataset; empty state sejati/server validation tetap memerlukan lingkungan disposable atau state existing yang sesuai.

Session penjaga dipertahankan di overview dengan viewport default. Tidak ada credential/PII yang dicetak dalam laporan, tidak ada operasi CRUD tulis, dan tidak ada temuan backend Phase 5A yang dinyatakan resolved. Berhenti setelah pelaporan Phase 5C; backend remediation belum dimulai.

Recommended Git commit: `fix(ui): contain dashboard table overflow and modal keyboard focus`.
