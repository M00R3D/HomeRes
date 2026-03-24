<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Web page: list notifications (paginated)
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = DatabaseNotification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id);

        if ($request->filled('filter') && $request->filter === 'unread') {
            $query->whereNull('read_at');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        $perPage = (int) $request->get('per_page', 20);
        $notifs = $query->orderByDesc('created_at')->paginate($perPage);

        return view('notifications.index', ['notifications' => $notifs]);
    }

    // Return unread count JSON
    public function count(Request $request)
    {
        $user = Auth::user();
        $count = DatabaseNotification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
        // include current user's notification preferences so frontend can decide what to show
        $prefs = DB::table('notification_preferences')->where('user_id', $user->id)->first();
        $channel_inapp = $prefs ? (bool) ($prefs->channel_inapp ?? false) : true;
        $receive_push = $prefs ? (bool) ($prefs->receive_push ?? false) : true;
        return response()->json(['unread_count' => $count, 'prefs' => ['channel_inapp' => $channel_inapp, 'receive_push' => $receive_push]]);
    }

    // Dropdown: latest N notifications as JSON
    public function dropdown(Request $request)
    {
        $user = Auth::user();
        $limit = min(50, (int) $request->get('limit', 10));
        $items = DatabaseNotification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($n){
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'link' => $n->data['link'] ?? ($n->data['url'] ?? ($n->link ?? null)),
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at->toDateTimeString(),
                ];
            });

        return response()->json(['notifications' => $items]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        $notif = DatabaseNotification::where('id', $id)
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        if (is_null($notif->read_at)) {
            $notif->markAsRead();
            $this->audit($user->id, 'mark_read', 'notification', $notif->id, $request);
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        DatabaseNotification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->audit($user->id, 'mark_all_read', 'notification', null, $request);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        $notif = DatabaseNotification::where('id', $id)
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        $notif->delete();
        $this->audit($user->id, 'delete', 'notification', $id, $request);

        return response()->json(['ok' => true]);
    }

    // Show notification detail page
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        $notif = DatabaseNotification::where('id', $id)
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        // mark as read
        if (is_null($notif->read_at)) $notif->markAsRead();

        // extract link if any
        $link = $notif->data['link'] ?? ($notif->data['url'] ?? ($notif->link ?? null));

        return view('notifications.show', ['notification' => $notif, 'link' => $link]);
    }

    protected function audit($userId, $action, $targetType = null, $targetId = null, Request $request)
    {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    // Simple preferences form & save
    public function preferencesForm(Request $request)
    {
        $user = Auth::user();
        $prefs = DB::table('notification_preferences')->where('user_id', $user->id)->first();
        // load propiedades for admin UI
        $propiedades = [];
        try{
            $propiedades = \App\Models\Propiedad::orderBy('nombre')->get();
        }catch(\Throwable $e){ }
        return view('profile.notifications_preferences', ['prefs' => $prefs, 'propiedades' => $propiedades, 'currentUser' => $user]);
    }

    public function savePreferences(Request $request)
    {
        $user = Auth::user();
        $categories = null;
        if($request->has('propiedades')){
            $categories = json_encode(array_values((array)$request->input('propiedades')));
        } elseif ($request->input('categories')){
            $categories = is_array($request->input('categories')) ? json_encode($request->input('categories')) : $request->input('categories');
        }

        $data = [
            // email option removed from UI; keep stored false by default
            'channel_email' => false,
            'channel_inapp' => $request->has('channel_inapp'),
            'receive_push' => $request->has('receive_push'),
            'categories' => $categories,
        ];

        DB::table('notification_preferences')->updateOrInsert(['user_id' => $user->id], $data);

        // If admin, allow updating basic user fields
        try{
            if(($user->rol ?? '') === 'admin'){
                $update = [];
                if($request->filled('user_nombre')) $update['nombre'] = $request->input('user_nombre');
                if($request->filled('user_apellido')) $update['apellido'] = $request->input('user_apellido');
                if($request->filled('user_email')) $update['email'] = $request->input('user_email');
                if(!empty($update)){
                    \App\Models\User::where('id',$user->id)->update($update);
                }
            }
        }catch(\Throwable $e){ }

        return redirect()->route('notifications.preferences')->with('success', 'Preferencias guardadas');
    }

    /**
     * Update current user's password (requires current password for non-admin users).
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (! $user) abort(403);

        $data = $request->validate([
            'current_password' => 'nullable|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // Non-admin must provide current password
        if (($user->rol ?? '') !== 'admin') {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                return redirect()->route('notifications.preferences')->withErrors(['current_password' => 'Contraseña actual inválida']);
            }
        }

        try {
            $user->password = Hash::make($data['new_password']);
            $user->save();
        } catch (\Throwable $e) {
            return redirect()->route('notifications.preferences')->withErrors(['new_password' => 'No se pudo actualizar la contraseña']);
        }

        return redirect()->route('notifications.preferences')->with('success', 'Contraseña actualizada');
    }
}
