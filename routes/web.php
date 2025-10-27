<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ImageController;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Propiedad;
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/dashboard', function () {
    $reservaciones = Reservation::with(['user','propiedad'])->orderByDesc('created_at')->get();
    $usuarios = User::all();
    $propiedades = Propiedad::all();
    $currentUser = auth()->user();
    return view('dashboard', compact('reservaciones','usuarios','propiedades','currentUser'));
})->middleware('auth')->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->names('users');
    Route::resource('propiedades', PropiedadController::class)->names('propiedades');
    Route::resource('notificaciones', NotificationController::class)->names('notificaciones');
    Route::resource('pagos', \App\Http\Controllers\PaymentController::class)->names('pagos');
    Route::resource('tarjetas', \App\Http\Controllers\TarjetaSimuladaController::class)->names('tarjetas');
    Route::post('tarjetas/{id}/deposit', [\App\Http\Controllers\TarjetaSimuladaController::class,'deposit'])->name('tarjetas.deposit');
    Route::post('tarjetas/{id}/withdraw', [\App\Http\Controllers\TarjetaSimuladaController::class,'withdraw'])->name('tarjetas.withdraw');
    Route::post('tarjetas/{id}/assign', [\App\Http\Controllers\TarjetaSimuladaController::class,'assign'])->name('tarjetas.assign');
    Route::resource('homepage', \App\Http\Controllers\HomepageController::class)->only(['index','store','update','show','destroy'])->names('homepage');
});

Route::get('/reservaciones', [ReservationController::class, 'index'])->middleware('auth')->name('reservaciones.index');
Route::get('/reservaciones/{id}', [ReservationController::class, 'showView'])->middleware('auth')->name('reservaciones.show');
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});
Route::post('/reservaciones', [ReservationController::class, 'store'])->middleware('auth')->name('reservaciones.store');

Route::middleware(['web','auth'])->group(function(){
    Route::get('/imagenes', [ImageController::class, 'index'])->name('images.index');
    Route::get('/imagenes/dirs', [ImageController::class, 'dirs'])->name('images.dirs'); 
    Route::post('/imagenes/upload', [ImageController::class, 'upload'])->name('images.upload');
    Route::get('/imagenes/list', [ImageController::class, 'list'])->name('images.list');

    Route::post('/reservaciones/{id}/changeEstado', [ReservationController::class, 'changeEstado'])
        ->name('reservaciones.changeEstado');

    Route::match(['put','patch'],'/reservaciones/{id}', [ReservationController::class, 'update'])
        ->name('reservaciones.update');
    Route::delete('/reservaciones/{id}', [ReservationController::class, 'destroy'])
        ->name('reservaciones.destroy');
});
Route::middleware('auth')->get('/propiedades/{id}/reservar', [ReservationController::class, 'createForPropiedad'])
    ->name('reservaciones.create_for_propiedad');
Route::middleware('auth')->get('/propiedades/{id}/reserved-dates', [ReservationController::class, 'reservedDates'])
    ->name('reservaciones.reserved_dates');
