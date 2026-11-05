<?php

namespace Tests\Database;

use App\Models\User;
use App\Services\CourtOperations;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardHourlyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_forms_lifecycle_filters_and_access(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-03-01 08:00', 'Asia/Jakarta'));
        try {
            $admin = $this->user('admin'); $owner = $this->user('penjaga'); $other = $this->user('penjaga');
            $venue = DB::table('lapangan')->insertGetId(['nama_lapangan'=>'Dashboard fixture','alamat'=>'Test','user_id'=>$owner->id,'regular_opens_at'=>'08:00','regular_closes_at'=>'22:00','hourly_booking_enabled'=>true]);
            $court = DB::table('venue_courts')->insertGetId(['lapangan_id'=>$venue,'name'=>'Court A','is_active'=>true]);
            app(CourtOperations::class)->generate($venue);
            $url = route('jadwal.operations.save',$venue);
            $this->get(route('jadwal.operations',$venue))->assertRedirect('/login');
            $this->actingAs($other)->get(route('jadwal.operations',$venue))->assertNotFound();
            $ownerPage = $this->actingAs($owner)->get(route('jadwal.operations',$venue));
            $ownerPage->assertOk()->assertSee('Catat booking')->assertDontSee('Simpan jam reguler')->assertSee('Tutup slot');
            $this->preview('caretaker', $ownerPage->getContent());
            $this->post($url,['action'=>'configure','open'=>'08:00','close'=>'20:00','days'=>14,'reason'=>'Test'])->assertForbidden();
            $adminPage = $this->actingAs($admin)->get(route('jadwal.operations',$venue));
            $adminPage->assertOk()->assertSee('Simpan jam reguler')->assertSee('Tambahkan court terverifikasi');
            $this->preview('admin', $adminPage->getContent());
            $this->post($url,['action'=>'configure','open'=>'08:00','close'=>'22:00','days'=>14,'reason'=>'Test','contact'=>'+628000000000'])->assertRedirect();
            $this->assertSame('+628000000000', DB::table('lapangan')->find($venue)->booking_contact);
            $candidate = DB::table('venue_courts')->insertGetId(['lapangan_id'=>$venue,'name'=>'Candidate B','is_active'=>false]);
            $this->post($url,['action'=>'court','existing_id'=>$candidate,'name'=>'Candidate B','verified'=>1])->assertRedirect();
            $this->assertSame(1,DB::table('venue_courts')->find($candidate)->is_active);
            $this->actingAs($owner);
            $this->post($url,['action'=>'court','name'=>'Unapproved court','verified'=>1])->assertForbidden();
            $this->post($url,['action'=>'reserve','date'=>'2030-03-01','key'=>str_repeat('test',6)])->assertSessionHasErrors(['slot_ids','nama','phone','source']);
            $ids = DB::table('schedule_slots')->where('venue_court_id',$court)->whereIn('starts_at',['2030-03-01 10:00:00','2030-03-01 11:00:00'])->pluck('id')->all();
            $payload=['action'=>'reserve','date'=>'2030-03-01','slot_ids'=>$ids,'nama'=>'Dashboard customer','phone'=>'0800000000','source'=>'whatsapp','key'=>str_repeat('dashboard',4)];
            $this->post($url,$payload)->assertRedirect();
            $booking = DB::table('konfirmasi_bookings')->where('nama','Dashboard customer')->first();
            $this->assertNotNull($booking);
            $this->assertSame(2,$booking->durasi);
            $detailPage = $this->get(route('booking.detail',$booking->id));
            $detailPage->assertOk()->assertSee('Konfirmasi booking #'.$booking->id)->assertSee('09:00:00 WIB')->assertSee('Slot yang dicatat');
            $this->preview('detail', $detailPage->getContent());
            $listPage = $this->get(route('booking.index',['status'=>'pending','date'=>'2030-03-01']));
            $listPage->assertOk()->assertSee('Dashboard customer')->assertSee('Menunggu konfirmasi: 1');
            $this->preview('bookings', $listPage->getContent());
            $this->get(route('booking.index',['search'=>'no-match-fixture']))->assertOk()->assertSee('Tidak ada booking yang sesuai filter.')->assertDontSee('Dashboard customer');
            $this->get(route('booking.index',['search'=>'Dashboard customer','sort'=>'name','direction'=>'asc']))->assertOk()->assertSee('Dashboard customer');
            $this->getJson(route('booking.index',['sort'=>'untrusted_column']))->assertUnprocessable()->assertJsonValidationErrors('sort');
            $this->getJson(route('booking.index'))->assertOk()->assertJson(['pending'=>1])->assertJsonMissing(['nama'=>'Dashboard customer']);
            $this->getJson(route('jadwal.operations',['venue'=>$venue,'date'=>'2030-03-01']))->assertOk()->assertJsonStructure(['slots','now']);
            $this->actingAs($other)->get(route('booking.detail',$booking->id))->assertNotFound();
            $this->post(route('booking.transition',$booking->id),['action'=>'confirm'])->assertNotFound();
            $this->actingAs($owner)->post(route('booking.transition',$booking->id),['action'=>'confirm'])->assertRedirect(route('booking.detail',$booking->id));
            $this->assertSame('confirmed',DB::table('konfirmasi_bookings')->find($booking->id)->status);
            $this->put(route('booking.update',$booking->id),['nama'=>'Changed','no_whatsapp'=>'0800000000','durasi'=>9])->assertNotFound();
            $this->delete(route('booking.destroy',$booking->id))->assertNotFound();
            $this->post(route('booking.transition',$booking->id),['action'=>'cancel'])->assertSessionHasErrors('reason');
            $this->post(route('booking.transition',$booking->id),['action'=>'cancel','reason'=>'Customer changed plans'])->assertRedirect();
            $this->assertSame('canceled',DB::table('konfirmasi_bookings')->find($booking->id)->status);
            $this->get(route('booking.detail',$booking->id))->assertOk()->assertSee('Customer changed plans')->assertDontSee('Konfirmasi booking #');
            $this->post($url,['action'=>'close','date'=>'2030-03-01','court_id'=>$court,'open'=>'13:00','close'=>'14:00','reason'=>'Maintenance'])->assertRedirect();
            $closure=DB::table('court_closures')->where('venue_court_id',$court)->first();
            $this->get(route('jadwal.operations',['venue'=>$venue,'date'=>'2030-03-01']))->assertOk()->assertSee('Maintenance');
            $this->post($url,['action'=>'reopen','closure_id'=>$closure->id,'reason'=>'Complete'])->assertRedirect();
            $this->post($url,['action'=>'exception','date'=>'2030-03-02','closed'=>1,'reason'=>'Holiday'])->assertRedirect();
            $this->assertDatabaseHas('operating_exceptions',['lapangan_id'=>$venue,'date'=>'2030-03-02','is_closed'=>1]);

            $this->post($url,['action'=>'reserve','date'=>'2030-03-01','slot_ids'=>$ids,'nama'=>'Expiry customer','phone'=>'0800000000','source'=>'walk_in','key'=>str_repeat('expiry',5)])->assertRedirect();
            $exp = DB::table('konfirmasi_bookings')->where('nama','Expiry customer')->first();
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2030-03-01 09:00','Asia/Jakarta'));
            $this->get(route('booking.detail',$exp->id))->assertOk()->assertSee('Kedaluwarsa')->assertDontSee('Konfirmasi booking #');
            $this->assertSame('expired', DB::table('konfirmasi_bookings')->find($exp->id)->status);
            for($i=0;$i<21;$i++) {
                DB::table('konfirmasi_bookings')->insert(['is_legacy'=>false,'venue_court_id'=>$court,'source'=>'walk_in','nama'=>'Pagination fixture '.$i,'no_whatsapp'=>'0800000000','durasi'=>1,'status'=>'canceled','starts_at'=>'2030-03-01 15:00:00','ends_at'=>'2030-03-01 16:00:00']);
            }
            $this->get(route('booking.index',['search'=>'Pagination fixture','sort'=>'name','direction'=>'asc','page'=>2]))->assertOk()->assertViewHas('rows',fn($rows)=>$rows->total()===21 && $rows->currentPage()===2 && $rows->count()===1)->assertSee('search=Pagination',false);
        } finally { CarbonImmutable::setTestNow(); }
    }

    private function user(string $role): User
    {
        return User::create(['name'=>'Dashboard','last_name'=>'Fixture','email'=>bin2hex(random_bytes(8)).'@example.test','password'=>'test-only','role'=>$role]);
    }

    private function preview(string $name, string $html): void
    {
        if (getenv('ARENAKU_EXPORT_PREVIEWS') !== '1') { return; }
        // Static, synthetic view previews only: all writes/navigation disabled, no session secrets exported.
        $html = str_replace('http://localhost', 'http://app_arenaku.test', $html);
        $html = preg_replace('/(<form[^>]*action=")[^"]*/', '$1#', $html);
        $html = preg_replace('/(<input[^>]*name="_token"[^>]*value=")[^"]*/', '$1', $html);
        $html = preg_replace('/(<meta name="csrf-token" content=")[^"]*/', '$1', $html);
        $html = str_replace('</head>', '<script>document.addEventListener("submit",e=>{e.preventDefault();e.stopImmediatePropagation();},true);</script></head>', $html);
        $directory = base_path('tests/.runtime/previews');
        if (!is_dir($directory)) { mkdir($directory,0700,true); }
        file_put_contents($directory.'/'.$name.'.html', $html);
    }
}
