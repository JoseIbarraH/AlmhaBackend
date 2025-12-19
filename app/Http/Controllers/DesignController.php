<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Design\StoreRequest;
use App\Http\Requests\Dashboard\Design\UpdateRequest;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\FileProcessor;
use App\Models\DesignSetting;
use Illuminate\Http\Request;
use App\Models\DesignItem;

class DesignController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    public function get_design(Request $request)
    {
        try {
            $settingsCollection = DesignSetting::with([
                'designItems.translation'
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
                $groupName = $groupMapping[$key] ?? 'others'; // Evita errores si la key no está en el mapa

                // Procesar ítems con seguridad
                $itemsArray = $design->designItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'path' => $item->path,
                        'full_path' => $item->full_path,
                        'title' => $item->translation->title ?? '', // El null-safe a veces da problemas en versiones viejas de PHP, mejor usar ??
                        'subtitle' => $item->translation->subtitle ?? '',
                    ];
                })->values()->toArray();

                // Inicializar grupo si no existe
                if (!isset($carry[$groupName])) {
                    $carry[$groupName] = [];
                }

                // Estructura de configuración
                $carry[$groupName][$key . 'Setting'] = [
                    'id' => $design->id,
                    'enabled' => filter_var($design->value, FILTER_VALIDATE_BOOLEAN), // Más seguro que (bool) para strings "0" o "false"
                ];

                // Inyectar los ítems
                $carry[$groupName][$key] = $itemsArray;

                return $carry;
            }, []);

            return ApiResponse::success(data: $transformedSettings);

        } catch (\Throwable $e) {
            // Log para que tú veas el error real en storage/logs/laravel.log
            \Log::error("Error en get_design: " . $e->getMessage());

            return ApiResponse::error(
                __('messages.design.error.getDesign') ?? 'Error fetching design',
                ['exception' => $e->getMessage()], // Corregido el typo 'execption'
                500
            );
        }
    }

    ////////////////////////////////////////////////////
    // Create ////// CarouselRequest
    ////////////////////////////////////////////////////

    public function create_item(StoreRequest $request, GoogleTranslateService $translator)
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

            $this->updateCreateTranslations($item, $data, $translator);
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

    public function update_item(UpdateRequest $request, int $id, GoogleTranslateService $translator)
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
                $this->updateCreateTranslations($item, $data, $translator);
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

    private function updateCreateTranslations(DesignItem $item, array $data, GoogleTranslateService $translator): void
    {
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                // Español: sin traducción
                $item->translations()->updateOrCreate(
                    ['lang' => $lang, 'item_id' => $item->id],
                    [
                        'title' => $data['title'],
                        'subtitle' => $data['subtitle'],
                    ]
                );
                continue;
            }

            try {
                $textsToTranslate = [
                    $data['title'],
                    $data['subtitle']
                ];

                $translated = $translator->translate($textsToTranslate, $lang);
                Log::info($translated);
                $item->translations()->updateOrCreate(
                    ['lang' => $lang, 'item_id' => $item->id],
                    [
                        'title' => $translated[0] ?? $data['title'],
                        'subtitle' => $translated[1] ?? $data['subtitle'],
                    ]
                );

            } catch (\Exception $e) {
                \Log::error("Translation error for item {$item->id} to {$lang}: " . $e->getMessage());

                $item->translations()->updateOrCreate(
                    ['lang' => $lang, 'item_id' => $item->id],
                    [
                        'title' => $data['title'],
                        'subtitle' => $data['subtitle'],
                    ]
                );
            }
        }
    }
}
