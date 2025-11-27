<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Design\StoreRequest;
use App\Http\Requests\Dashboard\Design\UpdateRequest;
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

            $groupMapping = [
                'background1' => 'backgrounds',
                'background2' => 'backgrounds',
                'background3' => 'backgrounds',
                'carousel' => 'carousel',
                'carouselNavbar' => 'carouselNavbar',
                'carouselTool' => 'carouselTool',
                'imageVideo' => 'imageVideo',
            ];

            $transformedSettings = $settingsCollection->reduce(function ($carry, $design) use ($groupMapping) {
                $key = $design->key;
                $groupName = $groupMapping[$key] ?? $key;

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
                })->values()->toArray();

                if (!isset($carry[$groupName])) {
                    $carry[$groupName] = [];
                }

                // 👈 CAMBIO APLICADO: Creamos un objeto que contiene el ID y el valor booleano
                $carry[$groupName][$key . 'Setting'] = [
                    'id' => $design->id, // ID del DesignSetting (ej. ID del background1)
                    'enabled' => (bool) $design->value, // Valor booleano (enabled/disabled)
                ];

                // Mantener el array de ítems
                $carry[$groupName][$key] = $itemsArray;

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
    // Create ////// CarouselRequest
    ////////////////////////////////////////////////////

    public function create_item(StoreRequest $request)
    {
        Log::info($request);
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $setting = DesignSetting::find($data['designId']);
            Log::info("setting: ", [$setting]);

            $pathType = [
                'type' => '',
                'path' => '',
            ];

            if ($request->hasFile('path')) {
                $pathType = FileProcessor::process(
                    file: $data['path'],
                    folder: $setting->folder
                );
            }

            $item = DesignItem::create([
                'design_id' => $data['designId'],
                'type' => $pathType['type'],
                'path' => $pathType['path']
            ]);

            $this->updateCreateTranslations($item, $data);
            DB::commit();
            return ApiResponse::success(
                __('messages.design.success.createItem')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error", [$e]);
            return ApiResponse::error(
                __('messages.design.error.createItem'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    ////////////////////////////////////////////////////
    // Update //////
    ////////////////////////////////////////////////////

    public function update_item(UpdateRequest $request, int $id)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            Log::info($request);
            $item = DesignItem::findOrFail($id);
            $setting = DesignSetting::find($data['designId']);

            $pathType = [
                'type' => $item->type,
                'path' => $item->path,
            ];

            if (($data['title'] ?? null) !== $item->title || ($data['subtitle'] ?? null) !== $item->subtitle) {
                $this->updateCreateTranslations($item, $data);
            }

            if ($request->hasFile('path')) {
                $pathType = FileProcessor::process(
                    file: $data['path'],
                    folder: $setting->folder,
                    oldFilePath: $item->path
                );
            }

            $item->update([
                'type' => $pathType['type'],
                'path' => $pathType['path']
            ]);

            DB::commit();

            return ApiResponse::success(
                __('messages.design.success.updateItem')
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);

            return ApiResponse::error(
                __('messages.design.error.updateItem'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    ////////////////////////////////////////////////////
    // Delete //////
    ////////////////////////////////////////////////////

    public function delete_item($id)
    {
        try {
            DB::beginTransaction();
            $item = DesignItem::findOrFail($id);

            if (Storage::disk('public')->exists($item->path)) {
                Storage::disk('public')->delete($item->path);
            }

            $item->delete();

            DB::commit();
            return ApiResponse::success(
                __('messages.design.success.deleteItem')
            );
        } catch (\Throwable $th) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.design.error.deleteItem'),
                ['exception' => $th->getMessage()],
                500
            );
        }
    }

    ////////////////////////////////////////////////////
    // Update item selection carousel/image //////
    ////////////////////////////////////////////////////

    public function update_state(Request $request)
    {
        Log::info($request);
        try {
            $data = $request->validate([
                'carouselEnabled' => 'required|boolean',
                'imageVideoEnabled' => 'required|boolean'
            ]);

            DesignSetting::where('key', 'carousel')->update([
                'value' => $data['carouselEnabled']
            ]);

            DesignSetting::where('key', 'imageVideo')->update([
                'value' => $data['imageVideoEnabled']
            ]);

            return ApiResponse::success(
                __('messages.design.success.updateState')
            );
        } catch (\Throwable $th) {
            Log::error($th);
            return ApiResponse::error(
                __('messages.design.error.updateState'),
                ['exception' => $th->getMessage()],
            );
        }
    }

    private function updateCreateTranslations(DesignItem $item, array $data): void
    {
        // ES
        $item->translations()->updateOrCreate(
            [
                'lang' => 'es',
                'item_id' => $item->id
            ],
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
            [
                'lang' => 'en',
                'item_id' => $item->id
            ],
            [
                "title" => $translated[0] ?? null,
                "subtitle" => $translated[1] ?? null,
            ]
        );
    }
}
