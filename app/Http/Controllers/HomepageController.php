<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homepage;

class HomepageController extends Controller
{
    public function index(Request $request)
    {
        $homepage = $this->singleton();
        $homepageMeta = $this->normalizeMeta($homepage->meta, $homepage);
        $folderFiles = $this->resolveFolderFiles($homepageMeta['settings']['image_folder'] ?? $homepage->image_folder);

        if ($request->wantsJson()) {
            return response()->json([
                'homepage' => $homepage,
                'meta' => $homepageMeta,
                'folderFiles' => $folderFiles,
            ]);
        }

        return view('homepage.index', compact('homepage', 'homepageMeta', 'folderFiles'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        return $this->persistSingleton($request);
    }

    public function show(Request $request, $id)
    {
        $homepage = $this->singleton();
        $homepageMeta = $this->normalizeMeta($homepage->meta, $homepage);

        if ($request->wantsJson()) {
            return response()->json([
                'homepage' => $homepage,
                'meta' => $homepageMeta,
            ]);
        }

        return redirect()->route('homepage.index');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        return $this->persistSingleton($request);
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeAdmin();

        $homepage = $this->singleton();
        $defaults = $this->defaultPayload();
        $homepage->fill($defaults);
        $homepage->save();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Homepage reiniciada', 'homepage' => $homepage]);
        }

        return redirect()->route('homepage.index')->with('success', 'Homepage reiniciada');
    }

    protected function persistSingleton(Request $request)
    {
        $data = $request->validate([
            'banner_image' => 'nullable|string|max:255',
            'image_folder' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'eslogan' => 'nullable|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
            'meta_json' => 'nullable|string',
        ]);

        $homepage = $this->singleton();
        $meta = $homepage->meta;
        if (! empty($data['meta_json'])) {
            $decoded = json_decode($data['meta_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return back()->withErrors(['meta_json' => 'La configuración del homepage no es válida.'])->withInput();
            }
            $meta = $decoded;
        }

        $payload = [
            'banner_image' => $data['banner_image'] ?? $homepage->banner_image,
            'image_folder' => $data['image_folder'] ?? $homepage->image_folder,
            'ubicacion' => $data['ubicacion'] ?? $homepage->ubicacion,
            'eslogan' => $data['eslogan'] ?? $homepage->eslogan,
            'nombre_empresa' => $data['nombre_empresa'] ?? $homepage->nombre_empresa,
            'meta' => $this->normalizeMeta($meta, $homepage, $data),
        ];

        Homepage::updateOrCreate(['id' => 1], $payload);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Homepage actualizada']);
        }

        return redirect()->route('homepage.index')->with('success', 'Homepage actualizada');
    }

    protected function authorizeAdmin(): void
    {
        if (! (auth()->user() && (auth()->user()->rol ?? '') === 'admin')) {
            abort(403);
        }
    }

    protected function singleton(): Homepage
    {
        $homepage = Homepage::find(1);
        if ($homepage) {
            return $homepage;
        }

        $homepage = new Homepage();
        $homepage->id = 1;
        $homepage->fill($this->defaultPayload());
        $homepage->save();

        return $homepage;
    }

    protected function defaultPayload(): array
    {
        $payload = [
            'banner_image' => 'uploads/banner.jpg',
            'image_folder' => 'uploads/homepage',
            'ubicacion' => 'Valle de las Flores',
            'eslogan' => 'Escápate y descansa',
            'nombre_empresa' => 'HomeRes Demo',
        ];

        $payload['meta'] = $this->defaultMeta($payload);

        return $payload;
    }

    protected function defaultMeta(array $source = []): array
    {
        $banner = $source['banner_image'] ?? 'uploads/banner.jpg';
        $folder = $source['image_folder'] ?? 'uploads/homepage';
        $name = $source['nombre_empresa'] ?? 'HomeRes Demo';
        $slogan = $source['eslogan'] ?? 'Escápate y descansa';
        $location = $source['ubicacion'] ?? 'Valle de las Flores';

        return [
            'settings' => [
                'image_folder' => $folder,
                'show_reservations' => true,
                'show_properties' => true,
                'dynamic_order' => ['reservations', 'properties'],
                'container' => 'wide',
                'hero_height' => 'lg',
            ],
            'blocks' => [
                [
                    'id' => 'hero-default',
                    'type' => 'hero',
                    'enabled' => true,
                    'title' => $name,
                    'subtitle' => $slogan,
                    'body' => 'Diseña una portada completa con bloques editables, banners, preguntas frecuentes y llamadas a la acción.',
                    'image' => $banner,
                    'height' => 'lg',
                    'overlay' => 45,
                    'align' => 'left',
                    'primary_label' => 'Explorar propiedades',
                    'primary_url' => '/propiedades',
                    'secondary_label' => 'Ver reservaciones',
                    'secondary_url' => '/reservaciones',
                    'eyebrow' => $location,
                ],
                [
                    'id' => 'title-default',
                    'type' => 'title',
                    'enabled' => true,
                    'title' => 'Hospedajes listos para reservar',
                    'subtitle' => 'Combina bloques visuales y secciones dinámicas sin salir del editor.',
                    'size' => 'xl',
                    'align' => 'center',
                ],
                [
                    'id' => 'dynamic-reservations',
                    'type' => 'dynamic_reservations',
                    'enabled' => true,
                    'title' => 'Reservaciones dinámicas',
                ],
                [
                    'id' => 'dynamic-properties',
                    'type' => 'dynamic_properties',
                    'enabled' => true,
                    'title' => 'Propiedades dinámicas',
                ],
                [
                    'id' => 'faq-default',
                    'type' => 'faq',
                    'enabled' => true,
                    'title' => 'Preguntas frecuentes',
                    'subtitle' => 'Una sección FAQ editable también forma parte del layout.',
                    'items' => [
                        ['question' => '¿Puedo reservar en línea?', 'answer' => 'Sí, cada propiedad enlaza directo al flujo de reservación.'],
                        ['question' => '¿Puedo cambiar banners?', 'answer' => 'Sí, puedes cambiar la imagen principal y agregar más bloques visuales.'],
                    ],
                ],
            ],
        ];
    }

    protected function normalizeMeta($meta, Homepage $homepage, array $incoming = []): array
    {
        $legacy = [
            'banner_image' => $incoming['banner_image'] ?? $homepage->banner_image,
            'image_folder' => $incoming['image_folder'] ?? $homepage->image_folder,
            'ubicacion' => $incoming['ubicacion'] ?? $homepage->ubicacion,
            'eslogan' => $incoming['eslogan'] ?? $homepage->eslogan,
            'nombre_empresa' => $incoming['nombre_empresa'] ?? $homepage->nombre_empresa,
        ];

        $base = $this->defaultMeta($legacy);
        if (! is_array($meta)) {
            $meta = [];
        }

        $settings = is_array($meta['settings'] ?? null) ? $meta['settings'] : [];
        $settings = array_merge($base['settings'], $settings);
        $settings['image_folder'] = $settings['image_folder'] ?? $legacy['image_folder'];
        $settings['show_reservations'] = (bool) ($settings['show_reservations'] ?? true);
        $settings['show_properties'] = (bool) ($settings['show_properties'] ?? true);

        $allowedDynamic = ['reservations', 'properties'];
        $order = is_array($settings['dynamic_order'] ?? null) ? $settings['dynamic_order'] : [];
        $normalizedOrder = [];
        foreach ($order as $item) {
            $item = strtolower(trim((string) $item));
            if (! in_array($item, $allowedDynamic, true) || in_array($item, $normalizedOrder, true)) {
                continue;
            }
            $normalizedOrder[] = $item;
        }
        foreach ($allowedDynamic as $required) {
            if (! in_array($required, $normalizedOrder, true)) {
                $normalizedOrder[] = $required;
            }
        }
        $settings['dynamic_order'] = $normalizedOrder;

        $blocks = [];
        foreach (($meta['blocks'] ?? []) as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = in_array(($block['type'] ?? ''), ['hero', 'banner', 'title', 'text', 'image', 'links', 'faq', 'dynamic_reservations', 'dynamic_properties'], true)
                ? $block['type']
                : 'text';

            $id = trim((string) ($block['id'] ?? ($type . '-' . $index)));
            if ($id === '') {
                $id = $type . '-' . $index;
            }

            $normalized = [
                'id' => $id,
                'type' => $type,
                'enabled' => array_key_exists('enabled', $block) ? (bool) $block['enabled'] : true,
                'title' => (string) ($block['title'] ?? ''),
                'subtitle' => (string) ($block['subtitle'] ?? ''),
                'body' => (string) ($block['body'] ?? ''),
                'image' => (string) ($block['image'] ?? ''),
                'link' => (string) ($block['link'] ?? ''),
                'label' => (string) ($block['label'] ?? ''),
                'height' => (string) ($block['height'] ?? 'md'),
                'width' => (string) ($block['width'] ?? 'md'),
                'align' => (string) ($block['align'] ?? 'left'),
                'overlay' => (int) ($block['overlay'] ?? 35),
                'eyebrow' => (string) ($block['eyebrow'] ?? ''),
                'primary_label' => (string) ($block['primary_label'] ?? ''),
                'primary_url' => (string) ($block['primary_url'] ?? ''),
                'secondary_label' => (string) ($block['secondary_label'] ?? ''),
                'secondary_url' => (string) ($block['secondary_url'] ?? ''),
                'size' => (string) ($block['size'] ?? 'md'),
                'style' => (string) ($block['style'] ?? 'card'),
                'items' => [],
            ];

            if (in_array($type, ['links', 'faq'], true) && is_array($block['items'] ?? null)) {
                foreach ($block['items'] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    if ($type === 'links') {
                        $normalized['items'][] = [
                            'label' => (string) ($item['label'] ?? ''),
                            'url' => (string) ($item['url'] ?? ''),
                            'style' => (string) ($item['style'] ?? 'primary'),
                        ];
                    } else {
                        $normalized['items'][] = [
                            'question' => (string) ($item['question'] ?? ''),
                            'answer' => (string) ($item['answer'] ?? ''),
                        ];
                    }
                }
            }

            $blocks[] = $normalized;
        }

        $hasDynamicReservations = collect($blocks)->contains(fn ($b) => ($b['type'] ?? '') === 'dynamic_reservations');
        $hasDynamicProperties = collect($blocks)->contains(fn ($b) => ($b['type'] ?? '') === 'dynamic_properties');
        if (! $hasDynamicReservations) {
            $blocks[] = [
                'id' => 'dynamic-reservations',
                'type' => 'dynamic_reservations',
                'enabled' => (bool) ($settings['show_reservations'] ?? true),
                'title' => 'Reservaciones dinámicas',
                'subtitle' => '',
                'body' => '',
                'image' => '',
                'link' => '',
                'label' => '',
                'height' => 'md',
                'width' => 'md',
                'align' => 'left',
                'overlay' => 35,
                'eyebrow' => '',
                'primary_label' => '',
                'primary_url' => '',
                'secondary_label' => '',
                'secondary_url' => '',
                'size' => 'md',
                'style' => 'card',
                'items' => [],
            ];
        }
        if (! $hasDynamicProperties) {
            $blocks[] = [
                'id' => 'dynamic-properties',
                'type' => 'dynamic_properties',
                'enabled' => (bool) ($settings['show_properties'] ?? true),
                'title' => 'Propiedades dinámicas',
                'subtitle' => '',
                'body' => '',
                'image' => '',
                'link' => '',
                'label' => '',
                'height' => 'md',
                'width' => 'md',
                'align' => 'left',
                'overlay' => 35,
                'eyebrow' => '',
                'primary_label' => '',
                'primary_url' => '',
                'secondary_label' => '',
                'secondary_url' => '',
                'size' => 'md',
                'style' => 'card',
                'items' => [],
            ];
        }

        if (empty($blocks)) {
            $blocks = $base['blocks'];
        }

        return [
            'settings' => $settings,
            'blocks' => array_values($blocks),
        ];
    }

    protected function resolveFolderFiles(?string $folder): array
    {
        $files = [];
        if (! $folder) {
            return $files;
        }

        $folder = trim(str_replace('\\', '/', $folder), '/ ');
        $target = public_path($folder);
        if (! is_dir($target)) {
            return $files;
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $all = @scandir($target) ?: [];
        sort($all);
        foreach ($all as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $target . DIRECTORY_SEPARATOR . $file;
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed, true)) {
                continue;
            }
            $files[] = asset($folder . '/' . $file);
        }

        return $files;
    }
}
