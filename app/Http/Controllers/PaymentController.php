<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\TarjetaSimulada;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $q = Payment::query()->with(['reservation','tarjeta.assignedUser']);
        $payments = $q->orderByDesc('id')->paginate(15);

        if ($request->wantsJson()) return response()->json($payments);

        $reservaciones = Reservation::orderByDesc('id')->get();
        $tarjetas = TarjetaSimulada::orderByDesc('id')->get();

        return view('pagos.index', compact('payments','reservaciones','tarjetas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'tarjeta_id' => 'nullable|exists:tarjetas_simuladas,id',
            'monto' => 'required|numeric',
            'metodo_pago' => 'required|string|max:50',
            'estado' => 'required|in:pendiente,pagado,cancelado',
            'fecha_pago' => 'nullable|date',
        ]);

        $data = $request->only(['reservacion_id','tarjeta_id','monto','metodo_pago','estado','fecha_pago']);
        $payment = Payment::create($data);

        if ($request->wantsJson()) return response()->json($payment, 201);
        return redirect()->route('pagos.index')->with('success','Pago creado');
    }

    public function show(Request $request, $id)
    {
        $p = Payment::with(['reservation','tarjeta'])->find($id);
        if (!$p) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        if ($request->wantsJson()) return response()->json($p);
        return view('pagos.show', ['payment' => $p]);
    }

    public function update(Request $request, $id)
    {
        $p = Payment::find($id);
        if (!$p) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);

        $request->validate([
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'tarjeta_id' => 'nullable|exists:tarjetas_simuladas,id',
            'monto' => 'sometimes|numeric',
            'metodo_pago' => 'sometimes|string|max:50',
            'estado' => 'sometimes|in:pendiente,pagado,cancelado',
            'fecha_pago' => 'nullable|date',
        ]);

        $p->update($request->only(['reservacion_id','tarjeta_id','monto','metodo_pago','estado','fecha_pago']));
        if ($request->wantsJson()) return response()->json($p);
        return redirect()->route('pagos.index')->with('success','Pago actualizado');
    }

    public function destroy(Request $request, $id)
    {
        $p = Payment::find($id);
        if (!$p) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        $p->delete();
        if ($request->wantsJson()) return response()->json(['message'=>'Eliminado']);
        return redirect()->route('pagos.index')->with('success','Pago eliminado');
    }
}
