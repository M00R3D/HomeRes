<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function getEnumValues(string $table, string $column): array
    {
        $row = DB::selectOne('SHOW COLUMNS FROM `' . $table . '` WHERE Field = ?', [$column]);
        if (!$row || empty($row->Type)) return [];
        if (preg_match("/^enum\((.*)\)$/i", $row->Type, $matches)) {
            $vals = str_getcsv($matches[1], ',', "'");
            return array_map(fn($v)=> trim($v, "'\""), $vals);
        }
        return [];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(Notification::all());
        }
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
            'tipo' => 'nullable|string',
            'descripcion' => 'required|string|max:500',
            'fecha_creacion' => 'nullable|date',
            'fecha_visto' => 'nullable|date',
        ]);

        $data = $request->only([
            'usuario_id','reservacion_id','propiedad_id','estado','tipo','descripcion','fecha_creacion','fecha_visto'
        ]);
        if (isset($data['id_usuario'])) {
            $data['usuario_id'] = $data['id_usuario'];
            unset($data['id_usuario']);
        }
        foreach ($data as $k => $v) {
            if (is_string($v) && trim($v) === '') {
                $data[$k] = null;
            }
        }
        $dbEnumTypes = $this->getEnumValues('notificaciones', 'tipo');
        $allowedTypes = !empty($dbEnumTypes)
            ? $dbEnumTypes
            : ['info','confirmacion','pago','alerta','mantenimiento','prueba','aprobada','rechazada','otra'];
        $allowedEstados = ['cerrada','abierta','vista'];
        if (empty($data['estado']) || !in_array($data['estado'], $allowedEstados, true)) {
            $data['estado'] = 'cerrada';
        }
        if (empty($data['tipo']) || !in_array($data['tipo'], $allowedTypes, true)) {
            $data['tipo'] = in_array('info', $allowedTypes, true) ? 'info' : ($allowedTypes[0] ?? 'info');
        }
        if (!empty($data['fecha_visto'])) {
            try {
                $dt = Carbon::parse(str_replace('T', ' ', $data['fecha_visto']));
                $data['fecha_visto'] = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $data['fecha_visto'] = null;
            }
        } else {
            $data['fecha_visto'] = null;
        }
        if (empty($data['fecha_creacion'])) {
            $data['fecha_creacion'] = Carbon::now()->format('Y-m-d H:i:s');
        } else {
            try {
                $data['fecha_creacion'] = Carbon::parse($data['fecha_creacion'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $data['fecha_creacion'] = Carbon::now()->format('Y-m-d H:i:s');
            }
        }
        if (empty($data['usuario_id'])) {
            $data['usuario_id'] = null;
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
            // validar tipo como string aquí; normalizamos contra el ENUM antes de actualizar
            'tipo' => 'nullable|string',
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
        if (array_key_exists('tipo', $data)) {
            $dbEnumTypes = $this->getEnumValues('notificaciones', 'tipo');
            if (!empty($dbEnumTypes)) {
                if (empty($data['tipo']) || !in_array($data['tipo'], $dbEnumTypes, true)) {
                    $data['tipo'] = in_array('info', $dbEnumTypes, true) ? 'info' : $dbEnumTypes[0];
                }
            }
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
