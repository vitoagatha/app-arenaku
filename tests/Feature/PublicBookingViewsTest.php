<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PublicBookingViewsTest extends TestCase
{
    private function bookingData(): array
    {
        // In-memory view fixtures only; no database records are created.
        $lapangan = (object) ['id' => 7, 'nama_lapangan' => 'Lapangan uji', 'alamat' => 'Alamat uji', 'image' => null, 'deskripsi' => 'Deskripsi uji'];
        $jadwal = (object) ['id' => 21, 'court_id' => 3, 'court' => (object) ['nama_courts' => 'Court uji'], 'tanggal' => '2026-11-01', 'jam_awal' => '09:00:00', 'jam_akhir' => '10:00:00', 'status' => 'ready'];

        return compact('lapangan', 'jadwal') + ['errors' => new ViewErrorBag()];
    }

    public function test_only_ready_schedules_have_booking_links(): void
    {
        $data = $this->bookingData();
        $booked = clone $data['jadwal'];
        $booked->id = 22;
        $booked->status = 'booking';
        $closed = clone $data['jadwal'];
        $closed->id = 23;
        $closed->status = 'closed';

        $this->view('cms.cek-booking', $data + ['tanggal' => '2026-11-01', 'jadwalLapangan' => collect([$data['jadwal'], $booked, $closed])])
            ->assertSee('Tersedia')->assertSee('Dipesan')->assertSee('Ditutup')
            ->assertSee(route('lapangan.confirm-booking', ['id' => 21]), false)
            ->assertDontSee(route('lapangan.confirm-booking', ['id' => 22]), false)
            ->assertDontSee(route('lapangan.confirm-booking', ['id' => 23]), false)
            ->assertSee('1 November 2026')->assertDontSee('Jam kosong hari ini');
    }

    public function test_empty_schedule_has_a_recovery_action(): void
    {
        $this->view('cms.cek-booking', $this->bookingData() + ['tanggal' => '2026-11-01', 'jadwalLapangan' => collect()])
            ->assertSee('Belum ada jadwal pada tanggal ini')->assertSee('Ganti tanggal');
    }

    public function test_confirmation_preserves_form_contract_and_validation_feedback(): void
    {
        $data = $this->bookingData();
        $data['errors']->put('default', new MessageBag(['nama' => 'Nama wajib diisi.']));
        session()->flashInput(['nama' => 'Nama uji', 'no_whatsapp' => '08123456789', 'durasi' => 1]);
        $this->app['request']->setLaravelSession(session()->driver());

        $this->view('cms.konfirmasi-booking', $data)
            ->assertSee(route('booking.store'), false)->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)->assertSee('name="jadwal_lapangan_id"', false)
            ->assertSee('value="Nama uji"', false)->assertSee('value="08123456789"', false)
            ->assertSee('nama-error')->assertSee('Nama wajib diisi.')->assertSee('type="tel"', false);
    }

    public function test_result_reports_recording_without_claiming_approval(): void
    {
        $this->view('cms.booking-success', $this->bookingData() + ['konfirmasiBooking' => (object) ['nama' => 'Nama uji', 'no_whatsapp' => '08123456789', 'durasi' => 1]])
            ->assertSee('Booking berhasil dicatat')->assertSee('bukan bukti pembayaran atau persetujuan booking')
            ->assertSee('Kembali ke daftar lapangan')->assertDontSee('Booking Sukses');
    }
}
