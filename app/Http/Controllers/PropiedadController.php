<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropiedadRequest;
use App\Http\Requests\UpdatePropiedadRequest;
use Illuminate\Http\Request;
use App\Models\Propiedad;
use App\Models\Log;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        $q = Propiedad::query();

        if ($request->filled('tipo')) $q->where('tipo', $request->tipo);
        if ($request->filled('estado')) $q->where('estado', $request->estado);
        if ($request->filled('q')) $q->where('nombre', 'like', '%'.$request->q.'%')->orWhere('codigo', 'like', '%'.$request->q.'%');
        if ($request->filled('max_precio')) $q->where('precio_noche', '<=', (float)$request->max_precio);

        $propiedades = $q->paginate(12);

        return view('propiedades.index', [
            'propiedades' => $propiedades,
        ]);
    }

    public function store(StorePropiedadRequest $request)
    {
        $propiedad = Propiedad::create($request->validated());

        try {
            Log::entry('propiedad', 'Propiedad creada: #' . $propiedad->id . ' - ' . ($propiedad->nombre ?? ''), auth()->id(), 'propiedad', $propiedad->id, route('propiedades.show', $propiedad->id));
        } catch (\Throwable $e) {
            // continue silently
        }

        return redirect()->route('propiedades.index')->with('success', 'Propiedad creada exitosamente.');
    }

    public function show($id)
    {
        $propiedad = Propiedad::findOrFail($id);
        // Build gallery from ruta_img which may be a file or a folder under public/
        $gallery = [];
        try {
            $base = public_path();
            $ruta = $propiedad->ruta_img ?? '';
            if ($ruta) {
                $full = $base . DIRECTORY_SEPARATOR . ltrim($ruta, '/\\');
                if (is_dir($full)) {
                    $files = @scandir($full) ?: [];
                    foreach ($files as $f) {
                        if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif'])) {
                            $gallery[] = trim($ruta, '/\\') . '/' . $f;
                        }
                    }
                } elseif (is_file($full)) {
                    // include the file itself
                    $gallery[] = $ruta;
                    // also try folder where the file is located
                    $folder = dirname($ruta);
                    $fullFolder = $base . DIRECTORY_SEPARATOR . ltrim($folder, '/\\');
                    if (is_dir($fullFolder)) {
                        $files = @scandir($fullFolder) ?: [];
                        foreach ($files as $f) {
                            if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif'])) {
                                $candidate = trim($folder, '/\\') . '/' . $f;
                                if (!in_array($candidate, $gallery)) $gallery[] = $candidate;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $gallery = [];
        }

        return view('propiedades.show', ['propiedad' => $propiedad, 'gallery' => $gallery]);
    }

    public function edit($id)
    {
        $propiedad = Propiedad::findOrFail($id);
        $folders = $this->listImageFolders();
        return view('propiedades.edit', ['propiedad' => $propiedad, 'imageFolders' => $folders]);
    }

    public function create()
    {
        $folders = $this->listImageFolders();
        return view('propiedades.create', ['imageFolders' => $folders]);
    }

    public function update(UpdatePropiedadRequest $request, $id)
    {
        $propiedad = Propiedad::findOrFail($id);
        $oldEstado = $propiedad->estado;

        $validated = $request->validated();

        $propiedad->update($validated);

        try {
            Log::entry('propiedad', 'Propiedad actualizada: #' . $propiedad->id . ' - ' . ($propiedad->nombre ?? ''), auth()->id(), 'propiedad', $propiedad->id, route('propiedades.show', $propiedad->id));

            // If estado changed, add a specific state-change log
            if (array_key_exists('estado', $validated) && $validated['estado'] !== $oldEstado) {
                Log::entry('propiedad', 'Estado cambiado de ' . ($oldEstado ?? 'N/A') . ' a ' . $validated['estado'] . ': #' . $propiedad->id, auth()->id(), 'propiedad', $propiedad->id, route('propiedades.show', $propiedad->id));
            }
        } catch (\Throwable $e) {
            // ignore notification/logging failures
        }

        return redirect()->route('propiedades.index')->with('success', 'Propiedad actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $propiedad = Propiedad::findOrFail($id);
        $nombre = $propiedad->nombre ?? '';
        $propiedad->delete();

        try {
            Log::entry('propiedad', 'Propiedad eliminada: #' . $id . ' - ' . $nombre, auth()->id(), 'propiedad', $id, route('propiedades.index'));
        } catch (\Throwable $e) {
            // continue
        }

        return redirect()->route('propiedades.index')->with('success', 'Propiedad eliminada exitosamente.');
    }

    /**
     * Scan public/ for folders that contain image files and return relative paths.
     */
    private function listImageFolders(): array
    {
        $base = public_path();
        $folders = [];

        try {
            // Check uploads folder first
            $uploads = $base . DIRECTORY_SEPARATOR . 'uploads';
            if (is_dir($uploads)) {
                $subs = @scandir($uploads) ?: [];
                foreach ($subs as $s) {
                    if ($s === '.' || $s === '..') continue;
                    $full = $uploads . DIRECTORY_SEPARATOR . $s;
                    if (is_dir($full)) {
                        $folders[] = 'uploads/' . $s;
                    }
                }
            }

            // Also scan top-level public directories for image-containing folders
            $top = @scandir($base) ?: [];
            $ignore = ['css','js','build','storage','vendor','fonts','logo','logos'];
            foreach ($top as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                if (in_array(strtolower($entry), $ignore)) continue;
                $full = $base . DIRECTORY_SEPARATOR . $entry;
                if (!is_dir($full)) continue;
                // check if this folder contains image files or subfolders with images
                $hasImg = false;
                $files = @scandir($full) ?: [];
                foreach ($files as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $hasImg = true; break; }
                    if (is_dir($full . DIRECTORY_SEPARATOR . $f)) {
                        $subFiles = @scandir($full . DIRECTORY_SEPARATOR . $f) ?: [];
                        foreach ($subFiles as $sf) {
                            if (in_array(strtolower(pathinfo($sf, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif'])) { $hasImg = true; break 2; }
                        }
                    }
                }
                if ($hasImg) $folders[] = $entry;
            }
        } catch (\Throwable $e) {
            // ignore and return whatever collected
        }

        $folders = array_values(array_unique($folders));
        sort($folders);
        return $folders;
    }
}