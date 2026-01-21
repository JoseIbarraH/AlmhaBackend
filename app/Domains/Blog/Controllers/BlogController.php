<?php

namespace App\Domains\Blog\Controllers;

use App\Domains\Blog\Requests\UpdateRequest;
use App\Domains\Blog\Models\BlogTranslation;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Domains\Blog\Models\Blog;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Helpers\Helpers;

class BlogController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = config('languages.supported');
    }

    /**
     * Display a listing of the resource.
     */
    public function list_blog(Request $request)
    {
        try {
            $perPage = 9;

            $blogs = QueryBuilder::for(Blog::class)
                ->select('id', 'slug', 'status', 'created_at', 'updated_at')
                ->allowedIncludes(['translation', 'category.translation'])
                ->allowedFilters([
                    AllowedFilter::scope('title', 'RelationTitle'),
                ])
                ->allowedSorts(['created_at', 'updated_at'])
                ->defaultSort('-created_at')
                ->whereHas('translation')
                ->with(['translation', 'category.translation'])
                ->paginate($perPage)
                ->withQueryString();

            $blogs->getCollection()->transform(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->translation?->title,
                    'status' => $blog->status,
                    'slug' => $blog->slug,
                    'created_at' => $blog->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $blog->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $total = Blog::count();
            $totalActivated = Blog::where('status', 'active')->count();
            $totalDeactivated = Blog::where('status', 'inactive')->count();
            $last = Blog::where('created_at', '>=', now()->subDays(15))->count();

            return ApiResponse::success(
                __('messages.blog.success.listBlogs'),
                [
                    'pagination' => $blogs,
                    'filters' => $request->only('search'),
                    'stats' => [
                        'total' => $total,
                        'totalActivated' => $totalActivated,
                        'totalDeactivated' => $totalDeactivated,
                        'lastCreated' => $last,
                    ],
                ]
            );

        } catch (\Throwable $e) {
            Log::error('Error en list_blogs: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.blog.error.listBlogs'),
                ['exception' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function get_blog($id)
    {
        try {
            $blog = Blog::with(['translation'])
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->firstOrFail();

            $data = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'image' => $blog->image,
                'writer' => $blog->writer,
                'title' => $blog->translation->title ?? null,
                'content' => $blog->translation->content ?? null,
                'category' => $blog->category_code,
                'status' => $blog->status,
                'random_blogs' => Blog::where('status', 'active')
                    ->where('id', '!=', $blog->id)
                    ->inRandomOrder()
                    ->limit(3)
                    ->get()
                    ->map(function ($randomBlog) {
                        return [
                            'id' => $randomBlog->id,
                            'title' => $randomBlog->translation->title ?? null,
                            'slug' => $randomBlog->slug,
                            'image' => $randomBlog->image,
                            'category' => $randomBlog->category_code,
                            'created_at' => $randomBlog->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ];

            return ApiResponse::success(
                "blog obtained successfully",
                $data
            );
        } catch (\Throwable $e) {
            Log::error('Error en get_blog: ' . $e->getMessage());

            return ApiResponse::error(
                "Error retrieving blog",
                $e,
                500
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create_blog(Request $request, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $blog = Blog::create([
                'user_id' => auth()->id(),
                'category_code' => null,
                'writer' => '',
                'status' => 'inactive',
                'views' => 0,
                'slug' => uniqid('temp-'),
                'image' => '',
            ]);

            $this->createTranslations($blog, $data, $translator);

            $blog->slug = null;
            $blog->save();

            DB::commit();

            return ApiResponse::success(
                __('messages.blog.success.createBlog'),
                $blog->load('translations'),
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_blog', [
                'message' => $e->getMessage()
            ]);

            return ApiResponse::error(
                __('messages.blog.error.createBlog'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    /**
     * Crear traducciones para todos los idiomas
     */
    private function createTranslations(Blog $blog, array $data, GoogleTranslateService $translator): void
    {
        $sourceLang = app()->getLocale();
        $textsToTranslate = [
            $data['title'] ?? 'Default title',
            $data['content'] ?? 'Write your blog'
        ];

        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang) {
                $this->createTranslation($blog->id, $targetLang, $textsToTranslate[0], $textsToTranslate[1]);
                continue;
            }

            try {
                $translated = $translator->translate($textsToTranslate, $targetLang, $sourceLang);

                $this->createTranslation(
                    $blog->id,
                    $targetLang,
                    $translated[0] ?? $textsToTranslate[0],
                    $translated[1] ?? $textsToTranslate[1]
                );

            } catch (\Exception $e) {
                Log::warning("Translation failed for blog {$blog->id} to {$targetLang}", [
                    'error' => $e->getMessage(),
                    'blog_id' => $blog->id,
                    'target_lang' => $targetLang
                ]);

                // Fallback: usar texto original
                $this->createTranslation($blog->id, $targetLang, $textsToTranslate[0], $textsToTranslate[1]);
            }
        }
    }

    /**
     * Crear una traducción de blog
     */
    private function createTranslation(int $blogId, string $lang, string $title, string $content): void
    {
        BlogTranslation::create([
            'blog_id' => $blogId,
            'lang' => $lang,
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_blog(UpdateRequest $request, string $id, GoogleTranslateService $translator)
    {
        DB::beginTransaction();
        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validated();

            $this->updateBlogData($blog, $data);
            $this->updateTranslations($blog, $data, $translator);

            $blog->touch();
            $blog->save();

            DB::commit();

            return ApiResponse::success(
                __('messages.blog.success.updateBlog')
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en update_blog', [
                'blog_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                __('messages.blog.error.updateBlog'),
                config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                500
            );
        }
    }

    /**
     * Actualizar datos básicos del blog
     */
    private function updateBlogData(Blog $blog, array $data): void
    {
        $updates = [];
        if (isset($data['category']) && $data['category'] !== $blog->category_code) {
            $updates['category_code'] = $data['category'];
        }

        if (isset($data['status']) && $data['status'] !== $blog->status) {
            $updates['status'] = $data['status'];
        }

        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($blog->image) {
                $path = Helpers::removeAppUrl($blog->image);
                if (!empty($path) && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            $updates['image'] = Helpers::saveWebpFile($data['image'], "images/blog/{$blog->id}/blog_image");
        }

        if (isset($data['writer']) && $data['writer'] !== $blog->writer) {
            $updates['writer'] = $data['writer'];
        }

        if (!empty($updates)) {
            $blog->update($updates);
        }
    }

    /**
     * Actualizar traducciones para todos los idiomas
     */
    private function updateTranslations(Blog $blog, array $data, GoogleTranslateService $translator): void
    {
        $sourceLang = app()->getLocale();

        $sourceTranslation = $blog->translations()->firstOrCreate([
            'blog_id' => $blog->id,
            'lang' => $sourceLang,
        ]);

        $changedFields = [];
        $textsToTranslate = [];

        if (isset($data['title']) && $data['title'] !== $sourceTranslation->title) {
            $changedFields[] = 'title';
            $textsToTranslate[] = $data['title'];
        }

        if (isset($data['content']) && $data['content'] !== $sourceTranslation->content) {
            $changedFields[] = 'content';
            $textsToTranslate[] = $data['content'];
        }

        if (empty($changedFields)) {
            return;
        }

        $sourceTranslation->update(
            collect($data)->only($changedFields)->toArray()
        );

        foreach ($this->languages as $targetLang) {
            if ($targetLang === $sourceLang) {
                continue;
            }
            try {
                $translatedTexts = $translator->translate(
                    $textsToTranslate,
                    $targetLang,
                    $sourceLang
                );

                $translation = $blog->translations()->firstOrCreate([
                    'blog_id' => $blog->id,
                    'lang' => $targetLang,
                ]);

                $updateData = [];

                foreach ($changedFields as $index => $field) {
                    $updateData[$field] = $translatedTexts[$index] ?? $translation->$field;
                }

                $translation->update($updateData);

            } catch (\Throwable $e) {
                \Log::error(
                    "Translation error | Blog {$blog->id} | {$sourceLang} → {$targetLang}",
                    ['error' => $e->getMessage()]
                );
            }
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function delete_blog($id)
    {
        DB::beginTransaction();
        try {
            $blog = Blog::findOrFail($id);
            $blog->delete();

            DB::commit();

            return ApiResponse::success(
                __('messages.blog.success.deleteBlog')
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return ApiResponse::error(
                __('messages.blog.error.deleteBlog'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update status.
     */
    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $blog->update(['status' => $data['status']]);

            DB::commit();
            return ApiResponse::success(
                __('messages.blog.success.updateStatus'),
                $blog
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.blog.error.updateStatus'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Upload image in ckeditor component.
     */
    public function upload_image(Request $request, $id)
    {
        // Validar el archivo
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        try {
            $image = $request->file('upload');

            $filename = uniqid() . '_' . time() . '.' . $image->getClientOriginalExtension();

            $path = $image->storeAs("images/blog/{$id}/image_content", $filename, 'public');

            $url = asset('storage/' . $path);

            return response()->json([
                'url' => $url
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error(
                __('messages.blog.error.uploadImage'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Delete image in ckeditor component.
     */
    public function delete_image(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json([
                'error' => ['message' => 'No autorizado']
            ], 401);
        }

        $request->validate([
            'url' => 'required|string'
        ]);

        try {
            $url = $request->input('url');

            $path = str_replace(asset('storage') . '/', '', $url);

            // Eliminar archivo físico
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return response()->json([
                    'message' => 'Imagen eliminada correctamente',
                    'deleted' => $url
                ]);
            }

            return ApiResponse::error(
                message: __('messages.blog.error.deleteImage'),
                code: 404
            );
        } catch (\Exception $e) {

            return ApiResponse::error(
                errors: ['exception' => $e->getMessage()],
                code: 500
            );
        }
    }
}

