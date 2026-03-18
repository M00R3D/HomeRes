@props([
    'status' => 'default',
    'text'   => null,
    'size'   => 'sm',
])
@php
    $map = [
        'pendiente'     => ['bg' => 'var(--payment-badge-bg-pendiente,#f59e0b)',  'color' => 'var(--payment-badge-text-pendiente,#fff)'],
        'confirmada'    => ['bg' => '#10b981',                                     'color' => '#fff'],
        'cancelada'     => ['bg' => 'var(--payment-badge-bg-fallido,#ef4444)',    'color' => 'var(--payment-badge-text-fallido,#fff)'],
        'completada'    => ['bg' => '#6366f1',                                     'color' => '#fff'],
        'pagado'        => ['bg' => 'var(--payment-badge-bg-pagado,#10b981)',     'color' => 'var(--payment-badge-text-pagado,#fff)'],
        'fallido'       => ['bg' => 'var(--payment-badge-bg-fallido,#ef4444)',    'color' => 'var(--payment-badge-text-fallido,#fff)'],
        'parcial'       => ['bg' => 'var(--payment-badge-bg-parcial,#6366f1)',    'color' => 'var(--payment-badge-text-parcial,#fff)'],
        'disponible'    => ['bg' => '#10b981',                                     'color' => '#fff'],
        'ocupada'       => ['bg' => '#f59e0b',                                     'color' => '#000'],
        'mantenimiento' => ['bg' => '#6366f1',                                     'color' => '#fff'],
        'admin'         => ['bg' => '#6366f1',                                     'color' => '#fff'],
        'cliente'       => ['bg' => '#06b6d4',                                     'color' => '#fff'],
        'vista'         => ['bg' => '#e5e7eb',                                     'color' => '#374151'],
        'no_vista'      => ['bg' => '#bfdbfe',                                     'color' => '#1e3a8a'],
    ];

    $colors  = $map[$status] ?? ['bg' => 'var(--badge-bg,#f3f4f6)', 'color' => 'var(--badge-text,#374151)'];
    $label   = $text ?? $status;
    $sizeStyle = $size === 'md' ? 'padding:5px 12px;font-size:0.85rem;' : '';
@endphp

<span class="ui-badge" style="background:{{ $colors['bg'] }};color:{{ $colors['color'] }};{{ $sizeStyle }}">
    {{ $label }}
</span>
