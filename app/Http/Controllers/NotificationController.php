<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

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
        return response()->json(['unread_count' => $count]);
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
        return view('profile.notifications_preferences', ['prefs' => $prefs]);
    }

    public function savePreferences(Request $request)
    {
        $user = Auth::user();
        $data = [
            'channel_email' => $request->has('channel_email'),
            'channel_inapp' => $request->has('channel_inapp'),
            'receive_push' => $request->has('receive_push'),
            'categories' => $request->input('categories') ? json_encode($request->input('categories')) : null,
        ];

        DB::table('notification_preferences')->updateOrInsert(['user_id' => $user->id], $data);

        return redirect()->route('notifications.preferences')->with('success', 'Preferencias guardadas');
    }
}
