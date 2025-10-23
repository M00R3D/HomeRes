<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(Notification::all());
        }

        // cargar usuario relacionado para mostrar nombre en la vista
        $notificaciones = Notification::with('user')->get();
        $usuarios = User::all();

        return view('notificaciones.index', compact('notificaciones', 'usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id',
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'estado' => 'nullable|in:cerrada,abierta,vista',
            'tipo' => 'nullable|in:info,confirmacion,pago,alerta,mantenimiento,prueba,aprobada,rechazada,otra',
            'descripcion' => 'required|string|max:500',
            'fecha_creacion' => 'nullable|date',
            'fecha_visto' => 'nullable|date',
        ]);

        $data = $request->only([
            'usuario_id','reservacion_id','propiedad_id','estado','tipo','descripcion','fecha_creacion','fecha_visto'
        ]);

        // normalizar nombre de campo si el formulario usa id_usuario
        if (isset($data['id_usuario'])) {
            $data['usuario_id'] = $data['id_usuario'];
            unset($data['id_usuario']);
        }

        $notification = Notification::create($data);

        if ($request->wantsJson()) {
            return response()->json($notification, 201);
        }

        return redirect('/notificaciones')->with('success', 'Notificación creada.');
    }

    public function show(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            if ($request->wantsJson()) return response()->json(['message' => 'Notificación no encontrada'], 404);
            abort(404);
        }

        if ($request->wantsJson()) return response()->json($notification);
        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            if ($request->wantsJson()) return response()->json(['message' => 'Notificación no encontrada'], 404);
            abort(404);
        }

        $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id',
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'estado' => 'nullable|in:cerrada,abierta,vista',
            'tipo' => 'nullable|in:info,confirmacion,pago,alerta,mantenimiento,prueba,aprobada,rechazada,otra',
            'descripcion' => 'sometimes|string|max:500',
            'fecha_visto' => 'nullable|date',
        ]);

        $data = $request->only([
            'usuario_id','reservacion_id','propiedad_id','estado','tipo','descripcion','fecha_creacion','fecha_visto'
        ]);

        if (isset($data['id_usuario'])) {
            $data['usuario_id'] = $data['id_usuario'];
            unset($data['id_usuario']);
        }

        $notification->update($data);

        if ($request->wantsJson()) {
            return response()->json($notification);
        }

        return redirect('/notificaciones')->with('success', 'Notificación actualizada.');
    }

    public function destroy(Request $request, $id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            if ($request->wantsJson()) return response()->json(['message' => 'Notificación no encontrada'], 404);
            abort(404);
        }

        $notification->delete();

        if ($request->wantsJson()) return response()->json(['message' => 'Notificación eliminada']);
        return redirect('/notificaciones')->with('success', 'Notificación eliminada.');
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
