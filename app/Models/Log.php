<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use App\Notifications\SystemNotification;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'tipo',
        'mensaje',
        'usuario_id',
        'referencia_id',
        'referencia_tipo',
        'accion',
        'modelo',
        'target_id',
        'status',
    ];

    /**
     * Helper to create log entries easily.
     * Usage: \App\Models\Log::entry('pago', 'Pago realizado', auth()->id(), 'reservacion', $id);
     */
    public static function entry(...$args)
    {
        [$payload, $legacy] = self::normalizeEntryArgs($args);

        $status = strtolower((string) ($payload['status'] ?? 'info'));
        $status = in_array($status, ['success', 'error', 'warning', 'info'], true) ? $status : 'info';
        $actorId = $payload['actor_id'] ?? (auth()->id() ?? null);

        $message = (string) ($payload['message'] ?? '');
        if ($legacy) {
            $messageForStorage = $message;
        } else {
            $messageForStorage = '[' . strtoupper($status) . '] ' . $message;
        }

        $createData = [
            'tipo' => (string) ($payload['action'] ?? 'evento'),
            'mensaje' => $messageForStorage,
            'usuario_id' => $actorId,
            'referencia_tipo' => $payload['model'] ?? null,
            'referencia_id' => $payload['target_id'] ?? null,
        ];

        if (self::hasLogColumn('accion')) {
            $createData['accion'] = $payload['action'] ?? null;
        }
        if (self::hasLogColumn('modelo')) {
            $createData['modelo'] = $payload['model'] ?? null;
        }
        if (self::hasLogColumn('target_id')) {
            $createData['target_id'] = $payload['target_id'] ?? null;
        }
        if (self::hasLogColumn('status')) {
            $createData['status'] = $status;
        }

        $duplicate = self::query()
            ->where('tipo', $createData['tipo'])
            ->where('mensaje', $createData['mensaje'])
            ->where('usuario_id', $createData['usuario_id'])
            ->where('referencia_tipo', $createData['referencia_tipo'])
            ->where('referencia_id', $createData['referencia_id'])
            ->where('created_at', '>=', now()->subSeconds(2))
            ->latest('id')
            ->first();

        if ($duplicate) {
            return $duplicate;
        }

        $insertData = [
            'tipo' => $createData['tipo'],
            'mensaje' => $createData['mensaje'],
            'usuario_id' => $createData['usuario_id'],
            'referencia_tipo' => $createData['referencia_tipo'],
            'referencia_id' => $createData['referencia_id'],
        ];

        foreach (['accion', 'modelo', 'target_id', 'status'] as $extra) {
            if (array_key_exists($extra, $createData)) {
                $insertData[$extra] = $createData[$extra];
            }
        }

        $log = self::create($insertData);

        try {
            $recipients = self::resolveRecipients($payload, $log);

            if ($recipients->isNotEmpty()) {
                $storedLink = self::normalizeLink($payload['link'] ?? null);

                Notification::send($recipients, new SystemNotification([
                    'type' => $status,
                    'title' => ucfirst((string) ($payload['action'] ?? 'evento')),
                    'message' => $message,
                    'link' => $storedLink,
                    'related' => [
                        'model' => $payload['model'] ?? null,
                        'id' => $payload['target_id'] ?? null,
                    ],
                    'timestamp' => now()->toDateTimeString(),
                    'meta' => [
                        'actor_id' => $actorId,
                        'log_id' => $log->id,
                    ],
                ]));
            }
        } catch (\Throwable $e) {
            // avoid breaking the flow if notifications fail
        }

        return $log;
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id', 'id');
    }

    private static function normalizeEntryArgs(array $args): array
    {
        $statusValues = ['success', 'error', 'warning', 'info'];
        $isNew = isset($args[4]) && in_array(strtolower((string) $args[4]), $statusValues, true);

        if ($isNew) {
            return [[
                'action' => (string) ($args[0] ?? 'evento'),
                'model' => (string) ($args[1] ?? 'sistema'),
                'actor_id' => $args[2] ?? null,
                'target_id' => $args[3] ?? null,
                'status' => strtolower((string) ($args[4] ?? 'info')),
                'message' => (string) ($args[5] ?? ''),
                'link' => $args[6] ?? null,
                'related_user_ids' => is_array($args[7] ?? null) ? $args[7] : [],
            ], false];
        }

        return [[
            'action' => (string) ($args[0] ?? 'evento'),
            'model' => $args[3] ?? null,
            'actor_id' => $args[2] ?? null,
            'target_id' => $args[4] ?? null,
            'status' => self::inferStatus((string) ($args[1] ?? '')),
            'message' => (string) ($args[1] ?? ''),
            'link' => $args[5] ?? null,
            'related_user_ids' => [],
        ], true];
    }

    private static function inferStatus(string $message): string
    {
        $m = strtolower($message);
        if (str_contains($m, 'error') || str_contains($m, 'fallid') || str_contains($m, 'no autorizado') || str_contains($m, 'bloque')) {
            return 'error';
        }
        if (str_contains($m, 'advert') || str_contains($m, 'warning')) {
            return 'warning';
        }
        return 'success';
    }

    private static function resolveRecipients(array $payload, self $log)
    {
        $recipientIds = collect();

        $admins = \App\Models\User::where('rol', 'admin')->pluck('id');
        $recipientIds = $recipientIds->merge($admins);

        if (!empty($payload['actor_id'])) {
            $recipientIds->push((int) $payload['actor_id']);
        }

        foreach ((array) ($payload['related_user_ids'] ?? []) as $uid) {
            if (!empty($uid)) {
                $recipientIds->push((int) $uid);
            }
        }

        $model = strtolower((string) ($payload['model'] ?? ''));
        $targetId = $payload['target_id'] ?? null;
        $resolved = self::resolveRelatedUsersByEntity($model, $targetId);
        $recipientIds = $recipientIds->merge($resolved);

        $recipientIds = $recipientIds->unique()->filter(fn ($id) => !empty($id));

        return \App\Models\User::whereIn('id', $recipientIds->all())->get();
    }

    private static function resolveRelatedUsersByEntity(string $model, $targetId): array
    {
        if (empty($targetId)) {
            return [];
        }

        try {
            if (in_array($model, ['usuario', 'user', 'usuarios'], true)) {
                return [(int) $targetId];
            }

            if (in_array($model, ['reservacion', 'reservation', 'reservaciones'], true)) {
                $uid = DB::table('reservaciones')->where('id', $targetId)->value('usuario_id');
                return $uid ? [(int) $uid] : [];
            }

            if (in_array($model, ['pago', 'payment', 'pagos'], true)) {
                $row = DB::table('pagos')->where('id', $targetId)->first(['usuario_id', 'reservacion_id']);
                if (!$row) {
                    return [];
                }
                $ids = [];
                if (!empty($row->usuario_id)) {
                    $ids[] = (int) $row->usuario_id;
                }
                if (empty($ids) && !empty($row->reservacion_id)) {
                    $uid = DB::table('reservaciones')->where('id', $row->reservacion_id)->value('usuario_id');
                    if ($uid) {
                        $ids[] = (int) $uid;
                    }
                }
                return $ids;
            }

            if (in_array($model, ['tarjeta', 'card', 'tarjetas'], true)) {
                $uid = DB::table('usuarios')->where('id_tarjeta', $targetId)->value('id');
                return $uid ? [(int) $uid] : [];
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    private static function normalizeLink($link): ?string
    {
        if (empty($link)) {
            return null;
        }

        $link = (string) $link;
        if (strpos($link, '/') === 0) {
            return $link;
        }

        if (!preg_match('#^https?://#i', $link)) {
            return $link;
        }

        try {
            $parsed = parse_url($link);
            $appUrl = config('app.url') ?? env('APP_URL');
            $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
            $linkHost = $parsed['host'] ?? null;
            if ($appHost && $linkHost && strcasecmp($appHost, $linkHost) === 0) {
                return ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '') . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
            }
        } catch (\Throwable $e) {
        }

        return $link;
    }

    private static function hasLogColumn(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        try {
            $cache[$column] = Schema::hasColumn('logs', $column);
        } catch (\Throwable $e) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}
