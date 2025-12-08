<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Blog\UpdateRequest;
use App\Services\GoogleTranslateService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\BlogTranslation;
use Illuminate\Http\Request;
use App\Models\BlogCategory;
use App\Helpers\Helpers;
use App\Models\Blog;

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
    public function list_blog_client(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 9;

            $query = Blog::join('blog_translations as t', function ($join) use ($locale) {
                $join->on('blogs.id', '=', 't.blog_id')
                    ->where('t.lang', $locale);
            })
                ->join('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
                ->join('blog_category_translations as ct', function ($join) use ($locale) {
                    $join->on('blog_categories.id', '=', 'ct.category_id')
                        ->where('ct.lang', $locale);
                })

                ->where('blogs.status', 'active')
                ->where('blogs.is_public', true)
                ->select(
                    't.title',
                    'blogs.slug',
                    'blogs.image',
                    'ct.title as category_title',
                    'blogs.created_at',
                    'blogs.updated_at'
                )
                ->orderBy('blogs.created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('t.title', 'like', "%{$search}%");
            }

            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            $paginate->getCollection()->transform(function ($blog) {
                return [
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'image' => $blog->image ? asset('storage/' . $blog->image) : null,
                    'category' => $blog->category_title,
                    'created_at' => $blog->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $blog->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return ApiResponse::success(
                __('messages.blog.success.listBlogs'),
                [
                    'pagination' => $paginate,
                    'filters' => $request->only('search')
                ]
            );

        } catch (\Throwable $e) {
            Log::error('Error en list_blogs: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.blog.error.listBlogs'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified resource in client with lang.
     */
    public function get_blog_client($id, Request $request)
    {
        try {
            $lang = $request->query('locale', app()->getLocale());

            $blog = Blog::with(['translation' => fn($q) => $q->where('lang', $lang)])
                ->where('status', 'active')
                ->where('is_public', true)
                ->where(function ($query) use ($id) {
                    $query->where('id', $id)
                        ->orWhere('slug', $id);
                })
                ->firstOrFail();

            $translation = $blog->translation ?? null;

            $data = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'image' => $blog->image ? asset('storage/' . $blog->image) : null,
                'title' => $translation->title ?? null,
                'content' => $translation->content ?? null,
                'category' => $blog->category_id,
                'status' => $blog->status,
            ];

            return ApiResponse::success(
                __('messages.blog.success.getBlog'),
                $data
            );
        } catch (\Throwable $e) {
            Log::error('Error en get_blog: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.blog.error.getBlog'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function list_blog(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 9;

            $query = Blog::join('blog_translations as t', function ($join) use ($locale) {
                $join->on('blogs.id', '=', 't.blog_id')
                    ->where('t.lang', $locale);
            })
                ->join('blog_categories', 'blog_categories.id', '=', 'blogs.category_id')
                ->join('blog_category_translations as ct', function ($join) use ($locale) {
                    $join->on('blog_categories.id', '=', 'ct.category_id')
                        ->where('ct.lang', $locale);
                })
                ->select(
                    'blogs.id',
                    't.title',
                    'blogs.slug',
                    'blogs.status',
                    'blogs.image',
                    'ct.title as category',
                    'blogs.created_at',
                    'blogs.updated_at'
                )
                ->orderBy('blogs.created_at', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('t.title', 'like', "%{$search}%");
            }

            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            $paginate->getCollection()->transform(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'status' => $blog->status,
                    'category' => $blog->category_title,
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
                    'pagination' => $paginate,
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
                ['exception' => $e->getMessage()],
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
            $lang = 'es';

            $blog = Blog::with(['translation' => fn($q) => $q->where('lang', $lang)])
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->firstOrFail();

            $translation = $blog->translation ?? null;

            $data = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'image' => $blog->image ? asset('storage/' . $blog->image) : null,
                'title' => $translation->title ?? null,
                'content' => $translation->content ?? null,
                'category' => $blog->category_id,
                'status' => $blog->status,
            ];

            return ApiResponse::success(
                __('messages.blog.success.getBlog'),
                $data
            );
        } catch (\Throwable $e) {
            Log::error('Error en get_blog: ' . $e->getMessage());

            return ApiResponse::error(
                __('messages.blog.error.getBlog'),
                ['exception' => $e->getMessage()],
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

            // Crear blog
            $blog = Blog::create([
                'user_id' => auth()->id(),
                'category_id' => $data['category_id'] ?? 1,
                'writer' => auth()->user()->name,
                'status' => $data['status'] ?? 'inactive',
                'view' => 0,
                'slug' => '', // Se genera después con el título traducido
                'image' => '',
            ]);

            // Guardar imagen si existe
            if (!empty($data['image'])) {
                $blog->image = Helpers::saveWebpFile($data['image'], "images/blog/{$blog->id}/blog_image");
                $blog->save();
            }

            // Crear traducciones
            $this->createTranslations($blog, $data, $translator);

            // Generar slug basado en el título en español
            $titleEs = $blog->translations()->where('lang', 'en')->first()->title;
            $blog->update([
                'slug' => Helpers::generateUniqueSlug(Blog::class, $titleEs, 'slug')
            ]);

            DB::commit();

            return ApiResponse::success(
                __('messages.blog.success.createBlog'),
                $blog->load('translations'),
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en create_blog', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
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
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                // Español: valores originales
                BlogTranslation::create([
                    'blog_id' => $blog->id,
                    'lang' => $lang,
                    'title' => $data['title'] ?? 'Título por defecto',
                    'content' => $data['content'] ?? '',
                ]);
                continue;
            }

            try {
                // Preparar textos para traducir
                $textsToTranslate = [
                    $data['title'] ?? 'Título por defecto',
                    $data['content'] ?? ''
                ];

                // UNA sola llamada API por idioma
                $translated = $translator->translate($textsToTranslate, $lang);

                BlogTranslation::create([
                    'blog_id' => $blog->id,
                    'lang' => $lang,
                    'title' => $translated[0] ?? ($data['title'] ?? 'Título por defecto'),
                    'content' => $translated[1] ?? ($data['content'] ?? ''),
                ]);

            } catch (\Exception $e) {
                \Log::error("Translation error for blog {$blog->id} to {$lang}: " . $e->getMessage());

                // Fallback: crear con texto original
                BlogTranslation::create([
                    'blog_id' => $blog->id,
                    'lang' => $lang,
                    'title' => $data['title'] ?? 'Título por defecto',
                    'content' => $data['content'] ?? '',
                ]);
            }
        }
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
            $locale = $request->query('locale', 'es');

            $this->updateBlogData($blog, $data);

            $this->updateTranslations($blog, $data, $translator);

            DB::commit();

            $blog->load(['translations' => fn($q) => $q->where('lang', $locale), 'category']);

            return ApiResponse::success(
                __('messages.blog.success.updateBlog'),
                $this->formatBlogResponse($blog, $locale)
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
        if (isset($data['category']) && $data['category'] !== $blog->category_id) {
            $updates['category_id'] = $data['category'];
        }

        if (isset($data['status']) && $data['status'] !== $blog->status) {
            $updates['status'] = $data['status'];
        }

        if (isset($data['title'])) {
            $translationEs = $blog->translations()->where('lang', 'es')->first();
            if ($translationEs && $data['title'] !== $translationEs->title) {
                $updates['slug'] = Helpers::generateUniqueSlug(Blog::class, $data['title'], 'slug');
            }
        }

        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            if (!empty($blog->image) && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }

            $updates['image'] = Helpers::saveWebpFile($data['image'], "images/blog/{$blog->id}/blog_image");
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
        // Obtener traducción en español
        $translationEs = $blog->translations()->firstOrCreate(['blog_id' => $blog->id, 'lang' => 'es']);

        $titleChanged = isset($data['title']) && $data['title'] !== $translationEs->title;
        $contentChanged = isset($data['content']) && $data['content'] !== $translationEs->content;

        // Si no hay cambios, salir
        if (!$titleChanged && !$contentChanged) {
            return;
        }

        // Actualizar español
        $translationEs->update([
            'title' => $data['title'] ?? $translationEs->title,
            'content' => $data['content'] ?? $translationEs->content,
        ]);

        // Preparar textos para traducir
        $textsToTranslate = [];
        $changedFields = [];

        if ($titleChanged) {
            $textsToTranslate[] = $data['title'];
            $changedFields[] = 'title';
        }

        if ($contentChanged) {
            $textsToTranslate[] = $data['content'];
            $changedFields[] = 'content';
        }

        // Traducir a otros idiomas
        foreach ($this->languages as $lang) {
            if ($lang === 'es') {
                continue;
            }

            try {
                // UNA sola llamada API con todos los textos cambiados
                $translated = $translator->translate($textsToTranslate, $lang);

                $translation = $blog->translations()->firstOrCreate([
                    'blog_id' => $blog->id,
                    'lang' => $lang
                ]);

                // Actualizar solo los campos que cambiaron
                $updatedFields = [];
                foreach ($changedFields as $index => $field) {
                    $updatedFields[$field] = $translated[$index] ?? $translation->$field;
                }

                if (!empty($updatedFields)) {
                    $translation->update($updatedFields);
                }

            } catch (\Exception $e) {
                \Log::error("Translation error for blog {$blog->id} to {$lang}: " . $e->getMessage());
            }
        }
    }

    /**
     * Formatear respuesta del blog
     */
    private function formatBlogResponse(Blog $blog, string $locale): array
    {
        $translation = $blog->translations->first();

        return [
            'id' => $blog->id,
            'slug' => $blog->slug,
            'category_id' => $blog->category_id,
            'category' => $blog->category ? [
                'id' => $blog->category->id,
                'name' => $blog->category->name,
                'slug' => $blog->category->slug,
            ] : null,
            'status' => $blog->status,
            'writer' => $blog->writer,
            'view' => $blog->view,
            'image' => $blog->image ? url("storage/{$blog->image}") : null,
            'title' => $translation?->title ?? '',
            'content' => $translation?->content ?? '',
            'lang' => $locale,
            'created_at' => $blog->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $blog->updated_at->format('Y-m-d H:i:s'),
        ];
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

    /**
     * Get all categories
     */
    public function get_categories(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());

            $categories = BlogCategory::join('blog_category_translations as t', function ($join) use ($locale) {
                $join->on('blog_categories.id', '=', 't.category_id')->where('t.lang', $locale);
            })
                ->select(
                    'blog_categories.id',
                    'blog_categories.code',
                    't.title'
                )
                ->get()
                ->map(function ($categories) {
                    return [
                        'id' => $categories->id,
                        'code' => $categories->code,
                        'title' => $categories->title
                    ];
                });

            return ApiResponse::success(
                __('messages.blog.success.getCategories'),
                ['categories' => $categories]
            );
        } catch (\Throwable $e) {
            return ApiResponse::error(
                __('messages.blog.error.getCategories'),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

}

