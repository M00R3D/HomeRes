<?php

namespace App\Support;

use Illuminate\Support\Str;

class NotificationPresenter
{
    public static function catalog(): array
    {
        return [
            'info' => [
                'label' => 'Informacion',
                'symbol' => 'i',
                'color' => '#3b82f6',
            ],
            'success' => [
                'label' => 'Exito',
                'symbol' => '✓',
                'color' => '#10b981',
            ],
            'warning' => [
                'label' => 'Advertencia',
                'symbol' => '!',
                'color' => '#f59e0b',
            ],
            'error' => [
                'label' => 'Error',
                'symbol' => '×',
                'color' => '#ef4444',
            ],
            'message' => [
                'label' => 'Mensaje',
                'symbol' => '✉',
                'color' => '#8b5cf6',
            ],
            'alert' => [
                'label' => 'Alerta del sistema',
                'symbol' => '⚠',
                'color' => '#f97316',
            ],
            'log' => [
                'label' => 'Registro',
                'symbol' => '≡',
                'color' => '#64748b',
            ],
        ];
    }

    public static function resolveType($notification): string
    {
        $data = self::extractData($notification);

        foreach (['ui_type', 'notification_type', 'type'] as $key) {
            $normalized = self::normalizeType($data[$key] ?? null);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        $className = self::extractClassName($notification);
        $classBase = class_exists($className) ? class_basename($className) : class_basename((string) $className);
        $classTypeMap = [
            'NewMessageNotification' => 'message',
            'SystemAlertNotification' => 'alert',
            'LogCreatedNotification' => 'log',
        ];

        if (isset($classTypeMap[$classBase])) {
            return $classTypeMap[$classBase];
        }

        $title = Str::lower((string) ($data['title'] ?? ''));
        if (str_contains($title, 'mensaje')) {
            return 'message';
        }
        if (str_contains($title, 'alerta')) {
            return 'alert';
        }
        if (str_contains($title, 'registro')) {
            return 'log';
        }

        return 'info';
    }

    public static function meta($notificationOrType): array
    {
        $catalog = self::catalog();
        $type = is_string($notificationOrType)
            ? (self::normalizeType($notificationOrType) ?? 'info')
            : self::resolveType($notificationOrType);

        return array_merge(['key' => $type], $catalog[$type] ?? $catalog['info']);
    }

    public static function extractLink($notification): ?string
    {
        $data = self::extractData($notification);

        $link = $data['link'] ?? ($data['url'] ?? null);
        if ($link === null && is_object($notification) && isset($notification->link)) {
            $link = $notification->link;
        }

        $link = is_string($link) ? trim($link) : null;

        return $link !== '' ? $link : null;
    }

    public static function allowedResourceTypes($value = null): array
    {
        $catalogKeys = array_keys(self::catalog());

        if ($value === null || $value === '') {
            return $catalogKeys;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $allowed = collect(is_array($value) ? $value : [])
            ->map(fn ($type) => self::normalizeType($type))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $allowed;
    }

    public static function allowsResource($notification, $allowedTypes = null): bool
    {
        $link = self::extractLink($notification);
        if ($link === null) {
            return false;
        }

        $resourceKind = self::resourceKindFromLink($link);
        if ($resourceKind === null || ! in_array($resourceKind, self::allowedResourceKinds(), true)) {
            return false;
        }

        $allowed = self::allowedResourceTypes($allowedTypes);

        return in_array(self::resolveType($notification), $allowed, true);
    }

    public static function present($notification, $allowedTypes = null): array
    {
        $meta = self::meta($notification);
        $link = self::extractLink($notification);
        $resourceKind = self::resourceKindFromLink($link);

        return [
            'type' => $meta['key'],
            'label' => $meta['label'],
            'symbol' => $meta['symbol'],
            'color' => $meta['color'],
            'link' => $link,
            'resource_kind' => $resourceKind,
            'resource_label' => self::resourceLabel($resourceKind),
            'allow_resource' => $link !== null && self::allowsResource($notification, $allowedTypes),
        ];
    }

    public static function allowedResourceKinds(): array
    {
        return ['pago', 'propiedad', 'reservacion', 'codigo', 'tarjeta'];
    }

    private static function extractData($notification): array
    {
        if (is_array($notification)) {
            return is_array($notification['data'] ?? null) ? $notification['data'] : $notification;
        }

        if (is_object($notification) && isset($notification->data) && is_array($notification->data)) {
            return $notification->data;
        }

        return [];
    }

    private static function extractClassName($notification): string
    {
        if (is_array($notification)) {
            return (string) ($notification['type'] ?? '');
        }

        if (is_object($notification) && isset($notification->type)) {
            return (string) $notification->type;
        }

        return '';
    }

    private static function normalizeType($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = Str::of($value)
            ->lower()
            ->ascii()
            ->replace(['-', ' '], '_')
            ->trim()
            ->toString();

        $aliases = [
            'info' => 'info',
            'informacion' => 'info',
            'success' => 'success',
            'exito' => 'success',
            'ok' => 'success',
            'warning' => 'warning',
            'advertencia' => 'warning',
            'error' => 'error',
            'danger' => 'error',
            'message' => 'message',
            'mensaje' => 'message',
            'alert' => 'alert',
            'alerta' => 'alert',
            'log' => 'log',
            'registro' => 'log',
        ];

        return $aliases[$normalized] ?? null;
    }

    private static function resourceKindFromLink(?string $link): ?string
    {
        if (! is_string($link) || trim($link) === '') {
            return null;
        }

        $path = (string) parse_url($link, PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $parts = array_values(array_filter(explode('/', $path), fn ($part) => $part !== ''));
        $first = Str::lower($parts[0] ?? '');
        $second = Str::lower($parts[1] ?? '');

        if ($first === 'pagos' && in_array($second, ['codes', 'code', 'codigo', 'codigos'], true)) {
            return 'codigo';
        }

        $map = [
            'pagos' => 'pago',
            'propiedades' => 'propiedad',
            'reservaciones' => 'reservacion',
            'codigos' => 'codigo',
            'tarjetas' => 'tarjeta',
            'tarjetas_simuladas' => 'tarjeta',
        ];

        return $map[$first] ?? null;
    }

    private static function resourceLabel(?string $resourceKind): string
    {
        $labels = [
            'pago' => 'pago',
            'propiedad' => 'propiedad',
            'reservacion' => 'reservacion',
            'codigo' => 'codigo',
            'tarjeta' => 'tarjeta',
        ];

        return $labels[$resourceKind] ?? 'recurso';
    }
}