<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController; // agregado

// Rutas de autenticación (solo para guests)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Logout (solo para usuarios autenticados)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Dashboard (protegido)
Route::get('/dashboard', function () {
    return view('dashboard', ['reservaciones' => []]);
})->middleware('auth')->name('dashboard');

// Usuarios (CRUD) - protege con auth
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->names('users');
});

// Root: redirige a dashboard si está autenticado, si no a login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});
