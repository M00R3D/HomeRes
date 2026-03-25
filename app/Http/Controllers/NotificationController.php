<?php
namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Support\NotificationPresenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['count', 'dropdown']);
    }

    // Web page: list notifications (paginated)
    public function index(Request $request)
    {
        $user = Auth::user();
        $prefs = NotificationPreference::query()->where('user_id', $user->id)->first();
        $query = DatabaseNotification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id);

        $filter = strtolower((string) $request->get('filter', 'all'));
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        $allNotifications = $query->orderByDesc('created_at')->get();
        $typeCatalog = NotificationPresenter::catalog();

        $resolvedTypeById = [];
        $resolveType = function ($notification) use (&$resolvedTypeById) {
            $id = (string) $notification->id;
            if (! isset($resolvedTypeById[$id])) {
                $resolvedTypeById[$id] = NotificationPresenter::resolveType($notification);
            }

            return $resolvedTypeById[$id];
        };

        $availableTypeKeys = $allNotifications
            ->map(fn ($n) => $resolveType($n))
            ->filter(fn ($type) => isset($typeCatalog[$type]))
            ->unique()
            ->values();

        $selectedType = strtolower((string) $request->get('type', 'all'));
        if ($selectedType !== '' && $selectedType !== 'all') {
            $allNotifications = $allNotifications
                ->filter(fn ($n) => $resolveType($n) === $selectedType)
                ->values();
        }

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $total = $allNotifications->count();
        $results = $allNotifications->slice(($page - 1) * $perPage, $perPage)->values();

        $notifs = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('notifications.index', [
            'notifications' => $notifs,
            'notificationTypeCatalog' => $typeCatalog,
            'availableNotificationTypes' => $availableTypeKeys
                ->mapWithKeys(fn ($type) => [$type => $typeCatalog[$type]['label'] ?? ucfirst($type)])
                ->all(),
            'resourceLinkTypes' => NotificationPresenter::allowedResourceTypes($prefs?->resource_link_types),
        ]);
    }

    // Return unread count JSON
    public function count(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['unread_count' => 0, 'prefs' => ['channel_inapp' => false, 'receive_push' => false]], 200);
        }

        try {
            $cacheKey = 'notif_count_' . (int) $user->id;
            $count = Cache::remember($cacheKey, now()->addSeconds(5), function () use ($user) {
                return DatabaseNotification::where('notifiable_type', get_class($user))
                    ->where('notifiable_id', $user->id)
                    ->whereNull('read_at')
                    ->count();
            });

            // include current user's notification preferences so frontend can decide what to show
            $prefs = DB::table('notification_preferences')->where('user_id', $user->id)->first();
            $channel_inapp = $prefs ? (bool) ($prefs->channel_inapp ?? false) : true;
            $receive_push = $prefs ? (bool) ($prefs->receive_push ?? false) : true;

            return response()->json(['unread_count' => (int) $count, 'prefs' => ['channel_inapp' => $channel_inapp, 'receive_push' => $receive_push]]);
        } catch (\Throwable $e) {
            return response()->json(['unread_count' => 0, 'prefs' => ['channel_inapp' => true, 'receive_push' => true]], 200);
        }
    }

    // Dropdown: latest N notifications as JSON
    public function dropdown(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['notifications' => []], 200);
        }
        $limit = min(50, (int) $request->get('limit', 10));
        try {
            $prefs = NotificationPreference::query()->where('user_id', $user->id)->first();
            $allowedResourceTypes = NotificationPresenter::allowedResourceTypes($prefs?->resource_link_types);
            $items = DatabaseNotification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($n) use ($allowedResourceTypes) {
                    $presentation = NotificationPresenter::present($n, $allowedResourceTypes);

                    return [
                        'id' => $n->id,
                        'type' => $n->type,
                        'data' => $n->data,
                        'link' => $presentation['link'],
                        'resource_kind' => $presentation['resource_kind'],
                        'resource_label' => $presentation['resource_label'],
                        'ui_type' => $presentation['type'],
                        'ui_label' => $presentation['label'],
                        'ui_symbol' => $presentation['symbol'],
                        'ui_color' => $presentation['color'],
                        'allow_resource' => $presentation['allow_resource'],
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at->toDateTimeString(),
                    ];
                });

            return response()->json(['notifications' => $items]);
        } catch (\Throwable $e) {
            return response()->json(['notifications' => []], 200);
        }
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
        $prefs = NotificationPreference::query()->where('user_id', $user->id)->first();
        $notif = DatabaseNotification::where('id', $id)
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        // mark as read
        if (is_null($notif->read_at)) $notif->markAsRead();

        $presentation = NotificationPresenter::present($notif, $prefs?->resource_link_types);

        return view('notifications.show', [
            'notification' => $notif,
            'link' => $presentation['link'],
            'notificationPresentation' => $presentation,
        ]);
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
        $prefs = NotificationPreference::query()->where('user_id', $user->id)->first();
        // load propiedades for admin UI
        $propiedades = [];
        try{
            $propiedades = \App\Models\Propiedad::orderBy('nombre')->get();
        }catch(\Throwable $e){ }
        return view('profile.notifications_preferences', [
            'prefs' => $prefs,
            'propiedades' => $propiedades,
            'currentUser' => $user,
            'notificationTypeCatalog' => NotificationPresenter::catalog(),
            'resourceLinkTypes' => NotificationPresenter::allowedResourceTypes($prefs?->resource_link_types),
        ]);
    }

    public function savePreferences(Request $request)
    {
        $user = Auth::user();
        $scope = (string) $request->input('settings_scope', 'notifications');
        $prefs = NotificationPreference::query()->firstOrNew(['user_id' => $user->id]);
        $categories = null;
        if($request->has('propiedades')){
            $categories = json_encode(array_values((array)$request->input('propiedades')));
        } elseif ($request->input('categories')){
            $categories = is_array($request->input('categories')) ? json_encode($request->input('categories')) : $request->input('categories');
        }

        if ($scope !== 'admin_profile') {
            $prefs->channel_email = false;
            $prefs->channel_inapp = $request->has('channel_inapp');
            $prefs->receive_push = $request->has('receive_push');
            $prefs->resource_link_types = NotificationPresenter::allowedResourceTypes($request->input('resource_link_types', []));
        }

        if ($categories !== null || $request->has('propiedades') || $request->has('categories')) {
            $prefs->categories = $categories;
        }

        $prefs->save();

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
