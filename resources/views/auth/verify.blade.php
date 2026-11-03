@extends('layouts.login')
@section('title', 'Verifikasi email | Arenaku')
@section('main-content')
<div class="ak-login-panel">
@include('components.auth.visual')
<section class="ak-login-form-section" aria-labelledby="verify-title">
<h1 id="verify-title">Verifikasi alamat email</h1>
@if (session('resent'))
<div class="akd-note" role="status">
{{ __('A fresh verification link has been sent to your email address.') }}
</div>
@endif

{{ __('Before proceeding, please check your email for a verification link.') }}
{{ __('If you did not receive the email') }}, <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>.
</section>
</div>
@endsection
