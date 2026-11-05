<?php

namespace Tests\Database;

use App\Models\Court;
use App\Models\JadwalLapangan;
use App\Models\KonfirmasiBooking;
use App\Models\Lapangan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManagementAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $caretaker;
    private User $unassigned;
    private Lapangan $ownVenue;
    private Lapangan $otherVenue;
    private Court $court;
    private JadwalLapangan $ownSchedule;
    private JadwalLapangan $otherSchedule;
    private KonfirmasiBooking $ownBooking;
    private KonfirmasiBooking $otherBooking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->user('admin');
        $this->caretaker = $this->user('penjaga');
        $other = $this->user('penjaga');
        $this->unassigned = $this->user('penjaga');
        $this->ownVenue = Lapangan::create(['nama_lapangan' => 'Assigned venue', 'alamat' => 'Test address', 'user_id' => $this->caretaker->id]);
        $this->otherVenue = Lapangan::create(['nama_lapangan' => 'Other venue', 'alamat' => 'Test address', 'user_id' => $other->id]);
        $this->court = Court::create(['nama_courts' => 'Test court']);
        $this->ownSchedule = $this->schedule($this->ownVenue);
        $this->otherSchedule = $this->schedule($this->otherVenue);
        $this->ownBooking = $this->booking($this->ownSchedule, 'Assigned customer');
        $this->otherBooking = $this->booking($this->otherSchedule, 'Other customer');
    }

    private function user(string $role): User
    {
        return User::create(['name' => 'Test', 'last_name' => 'Account', 'email' => bin2hex(random_bytes(6)).'@example.test', 'password' => 'test-only-password', 'role' => $role]);
    }

    private function schedule(Lapangan $venue): JadwalLapangan
    {
        return JadwalLapangan::create(['lapangan_id' => $venue->id, 'court_id' => $this->court->id, 'tanggal' => '2030-01-02', 'jam_awal' => '10:00', 'jam_akhir' => '11:00', 'status' => 'ready']);
    }

    private function booking(JadwalLapangan $schedule, string $name): KonfirmasiBooking
    {
        return KonfirmasiBooking::create(['jadwal_lapangan_id' => $schedule->id, 'nama' => $name, 'no_whatsapp' => '0800000000', 'durasi' => 1, 'status' => 'pending']);
    }

    private function schedulePayload(Lapangan $venue): array
    {
        return ['lapangan_id' => $venue->id, 'court_id' => $this->court->id, 'tanggal' => '2030-01-02', 'jam_awal' => '12:00', 'jam_akhir' => '13:00', 'status' => 'ready'];
    }

    public function test_guests_cannot_read_or_mutate_management_routes(): void
    {
        $requests = [
            ['GET', '/booking'], ['GET', '/booking/'.$this->ownBooking->id.'/edit'],
            ['PUT', '/booking/'.$this->ownBooking->id], ['DELETE', '/booking/'.$this->ownBooking->id],
            ['GET', '/jadwal'], ['GET', '/jadwal/'.$this->ownVenue->id.'/court'],
            ['POST', '/jadwal/store'], ['PUT', '/jadwal/'.$this->ownSchedule->id], ['DELETE', '/jadwal/'.$this->ownSchedule->id],
            ['GET', '/home'], ['GET', '/profile'], ['PUT', '/profile'],
        ];
        foreach (['users' => $this->caretaker->id, 'courts' => $this->court->id, 'lapangan' => $this->ownVenue->id] as $resource => $id) {
            foreach ([['GET', ''], ['GET', '/create'], ['POST', ''], ['GET', '/'.$id], ['GET', '/'.$id.'/edit'], ['PUT', '/'.$id], ['DELETE', '/'.$id]] as [$method, $suffix]) {
                $requests[] = [$method, '/'.$resource.$suffix];
            }
        }
        foreach ($requests as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect('/login');
        }
        $this->assertDatabaseCount('konfirmasi_bookings', 2);
        $this->assertDatabaseCount('users', 4);
    }

    public function test_caretakers_cannot_access_any_users_or_global_courts_action(): void
    {
        $this->actingAs($this->caretaker);
        foreach (['users' => $this->caretaker->id, 'courts' => $this->court->id] as $resource => $id) {
            foreach ([['GET', ''], ['GET', '/create'], ['POST', ''], ['GET', '/'.$id], ['GET', '/'.$id.'/edit'], ['PUT', '/'.$id], ['DELETE', '/'.$id]] as [$method, $suffix]) {
                $this->call($method, '/'.$resource.$suffix, ['role' => 'admin'])->assertForbidden();
            }
        }
        $this->assertSame('penjaga', $this->caretaker->fresh()->role);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('courts', 1);
    }

    public function test_booking_lists_are_scoped_and_unassigned_caretaker_gets_empty_list(): void
    {
        $this->actingAs($this->caretaker)->get('/booking')->assertOk()
            ->assertSee('Assigned customer')->assertDontSee('Other customer');
        $this->actingAs($this->unassigned)->get('/booking')->assertOk()
            ->assertDontSee('Assigned customer')->assertDontSee('Other customer');
        $this->actingAs($this->admin)->get('/booking')->assertOk()
            ->assertSee('Assigned customer')->assertSee('Other customer');
    }

    public function test_foreign_booking_ids_are_denied_for_read_update_and_delete(): void
    {
        foreach ([$this->caretaker, $this->unassigned] as $actor) {
            $this->actingAs($actor)->get('/booking/'.$this->otherBooking->id.'/edit')->assertNotFound();
            $this->put('/booking/'.$this->otherBooking->id, ['nama' => 'Changed', 'no_whatsapp' => '0800000000', 'durasi' => 2])->assertNotFound();
            $this->delete('/booking/'.$this->otherBooking->id)->assertNotFound();
        }
        $this->assertSame('Other customer', $this->otherBooking->fresh()->nama);
        $this->assertDatabaseCount('konfirmasi_bookings', 2);
    }

    public function test_caretaker_can_edit_own_booking_but_cannot_reassign_its_schedule(): void
    {
        $this->actingAs($this->caretaker)->get('/booking/'.$this->ownBooking->id.'/edit')->assertOk();
        $this->put('/booking/'.$this->ownBooking->id, [
            'nama' => 'Updated test customer', 'no_whatsapp' => '0800000000', 'durasi' => 1,
            'jadwal_lapangan_id' => $this->otherSchedule->id, 'status' => 'confirmed',
        ])->assertRedirect(route('booking.index'));
        $this->assertDatabaseHas('konfirmasi_bookings', [
            'id' => $this->ownBooking->id, 'nama' => 'Updated test customer',
            'jadwal_lapangan_id' => $this->ownSchedule->id, 'status' => 'pending',
        ]);
    }

    public function test_schedule_update_authorizes_both_original_and_destination_venues(): void
    {
        $this->actingAs($this->caretaker);
        $this->put('/jadwal/'.$this->ownSchedule->id, $this->schedulePayload($this->otherVenue))->assertNotFound();
        $this->put('/jadwal/'.$this->otherSchedule->id, $this->schedulePayload($this->ownVenue))->assertNotFound();
        $this->post('/jadwal/store', $this->schedulePayload($this->otherVenue))->assertNotFound();
        $this->delete('/jadwal/'.$this->otherSchedule->id)->assertNotFound();
        $this->assertSame($this->ownVenue->id, $this->ownSchedule->fresh()->lapangan_id);
        $this->assertSame($this->otherVenue->id, $this->otherSchedule->fresh()->lapangan_id);
        $this->assertDatabaseCount('jadwal_lapangan', 2);
    }

    public function test_own_schedule_actions_work_and_create_ignores_unvalidated_status(): void
    {
        $this->actingAs($this->caretaker)->get('/jadwal/'.$this->ownVenue->id.'/court')->assertOk();
        $payload = $this->schedulePayload($this->ownVenue);
        $payload['status'] = 'booking';
        $this->post('/jadwal/store', $payload)->assertRedirect();
        $created = JadwalLapangan::orderByDesc('id')->first();
        $this->assertSame('ready', $created->status);
        $payload['status'] = 'closed';
        $this->put('/jadwal/'.$created->id, $payload)->assertRedirect();
        $this->assertSame('closed', $created->fresh()->status);
        $this->delete('/jadwal/'.$created->id)->assertRedirect();
        $this->assertDatabaseMissing('jadwal_lapangan', ['id' => $created->id]);
    }

    public function test_venue_ownership_protects_updates_and_deletes(): void
    {
        $this->actingAs($this->caretaker);
        $payload = ['nama_lapangan' => 'Hijacked venue', 'alamat' => 'Test'];
        $this->put('/lapangan/'.$this->otherVenue->id, $payload)->assertNotFound();
        $this->delete('/lapangan/'.$this->otherVenue->id)->assertRedirect(route('lapangan.index'));
        $this->assertSame('Other venue', $this->otherVenue->fresh()->nama_lapangan);
        $payload['nama_lapangan'] = 'Own venue changed';
        $payload['user_id'] = $this->admin->id;
        $this->put('/lapangan/'.$this->ownVenue->id, $payload)->assertRedirect(route('lapangan.index'));
        $this->assertSame($this->caretaker->id, $this->ownVenue->fresh()->user_id);
    }

    public function test_admin_can_access_global_management_and_update_foreign_booking(): void
    {
        $this->actingAs($this->admin);
        foreach (['/users', '/users/create', '/courts', '/courts/create', '/lapangan', '/jadwal', '/booking/'.$this->otherBooking->id.'/edit'] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->put('/booking/'.$this->otherBooking->id, ['nama' => 'Admin update', 'no_whatsapp' => '0800000000', 'durasi' => 1])->assertRedirect();
        $this->assertSame('Admin update', $this->otherBooking->fresh()->nama);
        $this->post('/courts', ['nama_courts' => 'Admin-created court'])->assertRedirect();
        $this->assertDatabaseHas('courts', ['nama_courts' => 'Admin-created court']);
    }

    public function test_unknown_roles_are_not_treated_as_caretakers(): void
    {
        $this->caretaker->role = 'unexpected-role'; // In-memory only; the database enum is unchanged.
        $this->actingAs($this->caretaker);
        foreach (['/booking', '/jadwal', '/lapangan', '/users', '/courts'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_public_booking_submission_stays_public(): void
    {
        // Isolated synthetic data only. Availability/lifecycle changes belong to stages 3-4.
        $this->post('/booking/store', [
            'jadwal_lapangan_id' => $this->ownSchedule->id,
            'nama' => 'Public test customer', 'no_whatsapp' => '0800000000', 'durasi' => 1,
        ])->assertOk()->assertSee('Booking berhasil dicatat');
        $this->assertDatabaseHas('konfirmasi_bookings', ['nama' => 'Public test customer', 'status' => 'pending']);
    }

    public function test_hourly_dashboard_explains_schema_readiness_without_migrating(): void
    {
        $this->actingAs($this->caretaker)->get(route('jadwal.operations', $this->ownVenue->id))
            ->assertOk()->assertSee('Pengaturan per jam sedang disiapkan');
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('venue_courts'));
    }
}
