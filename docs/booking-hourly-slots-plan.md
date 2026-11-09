# Arenaku — Kesimpulan Diskusi Jadwal dan Booking Per Jam

Tanggal: 4 November 2026

Status per 9 November 2026: implementasi tahap 1–7 selesai dan pilot Giri Krida Court A aktif pada database lokal. Pengujian alur utama pilot lulus.

Dokumen ini merangkum keputusan produk yang menjadi dasar implementasi jadwal, booking, dan dashboard. Bukti penerapan serta batas verifikasi terbaru tersedia pada [laporan pilot Giri Krida](qa/giri-krida-pilot-acceptance.md). Aktivasi venue lain dilakukan setelah konfigurasi dan pemeriksaan data masing-masing venue.

## 1. Tujuan

Jadwal tersedia otomatis berdasarkan jam operasional. Pengguna memilih slot yang terlihat tanpa memasukkan ulang jam booking. Pengelola menangani pengecualian, pemesanan langsung, dan pembatalan melalui alur yang tercatat.

Prinsip utama yang disepakati:

- Jam reguler sama setiap hari.
- Ada pengecualian untuk tanggal tertentu.
- Pengelola dapat menutup slot per court.
- Interval slot tetap satu jam, pada jam bulat.
- Satu booking dapat mencakup beberapa slot berurutan pada court dan tanggal yang sama.
- Pembatalan dilakukan melalui pengelola dan riwayatnya dipertahankan.
- Rentang pemesanan awal 14 hari, termasuk hari ini, dan dapat dikonfigurasi.
- Seluruh slot dalam jadwal tetap ditampilkan, termasuk yang sudah dipesan atau tidak tersedia.
- Booking website menahan seluruh slot sementara sampai dikonfirmasi pengelola, dengan batas maksimal 60 menit atau waktu mulai booking, mana yang lebih dahulu.
- Operasional tidak melewati tengah malam; seluruh perhitungan dan tampilan jadwal menggunakan Asia/Jakarta, WIB (UTC+7).
- Jam reguler dan rentang pemesanan hanya dapat diubah admin. Izin khusus jam reguler bagi penjaga tidak termasuk tahap awal.
- Slot per jam disimpan di database; riwayat booking dipisahkan dari reservasi slot yang masih aktif.

## 2. Jam operasional reguler

Setiap venue memiliki pengaturan jam buka dan jam tutup yang sama untuk setiap hari. Untuk tahap awal, tidak diperlukan konfigurasi berulang yang berbeda per hari dalam seminggu.

Pengaturan menggunakan jam bulat, misalnya 08.00 sampai 22.00. Tidak ada pilihan menit bebas seperti 08.15 atau 10.30.

Contoh hasil pengaturan 08.00–22.00:

- 08.00–09.00
- 09.00–10.00
- Berlanjut per jam hingga 21.00–22.00.

Jam tutup merupakan akhir slot terakhir, bukan awal slot baru. Contoh tersebut adalah ilustrasi, bukan penetapan jam operasional seluruh venue.

Saat ini tidak ada venue yang beroperasi melewati tengah malam. Tahap awal hanya mendukung jam buka lebih awal daripada jam tutup dalam satu hari. Slot dan satu booking tidak melintasi tanggal; dukungan operasional lintas tengah malam berada di luar cakupan.

## 3. Rentang hari pemesanan

Nilai awal adalah **14 hari, termasuk hari ini**, dan dapat diubah oleh admin per venue.

Contoh: jika hari ini tanggal 1, tanggal yang dapat dipesan adalah tanggal 1 sampai 14. Pada tanggal 2, rentangnya bergeser menjadi tanggal 2 sampai 15.

Rentang pemesanan berbeda dari durasi booking. Rentang mengatur tanggal yang bisa dipilih, sedangkan durasi berasal dari jumlah slot satu jam yang dipilih.

Saat tanggal baru masuk ke dalam rentang, jadwal tanggal tersebut tersedia otomatis sesuai aturan operasional. Slot per jam disimpan di database melalui proses pembentukan yang aman dijalankan berulang. Rancangan penyimpanan dan pemeriksaan kelengkapan rentang dijelaskan pada bagian 12.

Perubahan rentang tidak boleh menghapus booking yang sudah tercatat di luar rentang baru. Riwayat dan booking tersebut tetap dapat dikelola.

## 4. Pengecualian tanggal dan penutupan slot

Jam reguler menjadi dasar. Pengelola dapat membuat pengecualian untuk tanggal tertentu tanpa mengubah jam reguler hari lainnya.

| Pengaturan | Contoh kebutuhan |
| --- | --- |
| Jam operasional khusus tanggal tertentu | Hari ini hanya buka 10.00–18.00 |
| Tutup venue satu tanggal penuh | Libur atau acara khusus |
| Tutup satu court satu tanggal penuh | Court B menjalani perawatan |
| Tutup satu atau beberapa slot pada court | Court B ditutup 13.00–15.00 |

Pengecualian harus memiliki konteks tanggal, cakupan venue/court, rentang waktu jika relevan, dan alasan. Pengelola dapat memilih tanggal hari ini.

Aturan perlindungan:

1. Pembuatan jadwal otomatis tidak boleh membuka kembali slot yang sengaja ditutup atau sudah dipesan.
2. Menjalankan pembentukan jadwal berulang kali tidak boleh menghasilkan slot duplikat.
3. Perubahan jam operasional tidak boleh menghapus atau mengubah booking yang telah ada secara diam-diam.
4. Jika jam operasional baru atau penutupan bertabrakan dengan booking aktif, tampilkan konflik dan minta penyelesaian booking tersebut terlebih dahulu.
5. Membuka kembali penutupan hanya membuat slot tersedia jika belum mulai, masih dalam jam operasional yang berlaku, tidak dipesan, dan tidak tertutup oleh pengecualian lain.

