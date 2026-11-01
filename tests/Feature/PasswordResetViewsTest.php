<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PasswordResetViewsTest extends TestCase
{
    public function test_reset_link_form_retains_email_status_and_route(): void
    {
        $this->app['request']->setLaravelSession(session()->driver());
        session()->flashInput(['email' => 'test@example.test']);
        session()->flash('status', 'Tautan reset telah dikirim.');
        $this->view('auth.passwords.email', ['errors' => new ViewErrorBag()])
            ->assertSee('arenaku-login.css')->assertSee('Lupa kata sandi?')
            ->assertSee(route('password.email'), false)->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)->assertSee('test@example.test')
            ->assertSee('Tautan reset telah dikirim.')->assertSee(route('login'), false);
    }

    public function test_reset_form_keeps_token_and_password_field_contracts(): void
    {
        $this->app['request']->setLaravelSession(session()->driver());
        session()->flashInput(['email' => 'corrected@example.test', 'password' => 'never-render-this', 'password_confirmation' => 'never-render-this']);
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['password' => 'Kata sandi tidak cocok.']));
        $this->view('auth.passwords.reset', ['token' => 'view-fixture-token', 'email' => 'original@example.test', 'errors' => $errors])
            ->assertSee(route('password.update'), false)->assertSee('method="POST"', false)
            ->assertSee('name="token" value="view-fixture-token"', false)
            ->assertSee('name="_token"', false)->assertSee('corrected@example.test')
            ->assertSee('name="password_confirmation"', false)->assertSee('autocomplete="new-password"', false)
            ->assertSee('aria-describedby="password-error"', false)->assertSee('Kata sandi tidak cocok.')
            ->assertDontSee('never-render-this');
    }
}
