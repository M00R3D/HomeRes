<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController; // agregado
use App\Http\Controllers\PropiedadController; // agregado
use App\Http\Controllers\NotificationController; // agregado
use App\Http\Controllers\ReservationController; // agregado
use App\Http\Controllers\ImageController; // agregado
use App\Models\Reservation;
use App\Models\User;
use App\Models\Propiedad;

// Rutas de autenticación (solo para guests)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Logout (solo para usuarios autenticados)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Dashboard (protegido) — ahora carga datos reales para la vista dashboard
Route::get('/dashboard', function () {
    $reservaciones = Reservation::with(['user','cabin'])->orderByDesc('created_at')->get();
    $usuarios = User::all();
    $cabanas = Propiedad::all();
    $currentUser = auth()->user();

    return view('dashboard', compact('reservaciones','usuarios','cabanas','currentUser'));
})->middleware('auth')->name('dashboard');

// Usuarios (CRUD) - protege con auth
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->names('users');

    // Rutas RESTful para propiedades
    Route::resource('propiedades', PropiedadController::class)->names('propiedades');

    // Rutas RESTful para notificaciones (index -> carga la vista)
    Route::resource('notificaciones', NotificationController::class)->names('notificaciones');
});

Route::get('/reservaciones', [ReservationController::class, 'index'])->middleware('auth')->name('reservaciones.index');
// Root: redirige a dashboard si está autenticado, si no a login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['web','auth'])->group(function(){
    Route::get('/imagenes', [ImageController::class, 'index'])->name('images.index');
    Route::post('/imagenes/upload', [ImageController::class, 'upload'])->name('images.upload');
    Route::get('/imagenes/list', [ImageController::class, 'list'])->name('images.list');
});
