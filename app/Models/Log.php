<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\LogCreatedNotification;

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
    ];

    /**
     * Helper to create log entries easily.
     * Usage: \App\Models\Log::entry('pago', 'Pago realizado', auth()->id(), 'reservacion', $id);
     */
    public static function entry(string $tipo, string $mensaje, $usuarioId = null, $referenciaTipo = null, $referenciaId = null, $link = null)
    {
        $log = self::create([
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'usuario_id' => $usuarioId ?? (auth()->id() ?? null),
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
        ]);

        try {
            // Prepare recipients: referenced user + all admins
            $recipients = collect();
            if ($log->usuario_id) {
                $u = User::find($log->usuario_id);
                if ($u) $recipients->push($u);
            }
            $admins = User::where('rol', 'admin')->get();
            $recipients = $recipients->merge($admins)->unique('id')->filter();

            if ($recipients->isNotEmpty()) {
                // Normalize link: prefer storing path-relative links when they point to this app
                $storedLink = null;
                if (! empty($link)) {
                    if (strpos($link, '/') === 0) {
                        // already a relative path
                        $storedLink = $link;
                    } elseif (preg_match('#^https?://#i', $link)) {
                        try {
                            $parsed = parse_url($link);
                            $appUrl = config('app.url') ?? env('APP_URL');
                            $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
                            $linkHost = $parsed['host'] ?? null;
                            if ($appHost && $linkHost && strcasecmp($appHost, $linkHost) === 0) {
                                $storedLink = ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '') . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
                            } else {
                                // external host — keep absolute
                                $storedLink = $link;
                            }
                        } catch (\Throwable $e) {
                            $storedLink = $link;
                        }
                    } else {
                        // not a full URL and not starting with / — store as-is
                        $storedLink = $link;
                    }
                }

                Notification::send($recipients, new LogCreatedNotification([
                    'tipo' => $log->tipo,
                    'mensaje' => $log->mensaje,
                    'usuario_id' => $log->usuario_id,
                    'referencia_tipo' => $log->referencia_tipo,
                    'referencia_id' => $log->referencia_id,
                    'link' => $storedLink,
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
}