Contoh konflik: venue akan tutup pukul 18.00 hari ini, tetapi ada booking pukul 19.00. Perubahan tidak boleh menghilangkan booking tersebut; pengelola harus menyelesaikan konfliknya melalui alur yang tercatat.

## 5. Slot kedaluwarsa saat waktu mulai tiba

Slot tidak dapat dipesan ketika waktu sekarang **sama dengan atau melewati waktu mulai slot**.

Contoh untuk slot 10.00–11.00:

| Waktu sekarang | Kondisi pemesanan |
| --- | --- |
| 09.59 | Masih dapat dipesan jika seluruh syarat lain terpenuhi |
| 10.00 | Tidak dapat dipesan |
| 10.15 | Tidak dapat dipesan |

Pada halaman publik, slot kosong yang waktunya telah mulai ditampilkan sebagai **Tidak tersedia** dan tidak bisa dipilih. Slot tetap terlihat.

Ketentuan ini tidak membatalkan booking yang sudah dikonfirmasi. Booking dan riwayatnya tetap dipertahankan. Booking yang masih menunggu konfirmasi mengikuti batas kedaluwarsa tersendiri pada bagian 7.1.

Waktu server dengan acuan **Asia/Jakarta — WIB (UTC+7)** digunakan untuk jam operasional, tanggal booking, pergeseran rentang 14 hari, batas mulai slot, dan kedaluwarsa booking. Jadwal tetap ditampilkan dalam WIB meskipun pengguna berada di zona waktu lain; tampilkan label WIB secara jelas.

Perubahan tampilan pada browser tidak menggantikan pemeriksaan waktu saat permintaan booking diterima server. Pemesanan tidak boleh bergantung pada ketepatan proses terjadwal yang mengubah status di database.

## 6. Tampilan jadwal untuk pengguna

Alur utama:

1. Pengguna memilih venue.
2. Pengguna memilih tanggal dalam rentang pemesanan.
3. Aplikasi menampilkan seluruh court dan slot pada tanggal tersebut.
4. Pengguna memilih slot pada satu court.
5. Aplikasi menampilkan ringkasan court, tanggal, jam mulai–selesai, dan durasi. Tarif/total hanya ditampilkan jika ada sumber data dan aturan tarif yang telah ditetapkan; bukan asumsi fitur yang sudah tersedia.
6. Pengguna melanjutkan pengisian informasi pemesan dan mengirim booking.
7. Setelah berhasil, pengguna melihat status Menunggu konfirmasi, batas waktu sebenarnya dalam WIB, dan informasi untuk menghubungi pengelola.

Pengguna tidak diminta memasukkan ulang jam atau durasi yang sudah ditentukan dari pilihan slot.

Contoh ilustrasi pada pukul 10.15:

| Jam | Court A | Court B | Court C |
| --- | --- | --- | --- |
| 09.00–10.00 | Tidak tersedia | Tidak tersedia | Tidak tersedia |
| 10.00–11.00 | Tidak tersedia | Tidak tersedia | Tidak tersedia |
| 11.00–12.00 | Tersedia | Dipesan | Tersedia |
| 12.00–13.00 | Tersedia | Dipesan | Tidak tersedia |
| 13.00–14.00 | Tersedia | Tersedia | Tersedia |

Tabel di atas bukan data aplikasi. Label Dipesan menunjukkan slot sedang dialokasikan kepada booking, bukan bukti pembayaran atau konfirmasi final. Status booking sebenarnya tetap ditampilkan secara benar pada detail booking.

Pada desktop, gunakan tampilan yang memudahkan perbandingan antar-court. Pada ponsel, court dapat ditampilkan sebagai bagian berurutan dengan pilihan slot yang mudah disentuh. Semua court tetap dapat diakses untuk tanggal yang sama.

Informasi ketersediaan tidak boleh hanya mengandalkan warna. Gunakan label, keadaan terpilih yang jelas, dan kontrol yang bisa digunakan dengan keyboard. Informasi pribadi pemesan tidak ditampilkan pada jadwal publik.

### 6.1. Pembaruan ketika halaman tetap terbuka

Tampilan perlu mengikuti perubahan waktu dan pemesanan oleh pengguna lain:

- Nonaktifkan slot ketika waktu mulainya tiba menggunakan acuan waktu server, bukan semata-mata jam perangkat pengguna.
- Periksa ulang ketersediaan setiap 30 detik ketika halaman aktif sebagai interval awal implementasi. Interval ini bukan jaminan bahwa slot tetap tersedia sampai pengiriman booking.
- Periksa ulang saat pengguna kembali ke tab.
- Periksa saat halaman pertama dibuka, venue/tanggal diganti, dan setelah tindakan pengelola berhasil.
- Jika slot yang dipilih tidak lagi tersedia, beri penjelasan dan minta pengguna memperbaiki pilihan. Jangan mengganti slot atau mengubah durasi secara diam-diam.
- Jika koneksi gagal, tampilkan bahwa pembaruan ketersediaan belum berhasil; jangan memberi kesan data pasti mutakhir.
- Tetap periksa seluruh slot di server ketika booking dikirim. Melihat atau memilih slot di browser belum menahan slot; penahanan baru berlaku setelah pengiriman booking berhasil diproses.

Contoh: halaman dibuka pukul 09.55 dan dibiarkan sampai 10.01. Slot 10.00–11.00 harus menjadi Tidak tersedia tanpa mengharuskan pengguna memuat ulang halaman. Jika tampilan masih tertinggal, server tetap menolak pemesanannya.

Pembaruan menggunakan permintaan ringan melalui JavaScript yang ada, tetap dengan Blade. Respons jadwal publik cukup memuat identitas slot, waktu, ketersediaan, dan waktu server tanpa data pribadi pemesan. Tidak perlu memuat ulang seluruh halaman atau memperkenalkan framework frontend baru.

