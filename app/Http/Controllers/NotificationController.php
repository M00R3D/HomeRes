<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(Notification::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_admin' => 'nullable|exists:usuarios,id',
            'id_usuario' => 'nullable|exists:usuarios,id',
            'estado' => 'nullable|in:cerrada,abierta,vista',
            'tipo' => 'nullable|in:info,confirmacion,pago,alerta',
            'descripcion' => 'required|string',
            'fecha_creacion' => 'nullable|date',
            'fecha_visto' => 'nullable|date',
            'ruta' => 'nullable|string',
        ]);

        $notification = Notification::create($request->all());

        return response()->json($notification, 201);
    }

    public function show($id)
    {
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['message' => 'Notificación no encontrada'], 404);
        return response()->json($notification);
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['message' => 'Notificación no encontrada'], 404);

        $request->validate([
            'id_admin' => 'nullable|exists:usuarios,id',
            'id_usuario' => 'nullable|exists:usuarios,id',
            'estado' => 'nullable|in:cerrada,abierta,vista',
            'tipo' => 'nullable|in:info,confirmacion,pago,alerta',
            'descripcion' => 'sometimes|string',
            'fecha_visto' => 'nullable|date',
            'ruta' => 'nullable|string',
        ]);

        $notification->update($request->all());

        return response()->json($notification);
    }

    public function destroy($id)
    {
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['message' => 'Notificación no encontrada'], 404);

        $notification->delete();

        return response()->json(['message' => 'Notificación eliminada']);
    }
}
