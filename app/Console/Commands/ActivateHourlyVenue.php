<?php

namespace App\Console\Commands;

use App\Services\CourtOperations;
use App\Services\HourlyMaintenance;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ActivateHourlyVenue extends Command
{
    protected $signature = 'hourly:activate {venue} {--apply : Aktifkan setelah pemeriksaan}';
    protected $description = 'Periksa kesiapan venue untuk cutover booking per jam';

    public function handle(HourlyMaintenance $maintenance, CourtOperations $operations): int
    {
        if (!$maintenance->ready() || !ctype_digit((string) $this->argument('venue'))) {
            $this->error('Schema per jam dan ID venue yang valid diperlukan.');
            return self::FAILURE;
        }
        $apply = $this->option('apply');
        if ($apply && !app()->environment('testing') && !app()->isDownForMaintenance()) {
            $this->error('Aktivasi memerlukan maintenance mode.');
            return self::FAILURE;
        }

        try {
            $created = DB::transaction(function () use ($apply, $operations) {
                $venue = DB::table('lapangan')->where('id', $this->argument('venue'))->lockForUpdate()->first();
                if (!$venue) {
                    throw new DomainException('Venue tidak ditemukan.');
                }
                if ($venue->hourly_booking_enabled) {
                    throw new DomainException('Venue sudah aktif.');
                }
                if (!$venue->regular_opens_at || !$venue->regular_closes_at
                    || strlen(preg_replace('/\D/', '', $venue->booking_contact ?? '')) < 7
                    || !DB::table('venue_courts')->where('lapangan_id', $venue->id)->where('is_active', true)->exists()) {
                    throw new DomainException('Lengkapi jam reguler, kontak pengelola, dan minimal satu court terverifikasi.');
                }
                $legacy = DB::table('jadwal_lapangan as j')->where('j.lapangan_id', $venue->id)
                    ->whereRaw('TIMESTAMP(j.tanggal, j.jam_akhir) > ?', [CarbonImmutable::now('Asia/Jakarta')->format('Y-m-d H:i:s')])
                    ->where(function ($query) {
                        $query->whereIn('j.status', ['closed', 'booking'])->orWhereExists(function ($booking) {
                            $booking->selectRaw('1')->from('konfirmasi_bookings as b')->whereColumn('b.jadwal_lapangan_id', 'j.id')
                                ->whereIn('b.status', ['pending', 'confirmed']);
                        });
                    })->exists();
                if ($legacy) {
                    throw new DomainException('Rekonsiliasi booking atau penutupan legacy yang masih berlaku sebelum aktivasi.');
                }
                if (!$apply) {
                    return null;
                }
                DB::table('lapangan')->where('id', $venue->id)->update(['hourly_booking_enabled' => true]);
                return $operations->generate($venue->id);
            });
        } catch (DomainException $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        }

        $this->info($apply ? "Venue aktif. $created slot baru dibentuk." : 'Pemeriksaan lulus. Venue siap diajukan untuk aktivasi.');
        return self::SUCCESS;
    }
}
