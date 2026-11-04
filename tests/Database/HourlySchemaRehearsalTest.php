<?php

namespace Tests\Database;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TestDatabase;
use Tests\TestCase;

class HourlySchemaRehearsalTest extends TestCase
{
    private array $tables = ['users', 'lapangan', 'courts', 'jadwal_lapangan', 'konfirmasi_bookings'];

    public function test_schema_preserves_legacy_data_enforces_constraints_and_can_restore_backup(): void
    {
        TestDatabase::assertSafe($this->app);
        $this->assertSame(ARENAKU_TEST_DATABASE, DB::selectOne('SELECT DATABASE() AS db')->db);
        foreach ($this->tables as $table) {
            $this->assertSame(0, DB::table($table)->count(), 'Rehearsal requires an empty isolated baseline.');
        }

        $snapshot = $GLOBALS['arenaku_audit_snapshot'];
        foreach ($snapshot['users'] as $row) {
            DB::table('users')->insert($row + [
                'name' => 'Anonymized', 'last_name' => 'Account '.$row['id'],
                'email' => 'legacy-'.$row['id'].'@example.test', 'password' => 'not-a-login-credential',
            ]);
        }
        foreach ($snapshot['lapangan'] as $row) {
            DB::table('lapangan')->insert($row + ['nama_lapangan' => 'Anonymized venue '.$row['id'], 'alamat' => 'Test address']);
        }
        foreach ($snapshot['courts'] as $row) {
            DB::table('courts')->insert($row);
        }
        foreach ($snapshot['jadwal_lapangan'] as $row) {
            DB::table('jadwal_lapangan')->insert($row);
        }
        foreach ($snapshot['konfirmasi_bookings'] as $row) {
            DB::table('konfirmasi_bookings')->insert($row + ['nama' => 'Anonymized customer '.$row['id'], 'no_whatsapp' => '0800000000']);
        }

        // Logical backup stays in memory and contains only anonymized test data.
        $backup = $this->readTables();
        $backupHash = hash('sha256', json_encode($backup));
        $migration = require base_path('database/staging/2026_11_04_000001_hourly_booking_schema.php');
        $migration->up();

        foreach ($backup as $table => $rows) {
            if ($rows !== []) {
                $actual = DB::table($table)->select(array_keys($rows[0]))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
                $this->assertSame($rows, $actual, 'Legacy fields changed in '.$table);
            }
        }
        $this->assertSame(count($snapshot['konfirmasi_bookings']), DB::table('konfirmasi_bookings')->where('is_legacy', true)->whereNull('expires_at')->count());
        $this->assertSame(0, DB::table('active_slot_reservations')->count());
        $this->assertSame(0, DB::table('lapangan')->where('hourly_booking_enabled', true)->count());
        $this->assertSame(0, DB::table('lapangan')->whereNotNull('regular_opens_at')->count());

        $pairs = DB::table('jadwal_lapangan as j')->join('courts as c', 'c.id', '=', 'j.court_id')
            ->select('j.lapangan_id', 'j.court_id', 'c.nama_courts')->distinct()->get();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            foreach ($pairs as $pair) {
                DB::table('venue_courts')->insertOrIgnore([
                    'lapangan_id' => $pair->lapangan_id, 'legacy_court_id' => $pair->court_id, 'name' => $pair->nama_courts,
                ]);
            }
        }
        $this->assertSame($pairs->count(), DB::table('venue_courts')->count());
        $this->assertSame(0, DB::table('venue_courts')->where('is_active', true)->count());

        // Synthetic records are independent of real inventory and never activate legacy candidates.
        $owner = DB::table('users')->insertGetId([
            'name' => 'Schema', 'last_name' => 'Fixture', 'email' => 'schema@example.test',
            'password' => 'not-a-login-credential', 'role' => 'admin',
        ]);
        $venue = DB::table('lapangan')->insertGetId(['nama_lapangan' => 'Schema fixture venue', 'alamat' => 'Test', 'user_id' => $owner]);
        $court = DB::table('venue_courts')->insertGetId(['lapangan_id' => $venue, 'name' => 'Fixture A']);
        $otherCourt = DB::table('venue_courts')->insertGetId(['lapangan_id' => $venue, 'name' => 'Fixture B']);
        $slot = DB::table('schedule_slots')->insertGetId([
            'venue_court_id' => $court, 'starts_at' => '2030-01-02 10:00:00', 'ends_at' => '2030-01-02 11:00:00',
        ]);
        $this->constraintFails(fn () => DB::table('schedule_slots')->insert([
            'venue_court_id' => $court, 'starts_at' => '2030-01-02 10:00:00', 'ends_at' => '2030-01-02 11:00:00',
        ]), 1062);
        $this->constraintFails(fn () => DB::table('schedule_slots')->insert([
            'venue_court_id' => $court, 'starts_at' => '2030-01-02 12:30:00', 'ends_at' => '2030-01-02 13:30:00',
        ]), 3819);
        $this->constraintFails(fn () => DB::table('schedule_slots')->insert([
            'venue_court_id' => $court, 'starts_at' => '2030-01-02 12:00:00', 'ends_at' => '2030-01-02 14:00:00',
        ]), 3819);
        $this->constraintFails(fn () => DB::table('lapangan')->where('id', $venue)->update([
            'regular_opens_at' => '20:00', 'regular_closes_at' => '08:00',
        ]), 3819);

