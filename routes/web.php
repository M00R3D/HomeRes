<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// rutas de autenticación (Blade)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return view('welcome');
});
