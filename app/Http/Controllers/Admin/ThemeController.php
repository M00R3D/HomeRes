<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    public function index(Request $request)
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }

        // Provide the single global theme (id=1) to the admin form
        $theme = Theme::find(1);

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

        // Preserve existing meta sub-keys (layouts, payment, price, notif, topbar) when saving
        $existing = Theme::find(1);
        if ($existing && is_array($existing->meta ?? null)) {
            $preserveKeys = ['layouts', 'payment', 'price', 'notif'];
            foreach ($preserveKeys as $key) {
                if (! isset($payload['meta'][$key]) && isset($existing->meta[$key])) {
                    $payload['meta'][$key] = $existing->meta[$key];
                }
            }
        }

        // Single global theme stored at id=1. Always write/update id=1.
        $payload['name'] = $payload['name'] ?? 'custom';
        Theme::updateOrCreate(['id' => 1], $payload);

        return redirect()->route('admin.themes')->with('success', 'Personalización guardada');
    }

    public function applyPreset(Request $request)
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }

        $data = $request->validate([
            'preset' => 'required|string|max:120'
        ]);

        $presetName = $data['preset'];
        $preset = Theme::where('name', $presetName)->first();
        if (! $preset) {
            return redirect()->route('admin.themes')->with('success', 'Preset no encontrado');
        }

        $attrs = $preset->toArray();
        // Remove id and timestamps so updateOrCreate will write into id=1
        unset($attrs['id']);
        if (isset($attrs['created_at'])) unset($attrs['created_at']);
        if (isset($attrs['updated_at'])) unset($attrs['updated_at']);

        // Preserve existing meta keys from id=1 when applying preset
        $existing = Theme::find(1);
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
        if (strtolower($presetName) === 'dark') {
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

        Theme::updateOrCreate(['id' => 1], $attrs);

        return redirect()->route('admin.themes')->with('success', 'Preset aplicado: ' . $presetName);
    }
}
