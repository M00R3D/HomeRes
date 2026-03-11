<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class ImageController extends Controller
{
    public function index()
    {
        $public = public_path();
        $entries = @scandir($public) ?: [];
        $folders = [];
        $allowed = ['jpg','jpeg','png','webp','gif','svg'];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $path = $public . DIRECTORY_SEPARATOR . $e;
            if (! is_dir($path)) continue;
            // collect up to 8 images found recursively inside this folder
            $found = [];
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS));
            foreach ($it as $file) {
                if (! $file->isFile()) continue;
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (! in_array($ext, $allowed)) continue;
                // build relative path from public
                $rel = str_replace($public . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $rel = str_replace('\\', '/', $rel);
                $found[] = $rel;
                if (count($found) >= 8) break;
            }
            $folders[] = ['name' => $e, 'images' => $found];
        }
        usort($folders, function($a,$b){ return strcmp($a['name'],$b['name']); });
        return view('images.index', ['folders' => $folders]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',
            'folder' => 'nullable|string|max:80',
            'filename_base' => 'nullable|string|max:120',
        ]);

        $folder = trim((string)$request->input('folder', 'uploads'));
        // normalize slashes and trim
        $folder = str_replace('\\', '/', $folder);
        $folder = trim($folder, "/ ");
        // sanitize each path segment to allow nested folders but prevent directory traversal
        $parts = array_filter(explode('/', $folder), function($p) { return $p !== '' && $p !== '.' && $p !== '..'; });
        $cleanParts = [];
        foreach ($parts as $p) {
            $clean = preg_replace('/[^A-Za-z0-9\-_]/', '_', $p);
            if ($clean !== '') $cleanParts[] = $clean;
        }
        $folder = count($cleanParts) ? implode('/', $cleanParts) : 'uploads';
        $targetDir = public_path($folder);
        if (! is_dir($targetDir)) {
            if (! @mkdir($targetDir, 0755, true)) {
                return response()->json(['message' => 'No se pudo crear la carpeta de destino'], 500);
            }
        }

        $saved = [];
        $base = trim((string)$request->input('filename_base', ''));
        $base = preg_replace('/[^A-Za-z0-9\-_]/', '_', $base);

        foreach ($request->file('files') as $index => $f) {
            if (! $f->isValid()) continue;
            $ext = strtolower($f->getClientOriginalExtension());
            if ($base !== '') {
                $name = $base . ($index ? "_{$index}" : '') . '.' . $ext;
            } else {
                $orig = pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME);
                $orig = preg_replace('/[^A-Za-z0-9\-_]/', '_', $orig);
                $name = ($orig ?: 'file_' . time() . "_{$index}") . '.' . $ext;
            }
            $dest = $targetDir . DIRECTORY_SEPARATOR . $name;
            if (file_exists($dest)) {
                $name = pathinfo($name, PATHINFO_FILENAME) . '_' . time() . '.' . $ext;
                $dest = $targetDir . DIRECTORY_SEPARATOR . $name;
            }
            try {
                $f->move($targetDir, $name);
                $saved[] = [
                    'url' => asset($folder . '/' . $name),
                    'path' => $folder . '/' . $name,
                    'name' => $name,
                ];
                try {
                    $adminId = auth()->id();
                    $actor = $adminId ? User::find($adminId) : null;
                    $actorName = $actor ? ($actor->nombre . ' ' . $actor->apellido) : 'Sistema';
                    Notification::create([
                        'usuario_id' => null,
                        'estado' => 'cerrada',
                        'tipo' => 'otra',
                        'descripcion' => "Imagen subida por {$actorName}: {$folder}/{$name}",
                        'fecha_creacion' => Carbon::now()->format('Y-m-d H:i:s'),
                        'fecha_visto' => null,
                        'ruta' => asset($folder . '/' . $name),
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Error creando notificación de imagen: ' . $e->getMessage());
                }

            } catch (\Exception $e) {
                \Log::error('Error moviendo archivo: '.$e->getMessage());
            }
        }

        if (count($saved) === 0) {
            return response()->json(['message' => 'No se subieron archivos.'], 422);
        }
        return response()->json(['message' => 'Archivos subidos correctamente', 'files' => $saved], 201);
    }

    public function list(Request $request)
    {
        $folder = trim((string)$request->query('folder', 'uploads'));
        $folder = preg_replace('/[^A-Za-z0-9\-_\/]/', '_', $folder);
        $target = public_path($folder);
        if (! is_dir($target)) {
            return response()->json(['files' => []]);
        }
        $allowed = ['jpg','jpeg','png','webp','gif','svg'];
        $all = @scandir($target) ?: [];
        $files = [];
        $subdirs = [];
        foreach ($all as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $target . DIRECTORY_SEPARATOR . $f;
            if (! is_file($path)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed)) continue;
            $files[] = $f;
        }
        // also list subdirectories inside the requested folder
        foreach ($all as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $target . DIRECTORY_SEPARATOR . $f;
            if (is_dir($path)) $subdirs[] = $f;
        }
        sort($files);
        sort($subdirs);
        return response()->json(['files' => $files, 'dirs' => $subdirs]);
    }

    public function dirs(Request $request)
    {
        $public = public_path();
        $entries = @scandir($public) ?: [];
        $folders = [];
        $allowed = ['jpg','jpeg','png','webp','gif','svg'];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $path = $public . DIRECTORY_SEPARATOR . $e;
            if (! is_dir($path)) continue;
            $found = [];
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS));
            foreach ($it as $file) {
                if (! $file->isFile()) continue;
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (! in_array($ext, $allowed)) continue;
                $rel = str_replace($public . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $rel = str_replace('\\', '/', $rel);
                $found[] = $rel;
                if (count($found) >= 8) break;
            }
            $folders[] = ['name' => $e, 'images' => $found];
        }
        usort($folders, function($a,$b){ return strcmp($a['name'],$b['name']); });
        return response()->json(['folders' => $folders]);
    }
}