Pada dashboard, pertahankan pencarian, halaman tabel, fokus keyboard, serta formulir/modal yang sedang dibuka ketika daftar pending diperbarui. Jangan mengganti isi formulir yang sedang dikerjakan pengguna. Pesan konflik harus meminta pemeriksaan ulang pilihan, bukan mengirim booking dengan durasi berbeda secara diam-diam.

## 7. Pemilihan beberapa jam

Satu booking mencakup satu court, satu tanggal, dan satu atau beberapa slot satu jam yang berurutan.

| Pilihan | Hasil |
| --- | --- |
| Court A 11.00–12.00 dan 12.00–13.00 | Satu booking, 11.00–13.00, durasi 2 jam |
| Court A 11.00–12.00 dan 13.00–14.00 | Harus menjadi booking terpisah |
| Court A dan Court B | Harus menjadi booking terpisah |
| Slot pada tanggal berbeda | Harus menjadi booking terpisah |

Satu booking harus mengacu pada seluruh slot yang dipilih. Durasi dihitung kembali di server berdasarkan slot yang sah, bukan mempercayai nilai bebas dari browser. Jika pengelolaan tarif ditambahkan melalui rancangan terpisah, total juga harus dihitung dari tarif yang sah di server.

Saat penyimpanan, seluruh slot harus diperiksa dan dialokasikan sebagai satu kesatuan. Jika satu slot sudah dipesan, ditutup, atau waktunya telah mulai, booking tidak boleh tersimpan sebagian. Tampilkan penjelasan dan perbarui ketersediaan agar pengguna dapat memilih kembali.

Sistem perlu mencegah dua pemesan memperoleh slot yang sama ketika mengirim permintaan bersamaan.

### 7.1. Pending, konfirmasi, dan kedaluwarsa otomatis

Pada kode yang diperiksa saat diskusi, booking baru memiliki status bawaan pending dan jadwal langsung berubah menjadi booking. Alur tersebut belum memiliki tindakan konfirmasi atau kedaluwarsa otomatis. Pending lama bukan bukti bahwa pelanggan belum membayar, dan tidak boleh diperlakukan sebagai booking sementara baru tanpa pemeriksaan data.

Alur baru yang disepakati:

1. Pengguna mengirim booking.
2. Setelah validasi dan penyimpanan berhasil, semua slot pilihannya ditahan sementara sebagai satu kesatuan.
3. Pengguna melihat batas waktu konfirmasi dan informasi untuk menghubungi pengelola.
4. Pengelola mengonfirmasi sebelum batas waktu.
5. Jika tidak dikonfirmasi, booking otomatis kedaluwarsa.

| Status booking | Arti |
| --- | --- |
| Menunggu konfirmasi | Permintaan telah tercatat dan seluruh slot ditahan sementara |
| Dikonfirmasi | Pengelola menerima pemesanan; tidak otomatis berarti lunas |
| Kedaluwarsa | Batas konfirmasi terlewati tanpa konfirmasi |
| Dibatalkan | Pembatalan tercatat melalui pengelola |

Istilah tersebut merupakan status produk yang dituju. Representasi teknisnya harus dirancang saat implementasi; dokumen ini tidak menyatakan bahwa semua status sudah didukung database saat ini.

**Batas pending maksimal 60 menit**, dihitung sejak booking dibuat oleh server. Batas konfirmasi adalah waktu yang lebih dahulu antara waktu pembuatan ditambah 60 menit dan waktu mulai slot pertama.

| Booking dibuat | Waktu bermain | Batas konfirmasi |
| --- | --- | --- |
| 08.00 | 15.00–17.00 pada hari yang sama | 09.00 |
| 09.30 | 10.00–12.00 pada hari yang sama | 10.00 |
| 16.00 hari ini | 10.00 besok | 17.00 hari ini |

Semua waktu contoh menggunakan WIB. Nilai 60 menit memberi pengelola waktu untuk merespons, dengan konsekuensi slot tertahan lebih lama jika pemesan tidak melanjutkan. Ini menggantikan usulan awal 30 menit.

Aturan rinci:

- Semua slot dalam satu booking memiliki satu batas konfirmasi dan kedaluwarsa sebagai satu kesatuan.
- Sesuai revisi tampilan yang disetujui setelah pilot, formulir menjelaskan aturan penahanan maksimal 60 menit atau sampai jam mulai. Ringkasan pilihan tidak menampilkan perkiraan tenggat. Halaman status pending menampilkan judul Menunggu konfirmasi dan pesan Menunggu konfirmasi pengelola. Tenggat sebenarnya tetap tersimpan dan tersedia bagi pengelola di dashboard.
- Membuka ulang halaman atau menghubungi WhatsApp tidak memperpanjang batas pending.
- Konfirmasi hanya sah ketika waktu server masih sebelum batas. Tepat pada atau setelah batas, booking dianggap kedaluwarsa.
- Server tidak boleh menerima konfirmasi terlambat hanya karena proses otomatis belum memperbarui status tersimpan.
- Konfirmasi dan proses kedaluwarsa yang berjalan bersamaan harus menghasilkan satu keputusan yang konsisten, tanpa melepaskan slot booking yang sudah sah dikonfirmasi.
- Setelah dikonfirmasi, booking tidak terkena lagi batas pending 60 menit.
- Booking kedaluwarsa tetap disimpan sebagai riwayat. Jangan mengaktifkannya kembali; buat booking baru dengan pemeriksaan ketersediaan jika pemesan ingin melanjutkan.
- Saat penahanan berakhir, setiap slot hanya tersedia kembali jika belum mulai, masih dalam jam operasional, dan tidak tertutup atau dialokasikan oleh aturan lain.
- Jika slot pertama sudah mulai saat booking beberapa jam kedaluwarsa, slot berikutnya yang belum mulai dapat tersedia kembali apabila memenuhi syarat. Booking lama tetap kedaluwarsa seluruhnya.
- Kedaluwarsa otomatis berbeda dari pembatalan oleh pengelola; riwayat harus membedakan penyebabnya.

## 8. Booking melalui pengelola

