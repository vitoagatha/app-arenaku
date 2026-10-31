<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ManagementViewsTest extends TestCase
{
    private function data(): array
    {
        $user = new User(['name' => 'Akun uji', 'last_name' => 'Pengelola', 'email' => 'test@example.test', 'role' => 'admin']);
        $user->id = 5;
        $this->actingAs($user);
        $this->app['request']->setLaravelSession(session()->driver());
        $lapangan = (object) ['id' => 6, 'nama_lapangan' => 'Tempat uji', 'alamat' => 'Alamat uji', 'deskripsi' => 'Deskripsi uji', 'image' => null];
        $court = (object) ['id' => 3, 'nama_courts' => 'Court uji'];
        $jadwal = (object) ['id' => 21, 'lapangan_id' => 6, 'court_id' => 3, 'court' => $court, 'lapangan' => $lapangan, 'tanggal' => '2026-11-01', 'jam_awal' => '09:00:00', 'jam_akhir' => '10:00:00', 'status' => 'ready'];
        $booking = (object) ['id' => 40, 'nama' => 'Pemesan uji', 'no_whatsapp' => '08123456789', 'durasi' => 1, 'status' => 'pending', 'jadwalLapangan' => $jadwal];
        return compact('user', 'lapangan', 'court', 'booking') + ['lapangans' => collect([$lapangan]), 'courts' => collect([$court]), 'users' => collect([$user]), 'bookings' => collect([$booking]), 'jadwalLapangan' => collect([$jadwal]), 'errors' => new ViewErrorBag()];
    }

    public function test_index_pages_keep_tables_data_and_delete_contracts(): void
    {
        $data = $this->data();
        foreach (['lapangan', 'courts', 'users', 'booking', 'jadwal'] as $area) {
            $view = $this->view('dashboard.' . $area . '.index', $data);
            $view->assertSee('id="dataTable"', false)->assertSee('scope="col"', false)->assertSee('role="region"', false);
            if ($area !== 'jadwal') {
                $view->assertSee('name="_method" value="DELETE"', false)->assertSee('data-confirm=', false);
            } else {
                $view->assertSee(route('jadwal.court', 6), false)->assertSee('Atur jadwal');
            }
        }
        $this->view('dashboard.booking.index', $data)->assertSee('Menunggu')->assertDontSee('Lunas');
    }

    public function test_create_and_edit_views_keep_submission_fields_and_methods(): void
    {
        $data = $this->data();
        foreach (['lapangan' => ['nama_lapangan', 'alamat', 'deskripsi', 'image'], 'courts' => ['nama_courts'], 'users' => ['name', 'last_name', 'email', 'password', 'no_handphone', 'role'], 'booking' => ['nama', 'no_whatsapp', 'durasi']] as $area => $fields) {
            foreach (($area === 'booking' ? ['edit'] : ['create', 'edit']) as $mode) {
                $view = $this->view('dashboard.' . $area . '.' . $mode, $data);
                $view->assertSee('method="POST"', false)->assertSee('name="_token"', false);
                if ($mode === 'edit') $view->assertSee('name="_method" value="PUT"', false);
                foreach ($fields as $field) $view->assertSee('name="' . $field . '"', false);
                if ($area === 'lapangan') $view->assertSee('enctype="multipart/form-data"', false);
            }
        }
    }

    public function test_old_input_and_field_errors_survive_validation(): void
    {
        $data = $this->data();
        session()->flashInput(['nama_lapangan' => 'Nama dikoreksi', 'password' => 'never-render-this', 'role' => 'penjaga']);
        $data['errors']->put('default', new MessageBag(['nama_lapangan' => 'Nama wajib diisi.']));
        $this->view('dashboard.lapangan.edit', $data)->assertSee('value="Nama dikoreksi"', false)->assertSee('aria-describedby="nama_lapangan-error"', false)->assertSee('Nama wajib diisi.');
        $this->view('dashboard.users.edit', $data)->assertSee('value="penjaga" selected', false)->assertDontSee('never-render-this');
    }

    public function test_schedule_modal_ids_are_unique_and_status_values_unchanged(): void
    {
        $data = $this->data();
        $second = clone $data['jadwalLapangan']->first();
        $second->id = 22;
        $second->status = 'closed';
        $data['jadwalLapangan']->push($second);
        $view = $this->view('dashboard.jadwal.jadwal', $data);
        $view->assertSee('Tersedia')->assertSee('Ditutup')->assertSee('value="ready"', false)->assertSee('value="booking"', false)->assertSee('value="closed"', false)
            ->assertSee('id="editModal21-court_id"', false)->assertSee('id="editModal22-court_id"', false)
            ->assertSee(route('jadwal.store'), false)->assertSee(route('jadwal.update', 21), false)->assertSee(route('jadwal.destroy', 22), false);
        preg_match_all('/\bid="([^"]+)"/', (string) $view, $matches);
        $this->assertSame(count($matches[1]), count(array_unique($matches[1])), 'Duplicate HTML IDs');
    }

    public function test_empty_indexes_do_not_invent_records_or_break_table_structure(): void
    {
        $data = $this->data();
        foreach (['lapangans', 'courts', 'users', 'bookings', 'jadwalLapangan'] as $key) $data[$key] = collect();
        foreach (['lapangan', 'courts', 'users', 'booking', 'jadwal'] as $area) {
            $this->view('dashboard.' . $area . '.index', $data)->assertSee('id="dataTable"', false)->assertDontSee('data-confirm=', false);
        }
        $this->view('dashboard.jadwal.jadwal', $data)->assertSee('Belum ada court yang dapat dipilih');
    }
}
