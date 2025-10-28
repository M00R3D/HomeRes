<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homepage;

class HomepageController extends Controller
{
    public function index(Request $request)
    {
        $homepages = Homepage::orderByDesc('id')->get();
        $folderFiles = [];
        $first = $homepages->first();
        if ($first && !empty($first->image_folder)) {
            $folder = trim($first->image_folder, "/\\");
            $target = public_path($folder);
            if (is_dir($target)) {
                $allowed = ['jpg','jpeg','png','webp','gif','svg'];
                $all = @scandir($target) ?: [];
                foreach ($all as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $path = $target . DIRECTORY_SEPARATOR . $f;
                    if (! is_file($path)) continue;
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (! in_array($ext, $allowed)) continue;
                    $folderFiles[] = asset($folder . '/' . $f);
                }
            }
        }

        if ($request->wantsJson()) return response()->json($homepages);
        return view('homepage.index', compact('homepages', 'folderFiles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'banner_image' => 'nullable|string|max:255',
            'image_folder' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'eslogan' => 'nullable|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
        ]);

        $hp = Homepage::create($data);

        if ($request->wantsJson()) return response()->json($hp, 201);
        return redirect()->back()->with('success','Homepage creada');
    }

    public function show(Request $request, $id)
    {
        $hp = Homepage::find($id);
        if (!$hp) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        if ($request->wantsJson()) return response()->json($hp);
        return view('homepage.show', ['homepage' => $hp]);
    }

    public function update(Request $request, $id)
    {
        $hp = Homepage::find($id);
        if (!$hp) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);

        $data = $request->validate([
            'banner_image' => 'nullable|string|max:255',
            'image_folder' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'eslogan' => 'nullable|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
        ]);

        $hp->update($data);

        if ($request->wantsJson()) return response()->json($hp);
        return redirect()->back()->with('success','Homepage actualizada');
    }

    public function destroy(Request $request, $id)
    {
        $hp = Homepage::find($id);
        if (!$hp) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        $hp->delete();
        if ($request->wantsJson()) return response()->json(['message'=>'Eliminado']);
        return redirect()->back()->with('success','Homepage eliminada');
    }
}