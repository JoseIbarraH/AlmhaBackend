<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Design\BackgroundRequest;
use App\Http\Requests\Dashboard\Design\CarouselBackgroundRequest;
use App\Http\Requests\Dashboard\Design\CarouselNavbarRequest;
use App\Http\Requests\Dashboard\Design\CarouselToolRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\DesignSetting;
use Illuminate\Http\Request;
use App\Models\DesignItem;
use App\Helpers\Helpers;

class DesignController extends Controller
{
    public function get_design_client(Request $request)
    {
        $locale = $request->query('locale', app()->getLocale());

        try {
            $response = [];

            $response['carouselStatic']['carouselSetting'] = DesignSetting::getOne('carousel');
            $response['carouselStatic']['imageVideoSetting'] = DesignSetting::getOne('imageVideo');

            // Helper para normalizar paths (array from getOne or model instances)
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            $formatStoredPath = function ($item) use ($disk) {
                if (!$item)
                    return null;

                if (is_array($item) && isset($item['path'])) {
                    if ($item['path'] && !filter_var($item['path'], FILTER_VALIDATE_URL)) {
                        $item['path'] = $disk->url($item['path']);
                    }
                    return $item;
                }

                if (is_object($item) && isset($item->path)) {
                    if ($item->path && !filter_var($item->path, FILTER_VALIDATE_URL)) {
                        $item->path = $disk->url($item->path);
                    }
                    return $item;
                }

                return $item;
            };

            // Backgrounds 1..3
            foreach (['background1', 'background2', 'background3'] as $index => $bgType) {
                $setting = DesignSetting::getAll($bgType);
                $item = null;
                if ($setting) {
                    $item = DesignItem::getOne($setting->id, $locale);
                    $item = $formatStoredPath($item);
                }
                $response['background' . ($index + 1)] = $item;
            }

            // Carousel (main)
            $carouselSetting = DesignSetting::getAll('carousel');
            $response['carouselStatic']['carousel'] = [];
            if ($carouselSetting) {
                $items = DesignItem::where('design_id', $carouselSetting->id)
                    ->where('type', 'carousel')
                    ->where('lang', $locale)
                    ->orderBy('id') // mantener orden predecible
                    ->get()
                    ->map(function ($it) use ($formatStoredPath) {
                        $it = $formatStoredPath($it);
                        return $it;
                    })->values();
                $response['carouselStatic']['carousel'] = $items;
            }

            // Carousel Navbar
            $navbarSetting = DesignSetting::getAll('carouselNavbar');
            $response['carouselNavbar'] = [];
            if ($navbarSetting) {
                $items = DesignItem::where('design_id', $navbarSetting->id)
                    ->where('type', 'carouselNavbar')
                    ->where('lang', $locale)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($it) use ($formatStoredPath) {
                        $it = $formatStoredPath($it);
                        return $it;
                    })->values();
                $response['carouselNavbar'] = $items;
            }

            // Carousel Tool
            $toolSetting = DesignSetting::getAll('carouselTool');
            $response['carouselTool'] = [];
            if ($toolSetting) {
                $items = DesignItem::where('design_id', $toolSetting->id)
                    ->where('type', 'carouselTool')
                    ->where('lang', $locale) // items may be only 'es' but keep filter
                    ->orderBy('id')
                    ->get()
                    ->map(function ($it) use ($formatStoredPath) {
                        $it = $formatStoredPath($it);
                        return $it;
                    })->values();
                $response['carouselTool'] = $items;
            }

            // Image/Video single item (si existe)
            $imageVideoSetting = DesignSetting::getAll('imageVideo');
            $response['carouselStatic']['imageVideo'] = null;
            if ($imageVideoSetting) {
                $iv = DesignItem::getOne($imageVideoSetting->id, $locale);
                $response['carouselStatic']['imageVideo'] = $formatStoredPath($iv);
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'messages' => __('messages.design.error.getDesign') ?? 'Error fetching design',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function get_design(Request $request)
    {
        try {
            $locale = 'es'; // Forzar español siempre
            $response = [];

            $response['carouselStatic']['carouselSetting'] = DesignSetting::getOne('carousel');
            $response['carouselStatic']['imageVideoSetting'] = DesignSetting::getOne('imageVideo');

            // Helper para normalizar paths (array from getOne or model instances)
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            $formatStoredPath = function ($item) use ($disk) {
                if (!$item)
                    return null;

                if (is_array($item) && isset($item['path'])) {
                    if ($item['path'] && !filter_var($item['path'], FILTER_VALIDATE_URL)) {
                        $item['path'] = $disk->url($item['path']);
                    }
                    return $item;
                }

                if (is_object($item) && isset($item->path)) {
                    if ($item->path && !filter_var($item->path, FILTER_VALIDATE_URL)) {
                        $item->path = $disk->url($item->path);
                    }
                    return $item;
                }

                return $item;
            };

            // Backgrounds 1..3
            foreach (['background1', 'background2', 'background3'] as $index => $bgType) {
                $setting = DesignSetting::getAll($bgType);
                $item = null;
                if ($setting) {
                    $item = DesignItem::getOne($setting->id, $locale);
                    $item = $formatStoredPath($item);
                }
                $response['background' . ($index + 1)] = $item;
            }

            // Carousel (main)
            $carouselSetting = DesignSetting::getAll('carousel');
            $response['carouselStatic']['carousel'] = [];
            if ($carouselSetting) {
                $items = DesignItem::where('design_id', $carouselSetting->id)
                    ->where('type', 'carousel')
                    ->where('lang', $locale)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($it) use ($formatStoredPath) {
                        $it = $formatStoredPath($it);
                        return $it;
                    })->values();
                $response['carouselStatic']['carousel'] = $items;
            }

            // Carousel Navbar
            $navbarSetting = DesignSetting::getAll('carouselNavbar');
            $response['carouselNavbar'] = [];
            if ($navbarSetting) {
                $items = DesignItem::where('design_id', $navbarSetting->id)
                    ->where('type', 'carouselNavbar')
                    ->where('lang', $locale)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($it) use ($formatStoredPath) {
                        $it = $formatStoredPath($it);
                        return $it;
                    })->values();
                $response['carouselNavbar'] = $items;
            }

            // Carousel Tool
            $toolSetting = DesignSetting::getAll('carouselTool');
            $response['carouselTool'] = [];
            if ($toolSetting) {
                $items = DesignItem::where('design_id', $toolSetting->id)
                    ->where('type', 'carouselTool')
                    ->where('lang', $locale)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($it) use ($formatStoredPath) {
                        $it = $formatStoredPath($it);
                        return $it;
                    })->values();
                $response['carouselTool'] = $items;
            }

            // Image/Video single item (si existe)
            $imageVideoSetting = DesignSetting::getAll('imageVideo');
            $response['carouselStatic']['imageVideo'] = null;
            if ($imageVideoSetting) {
                $iv = DesignItem::getOne($imageVideoSetting->id, $locale);
                $response['carouselStatic']['imageVideo'] = $formatStoredPath($iv);
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'messages' => __('messages.design.error.getDesign') ?? 'Error fetching design',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    ////////////////////////////////////////////////////
    // Carusel and image Update
    ////////////////////////////////////////////////////

    public function update_carouselImage(CarouselBackgroundRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $carousel = DesignSetting::set('carousel', $data['carouselSetting']);
            $imageVideo = DesignSetting::set('imageVideo', $data['imageVideoSetting']);

            if (!empty($data['imageVideo'])) {
                $this->processImageVideo($imageVideo->id, $data['imageVideo']);
            }

            if (!empty($data['carousel'])) {
                $this->syncCarouselItems($carousel->id, $data['carousel']);
            }

            DB::commit();
            return ApiResponse::success(
                __('messages.design.success.carouselImage'),
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.design.error.carouselImage'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    private function processImageVideo($designId, $item)
    {

        log::info("info", $item);
        $fileData = $this->processFile($item['path'], $designId, 'image_video');
        if (!$fileData)
            return;

        $data = [
            'path' => $fileData['path'],
            'type' => "imageVideo",
            'title' => $item['title'] ?? '',
            'subtitle' => $item['subtitle'] ?? '',
        ];

        DesignItem::updateOrCreate(
            ['design_id' => $designId, 'lang' => 'es'],
            $data
        );

        if ($data['title'] || $data['subtitle']) {
            $translated = Helpers::translateBatch(
                array_filter([$data['title'], $data['subtitle']]),
                'es',
                'en'
            );
            $data['title'] = $data['title'] ? array_shift($translated) : '';
            $data['subtitle'] = $data['subtitle'] ? ($translated[0] ?? '') : '';
        }

        DesignItem::updateOrCreate(
            ['design_id' => $designId, 'lang' => 'en'],
            $data
        );
    }

    private function processFile($file, $designId = null, $folder = 'carousel')
    {
        if ($file instanceof UploadedFile) {
            if ($designId) {
                $oldItem = DesignItem::getOne($designId, 'es');
                if ($oldItem?->path && Storage::disk('public')->exists($oldItem->path)) {
                    Storage::disk('public')->delete($oldItem->path);
                }
            }

            $mimeType = $file->getMimeType();
            $type = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                default => 'other'
            };

            // Guardar según el tipo de archivo
            $path = $type === 'video'
                ? $file->store("images/design/{$folder}", 'public')
                : Helpers::saveWebpFile($file, "images/design/{$folder}");

            return [
                'path' => $path,
                'type' => $type
            ];
        }

        if (is_string($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            $path = Helpers::removeAppUrl($file);
            Log::info("cosa", [$path]);

            return [
                'path' => $path,
                'type' => match (true) {
                    in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => 'image',
                    in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']) => 'video',
                    default => 'other'
                }
            ];
        }

        return null;
    }


    private function syncCarouselItems($designId, $carouselUrls)
    {
        $existingItems = DesignItem::where('design_id', $designId)
            ->where('type', 'carousel')
            ->get()
            ->groupBy('lang');

        $processedPaths = [];
        $textsToTranslate = [];
        $itemsData = [];

        foreach ($carouselUrls as $index => $item) {
            $fileData = $this->processFile($item['path'], null, 'carousel');
            if (!$fileData)
                continue;

            $title = $item['title'] ?? '';
            $subtitle = $item['subtitle'] ?? '';

            $itemsData[$index] = [
                'path' => $fileData['path'],
                'type' => $fileData['type'],
                'title' => $title,
                'subtitle' => $subtitle,
            ];

            if ($title)
                $textsToTranslate[] = $title;
            if ($subtitle)
                $textsToTranslate[] = $subtitle;
        }

        $translations = !empty($textsToTranslate)
            ? Helpers::translateBatch($textsToTranslate, 'es', 'en')
            : [];

        $translationIndex = 0;

        foreach ($itemsData as $data) {
            $titleEn = $data['title'] ? ($translations[$translationIndex++] ?? '') : '';
            $subtitleEn = $data['subtitle'] ? ($translations[$translationIndex++] ?? '') : '';

            foreach ([
                'es' => ['title' => $data['title'], 'subtitle' => $data['subtitle']],
                'en' => ['title' => $titleEn, 'subtitle' => $subtitleEn]
            ] as $lang => $texts) {
                $existing = ($existingItems[$lang] ?? collect())->firstWhere('path', $data['path']);

                if ($existing && ($existing->title !== $texts['title'] || $existing->subtitle !== $texts['subtitle'])) {
                    $existing->update($texts);
                } elseif (!$existing) {
                    DesignItem::create([
                        'design_id' => $designId,
                        'lang' => $lang,
                        'type' => 'carousel',
                        'path' => $data['path'],
                        'file_type' => $data['type'], // Agregado para distinguir imagen/video
                        'title' => $texts['title'],
                        'subtitle' => $texts['subtitle'],
                    ]);
                }

                $processedPaths[$lang][] = $data['path'];
            }
        }

        foreach (['es', 'en'] as $lang) {
            $toDelete = ($existingItems[$lang] ?? collect())
                ->whereNotIn('path', $processedPaths[$lang] ?? []);

            foreach ($toDelete as $item) {
                if ($lang === 'es' && $item->path) {
                    Storage::disk('public')->delete($item->path);
                }
                $item->delete();
            }
        }
    }

    ////////////////////////////////////////////////////
    // backgrounds update
    ////////////////////////////////////////////////////

    public function update_backgrounds(BackgroundRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $locale = $request->query('locale', app()->getLocale());
            $ids = [];

            // Procesar backgrounds 1, 2 y 3
            foreach (['background1', 'background2', 'background3'] as $backgroundType) {
                if (!empty($data[$backgroundType]) && is_array($data[$backgroundType])) {
                    $bg = DesignSetting::getAll($backgroundType);
                    $backgroundData = $data[$backgroundType];
                    $url = $backgroundData['path'];
                    $ids[] = $bg->id;

                    // Determinar si hay nueva imagen o video
                    $updateData = [];

                    if ($url instanceof UploadedFile) {
                        // Eliminar archivo anterior
                        $old = DesignItem::getOne($bg->id, 'es');
                        if (!empty($old['path']) && Storage::disk('public')->exists($old['path'])) {
                            Storage::disk('public')->delete($old['path']);
                        }

                        // Verificar si es imagen o video
                        $mimeType = $url->getMimeType();

                        if (str_starts_with($mimeType, 'image/')) {
                            // Procesar como imagen
                            $updateData['path'] = Helpers::saveWebpFile($url, "images/design/background/{$backgroundType}");
                        } elseif (str_starts_with($mimeType, 'video/')) {
                            // Procesar como video
                            $updateData['path'] = $this->saveVideoFile($url, "images/design/background/{$backgroundType}");
                        }
                    }

                    // Preparar traducciones una sola vez
                    $translations = Helpers::translateBatch(
                        [$backgroundData['title'], $backgroundData['subtitle']],
                        'es',
                        'en'
                    );

                    // Configuración para ambos idiomas
                    $languages = [
                        'es' => [
                            'title' => $backgroundData['title'],
                            'subtitle' => $backgroundData['subtitle'],
                        ],
                        'en' => [
                            'title' => $translations[0] !== "" ? $translations[0] : null,
                            'subtitle' => $translations[1] !== "" ? $translations[1] : null,
                        ],
                    ];

                    // Crear/actualizar para ambos idiomas
                    foreach ($languages as $lang => $content) {
                        DesignItem::updateOrCreate(
                            [
                                'design_id' => $bg->id,
                                'lang' => $lang,
                                'type' => $backgroundType,
                            ],
                            array_merge($content, $updateData)
                        );
                    }
                }
            }

            $response = [];
            foreach ($ids as $index => $id) {
                $item = DesignItem::getOne($id, $locale);

                if ($item && isset($item['path'])) {
                    $item['path'] = url("storage", $item['path']);
                }

                $response['background' . ($index + 1)] = $item;
            }

            DB::commit();
            return ApiResponse::success(
                __('messages.design.success.backgrounds'),
                $response
            );


        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.design.error.backgrounds'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Guardar archivo de video
     */
    private function saveVideoFile(UploadedFile $file, string $path): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($path, $filename, 'public');
    }

    ////////////////////////////////////////////////////
    // Carusel navbar Update
    ////////////////////////////////////////////////////

    public function update_carouselNavbar(CarouselNavbarRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $bg = DesignSetting::getAll('carouselNavbar');
            Log::info($request);
            // Obtener items existentes agrupados por idioma
            $existingItems = DesignItem::where('design_id', $bg->id)
                ->where('type', 'carouselNavbar')
                ->get()
                ->groupBy('lang');

            $processedPaths = [];
            $textsToTranslate = [];
            $itemsData = [];

            if (!empty($data['carouselNavbar']) && is_array($data['carouselNavbar'])) {

                // Preparar datos y textos para traducir
                foreach ($data['carouselNavbar'] as $index => $item) {
                    $fileData = null;

                    // Procesar archivo nuevo (imagen o video)
                    if ($item['path'] instanceof UploadedFile) {
                        $mimeType = $item['path']->getMimeType();
                        $type = match (true) {
                            str_starts_with($mimeType, 'image/') => 'image',
                            str_starts_with($mimeType, 'video/') => 'video',
                            default => 'other'
                        };

                        // Guardar según el tipo
                        if ($type === 'video') {
                            $path = $item['path']->store('images/design/carouselNavbar', 'public');
                        } else {
                            $path = Helpers::saveWebpFile($item['path'], 'images/design/carouselNavbar');
                        }

                        $fileData = [
                            'path' => $path,
                            'file_type' => $type
                        ];
                    }
                    // Mantener path existente (string URL)
                    elseif (is_string($item['path'])) {
                        $pathOut = Helpers::removeAppUrl($item['path']);
                        $ext = strtolower(pathinfo($pathOut, PATHINFO_EXTENSION));
                        $type = match (true) {
                            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => 'image',
                            in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']) => 'video',
                            default => 'other'
                        };

                        $fileData = [
                            'path' => $pathOut,
                            'file_type' => $type
                        ];
                    }

                    if (!$fileData)
                        continue;

                    $title = $item['title'] ?? '';
                    $subtitle = $item['subtitle'] ?? '';

                    $itemsData[$index] = [
                        'path' => $fileData['path'],
                        'file_type' => $fileData['file_type'],
                        'title' => $title,
                        'subtitle' => $subtitle,
                    ];

                    // Recopilar textos para traducir
                    if ($title)
                        $textsToTranslate[] = $title;
                    if ($subtitle)
                        $textsToTranslate[] = $subtitle;
                }

                // Traducir todos los textos de una vez
                $translations = !empty($textsToTranslate)
                    ? Helpers::translateBatch($textsToTranslate, 'es', 'en')
                    : [];

                $translationIndex = 0;

                // Crear o actualizar items
                foreach ($itemsData as $data) {
                    $titleEn = $data['title'] ? ($translations[$translationIndex++] ?? '') : '';
                    $subtitleEn = $data['subtitle'] ? ($translations[$translationIndex++] ?? '') : '';

                    foreach ([
                        'es' => ['title' => $data['title'], 'subtitle' => $data['subtitle']],
                        'en' => ['title' => $titleEn, 'subtitle' => $subtitleEn]
                    ] as $lang => $texts) {

                        // Buscar item existente con el mismo path
                        $existing = ($existingItems[$lang] ?? collect())->firstWhere('path', $data['path']);

                        if ($existing) {
                            // Actualizar solo si cambió algo
                            if (
                                $existing->title !== $texts['title'] ||
                                $existing->subtitle !== $texts['subtitle'] ||
                                $existing->file_type !== $data['file_type']
                            ) {
                                $existing->update(array_merge($texts, ['file_type' => $data['file_type']]));
                            }
                        } else {
                            // Crear nuevo item
                            DesignItem::create([
                                'design_id' => $bg->id,
                                'lang' => $lang,
                                'type' => 'carouselNavbar',
                                'path' => $data['path'],
                                'file_type' => $data['file_type'],
                                'title' => $texts['title'],
                                'subtitle' => $texts['subtitle'],
                            ]);
                        }

                        // Registrar paths procesados
                        $processedPaths[$lang][] = $data['path'];
                    }
                }
            }

            // Eliminar items que ya no están en la lista
            foreach (['es', 'en'] as $lang) {
                $toDelete = ($existingItems[$lang] ?? collect())
                    ->whereNotIn('path', $processedPaths[$lang] ?? []);

                foreach ($toDelete as $item) {
                    // Solo eliminar el archivo físico una vez (desde 'es')
                    if ($lang === 'es' && $item->path && !filter_var($item->path, FILTER_VALIDATE_URL)) {
                        if (Storage::disk('public')->exists($item->path)) {
                            Storage::disk('public')->delete($item->path);
                        }
                    }
                    $item->delete();
                }
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.design.success.carouselNavbar')
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return ApiResponse::error(
                __('messages.design.error.carouselNavbar'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    ////////////////////////////////////////////////////
    // Carusel tools navbar Update
    ////////////////////////////////////////////////////

    public function update_carouselTool(CarouselToolRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $bg = DesignSetting::getAll('carouselTool');

            // Obtener items existentes
            $existingItems = DesignItem::where('design_id', $bg->id)
                ->where('type', 'carouselTool')
                ->get();

            $processedPaths = [];
            $itemsData = [];

            if (!empty($data['carouselTool']) && is_array($data['carouselTool'])) {

                // Preparar datos
                foreach ($data['carouselTool'] as $index => $item) {
                    $fileData = null;

                    // Procesar archivo nuevo (imagen o video)
                    if ($item['path'] instanceof UploadedFile) {
                        $mimeType = $item['path']->getMimeType();
                        $type = match (true) {
                            str_starts_with($mimeType, 'image/') => 'image',
                            str_starts_with($mimeType, 'video/') => 'video',
                            default => 'other'
                        };

                        // Guardar según el tipo
                        if ($type === 'video') {
                            $path = $item['path']->store('images/design/carouselTool', 'public');
                        } else {
                            $path = Helpers::saveWebpFile($item['path'], 'images/design/carouselTool');
                        }

                        $fileData = [
                            'path' => $path,
                            'file_type' => $type
                        ];
                    }
                    // Mantener path existente (string URL)
                    elseif (is_string($item['path'])) {
                        $pathOut = Helpers::removeAppUrl($item['path']);
                        $ext = strtolower(pathinfo($pathOut, PATHINFO_EXTENSION));
                        $type = match (true) {
                            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => 'image',
                            in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']) => 'video',
                            default => 'other'
                        };

                        $fileData = [
                            'path' => $pathOut,
                            'file_type' => $type
                        ];
                    }

                    if (!$fileData)
                        continue;

                    $itemsData[$index] = [
                        'path' => $fileData['path'],
                        'file_type' => $fileData['file_type'],
                    ];

                    // Registrar paths procesados
                    $processedPaths[] = $fileData['path'];
                }

                // Crear o actualizar items
                foreach ($itemsData as $data) {
                    // Buscar item existente con el mismo path
                    $existing = $existingItems->firstWhere('path', $data['path']);

                    if ($existing) {
                        // Actualizar solo si cambió el tipo de archivo
                        if ($existing->file_type !== $data['file_type']) {
                            $existing->update(['file_type' => $data['file_type']]);
                        }
                    } else {
                        // Crear nuevo item
                        DesignItem::create([
                            'design_id' => $bg->id,
                            'lang' => 'es', // Solo idioma por defecto ya que no hay textos
                            'type' => 'carouselTool',
                            'path' => $data['path'],
                            'file_type' => $data['file_type'],
                        ]);
                    }
                }
            }

            // Eliminar items que ya no están en la lista
            $toDelete = $existingItems->whereNotIn('path', $processedPaths);

            foreach ($toDelete as $item) {
                // Eliminar archivo físico si no es URL externa
                if ($item->path && !filter_var($item->path, FILTER_VALIDATE_URL)) {
                    if (Storage::disk('public')->exists($item->path)) {
                        Storage::disk('public')->delete($item->path);
                    }
                }
                $item->delete();
            }

            DB::commit();
            Log::info("Sirvio owo");
            return ApiResponse::success(
                __('messages.design.success.carouselTool')
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return ApiResponse::error(
                __('messages.design.error.carouselTool'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
