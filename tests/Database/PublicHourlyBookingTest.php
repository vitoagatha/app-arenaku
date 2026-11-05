<?php

namespace Tests\Database;

use App\Models\User;
use App\Services\CourtOperations;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicHourlyBookingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_booking_retries_receipt_privacy_and_slot_conflicts(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-04-01 08:00', 'Asia/Jakarta'));
        try {
            $owner = User::create([
                'name' => 'Public fixture', 'last_name' => 'Owner', 'email' => Str::uuid().'@example.test',
                'password' => 'test-only', 'role' => 'penjaga',
            ]);
            $venue = DB::table('lapangan')->insertGetId([
                'nama_lapangan' => 'Public fixture venue', 'alamat' => 'Test', 'user_id' => $owner->id,
                'regular_opens_at' => '08:00', 'regular_closes_at' => '13:00',
                'hourly_booking_enabled' => true, 'booking_contact' => '+628000000000',
            ]);
            $court = DB::table('venue_courts')->insertGetId(['lapangan_id' => $venue, 'name' => 'Court Alpha', 'is_active' => true]);
            DB::table('venue_courts')->insert(['lapangan_id' => $venue, 'name' => 'Court Beta', 'is_active' => true]);
            app(CourtOperations::class)->generate($venue);
            $page = route('cms.cek-booking', ['lapangan' => $venue, 'tanggal' => '2030-04-01']);
            $store = route('booking.hourly.store', $venue);
            $availability = route('booking.hourly.availability', ['lapangan' => $venue, 'tanggal' => '2030-04-01']);
            $cookie = config('session.cookie');
            $session = Str::random(40);
            $this->withCookie($cookie, $session);
            $pageResponse = $this->get($page);
            $pageResponse->assertOk()->assertSee('Court Alpha')->assertSee('Court Beta')
                ->assertSee('Tidak tersedia')->assertSee('max="2030-04-14"', false);
            $this->preview('public-hourly', $pageResponse->getContent());
            $this->getJson(route('cms.cek-booking', ['lapangan' => $venue, 'tanggal' => '2030-04-15']))->assertUnprocessable();
            $this->get(route('cms.cek-booking', ['lapangan' => $venue, 'tanggal' => '2030-04-15']))
                ->assertRedirect(route('cms.cek-booking', $venue))->assertSessionHasErrors('tanggal');
            $slot = fn ($hour) => DB::table('schedule_slots')->where('venue_court_id', $court)
                ->where('starts_at', '2030-04-01 '.$hour.':00:00')->value('id');

            $payload = [
                'slot_ids' => [$slot('10'), $slot('11')], 'nama' => 'Private fixture customer',
                'phone' => '081234567890', 'key' => (string) Str::uuid(),
                'actor' => $owner->id, 'source' => 'walk_in', 'confirmed' => 1,
            ];
            $receipt = route('booking.hourly.receipt', ['lapangan' => $venue, 'key' => $payload['key']]);
            $this->from($page)->post($store, $payload)->assertStatus(303)->assertRedirect($receipt);
            $booking = DB::table('konfirmasi_bookings')->where('nama', $payload['nama'])->first();
            $this->assertSame('pending', $booking->status);
            $this->assertSame('website', $booking->source);
            $this->assertNull($booking->created_by);
            $this->assertSame(2, $booking->durasi);
            $this->assertSame('2030-04-01 09:00:00', $booking->expires_at);
            $this->post($store, $payload)->assertRedirect($receipt);
            $this->assertSame(1, DB::table('konfirmasi_bookings')->where('nama', $payload['nama'])->count());
            $receiptResponse = $this->get($receipt);
            $receiptResponse->assertOk()->assertSee('Menunggu konfirmasi')->assertSee('+628000000000')
                ->assertDontSee($payload['phone'])->assertDontSee($payload['nama']);
            $this->preview('public-receipt', $receiptResponse->getContent());
            $this->withCookie($cookie, Str::random(40))->get($receipt)->assertNotFound();
            $this->getJson($availability)->assertOk()->assertJsonMissing(['nama' => $payload['nama']])
                ->assertJsonFragment(['id' => $slot('10'), 'available' => false]);

            $conflict = array_replace($payload, ['key' => (string) Str::uuid(), 'slot_ids' => [$slot('09'), $slot('10')]]);
            $this->from($page)->post($store, $conflict)->assertRedirect($page)->assertSessionHasErrors('booking')
                ->assertSessionHasInput('nama', $payload['nama']);
            $this->assertFalse(DB::table('active_slot_reservations')->where('slot_id', $slot('09'))->exists());

            $legacyCourt = DB::table('courts')->insertGetId(['nama_courts' => 'Legacy fixture']);
            $legacy = DB::table('jadwal_lapangan')->insertGetId([
                'lapangan_id' => $venue, 'court_id' => $legacyCourt, 'tanggal' => '2030-04-01',
                'jam_awal' => '10:00', 'jam_akhir' => '11:00', 'status' => 'ready',
            ]);
            $this->post(route('booking.store'), [
                'jadwal_lapangan_id' => $legacy, 'nama' => 'Legacy attempt', 'no_whatsapp' => '081234567890', 'durasi' => 1,
            ])->assertConflict();
            $this->assertDatabaseMissing('konfirmasi_bookings', ['jadwal_lapangan_id' => $legacy]);

            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-04-01 09:00', 'Asia/Jakarta'));
            $this->withCookie($cookie, $session)->get($receipt)->assertOk()->assertSee('Permintaan kedaluwarsa');
            $this->getJson($availability)->assertJsonFragment(['id' => $slot('09'), 'available' => false])
                ->assertJsonFragment(['id' => $slot('10'), 'available' => true]);
            DB::table('lapangan')->where('id', $venue)->update(['hourly_booking_enabled' => false]);
            $this->getJson($availability)->assertNotFound();
            $this->get($receipt)->assertOk()->assertSee('Permintaan kedaluwarsa');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function preview(string $name, string $html): void
    {
        if (getenv('ARENAKU_EXPORT_PREVIEWS') !== '1') {
            return;
        }

        $html = preg_replace('#http://localhost/(?=(?:assets|css|js)/)#', '/', $html);
        $html = preg_replace('/(<form[^>]*action=")[^"]*/', '$1#', $html);
        $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1', $html);
        $html = str_replace('</head>', '<script>document.addEventListener("submit", e => { e.preventDefault(); e.stopImmediatePropagation(); }, true);</script></head>', $html);
        $directory = base_path('tests/.runtime/previews');
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        file_put_contents($directory.'/'.$name.'.html', $html);
    }
}