        $bookingIds = [];
        foreach (['a', 'b'] as $key) {
            $bookingIds[] = DB::table('konfirmasi_bookings')->insertGetId([
                'jadwal_lapangan_id' => null, 'venue_court_id' => $court, 'is_legacy' => false,
                'nama' => 'Schema customer', 'no_whatsapp' => '0800000000', 'durasi' => 1,
                'status' => 'pending', 'source' => 'website', 'starts_at' => '2030-01-02 10:00:00',
                'ends_at' => '2030-01-02 11:00:00', 'expires_at' => '2030-01-02 09:00:00',
                'idempotency_key_hash' => hash('sha256', $key),
            ]);
        }
        foreach ($bookingIds as $bookingId) {
            DB::table('booking_slots')->insert(['booking_id' => $bookingId, 'slot_id' => $slot, 'venue_court_id' => $court]);
        }
        $this->constraintFails(fn () => DB::table('konfirmasi_bookings')->where('id', $bookingIds[0])->update(['durasi' => 2]), 3819);
        $this->constraintFails(fn () => DB::table('booking_slots')->insert([
            'booking_id' => $bookingIds[0], 'slot_id' => $slot, 'venue_court_id' => $court,
        ]), 1062);

        $foreignSlot = DB::table('schedule_slots')->insertGetId([
            'venue_court_id' => $otherCourt, 'starts_at' => '2030-01-02 10:00:00', 'ends_at' => '2030-01-02 11:00:00',
        ]);
        $this->constraintFails(fn () => DB::table('booking_slots')->insert([
            'booking_id' => $bookingIds[0], 'slot_id' => $foreignSlot, 'venue_court_id' => $court,
        ]), 1452);

        DB::table('active_slot_reservations')->insert(['slot_id' => $slot, 'booking_id' => $bookingIds[0], 'venue_court_id' => $court]);
        $this->constraintFails(fn () => DB::table('active_slot_reservations')->insert([
            'slot_id' => $slot, 'booking_id' => $bookingIds[1], 'venue_court_id' => $court,
        ]), 1062);
        $this->constraintFails(fn () => DB::table('lapangan')->where('id', $venue)->delete(), 1451);
        $this->constraintFails(fn () => DB::table('konfirmasi_bookings')->where('id', $bookingIds[0])->delete(), 1451);

        DB::table('active_slot_reservations')->where('slot_id', $slot)->delete();
        $this->assertSame(2, DB::table('booking_slots')->where('slot_id', $slot)->count());
        DB::table('active_slot_reservations')->insert(['slot_id' => $slot, 'booking_id' => $bookingIds[1], 'venue_court_id' => $court]);
        $this->assertSame($bookingIds[1], DB::table('active_slot_reservations')->where('slot_id', $slot)->value('booking_id'));
        $this->constraintFails(fn () => DB::table('konfirmasi_bookings')->insert([
            'nama' => 'Retry', 'no_whatsapp' => '0800000000', 'idempotency_key_hash' => hash('sha256', 'a'),
        ]), 1062);

        // Simulate whole-backup restoration, not destructive application rollback on live data.
        TestDatabase::assertSafe($this->app);
        $this->assertSame(ARENAKU_TEST_DATABASE, DB::selectOne('SELECT DATABASE() AS db')->db);
        Schema::dropAllTables();
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        foreach ($backup as $table => $rows) {
            if ($rows !== []) {
                DB::table($table)->insert($rows);
            }
        }
        $this->assertSame($backupHash, hash('sha256', json_encode($this->readTables())));
        $this->assertFalse(Schema::hasTable('active_slot_reservations'));
        $this->assertFalse(Schema::hasColumn('konfirmasi_bookings', 'expires_at'));
    }

    private function readTables(): array
    {
        $result = [];
        foreach ($this->tables as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        return $result;
    }

    private function constraintFails(callable $action, int $mysqlCode): void
    {
        try {
            $action();
            $this->fail('Expected MySQL constraint rejection '.$mysqlCode);
        } catch (QueryException $e) {
            // MySQL can return the non-disclosing FK variants for restricted accounts.
            $allowed = match ($mysqlCode) {
                1452 => [1452, 1216],
                1451 => [1451, 1217],
                default => [$mysqlCode],
            };
            $this->assertContains((int) $e->errorInfo[1], $allowed);
        }
    }
}
