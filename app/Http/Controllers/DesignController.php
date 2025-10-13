<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Design\BackgroundRequest;
use App\Http\Requests\Dashboard\Design\CarouselBackgroundRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\DesignSetting;
use Illuminate\Http\Request;
use App\Models\DesignItem;
use App\Helpers\Helpers;

class DesignController extends Controller
{
    public function index()
    {

    }

    ////////////////////////////////////////////////////
    // Carusel and image Update
    ////////////////////////////////////////////////////

    public function update_carouselImage(CarouselBackgroundRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $carousel = DesignSetting::set('carousel', $data['carousel']);
            $imageVideo = DesignSetting::set('imageVideo', $data['imageVideo']);

            if (!empty($data['imageVideoUrl'])) {
                $this->processImageVideo($imageVideo->id, $data['imageVideoUrl'][0]);
            }

            if (!empty($data['carouselUrls'])) {
                $this->syncCarouselItems($carousel->id, $data['carouselUrls']);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'messages' => __('messages.design.success.carouselImage'),
                'data' => ''
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'messages' => __('messages.design.error.carouselImage'),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processImageVideo($designId, $item)
    {
        $fileData = $this->processFile($item['url'], $designId);
        if (!$fileData)
            return;

        $data = [
            'path' => $fileData['path'],
            'type' => $fileData['type'],
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

    private function processFile($file, $designId = null)
    {
        if ($file instanceof UploadedFile) {
            if ($designId) {
                $oldItem = DesignItem::getOne($designId, 'es');
                if ($oldItem?->path && Storage::disk('public')->exists($oldItem->path)) {
                    Storage::disk('public')->delete($oldItem->path);
                }
            }

            return [
                'path' => Helpers::saveWebpFile($file, $designId ? 'images/design/image_video' : 'images/design/carousel'),
                'type' => match (true) {
                    str_starts_with($file->getMimeType(), 'image/') => 'image',
                    str_starts_with($file->getMimeType(), 'video/') => 'video',
                    default => 'other'
                }
            ];
        }

        if (is_string($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return [
                'path' => $file,
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
            $fileData = $this->processFile($item['url']);
            if (!$fileData)
                continue;

            $title = $item['title'] ?? '';
            $subtitle = $item['subtitle'] ?? '';

            $itemsData[$index] = [
                'path' => $fileData['path'],
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

    public function update_backgrounds(BackgroundRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Procesar backgrounds 1, 2 y 3
            foreach (['background1', 'background2', 'background3'] as $backgroundType) {
                if (!empty($data[$backgroundType]) && is_array($data[$backgroundType])) {
                    $bg = DesignSetting::getAll($backgroundType);
                    $backgroundData = $data[$backgroundType];
                    $url = $backgroundData['url'];

                    // Determinar si hay nueva imagen
                    $updateData = [];

                    if ($url instanceof UploadedFile) {
                        // Eliminar imagen anterior
                        $old = DesignItem::getOne($bg->id, 'es');
                        if (!empty($old['path']) && Storage::disk('public')->exists($old['path'])) {
                            Storage::disk('public')->delete($old['path']);
                        }

                        $updateData['path'] = Helpers::saveWebpFile($url, "images/design/background/{$backgroundType}");
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
                            'title' => $translations[0] ?? '',
                            'subtitle' => $translations[1] ?? '',
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

            DB::commit();
            return response()->json([
                'success' => true,
                'messages' => __('messages.design.success.backgrounds'),
                'data' => ''
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'messages' => __('messages.design.error.backgrounds'),
                'error' => $e->getMessage()
            ]);
        }
    }
}
