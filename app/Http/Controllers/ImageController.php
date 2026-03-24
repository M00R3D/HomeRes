<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;

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
                try {
                    Log::entry('upload', 'imagen', auth()->id(), null, 'error', 'No se pudo crear la carpeta de destino: ' . $folder, url('/imagenes'));
                } catch (\Throwable $e) {
                }
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
                    Log::entry('upload', 'imagen', auth()->id(), null, 'success', 'Imagen subida: ' . $folder . '/' . $name, asset($folder . '/' . $name));
                } catch (\Throwable $e) {
                    \Log::error('Error creando log/notificacion de imagen: ' . $e->getMessage());
                }

            } catch (\Exception $e) {
                try {
                    Log::entry('upload', 'imagen', auth()->id(), null, 'error', 'Error moviendo archivo: ' . $e->getMessage(), url('/imagenes'));
                } catch (\Throwable $ignored) {
                }
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

    public function mkdir(Request $request)
    {
        $request->validate([
            'path' => 'required|string|max:200',
        ]);

        $rawPath = trim(str_replace('\\', '/', (string)$request->input('path')), '/ ');

        // Prevent directory traversal: reject any segment that is '.' or '..'
        $parts = array_filter(explode('/', $rawPath), fn($p) => $p !== '' && $p !== '.' && $p !== '..');
        $cleanParts = [];
        foreach ($parts as $p) {
            $clean = preg_replace('/[^A-Za-z0-9\-_]/', '_', $p);
            if ($clean !== '') $cleanParts[] = $clean;
        }

        if (empty($cleanParts)) {
            return response()->json(['message' => 'Ruta inválida'], 422);
        }

        $relativePath = implode('/', $cleanParts);
        $target = public_path($relativePath);

        if (is_dir($target)) {
            return response()->json(['message' => 'La carpeta ya existe', 'path' => $relativePath]);
        }

        if (! @mkdir($target, 0755, true)) {
            return response()->json(['message' => 'No se pudo crear la carpeta'], 500);
        }

        try {
            Log::entry('images', 'Carpeta creada: ' . $relativePath, auth()->id(), null, null, null, url('/imagenes'));
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'Carpeta creada', 'path' => $relativePath], 201);
    }

    public function deleteFile(Request $request)
    {
        if ((auth()->user()->rol ?? '') !== 'admin') {
            abort(403);
        }

        $request->validate(['path' => 'required|string|max:400']);
        $rawPath = trim(str_replace('\\', '/', (string)$request->input('path')), '/');
        $parts = array_values(array_filter(
            explode('/', $rawPath),
            fn($p) => $p !== '' && $p !== '.' && $p !== '..'
        ));
        if (empty($parts)) return response()->json(['message' => 'Ruta inválida'], 422);

        $cleanParts = [];
        $lastIdx = count($parts) - 1;
        foreach ($parts as $i => $p) {
            $clean = ($i === $lastIdx)
                ? preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $p)  // filename: allow dot for extension
                : preg_replace('/[^A-Za-z0-9\-_]/', '_', $p);   // directory segment: no dots
            if ($clean !== '') $cleanParts[] = $clean;
        }
        if (empty($cleanParts)) return response()->json(['message' => 'Ruta inválida'], 422);

        $ext = strtolower(pathinfo(end($cleanParts), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg'], true)) {
            return response()->json(['message' => 'Tipo de archivo no permitido'], 422);
        }

        $relativePath = implode('/', $cleanParts);
        $target = public_path($relativePath);
        if (!is_file($target)) return response()->json(['message' => 'Archivo no encontrado'], 404);
        if (!@unlink($target)) return response()->json(['message' => 'No se pudo eliminar el archivo'], 500);

        try {
            Log::entry('images', 'Archivo eliminado: ' . $relativePath, auth()->id(), null, null, null, url('/imagenes'));
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'Archivo eliminado']);
    }

    public function deleteFolder(Request $request)
    {
        if ((auth()->user()->rol ?? '') !== 'admin') {
            abort(403);
        }

        $request->validate(['path' => 'required|string|max:200']);
        $rawPath = trim(str_replace('\\', '/', (string)$request->input('path')), '/');
        $parts = array_filter(explode('/', $rawPath), fn($p) => $p !== '' && $p !== '.' && $p !== '..');
        $cleanParts = [];
        foreach ($parts as $p) {
            $clean = preg_replace('/[^A-Za-z0-9\-_]/', '_', $p);
            if ($clean !== '') $cleanParts[] = $clean;
        }
        if (empty($cleanParts)) return response()->json(['message' => 'Ruta inválida'], 422);

        // Protect compiled asset folder from accidental deletion
        if (count($cleanParts) === 1 && in_array($cleanParts[0], ['build'], true)) {
            return response()->json(['message' => 'No se puede eliminar esta carpeta del sistema'], 403);
        }

        $relativePath = implode('/', $cleanParts);
        $target = public_path($relativePath);
        if (!is_dir($target)) return response()->json(['message' => 'Carpeta no encontrada'], 404);

        $this->rrmdir($target);

        if (is_dir($target)) return response()->json(['message' => 'No se pudo eliminar la carpeta'], 500);

        try {
            Log::entry('images', 'Carpeta eliminada: ' . $relativePath, auth()->id(), null, null, null, url('/imagenes'));
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'Carpeta eliminada']);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (@scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}