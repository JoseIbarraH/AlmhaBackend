<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Blog\UpdateRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\BlogTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Helpers;
use App\Models\Blog;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function list_blogs(Request $request)
    {
        try {
            $locale = $request->query('locale', app()->getLocale());
            $perPage = 12;

            $query = Blog::join('blog_translations as t', function ($join) use ($locale) {
                $join->on('blogs.id', '=', 't.blog_id')
                    ->where('t.lang', $locale);
            })
                ->select(
                    'blogs.id',
                    't.title',
                    'blogs.slug',
                    'blogs.status',
                    'blogs.image',
                    'blogs.category',
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
                $blog->image = $blog->image ? url("storage/{$blog->image}") : null;
                return $blog;
            });

            // 🔹 Datos adicionales
            $total = Blog::count();
            $totalActivated = Blog::where('status', 'active')->count();
            $totalDeactivated = Blog::where('status', 'inactive')->count();
            $lastBlogs = Blog::where('created_at', '>=', now()->subDays(15))->count();

            return response()->json([
                'success' => true,
                'message' => __('messages.blog.success.listBlogs'),
                'data' => [
                    'pagination' => $paginate,
                    'filters' => $request->only('search'),
                    'stats' => [
                        'total' => $total,
                        'totalActivated' => $totalActivated,
                        'totalDeactivated' => $totalDeactivated,
                        'lastBlogs' => $lastBlogs,
                    ],
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en list_blogs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.blog.error.listBlogs'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function get_blog($id, Request $request)
    {
        try {
            $blog = Blog::with(['blogTranslations' => fn($q) => $q->where('lang', 'es')])->findOrFail($id);

            $data = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'image' => url('storage', $blog->image),
                'title' => $blog->blogTranslation->title,
                'content' => $blog->blogTranslation->content,
                'category' => $blog->category
            ];

            return response()->json([
                'success' => true,
                'message' => __('messages.blog.success.getBlog'),
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en get_blogs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.blog.error.getBlog'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create_blog()
    {
        DB::beginTransaction();
        try {
            $title = 'Nuevo Blog';

            $blog = Blog::create([
                'user_id' => auth()->id() ?? '1',
                'slug' => $this->generateUniqueSlug($title), // o lo generas luego con el título
                'image' => null,
                'category' => 'general',
                'writer' => auth()->user()->name ?? 'Administrador',
                'status' => 'inactive',
                'view' => 0,
            ]);

            BlogTranslation::create([
                'blog_id' => $blog->id,
                'lang' => 'es',
                'title' => $title,
                'content' => '',
            ]);

            BlogTranslation::create([
                'blog_id' => $blog->id,
                'lang' => 'en',
                'title' => Helpers::translateBatch([$title], 'es', 'en')[0] ?? '',
                'content' => '',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.blog.success.createBlog'),
                'data' => $blog
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('messages.blog.error.createBlog'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_blog(UpdateRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validated();
            $locale = $request->query('locale', app()->getLocale()); // 'es' por defecto

            // --- ACTUALIZAR TÍTULO Y SLUG ---
            $translationEs = $blog->blogTranslations()->firstOrNew(['lang' => 'es']);
            if (($data['title'] ?? null) !== ($translationEs->title ?? null)) {
                $slug = $this->generateUniqueSlug($data['title']);
                $blog->update(['slug' => $slug]);

                // Español
                $translationEs->title = $data['title'];
                $translationEs->save();

                // Inglés (traducción automática)
                $translationEn = $blog->blogTranslations()->firstOrNew(['lang' => 'en']);
                $translationEn->title = Helpers::translateBatch([$data['title']], 'es', 'en', 'html')[0]
                    ?? $translationEs->title;
                $translationEn->save();
            }

            // --- ACTUALIZAR CATEGORÍA ---
            if (($data['category'] ?? null) !== $blog->category) {
                $blog->update(['category' => $data['category']]);
            }

            // --- ACTUALIZAR IMAGEN ---
            if (isset($data['image']) && !is_string($data['image'])) {
                if (!empty($blog->image) && Storage::disk('public')->exists($blog->image)) {
                    Storage::disk('public')->delete($blog->image);
                }

                $path = Helpers::saveWebpFile($data['image'], "images/blog/{$blog->id}/blog_image");
                $blog->update(['image' => $path]);
            }

            // --- ACTUALIZAR CONTENIDO ---
            if (isset($data['content']) && is_string($data['content'])) {
                if ($data['content'] !== ($translationEs->content ?? null)) {
                    $translationEs->content = $data['content'];
                    $translationEs->save();

                    // Traducción automática al inglés
                    $translationEn = $blog->blogTranslations()->firstOrNew(['lang' => 'en']);
                    $translationEn->content = Helpers::translateBatch([$data['content']], 'es', 'en')[0]
                        ?? $translationEs->content;
                    $translationEn->save();
                }
            }

            // --- OBTENER SOLO EL IDIOMA SOLICITADO ---
            $blog->load([
                'blogTranslations' => function ($q) use ($locale) {
                    $q->where('lang', $locale);
                }
            ]);

            $translation = $blog->blogTranslations->first();

            // --- FORMATEAR RESPUESTA ---
            $dataResponse = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'category' => $blog->category,
                'status' => $blog->status,
                'image' => $blog->image ? url("storage/{$blog->image}") : null,
                'title' => $translation?->title,
                'content' => $translation?->content,
                'updated_at' => $blog->updated_at->format('Y-m-d H:i:s'),
            ];

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.blog.success.updateBlog'),
                'data' => $dataResponse,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('messages.blog.error.updateBlog'),
                'error' => $e->getMessage(),
            ], 500);
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

            return response()->json([
                'success' => true,
                'message' => __('messages.blog.success.deleteBlog')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('messages.blog.error.deleteBlog')
            ]);
        }
    }

    public function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (Blog::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