Pemesanan langsung atau melalui WhatsApp dicatat melalui tindakan **Catat booking** pada dashboard. Mengganti status slot menjadi booking saja tanpa record pemesan tidak digunakan sebagai alur pemesanan.

Alur pengelola:

1. Pilih venue yang berhak dikelola, tanggal, dan court.
2. Pilih slot berurutan yang tersedia.
3. Isi informasi pemesan sesuai kebutuhan pencatatan.
4. Periksa ringkasan jadwal dan durasi.
5. Simpan booking melalui pemeriksaan ketersediaan yang sama dengan booking publik.

Booking ini harus dapat dicari, dilihat detailnya, dan dibatalkan melalui aturan yang sama. Pencatatan booking tidak otomatis menyatakan booking lunas.

Booking langsung yang sudah disepakati dengan pelanggan dapat langsung dikonfirmasi secara eksplisit oleh pengelola saat pencatatan, tanpa menunggu 60 menit. Jangan menyimpulkan bahwa setiap booking yang dimasukkan pengelola pasti sudah dikonfirmasi atau dibayar.

Sumber booking yang digunakan:

| Sumber | Pencatatan |
| --- | --- |
| Website | Otomatis dari formulir publik |
| Datang langsung | Dipilih pengelola ketika mencatat pemesanan di lokasi |
| WhatsApp | Dipilih pengelola ketika mencatat pemesanan melalui WhatsApp |

Informasi wajib adalah nama pemesan, nomor WhatsApp/telepon yang dapat dihubungi, court, tanggal, dan slot. Venue ditentukan dari court yang dipilih. Durasi dihitung otomatis; akun pengelola yang mencatat dan waktu pencatatan disimpan otomatis. Catatan tambahan opsional. Tidak perlu meminta alamat, identitas resmi, atau email tanpa kebutuhan yang jelas.

## 9. Pembatalan melalui pengelola

Untuk tahap awal, pembatalan dilakukan melalui pengelola yang memiliki akses terhadap booking tersebut.

Aturan yang disepakati:

- Gunakan tindakan **Batalkan booking**, bukan penghapusan record.
- Wajib mencatat alasan, waktu pembatalan, dan pengelola yang melakukan pembatalan.
- Data pemesan, tanggal, court, serta slot booking tetap tersimpan dalam riwayat.
- Pembatalan berlaku untuk seluruh slot dalam satu booking; pembatalan sebagian jam belum termasuk tahap awal.
- Booking yang waktu mulainya sudah tiba atau telah selesai tidak dapat dibatalkan melalui tindakan pembatalan biasa.
- Koreksi terhadap booking yang sudah berjalan/selesai memerlukan rancangan terpisah, bukan mengubah riwayat secara diam-diam.

Setelah pembatalan, ketersediaan setiap slot dihitung kembali:

| Kondisi slot | Hasil |
| --- | --- |
| Belum mulai, dalam jam operasional, tidak memiliki penghalang lain | Tersedia kembali |
| Jam mulai sudah tiba atau lewat | Tetap tidak tersedia |
| Ditutup untuk perawatan/acara/libur | Tetap tidak tersedia |
| Di luar jam operasional yang berlaku | Tetap tidak tersedia |

Pembatalan tidak otomatis berarti refund. Jika pembayaran dilakukan di luar aplikasi, pengembalian dana mengikuti kebijakan venue dan tidak boleh ditampilkan sebagai selesai tanpa proses pendukung.

Pembatalan mandiri oleh pelanggan belum termasuk tahap awal. Batas 24 jam sebelum mulai yang pernah dibahas adalah contoh kebijakan untuk fitur tersebut di masa depan, bukan batas pembatalan pengelola yang langsung diterapkan sekarang.

## 10. Penyesuaian dashboard

Pertahankan struktur navigasi yang ada sejauh memungkinkan. Tambahkan kemampuan pada halaman yang relevan, tanpa memperbanyak menu yang tidak diperlukan.

### Pengaturan venue/lapangan

- Jam buka dan tutup reguler pada jam bulat.
- Rentang pemesanan dengan nilai awal 14 hari.
- Informasi bahwa interval slot tetap satu jam.
- Peringatan konflik ketika perubahan memengaruhi booking aktif.

### Atur jadwal

- Filter venue, tanggal, dan court serta pintasan Hari ini.
- Tampilan seluruh slot dengan ketersediaan dan alasan operasional yang jelas.
- Pengaturan jam khusus untuk tanggal tertentu.
- Penutupan slot, court sehari penuh, atau venue sehari penuh beserta alasan.
- Aksi Catat booking, Lihat booking, Tutup slot, dan buka kembali penutupan jika memenuhi syarat.
- Pembentukan jadwal otomatis sehingga penjaga tidak perlu membuat setiap jam satu per satu.

### Manajemen booking

- Informasi pemesan, venue, court, tanggal, jam mulai–selesai, durasi, dan status sebenarnya.
- Detail seluruh slot yang terkait dengan satu booking.
- Pencatatan booking langsung/WhatsApp.
- Daftar permintaan Menunggu konfirmasi dengan batas waktu dalam WIB dan sisa waktu yang jelas.
- Tindakan Konfirmasi booking bagi pengelola yang berwenang, hanya sebelum batas konfirmasi.
- Tampilan status Kedaluwarsa dan riwayat konfirmasi/pembatalan yang sesuai data sebenarnya.
- Pembatalan dengan alasan dan riwayat, bukan penghapusan.
- Filter operasional yang membantu menemukan booking menurut tanggal, venue/court, dan status.

### Ringkasan dashboard

Utamakan permintaan Menunggu konfirmasi serta pintasan ke jadwal hari ini, pencatatan booking, dan daftar booking. Ringkasan jumlah booking, slot tersedia, atau penutupan hanya ditambahkan jika berasal dari data nyata dan definisi perhitungannya jelas.

### Hak admin dan penjaga

