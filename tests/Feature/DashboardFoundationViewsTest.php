<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DashboardFoundationViewsTest extends TestCase
{
    private function asRole(string $role): void
    {
        $user = new User(['name' => 'Nama uji', 'last_name' => 'Akun', 'email' => 'test@example.test', 'role' => $role]);
        $user->id = 900;
        $this->actingAs($user);
        $this->app['request']->setLaravelSession(session()->driver());
    }

    public function test_admin_has_management_navigation_and_real_counts(): void
    {
        $this->asRole('admin');
        $this->app['request']->setRouteResolver(fn () => app('router')->getRoutes()->getByName('home'));
        $this->view('home', ['widget' => ['lapangan' => 12, 'users' => 23], 'errors' => new ViewErrorBag()])
            ->assertSee('Ringkasan pengelolaan')->assertSee('Lapangan terdaftar')->assertSee('Pengguna terdaftar')
            ->assertSee('<dd>12</dd>', false)->assertSee('<dd>23</dd>', false)
            ->assertSee(route('users.index'), false)->assertSee(route('courts.index'), false)
            ->assertSee('aria-current="page"', false)->assertSee('dataTables.bootstrap4.min.js')
            ->assertDontSee('assets/vendor/bootstrap/js/bootstrap.bundle.min.js');
    }

    public function test_caretaker_sees_operational_shortcuts_without_admin_menus(): void
    {
        $this->asRole('penjaga');
        $this->view('home', ['widget' => ['lapangan' => 0, 'users' => 23], 'errors' => new ViewErrorBag()])
            ->assertSee('Tugas operasional')->assertSee('Lapangan yang ditugaskan')
            ->assertSee('Belum ada lapangan yang ditugaskan')->assertSee('Penjaga')
            ->assertSee(route('jadwal.index'), false)->assertSee(route('booking.index'), false)
            ->assertDontSee(route('users.index'), false)->assertDontSee(route('courts.index'), false)
            ->assertDontSee('Pengguna terdaftar')->assertDontSee('<dd>23</dd>', false);
    }

    public function test_profile_keeps_update_contract_old_input_and_correct_role(): void
    {
        $this->asRole('penjaga');
        session()->flashInput(['name' => 'Nama diperbarui', 'no_handphone' => '08123456789', 'new_password' => 'never-render-this']);
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => 'Email wajib diisi.']));
        $this->view('profile', compact('errors'))
            ->assertSee('Penjaga')->assertDontSee('Administrator')
            ->assertSee(route('profile.update'), false)->assertSee('method="POST"', false)
            ->assertSee('name="_method" value="PUT"', false)->assertSee('name="_token"', false)
            ->assertSee('value="Nama diperbarui"', false)->assertSee('type="tel"', false)
            ->assertSee('email-error')->assertSee('Email wajib diisi.')
            ->assertSee('name="password_confirmation"', false)->assertDontSee('never-render-this');
    }
}
