<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TarjetaSimuladaController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\Admin\ThemeController;

// Root redirect
Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.post');
    Route::post('/register',[AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// All authenticated routes in a single group
Route::middleware('auth')->group(function () {

    // ── Dashboard ────────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Admin panel ──────────────────────────────────────────────────────────
    Route::get('/admin/logs',            [LogController::class,  'index'])->name('admin.logs');
    Route::get('/admin/themes',          [ThemeController::class,'index'])->name('admin.themes');
    Route::post('/admin/themes/save',    [ThemeController::class,'save'])->name('admin.themes.save');
    Route::post('/admin/themes/apply',   [ThemeController::class,'applyPreset'])->name('admin.themes.apply');

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::resource('users', UserController::class)->names('users');
    Route::post('/users/{id}/toggle-bloqueo', [UserController::class,'toggleBloqueo'])->name('users.toggleBloqueo');
    Route::post('/users/{id}/toggle-ban',     [UserController::class,'toggleBan'])->name('users.toggleBan');

    // ── Properties ───────────────────────────────────────────────────────────
    Route::resource('propiedades', PropiedadController::class)->names('propiedades');

    // ── Reservations ─────────────────────────────────────────────────────────
    Route::get('/propiedades/{id}/reservar',       [ReservationController::class,'createForPropiedad'])->name('reservaciones.create_for_propiedad');
    Route::get('/propiedades/{id}/reserved-dates', [ReservationController::class,'reservedDates'])->name('reservaciones.reserved_dates');

    Route::get('/reservaciones',                   [ReservationController::class,'index'])->name('reservaciones.index');
    Route::post('/reservaciones',                  [ReservationController::class,'store'])->name('reservaciones.store');
    Route::get('/reservaciones/{id}',              [ReservationController::class,'showView'])->name('reservaciones.show');
    Route::get('/reservaciones/{id}/edit',         [ReservationController::class,'editView'])->name('reservaciones.edit');
    Route::match(['put','patch'],'/reservaciones/{id}', [ReservationController::class,'update'])->name('reservaciones.update');
    Route::delete('/reservaciones/{id}',           [ReservationController::class,'destroy'])->name('reservaciones.destroy');
    Route::post('/reservaciones/{id}/changeEstado',[ReservationController::class,'changeEstado'])->name('reservaciones.changeEstado');

    // ── Payments (specific routes before resource to avoid conflicts) ─────────
    Route::post('/pagos/procesar',       [PaymentController::class,'procesarPago'])->name('pagos.procesar');
    Route::get('/mis-pagos',             [PaymentController::class,'myPayments'])->name('pagos.mine');
    Route::get('/mis-codigos',           [PaymentController::class,'myCodes'])->name('pagos.codes');
    Route::get('/mis-codigos/{id}',      [PaymentController::class,'showCode'])->name('pagos.codes.show');
    Route::get('/reservaciones/{id}/pagar', [PaymentController::class,'form'])->name('pagos.form');
    Route::resource('pagos', PaymentController::class)->names('pagos');

    // ── Tarjetas (specific routes before resource) ────────────────────────────
    Route::post('/tarjetas/{id}/deposit',    [TarjetaSimuladaController::class,'deposit'])->name('tarjetas.deposit');
    Route::post('/tarjetas/{id}/withdraw',   [TarjetaSimuladaController::class,'withdraw'])->name('tarjetas.withdraw');
    Route::post('/tarjetas/{id}/assign',     [TarjetaSimuladaController::class,'assign'])->name('tarjetas.assign');
    Route::get('/tarjetas/check',            [TarjetaSimuladaController::class,'check'])->name('tarjetas.check');
    Route::post('/tarjetas/create-random',   [TarjetaSimuladaController::class,'createRandom'])->name('tarjetas.create_random');
    Route::resource('tarjetas', TarjetaSimuladaController::class)->names('tarjetas');

    // ── Notifications (specific routes before catch-all {id}) ─────────────────
    Route::resource('notificaciones', NotificationController::class)->names('notificaciones');
    Route::get('/notifications',                  [NotificationController::class,'index'])->name('notifications.index');
    Route::get('/notifications/count',            [NotificationController::class,'count'])->name('notifications.count');
    Route::get('/notifications/dropdown',         [NotificationController::class,'dropdown'])->name('notifications.dropdown');
    Route::post('/notifications/mark-all-read',   [NotificationController::class,'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{id}/read',       [NotificationController::class,'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{id}',             [NotificationController::class,'show'])->name('notifications.show');
    Route::delete('/notifications/{id}',          [NotificationController::class,'destroy'])->name('notifications.destroy');
    Route::get('/profile/notifications/preferences',  [NotificationController::class,'preferencesForm'])->name('notifications.preferences');
    Route::post('/profile/notifications/preferences', [NotificationController::class,'savePreferences'])->name('notifications.preferences.save');

    // ── Homepage ──────────────────────────────────────────────────────────────
    Route::resource('homepage', HomepageController::class)
        ->only(['index','store','update','show','destroy'])
        ->names('homepage');

    // ── Images ────────────────────────────────────────────────────────────────
    Route::get('/imagenes',        [ImageController::class,'index'])->name('images.index');
    Route::get('/imagenes/dirs',   [ImageController::class,'dirs'])->name('images.dirs');
    Route::post('/imagenes/upload',[ImageController::class,'upload'])->name('images.upload');
    Route::get('/imagenes/list',   [ImageController::class,'list'])->name('images.list');
});

