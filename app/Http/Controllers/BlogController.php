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


            // 🔹 Filtro de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('t.title', 'like', "%{$search}%");
            }

            // 🔹 Paginación
            $paginate = $query->paginate($perPage)->appends($request->only('search'));

            // 🔹 Convertir la ruta de la imagen a URL completa
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

            // 🔹 Datos adicionales
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
                'image' => $blog->image ? url('storage', $blog->image) : null,
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
     * Display the specified resource in client with lang.
     */
    public function get_blog_client($id, Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());

            $blog = Blog::with(['blogTranslations' => fn($q) => $q->where('lang', $locale)])
                ->where('id', $id) // o slug
                ->orWhere('slug', $id)
                ->firstOrFail();

            $data = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'image' => $blog->image ? url('storage', $blog->image) : null,
                'title' => $blog->blogTranslation->title,
                'content' => $blog->blogTranslation->content,
                'category' => $blog->category,
                'status' => $blog->status
            ];

            return ApiResponse::success(
                __('messages.blog.success.getBlog'),
                $data
            );
        } catch (\Throwable $e) {
            Log::error('Error en get_blogs: ' . $e->getMessage());

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
            $data = $request->validate([
                'title' => 'string'
            ]);
            $data['title'] = $data['title'] ?? 'Título por defecto';

            $blog = Blog::create([
                'user_id' => auth()->id(),
                'slug' => Helpers::generateUniqueSlug(Blog::class, $data['title'], 'slug'), // o lo generas luego con el título
                'image' => null,
                'category_id' => 1,
                'writer' => auth()->user()->name,
                'status' => 'inactive',
                'view' => 0,
            ]);

            foreach ($this->languages as $lang) {
                BlogTranslation::create([
                    'blog_id' => $blog->id,
                    'lang' => $lang,
                    'title' => $lang === 'es'
                        ? $data['title']
                        : $translator->translate($data['title'], $lang),
                    'content' => '',
                ]);
            }

            DB::commit();

            return ApiResponse::success(
                __('messages.blog.success.createBlog'),
                $blog,
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.blog.error.createBlog'),
                ['exception' => $e->getMessage()],
                500
            );
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
            $locale = $request->query('locale', app()->getLocale());

            $translationEs = $blog->translations()->firstOrNew(['lang' => 'es']);
            $oldTitle = $translationEs?->title;
            $oldContent = $translationEs?->content;

            $updates = [];

            if (($data['title'] ?? null) && $data['title'] !== $oldTitle) {
                $slug = Helpers::generateUniqueSlug(Blog::class, $data['title'], 'slug');
                $updates['slug'] = $slug;
            }

            if (($data['category'] ?? null) !== $blog->category_id) {
                $updates['category_id'] = $data['category'];
            }

            if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
                if (!empty($blog->image) && Storage::disk('public')->exists($blog->image)) {
                    Storage::disk('public')->delete($blog->image);
                }

                $path = Helpers::saveWebpFile($data['image'], "images/blog/{$blog->id}/blog_image");
                $updates['image'] = $path;
            }

            if (!empty($updates)) {
                $blog->update($updates);
            }

            foreach ($this->languages as $lang) {

                $translation = $blog->translations()->firstOrNew(['lang' => $lang]);

                if (isset($data['title']) && $data['title'] !== $oldTitle) {
                    $translation->title = $lang === 'es'
                        ? $data['title']
                        : $translator->translate($data['title'], $lang);
                }

                if (isset($data['content']) && $data['content'] !== $oldContent) {
                    $translation->content = $lang === 'es'
                        ? $data['content']
                        : $translator->translate($data['content'], $lang);
                }

                if ($translation->isDirty()) {
                    $translation->save();
                }
            }

            $blog->load(['translations' => fn($q) => $q->where('lang', $locale)]);
            $t = $blog->translations->first();

            $dataResponse = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'category' => $blog->category_id,
                'status' => $blog->status,
                'image' => $blog->image ? url("storage/{$blog->image}") : null,
                'title' => $t?->title,
                'content' => $t?->content,
                'updated_at' => $blog->updated_at->format('Y-m-d H:i:s'),
            ];

            DB::commit();
            return ApiResponse::success(
                __('messages.blog.success.updateBlog'),
                $dataResponse,
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error(
                __('messages.blog.error.updateBlog'),
                ['exception' => $e->getMessage()],
                500
            );
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

