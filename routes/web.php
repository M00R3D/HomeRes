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
    Route::middleware('admin')->group(function () {
        Route::get('/admin/logs',            [LogController::class,  'index'])->name('admin.logs');
        Route::get('/admin/themes',          [ThemeController::class,'index'])->name('admin.themes');
        Route::post('/admin/themes/save',    [ThemeController::class,'save'])->name('admin.themes.save');
        Route::post('/admin/themes/apply',   [ThemeController::class,'applyPreset'])->name('admin.themes.apply');
    });

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->names('users');
        Route::post('/users/{id}/toggle-bloqueo', [UserController::class,'toggleBloqueo'])->name('users.toggleBloqueo');
        Route::post('/users/{id}/toggle-ban',     [UserController::class,'toggleBan'])->name('users.toggleBan');
    });

    // ── Properties (non-admin: browse/reserve only) ────────────────────────
    Route::get('/propiedades',                   [PropiedadController::class,'index'])->name('propiedades.index');

    // Simple info endpoint used by some front-end widgets: /propiedades/info?id=123
    Route::get('/propiedades/info',               [PropiedadController::class,'info'])->name('propiedades.info');

    // Static admin routes MUST come before {propiedade} wildcard to avoid being captured
    Route::middleware('admin')->group(function () {
        Route::get('/propiedades/create',            [PropiedadController::class,'create'])->name('propiedades.create');
        Route::post('/propiedades',                  [PropiedadController::class,'store'])->name('propiedades.store');
        Route::get('/propiedades/{propiedade}/edit', [PropiedadController::class,'edit'])->name('propiedades.edit');
        Route::match(['put','patch'], '/propiedades/{propiedade}', [PropiedadController::class,'update'])->name('propiedades.update');
        Route::delete('/propiedades/{propiedade}',   [PropiedadController::class,'destroy'])->name('propiedades.destroy');
    });

    Route::get('/propiedades/{propiedade}',      [PropiedadController::class,'show'])->name('propiedades.show');

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
    // pagos.index and pagos.show are accessible to any authenticated user;
    // the controller enforces ownership for non-admins on show().
    Route::get('/pagos',          [PaymentController::class,'index'])->name('pagos.index');
    Route::get('/pagos/{id}',     [PaymentController::class,'show'])->name('pagos.show');
    Route::middleware('admin')->group(function () {
        Route::post('/pagos',                     [PaymentController::class,'store'])->name('pagos.store');
        Route::get('/pagos/create',               [PaymentController::class,'create'])->name('pagos.create');
        Route::get('/pagos/{id}/edit',            [PaymentController::class,'edit'])->name('pagos.edit');
        Route::match(['put','patch'],'/pagos/{id}',[PaymentController::class,'update'])->name('pagos.update');
        Route::delete('/pagos/{id}',              [PaymentController::class,'destroy'])->name('pagos.destroy');
    });

    // ── Tarjetas (specific routes before resource) ────────────────────────────
    Route::post('/tarjetas/{id}/deposit',    [TarjetaSimuladaController::class,'deposit'])->middleware('admin')->name('tarjetas.deposit');
    Route::post('/tarjetas/{id}/withdraw',   [TarjetaSimuladaController::class,'withdraw'])->middleware('admin')->name('tarjetas.withdraw');
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
    Route::post('/profile/password', [NotificationController::class,'updatePassword'])->name('notifications.updatePassword');

    // ── Homepage ──────────────────────────────────────────────────────────────
    Route::get('/homepage', [HomepageController::class, 'index'])->name('homepage.index');
    Route::middleware('admin')->group(function () {
        Route::post('/homepage',               [HomepageController::class, 'store'])->name('homepage.store');
        Route::get('/homepage/{homepage}',     [HomepageController::class, 'show'])->name('homepage.show');
        Route::match(['put','patch'], '/homepage/{homepage}', [HomepageController::class, 'update'])->name('homepage.update');
        Route::delete('/homepage/{homepage}',  [HomepageController::class, 'destroy'])->name('homepage.destroy');
    });

    // ── Images (admin-only) ──────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::get('/imagenes',          [ImageController::class,'index'])->name('images.index');
        Route::get('/imagenes/dirs',     [ImageController::class,'dirs'])->name('images.dirs');
        Route::post('/imagenes/upload',  [ImageController::class,'upload'])->name('images.upload');
        Route::get('/imagenes/list',     [ImageController::class,'list'])->name('images.list');
        Route::post('/imagenes/mkdir',    [ImageController::class,'mkdir'])->name('images.mkdir');
        Route::delete('/imagenes/file',   [ImageController::class,'deleteFile'])->name('images.deleteFile');
        Route::delete('/imagenes/folder', [ImageController::class,'deleteFolder'])->name('images.deleteFolder');
    });
});