Admin mengatur kebijakan dasar lintas venue; penjaga menangani operasional venue penugasannya. Jam reguler memengaruhi pembentukan slot pada banyak tanggal, sehingga hak mengubahnya lebih terbatas daripada pengecualian satu tanggal.

| Tindakan | Admin | Penjaga |
| --- | --- | --- |
| Mengubah jam reguler | Semua venue | Tidak |
| Mengubah rentang pemesanan | Semua venue | Tidak |
| Mengatur jam khusus tanggal tertentu | Semua venue | Venue penugasannya |
| Menutup slot, court, atau venue pada tanggal tertentu | Semua venue | Venue penugasannya |
| Membuka kembali penutupan | Sesuai aturan dan konflik yang berlaku | Penutupan operasional yang dibuatnya, tanpa pembatasan admin/penutupan lain |
| Mencatat, mengonfirmasi, dan membatalkan booking | Semua venue | Venue penugasannya |
| Mengelola akun dan penugasan | Ya | Tidak |

Penjaga tidak boleh membuka penutupan yang diberlakukan admin. Semua perubahan pengaturan dan tindakan penting dicatat dengan pelaku, waktu, alasan/konteks, serta perubahan nilainya. Perubahan yang bertabrakan dengan booking aktif harus diselesaikan melalui alur yang tercatat; admin juga tidak boleh melewati perlindungan ini.

Pembatasan harus ditegakkan di server pada setiap tindakan, termasuk ketika ID venue atau booking dikirim langsung. Menyembunyikan menu saja tidak cukup. Matriks ini adalah target perilaku baru, bukan pernyataan bahwa akses backend saat ini sudah memenuhi ketentuan tersebut.

**Keputusan tahap awal:** tidak ada izin khusus untuk mendelegasikan perubahan jam reguler kepada penjaga. Jam reguler dan rentang pemesanan tetap hanya dikelola admin; penjaga menangani pengecualian tanggal, penutupan, serta booking pada venue penugasannya sesuai tabel di atas.

## 11. Perlindungan data dan batas implementasi

- Tetap menggunakan arsitektur Laravel dan Blade yang ada serta identitas Arenaku Courtside.
- Pembaruan ini memerlukan pekerjaan backend; tidak dapat diselesaikan hanya melalui perubahan tampilan atau JavaScript.
- Arah struktur penyimpanan slot, relasi booking ke beberapa slot, penutupan khusus, dan riwayat pembatalan ditetapkan pada bagian 12. Detail kolom dan migrasi harus disesuaikan dengan audit data terbaru sebelum implementasi.
- Booking lama tidak boleh hilang atau berubah makna ketika struktur baru diterapkan.
- Jadwal lama yang tidak sesuai interval satu jam perlu ditinjau dan ditangani secara eksplisit; jangan dibulatkan atau dipindahkan otomatis sehingga mengubah reservasi pelanggan.
- Slot kedaluwarsa, penutupan operasional, dan status booking harus dibedakan. Ketiganya tidak boleh saling menimpa riwayat.
- Tidak memperkenalkan pembayaran, refund otomatis, atau tahapan persetujuan tambahan di luar alur konfirmasi yang disepakati pada bagian 7.1.

### Pemetaan booking dan jadwal lama

| Kondisi data lama | Penanganan |
| --- | --- |
| Booking 10.00–12.00 dengan durasi konsisten | Hubungkan satu booking ke slot 10.00–11.00 dan 11.00–12.00 |
| Booking 10.30–11.30 | Pertahankan waktu dan durasi asli serta informasi harga jika memang ada; blok slot baru 10.00–11.00 dan 11.00–12.00 agar tidak dipesan bertabrakan |
| Durasi booking berbeda dari waktu jadwal | Masukkan daftar konflik untuk diperiksa pengelola; jangan menebak waktu yang benar |
| Jadwal kosong dengan menit tidak bulat | Bentuk ulang sesuai jam operasional setelah memastikan tidak terkait booking atau penutupan |
| Jadwal sengaja ditutup | Pertahankan sebagai penutupan; pembentukan otomatis tidak boleh membukanya |
| Booking/jadwal masa lalu | Pertahankan sebagai riwayat |

Pemblokiran dua slot untuk booking lama 10.30–11.30 adalah perlindungan terhadap bentrok, bukan perubahan booking menjadi dua jam. Jika beberapa booking lama beririsan dengan slot baru yang sama atau ada ambiguitas lain, masukkan ke pemeriksaan konflik sebelum pemetaan diterapkan; jangan menimpa salah satu record.

Booking lama berstatus pending tidak langsung diberi tenggat 60 menit berdasarkan waktu pembuatannya. Pengelola harus meninjau booking mendatang terlebih dahulu karena pending lama mungkin merupakan pemesanan sah yang belum pernah diubah statusnya. Jangan otomatis mengonfirmasi atau menandai data tersebut kedaluwarsa berdasarkan asumsi.

Transisi dilakukan melalui pencadangan, simulasi pemetaan, laporan konflik, pemeriksaan pengelola, lalu penerapan dan verifikasi hasil. Detail teknis serta setiap konflik nyata ditentukan setelah audit data.

## 12. Penetapan arah teknis dan tindak lanjut audit

Rancangan berikut menjadi acuan implementasi berdasarkan pembahasan lanjutan. Skema telah diterapkan pada database lokal melalui jalur migration cutover setelah backup dan uji restore. Detail aktual tercatat dalam laporan implementasi tiap tahap.

### 12.1. Struktur penyimpanan database

Pisahkan pengaturan operasional, identitas slot, riwayat booking, dan penguasaan slot aktif.

