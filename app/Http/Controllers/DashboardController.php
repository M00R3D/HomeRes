<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Propiedad;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();
        $isAdmin     = ($currentUser->rol ?? '') === 'admin';

        $reservaciones = Reservation::with(['user', 'propiedad'])
            ->when(! $isAdmin, fn ($q) => $q->where('usuario_id', $currentUser?->id))
            ->orderByDesc('created_at')
            ->get();

        $usuarios    = User::all();
        $propiedades = Propiedad::all();

        $hasCodigo    = Schema::hasColumn('pagos', 'codigo_qr');
        $hasUsuarioId = Schema::hasColumn('pagos', 'usuario_id');

        $dashboardPayments = Payment::with(['reservation.user', 'reservation.propiedad'])
            ->where('estado', 'pagado')
            ->when($hasCodigo, fn ($q) => $q->whereNotNull('codigo_qr'))
            ->when(! $isAdmin, function ($q) use ($currentUser, $hasUsuarioId) {
                $q->where(function ($inner) use ($currentUser, $hasUsuarioId) {
                    if ($hasUsuarioId) {
                        $inner->where('usuario_id', $currentUser?->id)
                              ->orWhereHas('reservation', fn ($r) => $r->where('usuario_id', $currentUser?->id));
                    } else {
                        $inner->whereHas('reservation', fn ($r) => $r->where('usuario_id', $currentUser?->id));
                    }
                });
            })
            ->orderByDesc('id')
            ->take(8)
            ->get();

        return view('dashboard', compact('reservaciones', 'usuarios', 'propiedades', 'currentUser', 'dashboardPayments'));
    }
}
