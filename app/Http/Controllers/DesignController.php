<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Design\BackgroundRequest;
use App\Http\Requests\Dashboard\Design\CarouselImageVideo\CarouselRequest;
use App\Http\Requests\Dashboard\Design\CarouselNavbarRequest;
use App\Http\Requests\Dashboard\Design\CarouselToolRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Helpers\FileProcessor;
use App\Models\DesignSetting;
use Illuminate\Http\Request;
use App\Models\DesignItem;
use App\Helpers\Helpers;

class DesignController extends Controller
{

    public function get_design_client(Request $request)
    {
        try {
            $lang = $request->query('lang', app()->getLocale());

            $settings = DesignSetting::with([
                'designItems.translations' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])->get();

            return ApiResponse::success(
                data: $settings
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.design.error.getDesign') ?? 'Error fetching design',
                ['execption' => $e->getMessage()],
                500
            );

        }
    }

    public function get_design(Request $request)
    {
        try {
            $lang = 'es';

            $settingsCollection = DesignSetting::with([
                'designItems.translations' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])->get();

            $transformedSettings = $settingsCollection->reduce(function ($carry, $design) {
                $itemsArray = $design->designItems->map(function ($item) {
                    $translation = $item->translations->first();

                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'path' => $item->path,
                        'full_path' => $item->full_path,
                        'title' => $translation?->title ?? '',
                        'subtitle' => $translation?->subtitle ?? '',
                    ];
                });

                $carry[$design->key . 'Setting'] = $design->value;
                $carry[$design->key] = $itemsArray;

                return $carry;
            }, []);


            return ApiResponse::success(
                data: $transformedSettings
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.design.error.getDesign') ?? 'Error fetching design',
                ['execption' => $e->getMessage()],
                500
            );

        }
    }

    ////////////////////////////////////////////////////
    // Carusel and image Update ////// CarouselRequest
    ////////////////////////////////////////////////////

    public function update_carousel(CarouselRequest $request, int $id)
    {
        DB::beginTransaction();

        try {

            $data = $request->validated();
            $item = DesignItem::findOrFail($id);

            $pathType = [
                'type' => $item->type,
                'path' => $item->path,
            ];

            // ⬆ Si se subió una nueva imagen, procesarla
            if ($request->hasFile('path')) {
                $pathType = FileProcessor::process(
                    file: $data['path'],
                    folder: "images/design/carousel",
                    oldFilePath: $item->path
                );
            }

            // ⬆ Actualizar item siempre
            $item->update([
                'type' => $pathType['type'],
                'path' => $pathType['path']
            ]);

            // Si cambia título o subtítulo → actualizar traducciones
            if (
                ($data['title'] ?? null) !== $item->title ||
                ($data['subtitle'] ?? null) !== $item->subtitle
            ) {

                $this->updateTranslations($item, $data);
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.design.success.carouselImage')
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return ApiResponse::error(
                __('messages.design.error.carouselImage'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }



    ////////////////////////////////////////////////////
    // backgrounds update
    ////////////////////////////////////////////////////

    public function update_backgrounds(BackgroundRequest $request)
    {
        DB::beginTransaction();
        try {


            DB::commit();
            return ApiResponse::success(
                __('messages.design.success.backgrounds'),

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

    ////////////////////////////////////////////////////
    // Carusel navbar Update
    ////////////////////////////////////////////////////

    public function update_carouselNavbar(CarouselNavbarRequest $request)
    {
        DB::beginTransaction();
        try {


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

            DB::commit();
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

    private function updateTranslations(DesignItem $item, array $data): void
    {
        // ES
        $item->translations()->updateOrCreate(
            ['lang' => 'es'],
            [
                "title" => $data['title'],
                "subtitle" => $data['subtitle'],
            ]
        );

        // EN
        $translated = Helpers::translateBatch([
            $data['title'],
            $data['subtitle']
        ]);

        $item->translations()->updateOrCreate(
            ['lang' => 'en'],
            [
                "title" => $translated[0] ?? null,
                "subtitle" => $translated[1] ?? null,
            ]
        );
    }

}
