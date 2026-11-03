<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AuthPolishViewsTest extends TestCase
{
    public function test_registration_retains_contract_and_accessible_error_recovery(): void
    {
        $this->app['request']->setLaravelSession(session()->driver());
        session()->flashInput(['name' => 'Fixture', 'password' => 'do-not-render-password']);
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => 'Email tidak valid.']));
        $view = $this->view('auth.register', ['errors' => $errors]);
        $view->assertSee(route('register'), false)->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)->assertSee('Fixture')
            ->assertSee('aria-describedby="email-error"', false)->assertSee('Email tidak valid.')
            ->assertDontSee('do-not-render-password')->assertSee('arenaku-tokens.css');
        foreach (['name', 'last_name', 'email', 'password', 'password_confirmation'] as $field) {
            $view->assertSee('name="'.$field.'"', false)->assertSee('for="'.$field.'"', false);
        }
    }

    public function test_confirmation_preserves_password_submission_and_current_password_autofill(): void
    {
        $this->app['request']->setLaravelSession(session()->driver());
        $this->view('auth.passwords.confirm', ['errors' => new ViewErrorBag()])
            ->assertSee(route('password.confirm'), false)->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false)->assertSee('for="password"', false)
            ->assertSee('data-password-toggle', false)->assertSee('arenaku-login.css');
    }
    public function test_table_assets_remain_on_management_lists(): void
    {
        $user = new \App\Models\User(['name' => 'Fixture', 'last_name' => 'User', 'role' => 'admin']);
        $this->actingAs($user);
        $route = new \Illuminate\Routing\Route('GET', '/booking', fn () => null);
        $route->name('booking.index');
        request()->setRouteResolver(fn () => $route);
        $this->view('layouts.admin')->assertSee('dataTables.bootstrap4.min.js')
            ->assertSee('dataTables.bootstrap4.min.css')->assertSee('js/demo/datatables-demo.js');
    }

}
