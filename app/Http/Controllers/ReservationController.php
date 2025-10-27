<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Propiedad;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $q = Reservation::query();

        if ($request->filled('usuario_id')) $q->where('usuario_id', $request->usuario_id);
        if ($request->filled('propiedad_id')) $q->where('propiedad_id', $request->propiedad_id);
        if ($request->filled('estado')) $q->where('estado', $request->estado);

        if ($request->wantsJson()) {
            return response()->json($q->with(['user','propiedad'])->get());
        }

        $reservaciones = $q->with(['user','propiedad'])->orderByDesc('created_at')->get();
        $usuarios = User::all();
        $propiedades = Propiedad::all();
        $currentUser = auth()->user();

        return view('reservaciones.index', compact('reservaciones','propiedades','usuarios','currentUser'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'num_personas' => 'required|integer|min:1',
            'total' => 'required|numeric',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'nota' => 'nullable|string|max:500',
        ]);

        $r = Reservation::create($request->only([
            'usuario_id','propiedad_id','check_in','check_out','num_personas','total','estado','nota'
        ]));

        if ($request->wantsJson()) {
            return response()->json($r, 201);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Reservación creada');
    }

    public function show($id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);
        return response()->json($r);
    }

    public function showView(Request $request, $id)
    {
        $r = Reservation::with(['user','propiedad'])->find($id);
        if (!$r) {
            abort(404);
        }

        if ($request->wantsJson()) {
            return response()->json($r);
        }

        $usuarios = User::all();
        $propiedades = Propiedad::all();
        $currentUser = auth()->user();

        return view('reservaciones.show', compact('r','usuarios','propiedades','currentUser'));
    }

    public function update(Request $request, $id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);

        $request->validate([
            'usuario_id' => 'sometimes|exists:usuarios,id',
            'propiedad_id' => 'sometimes|exists:propiedades,id',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after_or_equal:check_in',
            'num_personas' => 'sometimes|integer|min:1',
            'total' => 'sometimes|numeric',
            'estado' => 'sometimes|in:pendiente,confirmada,cancelada,completada',
            'nota' => 'nullable|string|max:500',
        ]);

        $r->update($request->only([
            'usuario_id','propiedad_id','check_in','check_out','num_personas','total','estado','nota'
        ]));

        if ($request->wantsJson()) {
            return response()->json($r);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Reservación actualizada');
    }

    public function destroy($id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);
        $r->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Reservación eliminada']);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Reservación eliminada');
    }

    public function changeEstado(Request $request, $id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);

        $request->validate([
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $r->estado = $request->estado;
        $r->save();

        if ($request->wantsJson()) {
            return response()->json($r);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Estado actualizado');
    }
}