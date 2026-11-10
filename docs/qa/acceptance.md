# Penerimaan UI dan booking Arenaku

Tanggal pemeriksaan 9 November 2026. Semua waktu menggunakan WIB.

## Lingkup dan metode

Pilot venue ID 6, Court A, jam reguler 08.00–22.00, rentang 14 hari termasuk hari ini. Pengujian menggunakan UI publik dan sesi penjaga yang dibuka sendiri oleh pengguna. Data pelanggan sintetis dipakai untuk booking uji. Pembacaan database memakai transaksi READ ONLY. Tidak ada manipulasi waktu atau tenggat booking.

## Hasil

| Pemeriksaan | Status | Bukti |
| --- | --- | --- |
| Konfirmasi booking #5 | PASS | Penjaga mengonfirmasi pada 6 November. Pengguna memverifikasi status di sesi pemesan terpisah. |
| Pembatalan booking #6 | PASS | Court A, 10 November 08.00–10.00. Dikonfirmasi 9 November 11.38.23, dibatalkan 11.38.39 melalui dashboard penjaga. |
| Alasan dan riwayat pembatalan | PASS | Detail #6 menyimpan alasan uji beserta tindakan pencatatan, konfirmasi, dan pembatalan. |
| Status publik pembatalan | PASS | Tombol Perbarui status menampilkan Booking dibatalkan. |
| Pelepasan kedua slot #6 | PASS | UI jadwal menampilkan 08.00–09.00 dan 09.00–10.00 kembali Tersedia. |
| Kedaluwarsa alami booking #7 | PASS | Scheduler expiry memproses satu booking pada 12.00.18, status expired dan reservasi aktif menjadi 0. Dua hubungan booking_slots tetap tersimpan. |
| Kelengkapan rentang 9–22 November | PASS setelah pemulihan | Scheduler slots berhasil pada 11.45.19 dan menambahkan 42 slot. Setiap tanggal memiliki 14 slot, total 196. |

## Scheduler

Task Windows Arenaku Hourly Scheduler aktif setiap menit melalui wscript dan peluncur tersembunyi. Log menunjukkan kegagalan koneksi MySQL hingga 9 November 11.33. Eksekusi expiry di database kembali berhasil pada 11.34.19. Hasil task Windows 0 saja tidak membuktikan pekerjaan internal selesai, sehingga pemeriksaan juga memakai operational_task_runs.

## Batas verifikasi

Pengujian pembatalan #6 memakai tab publik dan dashboard dalam satu sesi browser yang sudah login sebagai penjaga. Ini memverifikasi alur pembatalan serta halaman publik, sementara pengujian pemesan tanpa login dengan sesi terpisah sebelumnya dilakukan bersama pengguna untuk #5. Booking uji dipertahankan sebagai riwayat. Pengujian #7 juga memakai sesi browser yang sama. Setelah scheduler menyelesaikan expiry, halaman publik menampilkan Permintaan kedaluwarsa. Slot 12.00–13.00 menjadi Tidak tersedia karena sudah mulai, sedangkan 13.00–14.00 kembali Tersedia. Riwayat booking_expired tercatat pada 12.00.18.

## Status akhir

PASS untuk konfirmasi, pembatalan, kedaluwarsa alami pada batas jam mulai, serta pengisian kembali rentang 14 hari setelah layanan pulih. Pemantauan ini tidak membuktikan ketersediaan layanan tanpa gangguan selama beberapa hari. Scheduler lokal memerlukan komputer, sesi Windows pengguna task, dan MySQL berjalan.

Skenario menunggu 60 menit penuh tidak diulang secara langsung dalam pilot ini. Booking #7 memakai batas jam mulai yang lebih awal. Tidak ada perubahan kode aplikasi atau konfigurasi scheduler dalam pengujian ini.

Bukti screenshot lokal tersimpan di tests/.runtime/pilot-cancellation-receipt.png dan tests/.runtime/pilot-expiry-receipt.png, folder yang diabaikan Git.


## Bukti regresi otomatis

Hasil historis berikut bukan eksekusi ulang pada 10 November.

| Checkpoint | Bukti dan batas |
| --- | --- |
| Fondasi akses dan schema | MySQL terisolasi memverifikasi role, scope venue, penolakan source oleh akun test, constraint dan retensi legacy |
| Operasional dan lifecycle | Verifikasi jam/penutupan, pembentukan idempotent, slot berurutan, retry, expiry dan konfirmasi pada tenggat |
| Concurrency | Worker paralel menguji bentrok slot, retry key yang sama, serta expiry bersamaan konfirmasi |
| Integrasi publik | 39 tes / 625 assertions, termasuk receipt lintas sesi 404, HTTP booking multi-slot, konflik atomik dan penolakan writer legacy venue aktif |
| Maintenance | 40 tes / 659 assertions, termasuk preflight, aktivasi, retry kegagalan, backlog dan catch-up dua hari |
| Simulasi konfigurasi pilot | 41 tes / 674 assertions termasuk tes sementara Court A 08.00–22.00 selama 14 hari; tes sementara dibersihkan setelah run |

Suite memakai php tests/run.php. Akun/schema uji dibersihkan dan fingerprint operasional source tetap sama. Lint PHP, sintaks JS, kompilasi Blade serta diff checks lulus pada pekerjaan terkait. [Instruksi pengujian](../../tests/README.md).

## Penerimaan antarmuka

Pratinjau fixture hourly dashboard/publik ditinjau pada 360, 390, 768, 1024, dan 1440 piksel. Cakupan termasuk overflow, label, keyboard checkbox, ringkasan pilihan, slot berubah dipesan, batas jam mulai, serta kegagalan pembaruan sambil mempertahankan pilihan/isian. Pratinjau fixture memakai submit nonaktif; submit nyata tercakup pada suite HTTP terisolasi dan alur pilot yang dijelaskan di atas.

Sesi admin dan penjaga nyata juga dipakai dalam QA dashboard sebelum hourly. Perbaikan mencakup deskripsi tabel panjang, overflow mobile akibat teks aksesibel, dan batas fokus Tab/Shift+Tab modal. Escape dan pemulihan fokus diperiksa pada skenario yang tercatat. Penerimaan visual dashboard menyeluruh tetap PARTIAL. Retest shared fixes pada sesi admin, beberapa kombinasi halaman/lebar admin, profil/user/booking secara penuh, backdrop click, upload preview, modal validation recovery, empty state dan pagination halaman kedua belum terverifikasi menyeluruh. Tidak mengklaim semua CRUD tulis telah diuji pada data utama.

## Batas lingkungan dan tindak lanjut

Mix legacy belum menjadi build yang terverifikasi lengkap. Hindari output SCSS yang menimpa aset vendor. Audit aksesibilitas parsial, tanpa sertifikasi WCAG atau pengujian pembaca layar lengkap. Pemulihan email reset password dan deployment produksi belum tercakup dalam penerimaan pilot.

[Operasional](../operations.md) menjelaskan pemantauan dan penerapan berikutnya.
