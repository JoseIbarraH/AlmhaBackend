<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\Blog\UpdateRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
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
            $perPage = 8;

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
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'status' => $blog->status,
                    'category' => $blog->category,
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
    public function get_blog($id, Request $request)
    {
        try {
            $blog = Blog::with(['blogTranslations' => fn($q) => $q->where('lang', 'es')])
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
    public function create_blog(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'title' => 'string'
            ]);
            $data['title'] = $data['title'] ?? 'Título por defecto';

            $blog = Blog::create([
                'user_id' => auth()->id() ?? '1',
                'slug' => $this->generateUniqueSlug($data['title']), // o lo generas luego con el título
                'image' => null,
                'category' => 'general',
                'writer' => auth()->user()->name ?? 'Administrador',
                'status' => 'inactive',
                'view' => 0,
            ]);

            BlogTranslation::create([
                'blog_id' => $blog->id,
                'lang' => 'es',
                'title' => $data['title'],
                'content' => '',
            ]);

            BlogTranslation::create([
                'blog_id' => $blog->id,
                'lang' => 'en',
                'title' => Helpers::translateBatch([$data['title']], 'es', 'en')[0] ?? '',
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
        Log::info('Datos: ', $request->all());
        DB::beginTransaction();
        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validated();
            $locale = $request->query('locale', app()->getLocale()); // 'es' por defecto
            $updates = [];

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
            if (!empty($data['image'])) {
                if ($data['image'] instanceof UploadedFile) {
                    if (!empty($blog->image) && Storage::disk('public')->exists($blog->image)) {
                        Storage::disk('public')->delete($blog->image);
                    }

                    $path = Helpers::saveWebpFile($data['image'], "images/blog/{$blog->id}/blog_image");
                    $updates['image'] = $path;
                }
            }

            if (!empty($updates)) {
                $blog->update($updates);
            }

            // --- ACTUALIZAR CONTENIDO ---
            if (isset($data['content']) && is_string($data['content'])) {
                if ($data['content'] !== ($translationEs->content ?? null)) {
                    $translationEs->content = $data['content'];
                    $translationEs->save();

                    // Traducción automática al inglés
                    $translationEn = $blog->blogTranslations()->firstOrNew(['lang' => 'en']);
                    $tr = Helpers::translateBatch([$data['content']], 'es', 'en')[0] ?? $translationEs->content;
                    Log::info("Tenemos traduccion? ", [$tr]);
                    $translationEn->content = $tr;
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

    public function update_status(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $team = Blog::findOrFail($id);
            $data = $request->validate([
                'status' => 'required|in:active,inactive'
            ]);
            $team->update(['status' => $data['status']]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('messages.teamMember.success.updateStatus'),
                'data' => $team
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('messages.teamMember.error.updateStatus'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function upload_image(Request $request, $id)
    {
        // Validar que el usuario esté autenticado
        if (!auth()->check()) {
            return response()->json([
                'error' => ['message' => 'No autorizado']
            ], 401);
        }

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
            return response()->json([
                'error' => [
                    'message' => 'Error al subir la imagen: ' . $e->getMessage()
                ]
            ], 500);
        }
    }


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

            return response()->json([
                'error' => ['message' => 'Imagen no encontrada']
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => ['message' => 'Error al eliminar imagen: ' . $e->getMessage()]
            ], 500);
        }
    }
}

