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
            'usuario_id' => 'nullable|exists:usuarios,id',
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'estado' => 'nullable|in:cerrada,abierta,vista',
            'tipo' => 'nullable|in:info,confirmacion,pago,alerta,mantenimiento',
            'descripcion' => 'required|string|max:500',
            'fecha_creacion' => 'nullable|date',
            'fecha_visto' => 'nullable|date',
        ]);

        $notification = Notification::create($request->only([
            'usuario_id','reservacion_id','propiedad_id','estado','tipo','descripcion','fecha_creacion','fecha_visto'
        ]));

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
            'usuario_id' => 'nullable|exists:usuarios,id',
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'estado' => 'nullable|in:cerrada,abierta,vista',
            'tipo' => 'nullable|in:info,confirmacion,pago,alerta,mantenimiento',
            'descripcion' => 'sometimes|string|max:500',
            'fecha_visto' => 'nullable|date',
        ]);

        $notification->update($request->only([
            'usuario_id','reservacion_id','propiedad_id','estado','tipo','descripcion','fecha_creacion','fecha_visto'
        ]));

        return response()->json($notification);
    }

    public function destroy($id)
    {
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['message' => 'Notificación no encontrada'], 404);

        $notification->delete();

        return response()->json(['message' => 'Notificación eliminada']);
    }

    public function markAsVisto($id)
    {
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['message' => 'Notificación no encontrada'], 404);

        $notification->estado = 'vista';
        $notification->fecha_visto = now();
        $notification->save();

        return response()->json($notification);
    }
}
