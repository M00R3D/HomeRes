<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $q = Reservation::query();

        if ($request->filled('usuario_id')) $q->where('usuario_id', $request->usuario_id);
        if ($request->filled('cabana_id')) $q->where('cabana_id', $request->cabana_id);
        if ($request->filled('estado')) $q->where('estado', $request->estado);

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id',
            'cabana_id' => 'nullable|exists:cabanas,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'num_personas' => 'required|integer|min:1',
            'total' => 'required|numeric',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'nota' => 'nullable|string|max:500',
        ]);

        $r = Reservation::create($request->only([
            'usuario_id','cabana_id','check_in','check_out','num_personas','total','estado','nota'
        ]));

        return response()->json($r, 201);
    }

    public function show($id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);
        return response()->json($r);
    }

    public function update(Request $request, $id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);

        $request->validate([
            'usuario_id' => 'sometimes|exists:usuarios,id',
            'cabana_id' => 'sometimes|exists:cabanas,id',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after_or_equal:check_in',
            'num_personas' => 'sometimes|integer|min:1',
            'total' => 'sometimes|numeric',
            'estado' => 'sometimes|in:pendiente,confirmada,cancelada,completada',
            'nota' => 'nullable|string|max:500',
        ]);

        $r->update($request->only([
            'usuario_id','cabana_id','check_in','check_out','num_personas','total','estado','nota'
        ]));

        return response()->json($r);
    }

    public function destroy($id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);
        $r->delete();
        return response()->json(['message' => 'Reservación eliminada']);
    }

    // Endpoint para cambiar sólo el estado (enum)
    public function changeEstado(Request $request, $id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);

        $request->validate([
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $r->estado = $request->estado;
        $r->save();

        return response()->json($r);
    }
}