<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Style;

class StyleController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ($currentUser->rol ?? '') !== 'admin') {
            abort(403);
        }

        $style = Style::first();
        if (! $style) {
            $style = Style::create([]);
        }

        return view('admin.styles', ['style' => $style, 'currentUser' => $currentUser]);
    }

    public function save(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ($currentUser->rol ?? '') !== 'admin') {
            abort(403);
        }

        $data = $request->validate([
            'btn_primary' => 'nullable|string|max:32',
            'btn_alt' => 'nullable|string|max:32',
            'bg' => 'nullable|string|max:32',
            'sidebar_bg' => 'nullable|string|max:32',
            'sidebar_text' => 'nullable|string|max:32',
            'transparency' => 'nullable|integer|min:0|max:100',
            'variant' => 'nullable|string|max:50',
            'exotic_animation' => 'nullable|string|max:100',
        ]);

        $style = Style::first();
        if (! $style) $style = new Style();

        $style->fill(array_merge(['name' => 'global'], $data));
        $style->save();

        return redirect()->route('admin.styles.index')->with('success', 'Estilos guardados correctamente.');
    }
}