| Bagian/tabel yang direncanakan | Tanggung jawab |
| --- | --- |
| Pengaturan pada `lapangan` | Jam buka reguler, jam tutup, dan rentang pemesanan awal 14 hari |
| `venue_courts` | Identitas court fisik pada masing-masing venue |
| `operating_exceptions` | Jam khusus atau libur pada tanggal tertentu |
| `court_closures` | Penutupan court/slot, cakupan waktu, alasan, pembuat, dan riwayat pembukaan kembali |
| `schedule_slots` | Satu record untuk satu court fisik, tanggal, dan interval satu jam |
| `konfirmasi_bookings` | Data pemesan, sumber, status, batas konfirmasi, serta informasi konfirmasi/pembatalan |
| `booking_slots` | Hubungan booking ke seluruh slot pilihannya yang tetap disimpan sebagai riwayat |
| `active_slot_reservations` | Penguasaan slot oleh booking yang sedang ditahan atau sudah dikonfirmasi |
| `activity_logs` | Catatan siapa melakukan tindakan, kapan, alasan/konteks, dan nilai yang berubah |

Tabel `courts` saat diperiksa hanya berisi nama court tanpa hubungan langsung ke venue. Hubungan venue dan court muncul melalui jadwal. Untuk membentuk seluruh jam secara otomatis, aplikasi harus memiliki daftar court fisik per venue meskipun belum pernah dibuatkan jadwal.

Gunakan `venue_courts` untuk memperjelas identitas tersebut: Court A di venue pertama dan Court A di venue kedua adalah unit reservasi yang berbeda. Jadwal lama menjadi bahan pemetaan, tetapi pengelola perlu memverifikasi daftar court, termasuk court yang belum pernah mempunyai jadwal.

Pemisahan data memberikan aturan berikut:

- Pembatalan/kedaluwarsa melepaskan reservasi aktif tanpa menghapus hubungan historis pada `booking_slots`.
- Penutupan perawatan tersimpan terpisah sehingga tidak hilang ketika booking dibatalkan.
- Pembentukan jadwal hanya menambahkan slot yang belum ada dan tetap menghormati pengecualian serta penutupan.
- Slot tidak perlu diubah menjadi closed satu per satu ketika waktunya lewat; ketersediaan dihitung dari waktu server dan aturan yang berlaku.
- Menonaktifkan venue atau court tidak boleh menghapus booking dan riwayatnya melalui penghapusan berantai.

### 12.2. Integritas, transaksi, dan pengiriman ulang

Perlindungan database yang dituju:

- Kombinasi court fisik, tanggal, dan jam mulai unik pada tabel slot.
- Satu slot hanya memiliki satu reservasi aktif.
- Pasangan booking dan slot tidak boleh tercatat dua kali dalam hubungan historis.
- Hubungan antar-record tetap konsisten dan tidak membolehkan penghapusan riwayat reservasi secara berantai.

Alur booking beberapa slot dijalankan sebagai satu transaksi:

1. Kunci record yang terkait dengan pilihan dan aturan operasionalnya dalam urutan yang konsisten.
2. Periksa kembali waktu, jam operasional, penutupan, hubungan venue/court, dan reservasi aktif.
3. Jika ada penahanan yang sudah kedaluwarsa, selesaikan booking lama beserta seluruh reservasinya secara aman sebelum mengalokasikan slot kembali.
4. Buat booking, hubungan semua slot, dan reservasi aktif sebagai satu kesatuan.
5. Jika salah satu langkah gagal, batalkan seluruh perubahan dalam transaksi.

Semua jalur mutasi terkait harus mengikuti aturan transaksi dan penguncian yang konsisten: booking publik, pencatatan pengelola, konfirmasi, pembatalan, kedaluwarsa, serta perubahan operasional yang dapat bertabrakan dengan booking. Konfirmasi dan pelepasan slot tidak boleh menghasilkan keputusan yang saling bertentangan.

