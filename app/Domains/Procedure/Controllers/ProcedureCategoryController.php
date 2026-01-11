<?php

namespace App\Domains\Procedure\Controllers;

use App\Domains\Procedure\Models\ProcedureCategory;
use App\Services\GoogleTranslateService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Helpers\Helpers;


class ProcedureCategoryController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    /**
     * Get all categories
     */
    public function list_categories(Request $request)
    {
        try {
            $categories = QueryBuilder::for(ProcedureCategory::class)
                ->select('id', 'code')
                ->allowedFilters([AllowedFilter::scope('title', 'RelationTitle')])
                ->get();

            $categories->transform(function (ProcedureCategory $category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'title' => $category->translation->title ?? 'Default title',
                ];
            });

            return ApiResponse::success(
                "List of categories obtained correctly",
                $categories
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                "Error listing categories",
                $e,
                500
            );
        }
    }

    public function create_category(Request $request, GoogleTranslateService $translator)
    {
        try {
            $sourceLang = app()->getLocale();
            $data = $request->validate([
                'title' => 'required|string'
            ]);

            $blogCategory = ProcedureCategory::create([
                'code' => Helpers::generateUniqueCode(8)
            ]);

            foreach ($this->languages as $targetLang) {
                if ($targetLang === $sourceLang) {
                    $blogCategory->translations()->create([
                        'lang' => $targetLang,
                        'title' => $data['title']
                    ]);
                    continue;
                }

                $translatedText = $translator->translate($data['title'], $targetLang, $sourceLang);

                $titleToSave = is_array($translatedText) ? $translatedText[0] : $translatedText;

                $blogCategory->translations()->create([
                    'lang' => $targetLang,
                    'title' => $titleToSave
                ]);
            }

            return ApiResponse::success(
                "Category created successfully",
                $blogCategory,
                201
            );

        } catch (\Throwable $th) {
            return ApiResponse::error(
                "Error creating category",
                $th,
                500
            );
        }
    }

    public function delete_category($id)
    {
        try {
            if ($id == 1) {
                return ApiResponse::error(
                    "You cannot delete the general category",
                    null,
                    403
                );
            }

            $category = ProcedureCategory::findOrFail($id);

            // Reassign blogs to general category (ID 1)
            $category->blogs()->update(['category_id' => 1]);

            $category->delete();

            return ApiResponse::success(
                "Category deleted successfully"
            );
        } catch (\Throwable $th) {
            return ApiResponse::error(
                "Error deleting category",
                $th,
                500
            );
        }
    }

    public function update_category(Request $request, $id, GoogleTranslateService $translator)
    {
        try {
            $sourceLang = app()->getLocale();
            $category = ProcedureCategory::findOrFail($id);

            $data = $request->validate([
                'title' => 'required|string'
            ]);

            foreach ($this->languages as $targetLang) {
                // Find existing translation or create new instance (not saved yet)
                $translation = $category->translations()->where('lang', $targetLang)->first();

                if ($targetLang === $sourceLang) {
                    if ($translation) {
                        $translation->update(['title' => $data['title']]);
                    } else {
                        $category->translations()->create([
                            'lang' => $targetLang,
                            'title' => $data['title']
                        ]);
                    }
                    continue;
                }

                // Translate for other languages
                try {
                    $translatedText = $translator->translate($data['title'], $targetLang, $sourceLang);
                    $titleToSave = is_array($translatedText) ? $translatedText[0] : $translatedText;

                    if ($translation) {
                        $translation->update(['title' => $titleToSave]);
                    } else {
                        $category->translations()->create([
                            'lang' => $targetLang,
                            'title' => $titleToSave
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other languages or fallback
                    \Log::warning("Translation update failed for category {$id} to {$targetLang}: " . $e->getMessage());
                    // Optional: keep old value or update with base language value as fallback
                }
            }

            return ApiResponse::success(
                "Category updated successfully",
                $category
            );

        } catch (\Throwable $th) {
            return ApiResponse::error(
                "Error updating category",
                $th,
                500
            );
        }
    }
}
