<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginViewTest extends TestCase
{
    public function test_login_uses_courtside_and_preserves_authentication_form(): void
    {
        $this->view('auth.login', ['errors' => new ViewErrorBag()])
            ->assertSee('arenaku-login.css')->assertSee('arenaku-dashboard.css')
            ->assertSee('logo_arenaku.jpg')->assertSee('Masuk pengelola')
            ->assertSee(route('login'), false)->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)->assertSee('name="email"', false)->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false)->assertSee('for="email"', false)
            ->assertSee(route('password.request'), false)->assertDontSee('bg-login-image');
    }

    public function test_login_keeps_email_and_validation_but_never_refills_password(): void
    {
        $this->app['request']->setLaravelSession(session()->driver());
        session()->flashInput(['email' => 'test@example.test', 'password' => 'never-render-password']);
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => 'Email atau kata sandi tidak cocok.']));
        $this->view('auth.login', compact('errors'))->assertSee('test@example.test')
            ->assertSee('Email atau kata sandi tidak cocok.')->assertSee('aria-describedby="email-error"', false)
            ->assertSee('data-login-errors')->assertDontSee('never-render-password');
    }
}