Tambahkan pengaman pengiriman ulang agar klik dua kali atau pengulangan permintaan setelah koneksi terputus tidak menghasilkan booking ganda. Penguncian dan transaksi didukung oleh [Query Builder Laravel 11](https://laravel.com/docs/11.x/queries#pessimistic-locking); implementasinya tetap harus diuji terhadap permintaan bersamaan pada database uji terisolasi.

### 12.3. Bukti audit data dan rencana rekonsiliasi

Angka berikut berasal dari **snapshot audit baca-saja 2 November 2026**, bukan hasil pemeriksaan database terbaru pada saat dokumen diperbarui. Sumber: [laporan Phase 5A](qa/phase-5-audit.md).

| Pemeriksaan | Hasil snapshot | Tindak lanjut |
| --- | --- | --- |
| Venue / court / jadwal / booking | 2 / 3 / 12 / 2 | Verifikasi hubungan venue dan court serta inventaris slot |
| Booking berstatus pending | 2 | Tinjau arti sebenarnya sebelum menerapkan tenggat baru |
| Durasi booking berbeda dari interval jadwal | 1 | Pengelola menentukan waktu yang benar berdasarkan pemesanan asli |
| Booking pending/confirmed pada jadwal closed | 1 | Rekonsiliasi booking dan alasan penutupan |
| Slot dengan beberapa booking | 0 | Tetap bangun perlindungan bentrok; snapshot bukan bukti pencegahan |
| Jadwal bertumpuk | 0 | Periksa ulang sebelum migrasi |

Dua jenis konflik booking tersebut mungkin berkaitan dengan record yang sama. Ringkasan audit tidak cukup untuk menyimpulkannya; jangan menganggap ada dua booking bermasalah yang pasti berbeda.

Sebelum migrasi, jalankan audit ulang baca-saja dan kelompokkan hasilnya:

1. **Siap dipetakan:** waktu bulat dan data konsisten.
2. **Perlu perlindungan jadwal lama:** misalnya booking 10.30–11.30 yang harus tetap dipertahankan.
3. **Perlu keputusan pengelola:** durasi berbeda, status bertentangan, atau hubungan court ambigu.
4. **Riwayat:** data masa lalu yang tidak boleh hilang atau berubah makna.

Data yang bermasalah tidak boleh ditebak atau dipaksa masuk struktur baru. Simulasi pemetaan harus menghasilkan daftar konflik untuk diselesaikan sebelum penerapan data terkait.

**Koreksi asumsi tarif:** pada skema yang diperiksa dalam pembahasan, belum terlihat penyimpanan tarif/harga. Jangan menganggap perhitungan harga atau total booking sudah tersedia. Penambahan tarif memerlukan pembahasan tersendiri; dokumen ini tidak menetapkan harga atau menambahkan sistem pembayaran.

Audit juga mencatat akses management dan booking lintas venue yang perlu diperbaiki. Pembatasan server sesuai hak admin/penjaga menjadi prasyarat pengaktifan fitur, bukan sekadar penyesuaian menu. Laporan lama adalah bukti historis; status masalah harus diperiksa ulang saat pekerjaan backend dimulai.

### 12.4. Proses kedaluwarsa dan pembentukan slot

Gunakan proses terjadwal **setiap satu menit** untuk:

- Mencari booking pending yang sudah mencapai `expires_at`.
- Mengubah status menjadi kedaluwarsa dan melepaskan reservasi aktif dalam transaksi yang aman.
- Menyimpan riwayat perubahan tanpa menghapus booking maupun hubungan historis slotnya.
- Mencatat hasil eksekusi agar dapat dipantau.

Proses harus aman dijalankan berulang, tidak tumpang tindih dengan eksekusi dirinya sendiri, dan tetap aman ketika berjalan bersamaan dengan konfirmasi atau pemesanan baru. [Laravel Scheduler](https://laravel.com/docs/11.x/scheduling) mendukung penjadwalan dan pencegahan proses tumpang tindih; proses scheduler pada server tetap harus dikonfigurasi dan dijalankan.

Jadwal setiap menit bukan tambahan toleransi satu menit. Jika batas konfirmasi 10.30.00, konfirmasi 10.30.01 tetap ditolak meskipun proses pembaruan status tersimpan baru berjalan pukul 10.31. Ketersediaan dan penerimaan tindakan selalu mengevaluasi tenggat menggunakan waktu server.

Permintaan booking baru juga harus dapat menyelesaikan penahanan yang sudah kedaluwarsa secara aman dalam transaksi. Keterlambatan scheduler tidak boleh membuat slot tertahan tanpa batas atau menyebabkan konfirmasi terlambat diterima.

Pembentukan slot untuk rentang 14 hari menggunakan proses harian disertai pemeriksaan susulan terhadap tanggal yang belum lengkap. Jika eksekusi tengah malam terlewat, eksekusi berikutnya harus melengkapi rentang yang berlaku, tanpa menggandakan slot, membuka penutupan, atau menghapus riwayat. Jadwal tepat pemeriksaan susulan ditentukan saat konfigurasi deployment.

### 12.5. Pemantauan proses otomatis

Catat kesehatan proses terpisah dari riwayat tindakan booking pelanggan:

| Informasi | Tujuan |
| --- | --- |
| Waktu terakhir proses dimulai dan berhasil | Mengetahui apakah proses berjalan |
| Jumlah booking yang dikedaluwarsakan | Memeriksa hasil eksekusi |
| Jumlah pending melewati batas yang belum diproses | Menemukan penumpukan pembaruan status |
| Kesalahan terakhir | Membantu penelusuran masalah |
| Kelengkapan slot sampai akhir rentang pemesanan | Menemukan pembentukan jadwal yang terlewat |

Admin mendapat peringatan operasional jika tidak ada eksekusi berhasil selama **lebih dari tiga menit**, atau ditemukan penumpukan pending melewati batas. Tiga menit adalah ambang pemantauan awal, bukan perpanjangan tenggat booking. Informasi kesalahan tidak boleh mengekspos kredensial atau data pribadi pelanggan.

Pemeriksaan kesehatan tidak boleh hanya bergantung pada proses yang dipantau. Dashboard membaca waktu keberhasilan terakhir sehingga proses yang berhenti tetap dapat terdeteksi ketika dashboard dibuka. Pemantauan independen di luar aplikasi dapat ditambahkan pada deployment produksi; jangan mengklaim pengiriman peringatan saat aplikasi tidak diakses jika mekanisme tersebut belum dipasang.

### 12.6. Detail yang masih perlu diselesaikan sebelum implementasi

Keputusan produk dan arah teknis sudah ditetapkan. Pekerjaan persiapan yang masih diperlukan:

1. Audit data terbaru, inventaris court fisik per venue, dan penyelesaian konflik aktual oleh pengelola.
2. Rancangan kolom, indeks, foreign key, pemetaan ID lama, serta urutan migrasi dan pemulihan yang sesuai hasil audit.
3. Detail penguncian transaksi dan pengaman pengiriman ulang, termasuk bukti uji permintaan bersamaan.
4. Konfigurasi scheduler, penyimpanan catatan kesehatan proses, serta jadwal pemeriksaan kelengkapan slot pada lingkungan deployment.
5. Rancangan tarif tersendiri hanya jika kemudian diminta; jangan menambah tarif atau pembayaran berdasarkan asumsi.

Pending 60 menit, pembaruan tampilan awal setiap 30 detik, proses kedaluwarsa setiap menit, ambang pemantauan awal tiga menit, WIB, serta tidak adanya izin khusus jam reguler bagi penjaga tidak lagi menjadi pertanyaan terbuka untuk tahap awal.

## 13. Urutan implementasi yang diusulkan

1. Audit ulang data, struktur jadwal/booking, inventaris court fisik, status, dan akses pengguna; petakan data lama serta konflik tanpa mengasumsikan fitur tarif tersedia.
2. Finalisasi detail skema dan transisi data, siapkan database uji terisolasi, serta tangani pembatasan akses backend sebelum fitur diaktifkan.
3. Bangun aturan ketersediaan, pengaturan jam, pengecualian, dan pembentukan slot yang aman dijalankan berulang.
4. Terapkan booking beberapa slot sebagai satu kesatuan, penahanan maksimal 60 menit, konfirmasi pengelola, kedaluwarsa otomatis, pencegahan bentrok, pencatatan pengelola, dan pembatalan tercatat.
5. Sesuaikan dashboard operasional.
6. Sesuaikan halaman publik agar seluruh court dan slot terlihat serta tidak ada input ulang jam.
7. Konfigurasikan proses otomatis dan pemantauannya. Jalankan pengujian aturan waktu, hak akses, konflik, transisi data, tampilan, dan alur booking pada lingkungan uji yang aman.

Urutan ini telah dilaksanakan sampai aktivasi dan pengujian pilot Giri Krida. Prosedur penerapan pada lingkungan atau venue lain tetap memerlukan pemeriksaan konfigurasi, backup, dan rekonsiliasi data sesuai runbook.

## 14. Kriteria penerimaan utama

- Rentang awal 14 hari termasuk hari ini, bergeser setiap hari, dan dapat dikonfigurasi.
- Slot terbentuk per jam sesuai jam reguler atau pengecualian tanggal.
- Pembentukan ulang jadwal tidak menggandakan slot atau membuka slot yang ditutup/dipesan.
- Slot 10.00–11.00 tidak bisa dipesan mulai pukul 10.00, termasuk ketika halaman pengguna masih menampilkan data lama.
- Semua court serta slot tersedia, dipesan, dan tidak tersedia tetap dapat dilihat tanpa mengekspos data pemesan.
- Pengguna dapat memilih beberapa jam berurutan tanpa memasukkan ulang jam atau durasi.
- Slot yang terpisah, court berbeda, atau tanggal berbeda tidak disimpan sebagai satu booking.
- Permintaan bersamaan tidak menghasilkan pemesanan ganda; kegagalan salah satu slot tidak menghasilkan booking sebagian.
- Booking website menahan seluruh slot sementara, menjelaskan aturan durasi penahanan, serta menampilkan status dan kontak pengelola. Tenggat sebenarnya ditampilkan pada dashboard pengelola sesuai revisi tampilan yang disetujui.
- Batas pending adalah waktu pembuatan ditambah 60 menit atau waktu mulai booking, mana yang lebih dahulu; membuka ulang halaman tidak memperpanjangnya.
- Konfirmasi hanya berhasil sebelum batas, termasuk saat proses kedaluwarsa berjalan bersamaan atau terlambat memperbarui database.
- Kedaluwarsa mempertahankan riwayat dan melepas hanya slot yang memenuhi syarat; booking dikonfirmasi tidak terkena tenggat pending lagi.
- Dashboard menyediakan daftar Menunggu konfirmasi dan tindakan Konfirmasi booking dengan akses sesuai venue penugasan.
- Booking langsung/WhatsApp memiliki record pemesan dan dapat dikelola.
- Perubahan operasional yang bertabrakan dengan booking menghasilkan konflik yang jelas.
- Pembatalan mempertahankan riwayat dan hanya melepaskan slot yang benar-benar memenuhi syarat tersedia.
- Booking yang sudah mulai tidak dibatalkan melalui tindakan biasa.
- Penjaga tidak dapat mengakses atau mengubah data venue di luar penugasannya.
- Jam reguler dan rentang pemesanan hanya dapat diubah admin; tidak ada izin khusus jam reguler bagi penjaga pada tahap awal.
- Booking lama tetap utuh dan proses transisi tidak mengubah reservasi secara diam-diam.
- Booking pending lama ditinjau sebelum transisi; aturan baru tidak otomatis menandainya kedaluwarsa atau mengonfirmasinya.
- Seluruh tanggal dan waktu operasional menggunakan WIB, tanpa dukungan slot lintas tengah malam pada tahap awal.
- Halaman yang tetap terbuka memperbarui ketersediaan dan memberi penjelasan ketika pilihan tidak lagi sah; server tetap memvalidasi seluruh slot saat pengiriman.
- Pembaruan awal setiap 30 detik pada tab aktif mempertahankan pencarian, halaman tabel, fokus, serta formulir/modal yang sedang dikerjakan.
- Identitas court fisik jelas per venue; slot unik dan satu slot tidak memiliki lebih dari satu reservasi aktif.
- Pembatalan/kedaluwarsa melepaskan reservasi aktif tanpa menghapus riwayat slot booking; menonaktifkan venue/court tidak menghapus riwayat berantai.
- Pengiriman ulang permintaan tidak menggandakan booking.
- Proses kedaluwarsa setiap menit aman dijalankan ulang; keterlambatannya tidak membolehkan konfirmasi lewat batas atau penahanan slot tanpa akhir.
- Pembentukan slot dapat mengejar tanggal yang terlewat tanpa membuka penutupan atau menggandakan slot.
- Admin dapat melihat kesehatan proses dan peringatan jika lebih dari tiga menit tanpa keberhasilan atau terdapat penumpukan yang belum diproses.
- Tampilan tetap responsif, memiliki label yang jelas, dan dapat digunakan dengan keyboard.

## 15. Status penerapan dan batas verifikasi

Implementasi tahap 1–7 selesai. Giri Krida Court A aktif dengan jam 08.00–22.00 WIB dan rentang 14 hari termasuk hari ini. Konfirmasi, pembatalan, kedaluwarsa alami pada batas jam mulai, serta pengisian kembali rentang jadwal telah diverifikasi dalam pilot. Riwayat data lama dipertahankan.

Uji langsung menunggu 60 menit penuh belum dilakukan dalam pilot. Pemantauan ini juga belum membuktikan layanan berjalan tanpa gangguan selama beberapa hari. Scheduler lokal bergantung pada komputer, sesi pengguna task Windows, dan MySQL. Venue lain belum diaktifkan. Lihat [laporan penerimaan pilot](qa/giri-krida-pilot-acceptance.md) dan [runbook cutover](qa/hourly-booking-stage-7-cutover.md) untuk bukti dan prosedur operasional.
