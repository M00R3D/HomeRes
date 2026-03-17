<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;

class ThemeController extends Controller
{
    public function index(Request $request)
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }

        // Provide the single global theme (id=1) to the admin form
        $theme = Theme::find(1);
        return view('admin.themes', ['theme' => $theme]);
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
            'button_variants' => 'nullable|string'
        ]);

        $payload = $data;
        if (! empty($data['button_variants'])) {
            $payload['button_variants'] = json_decode($data['button_variants'], true) ?: null;
        }

        // Single global theme stored at id=1. Always write/update id=1.
        $payload['name'] = $payload['name'] ?? 'custom';
        Theme::updateOrCreate(['id' => 1], $payload);

        return redirect()->route('admin.themes')->with('success', 'Personalización guardada');
    }
}
