<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CMSController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\LapanganController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/**
 * Route Landing Pages
 */

Route::get('/', [CMSController::class, 'index'])->name('/');
Route::get('cms/search', [CMSController::class, 'index'])->name('cms.index');
Route::post('cms/search', [CMSController::class, 'search'])->name('cms.search');
Route::get('cms/{lapangan}/cek-booking', [CMSController::class, 'cekBooking'])->name('cms.cek-booking');
Route::post('cms/{lapangan}/cek-booking', [CMSController::class, 'checkAvailability'])->name('lapangan.check-availability');
Route::get('cms/{id}/confirm-booking', [CMSController::class, 'confirmBooking'])->name('lapangan.confirm-booking');
Route::post('booking/store', [BookingController::class, 'store'])->name('booking.store');
Route::get('cms/{lapangan}/slot-per-jam', [\App\Http\Controllers\PublicHourlyBookingController::class, 'availability'])->name('booking.hourly.availability');
Route::post('cms/{lapangan}/booking-per-jam', [\App\Http\Controllers\PublicHourlyBookingController::class, 'store'])->name('booking.hourly.store');
Route::get('cms/{lapangan}/booking-per-jam/{key}', [\App\Http\Controllers\PublicHourlyBookingController::class, 'receipt'])->whereUuid('key')->name('booking.hourly.receipt');


/**
 * Route Authentication
 */

Auth::routes();


/**
 * Route Dashboard
 */

Route::get('home', 'HomeController@index')->name('home');

Route::get('profile', 'ProfileController@index')->name('profile');
Route::put('profile', 'ProfileController@update')->name('profile.update');

Route::middleware(['auth', 'can:access-management'])->group(function () {
    Route::get('jadwal/{venue}/operasional', [\App\Http\Controllers\DashboardOperationsController::class, 'operations'])->name('jadwal.operations');
    Route::post('jadwal/{venue}/operasional', [\App\Http\Controllers\DashboardOperationsController::class, 'save'])->name('jadwal.operations.save');
    Route::get('booking/{booking}/detail', [\App\Http\Controllers\DashboardOperationsController::class, 'detail'])->name('booking.detail');
    Route::post('booking/{booking}/status', [\App\Http\Controllers\DashboardOperationsController::class, 'transition'])->name('booking.transition');
    Route::resource('users', UserController::class)->middleware('can:manage-administration');
    Route::resource('lapangan', LapanganController::class);
    Route::resource('courts', CourtController::class)->middleware('can:manage-administration');

    Route::get('jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::get('jadwal/{jadwal}/court', [JadwalController::class, 'jadwal'])->name('jadwal.court');
    Route::post('jadwal/store', [JadwalController::class, 'store'])->name('jadwal.store');
    Route::put('jadwal/{jadwal}', [JadwalController::class, 'update'])->name('jadwal.update');
    Route::delete('jadwal/{jadwal}', [JadwalController::class, 'destroy'])->name('jadwal.destroy');

    Route::get('booking', [BookingController::class, 'index'])->name('booking.index');
    Route::get('booking/{booking}/edit', [BookingController::class, 'edit'])->name('booking.edit');
    Route::put('booking/{booking}', [BookingController::class, 'update'])->name('booking.update');
    Route::delete('booking/{booking}', [BookingController::class, 'destroy'])->name('booking.destroy');
});
