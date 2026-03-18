<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    private const PRESET_SLOTS = [
        1 => 'Light',
        2 => 'Dark',
        3 => 'Sakura',
        4 => 'Abstract',
    ];

    private const CUSTOM_THEME_ID = 5;
    private const CUSTOM_THEME_NAME = 'custom';

    public function index(Request $request)
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }

        $this->normalizeThemeSlots();

        // Provide the editable global theme (id=5) to the admin form
        $theme = Theme::find(self::CUSTOM_THEME_ID) ?? Theme::find(1);

        // Scan blade views for button variants (classes that use `btn') and for button/link/input texts
        $variants = ['default'];
        try {
            $files = File::allFiles(resource_path('views'));
            foreach ($files as $f) {
                $contents = File::get($f->getRealPath());
                if (! $contents) continue;
                if (preg_match_all('/class\s*=\s*"([^"]+)"/i', $contents, $matches)) {
                    foreach ($matches[1] as $cls) {
                        $tokens = preg_split('/\s+/', trim($cls));
                        $hasBtn = in_array('btn', $tokens) || preg_grep('/^btn(-|$)/', $tokens);
                        if ($hasBtn) {
                            foreach ($tokens as $t) {
                                if ($t === 'btn') continue;
                                if (preg_match('/^btn-(.+)$/', $t, $m)) {
                                    $variants[] = $m[1];
                                } else {
                                    // token alongside btn (e.g. "btn view") treat as variant
                                    if ($t !== 'btn' && strpos($t, 'btn') === false) {
                                        $variants[] = $t;
                                    }
                                }
                            }
                        }
                    }
                }
                // Find <button>texts</button> and <a class="...btn...">texts</a>
                if (preg_match_all('/<button[^>]*>(.*?)<\\/button>/is', $contents, $btns)) {
                    foreach ($btns[1] as $t) {
                        $text = trim(strip_tags($t));
                        if ($text !== '') {
                            $key = Str::slug(mb_strtolower($text), '-');
                            $variants[] = $key;
                        }
                    }
                }
                if (preg_match_all('/<a[^>]*class\s*=\s*"[^"]*btn[^"]*"[^>]*>(.*?)<\\/a>/is', $contents, $links)) {
                    foreach ($links[1] as $t) {
                        $text = trim(strip_tags($t));
                        if ($text !== '') {
                            $key = Str::slug(mb_strtolower($text), '-');
                            $variants[] = $key;
                        }
                    }
                }
                // Find input buttons: <input type="submit" value="...">
                if (preg_match_all('/<input[^>]+type\s*=\s*"(?:submit|button)"[^>]*>/i', $contents, $inputs)) {
                    foreach ($inputs[0] as $inp) {
                        if (preg_match('/value\s*=\s*"([^"]+)"/i', $inp, $m)) {
                            $text = trim($m[1]);
                            if ($text !== '') {
                                $key = Str::slug(mb_strtolower($text), '-');
                                $variants[] = $key;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore scan failures
        }

        $buttonVariants = array_values(array_unique(array_filter($variants)));

        $layoutSections = [
            'dashboard' => 'Dashboard',
            'reservations' => 'Reservaciones',
            'properties' => 'Propiedades',
            'notifications' => 'Notificaciones',
            'cards' => 'Tarjetas',
        ];

        $layoutVariants = [
            'card' => 'Carta',
            'elegant' => 'Elegante',
            'hyperminimal' => 'Hyperminimalista',
        ];

        return view('admin.themes', [
            'theme' => $theme,
            'buttonVariants' => $buttonVariants,
            'layoutSections' => $layoutSections,
            'layoutVariants' => $layoutVariants,
        ]);
    }

    public function save(Request $request)
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'btn_primary' => 'nullable|string|max:32',
            'btn_alt' => 'nullable|string|max:32',
            'bg' => 'nullable|string|max:32',
            'sidebar_bg' => 'nullable|string|max:32',
            'sidebar_text' => 'nullable|string|max:32',
            'gradient_start' => 'nullable|string|max:32',
            'gradient_end' => 'nullable|string|max:32',
            'gradient_angle' => 'nullable|integer',
            'animated_gradient' => 'sometimes|boolean',
            'animation_speed' => 'nullable|numeric',
            'font_size' => 'nullable|integer',
            'button_variants' => 'nullable|array',
            'bg_gradient_start' => 'nullable|string|max:32',
            'bg_gradient_end' => 'nullable|string|max:32',
            'bg_gradient_angle' => 'nullable|integer',
            'bg_animated' => 'sometimes|boolean',
            'sidebar_gradient_start' => 'nullable|string|max:32',
            'sidebar_gradient_end' => 'nullable|string|max:32',
            'sidebar_gradient_angle' => 'nullable|integer',
            'sidebar_animated' => 'sometimes|boolean',
            'hover_animation' => 'nullable|string|max:60',
            'hover_animation_duration' => 'nullable|numeric',
            'float_animation' => 'nullable|string|max:60',
            'float_animation_duration' => 'nullable|numeric',
            'meta' => 'nullable|array',
        ]);

        $payload = $data;
        if (! empty($data['button_variants']) && is_array($data['button_variants'])) {
            $payload['button_variants'] = $data['button_variants'];
        }

        $this->normalizeThemeSlots();

        // Preserve existing meta sub-keys (layouts, payment, price, notif, topbar) when saving
        $existing = Theme::find(self::CUSTOM_THEME_ID);
        if ($existing && is_array($existing->meta ?? null)) {
            $preserveKeys = ['layouts', 'payment', 'price', 'notif'];
            foreach ($preserveKeys as $key) {
                if (! isset($payload['meta'][$key]) && isset($existing->meta[$key])) {
                    $payload['meta'][$key] = $existing->meta[$key];
                }
            }
        }

        // Only editable global theme: id=5, always named custom.
        $payload['name'] = self::CUSTOM_THEME_NAME;
        Theme::updateOrCreate(['id' => self::CUSTOM_THEME_ID], $payload);

        return redirect()->route('admin.themes')->with('success', 'Personalización guardada');
    }

    public function applyPreset(Request $request)
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }

        $this->normalizeThemeSlots();

        $data = $request->validate([
            'preset_id' => 'nullable|integer|in:1,2,3,4',
            'preset' => 'nullable|string|max:120',
        ]);

        $presetId = (int) ($data['preset_id'] ?? 0);
        if (! $presetId && ! empty($data['preset'])) {
            $incoming = strtolower((string) $data['preset']);
            foreach (self::PRESET_SLOTS as $id => $name) {
                if ($incoming === strtolower($name)) {
                    $presetId = $id;
                    break;
                }
            }
        }

        $preset = $presetId ? Theme::find($presetId) : null;
        if (! $preset) {
            return redirect()->route('admin.themes')->with('success', 'Preset no encontrado');
        }

        $presetName = self::PRESET_SLOTS[$presetId] ?? ($preset->name ?? 'Preset');

        $attrs = $preset->toArray();
        // Remove id and timestamps so updateOrCreate will write into id=5 (custom)
        unset($attrs['id']);
        if (isset($attrs['created_at'])) unset($attrs['created_at']);
        if (isset($attrs['updated_at'])) unset($attrs['updated_at']);

        // Preserve existing meta keys from id=5 when applying preset
        $existing = Theme::find(self::CUSTOM_THEME_ID);
        if ($existing && is_array($existing->meta ?? null)) {
            $preserveKeys = ['layouts', 'payment', 'price', 'notif'];
            foreach ($preserveKeys as $key) {
                if (! isset($attrs['meta'][$key]) && isset($existing->meta[$key])) {
                    if (! isset($attrs['meta'])) $attrs['meta'] = [];
                    $attrs['meta'][$key] = $existing->meta[$key];
                }
            }
        }

        // Ensure dark preset has visible light text by default
        if ((int) $presetId === 2 || strtolower($presetName) === 'dark') {
            $attrs['sidebar_text'] = $attrs['sidebar_text'] ?? '#f8fafc';
            if (! isset($attrs['meta']) || ! is_array($attrs['meta'])) $attrs['meta'] = [];
            $attrs['meta']['topbar'] = $attrs['meta']['topbar'] ?? [];
            $attrs['meta']['topbar']['text'] = $attrs['meta']['topbar']['text'] ?? '#f8fafc';
            // ensure button variants have light text
            if (! empty($attrs['button_variants']) && is_array($attrs['button_variants'])) {
                foreach ($attrs['button_variants'] as $k => $bv) {
                    if (empty($bv['color'])) {
                        $attrs['button_variants'][$k]['color'] = '#ffffff';
                    }
                }
            }
        }

        $attrs['name'] = self::CUSTOM_THEME_NAME;
        Theme::updateOrCreate(['id' => self::CUSTOM_THEME_ID], $attrs);

        return redirect()->route('admin.themes')->with('success', 'Preset aplicado: ' . $presetName);
    }

    private function normalizeThemeSlots(): void
    {
        foreach (self::PRESET_SLOTS as $id => $name) {
            $slotTheme = Theme::find($id);
            if ($slotTheme && strtolower((string) $slotTheme->name) === strtolower($name)) {
                continue;
            }

            $byName = Theme::whereRaw('LOWER(name) = ?', [strtolower($name)])->orderBy('id')->first();
            $attrs = $byName ? $byName->toArray() : $this->defaultPresetPayload($name);

            unset($attrs['id'], $attrs['created_at'], $attrs['updated_at']);
            $attrs['name'] = $name;
            Theme::updateOrCreate(['id' => $id], $attrs);
        }

        $custom = Theme::find(self::CUSTOM_THEME_ID);
        if (! $custom) {
            $seed = Theme::find(1);
            $attrs = $seed ? $seed->toArray() : $this->defaultPresetPayload('Light');
            unset($attrs['id'], $attrs['created_at'], $attrs['updated_at']);
            $attrs['name'] = self::CUSTOM_THEME_NAME;
            Theme::updateOrCreate(['id' => self::CUSTOM_THEME_ID], $attrs);
            return;
        }

        if (strtolower((string) $custom->name) !== self::CUSTOM_THEME_NAME) {
            $custom->name = self::CUSTOM_THEME_NAME;
            $custom->save();
        }
    }

    private function defaultPresetPayload(string $name): array
    {
        $key = strtolower($name);
        if ($key === 'dark') {
            return [
                'name' => 'Dark',
                'btn_primary' => '#7c3aed',
                'btn_alt' => '#fb923c',
                'bg' => '#0b1220',
                'sidebar_bg' => '#071029',
                'sidebar_text' => '#f8fafc',
                'gradient_start' => '#7c3aed',
                'gradient_end' => '#fb923c',
                'gradient_angle' => 90,
                'animated_gradient' => false,
                'animation_speed' => 6,
                'font_size' => 16,
                'meta' => ['topbar' => ['bg' => '#071029', 'text' => '#f8fafc']],
            ];
        }

        if ($key === 'sakura') {
            return [
                'name' => 'Sakura',
                'btn_primary' => '#f9a8d4',
                'btn_alt' => '#ffd7b5',
                'bg' => '#fff7fb',
                'sidebar_bg' => '#fffaf6',
                'sidebar_text' => '#0f172a',
                'gradient_start' => '#f9a8d4',
                'gradient_end' => '#ffd7b5',
                'gradient_angle' => 90,
                'animated_gradient' => false,
                'animation_speed' => 6,
                'font_size' => 16,
                'meta' => ['topbar' => ['bg' => '#fff4f7', 'text' => '#0f172a']],
            ];
        }

        if ($key === 'abstract') {
            return [
                'name' => 'Abstract',
                'btn_primary' => '#6d28d9',
                'btn_alt' => '#fb923c',
                'bg' => '#f5f3ff',
                'sidebar_bg' => '#fdf2f8',
                'sidebar_text' => '#0f172a',
                'gradient_start' => '#6d28d9',
                'gradient_end' => '#fb923c',
                'gradient_angle' => 45,
                'animated_gradient' => true,
                'animation_speed' => 8,
                'font_size' => 16,
                'meta' => ['topbar' => ['bg' => '#f5f3ff', 'text' => '#0f172a']],
            ];
        }

        return [
            'name' => 'Light',
            'btn_primary' => '#2563eb',
            'btn_alt' => '#06b6d4',
            'bg' => '#ffffff',
            'sidebar_bg' => '#f8fafc',
            'sidebar_text' => '#0f172a',
            'gradient_start' => '#2563eb',
            'gradient_end' => '#06b6d4',
            'gradient_angle' => 90,
            'animated_gradient' => false,
            'animation_speed' => 6,
            'font_size' => 16,
            'meta' => ['topbar' => ['bg' => '#ffffff', 'text' => '#0f172a']],
        ];
    }
}
