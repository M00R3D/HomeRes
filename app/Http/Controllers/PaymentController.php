<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'reservacion_id' => 'required|exists:reservaciones,id',
            'monto' => 'required|numeric',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
            'estado' => 'required|in:pendiente,pagado,cancelado',
            'fecha_pago' => 'nullable|date',
        ]);

        $data = $request->only(['reservacion_id','monto','metodo_pago','estado','fecha_pago']);
        $payment = Payment::create($data);

        return response()->json($payment, 201);
    }

    public function show($id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['message' => 'Pago no encontrado'], 404);
        return response()->json($payment);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['message' => 'Pago no encontrado'], 404);

        $request->validate([
            'reservacion_id' => 'sometimes|exists:reservaciones,id',
            'monto' => 'sometimes|numeric',
            'metodo_pago' => 'sometimes|in:efectivo,tarjeta,transferencia',
            'estado' => 'sometimes|in:pendiente,pagado,cancelado',
            'fecha_pago' => 'nullable|date',
        ]);

        $payment->update($request->only(['reservacion_id','monto','metodo_pago','estado','fecha_pago']));

        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['message' => 'Pago no encontrado'], 404);

        $payment->delete();

        return response()->json(['message' => 'Pago eliminado']);
    }
}
