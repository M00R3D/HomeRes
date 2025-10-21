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
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:card,transfer,cash',
            'currency' => 'required|string|size:3',
            'payment_type' => 'required|in:deposit,final_payment',
            'payment_status' => 'required|in:pending,paid,failed',
            'transaction_details' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        $payment = Payment::create($request->all());

        return response()->json($payment, 201);
    }

    public function show($id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['message' => 'Payment not found'], 404);
        return response()->json($payment);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['message' => 'Payment not found'], 404);

        $request->validate([
            'reservation_id' => 'sometimes|exists:reservations,id',
            'amount' => 'sometimes|numeric',
            'payment_method' => 'sometimes|in:card,transfer,cash',
            'currency' => 'sometimes|string|size:3',
            'payment_type' => 'sometimes|in:deposit,final_payment',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'transaction_details' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        $payment->update($request->all());

        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) return response()->json(['message' => 'Payment not found'], 404);

        $payment->delete();

        return response()->json(['message' => 'Payment deleted']);
    }
}
