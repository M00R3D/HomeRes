<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Propiedad;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $role        = (string) ($currentUser->rol ?? '');
        $isAdmin     = $role === 'admin';
        $isReception = $role === 'recepcionista';
        $calendarMode = $request->query('panel') === 'calendar';

        if ($calendarMode) {
            $hasUsuarioId = Schema::hasColumn('pagos', 'usuario_id');

            $reservacionesCalendar = Reservation::with(['user', 'propiedad'])
                ->where('estado', '!=', 'cancelada')
                ->when(! $isAdmin && ! $isReception, fn ($q) => $q->where('usuario_id', $currentUser?->id))
                ->orderByDesc('check_in')
                ->get();

            $pagosCalendar = Payment::with(['reservation.user', 'reservation.propiedad'])
                ->when(! $isAdmin && ! $isReception, function ($q) use ($currentUser, $hasUsuarioId) {
                    $q->where(function ($inner) use ($currentUser, $hasUsuarioId) {
                        if ($hasUsuarioId) {
                            $inner->where('usuario_id', $currentUser?->id)
                                  ->orWhereHas('reservation', fn ($r) => $r->where('usuario_id', $currentUser?->id));
                        } else {
                            $inner->whereHas('reservation', fn ($r) => $r->where('usuario_id', $currentUser?->id));
                        }
                    });
                })
                ->orderByDesc('fecha_pago')
                ->orderByDesc('id')
                ->get();

            $propiedadesMap = Propiedad::query()
                ->get(['id', 'nombre', 'tipo', 'ubicacion'])
                ->keyBy('id');

            return view('dashboard_calendar', [
                'currentUser' => $currentUser,
                'isAdmin' => $isAdmin,
                'isReception' => $isReception,
                'reservacionesCalendar' => $reservacionesCalendar,
                'pagosCalendar' => $pagosCalendar,
                'propiedadesMap' => $propiedadesMap,
            ]);
        }

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
