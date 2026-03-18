<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\Admin\StyleController;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Propiedad;
use App\Models\Payment;
use App\Http\Controllers\LogController;
use App\Http\Controllers\Admin\ThemeController;
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->group(function(){
    Route::get('/admin/themes', [ThemeController::class, 'index'])->name('admin.themes');
    Route::post('/admin/themes/save', [ThemeController::class, 'save'])->name('admin.themes.save');
    Route::post('/admin/themes/apply', [ThemeController::class, 'applyPreset'])->name('admin.themes.apply');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/admin/logs', [LogController::class, 'index'])->middleware('auth')->name('admin.logs');
Route::get('/dashboard', function () {
    $currentUser = auth()->user();

    $reservacionesQ = Reservation::with(['user','propiedad'])->orderByDesc('created_at');
    if (! $currentUser || (($currentUser->rol ?? '') !== 'admin')) {
        $reservacionesQ->where('usuario_id', $currentUser?->id);
    }
    $reservaciones = $reservacionesQ->get();

    $usuarios = User::all();
    $propiedades = Propiedad::all();

    $paymentsQ = Payment::with(['reservation.user','reservation.propiedad'])
        ->where('estado', 'pagado')
        ->whereNotNull('codigo_qr')
        ->orderByDesc('id');
    if (! $currentUser || (($currentUser->rol ?? '') !== 'admin')) {
        $paymentsQ->where(function ($q) use ($currentUser) {
            $q->where('usuario_id', $currentUser?->id)
              ->orWhereHas('reservation', function ($rq) use ($currentUser) {
                  $rq->where('usuario_id', $currentUser?->id);
              });
        });
    }
    $dashboardPayments = $paymentsQ->take(8)->get();

    return view('dashboard', compact('reservaciones','usuarios','propiedades','currentUser','dashboardPayments'));
})->middleware('auth')->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->names('users');
    Route::post('users/{id}/toggle-bloqueo', [UserController::class, 'toggleBloqueo'])->name('users.toggleBloqueo');
    Route::resource('propiedades', PropiedadController::class)->names('propiedades');
    Route::get('propiedades/{id}', [PropiedadController::class, 'show'])->name('propiedades.show');
    Route::get('propiedades/{id}/edit', [PropiedadController::class, 'edit'])->name('propiedades.edit');
    Route::get('propiedades/create', [PropiedadController::class, 'create'])->name('propiedades.create');
    Route::resource('notificaciones', NotificationController::class)->names('notificaciones');
    Route::resource('pagos', \App\Http\Controllers\PaymentController::class)->names('pagos');
    Route::get('/mis-pagos', [\App\Http\Controllers\PaymentController::class, 'myPayments'])->name('pagos.mine');
    Route::get('/mis-codigos', [\App\Http\Controllers\PaymentController::class, 'myCodes'])->name('pagos.codes');
    Route::get('/mis-codigos/{id}', [\App\Http\Controllers\PaymentController::class, 'showCode'])->name('pagos.codes.show');
    Route::post('pagos/procesar', [\App\Http\Controllers\PaymentController::class, 'procesarPago'])->name('pagos.procesar');
    Route::get('/reservaciones/{id}/pagar', [\App\Http\Controllers\PaymentController::class, 'form'])->name('pagos.form');
    Route::post('tarjetas/{id}/deposit', [\App\Http\Controllers\TarjetaSimuladaController::class,'deposit'])->name('tarjetas.deposit');
    Route::post('tarjetas/{id}/withdraw', [\App\Http\Controllers\TarjetaSimuladaController::class,'withdraw'])->name('tarjetas.withdraw');
    Route::post('tarjetas/{id}/assign', [\App\Http\Controllers\TarjetaSimuladaController::class,'assign'])->name('tarjetas.assign');
    Route::get('tarjetas/check', [\App\Http\Controllers\TarjetaSimuladaController::class,'check'])->name('tarjetas.check');
    Route::post('tarjetas/create-random', [\App\Http\Controllers\TarjetaSimuladaController::class,'createRandom'])->name('tarjetas.create_random');
    Route::resource('tarjetas', \App\Http\Controllers\TarjetaSimuladaController::class)->names('tarjetas');
    Route::resource('homepage', \App\Http\Controllers\HomepageController::class)->only(['index','store','update','show','destroy'])->names('homepage');
    
    // Admin styles panel
    Route::get('/admin/styles', [StyleController::class, 'index'])->name('admin.styles.index');
    Route::post('/admin/styles', [StyleController::class, 'save'])->name('admin.styles.save');
});

Route::get('/reservaciones', [ReservationController::class, 'index'])->middleware('auth')->name('reservaciones.index');
Route::get('/reservaciones/{id}/edit', [ReservationController::class, 'editView'])->middleware('auth')->name('reservaciones.edit');
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

// Notifications routes (web
Route::middleware('auth')->group(function() {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'count'])->name('notifications.count');
    Route::get('/notifications/dropdown', [\App\Http\Controllers\NotificationController::class, 'dropdown'])->name('notifications.dropdown');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/profile/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferencesForm'])->name('notifications.preferences');
    Route::post('/profile/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'savePreferences'])->name('notifications.preferences.save');
});
