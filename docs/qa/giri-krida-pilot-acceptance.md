# Penerimaan pilot Giri Krida

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

Pesan commit yang disarankan adalah docs(qa): record Giri Krida pilot acceptance results.