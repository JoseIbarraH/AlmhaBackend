<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Blog\Models\BlogCategory;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use App\Domains\Blog\Models\Blog;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Str;

class BlogClientController extends Controller
{
    public function list_blog(Request $request)
    {
        try {
            $perPage = 6;

            $query = QueryBuilder::for(Blog::class)
                ->select('id', 'slug', 'status', 'writer', 'image', 'category_code', 'created_at')
                ->allowedFilters([
                    AllowedFilter::scope('search', 'RelationTitle'),
                    AllowedFilter::exact('category_code')
                ])
                ->allowedSorts(['created_at', 'views'])
                ->with(['translation', 'category.translation'])
                ->where('status', 'active')
                ->whereHas('category');

            $blogs = (clone $query)
                ->defaultSort('-views')
                ->paginate($perPage)
                ->withQueryString();

            $blogs->getCollection()->transform(function ($blog) {

                $excerpt = Str::limit(
                    strip_tags($blog->translation?->content),
                    150
                );

                return [
                    'id' => $blog->id,
                    'title' => $blog->translation?->title,
                    'excerpts' => $excerpt,
                    'status' => $blog->status,
                    'image' => $blog->image,
                    'writer' => $blog->writer,
                    'slug' => $blog->slug,
                    'category' => $blog->category?->translation?->title,
                    'category_code' => $blog->category->code,
                    'created_at' => $blog->created_at->format('Y-m-d H:i:s')
                ];
            });

            $categories = BlogCategory::with('translation')
                ->withCount([
                    'blogs' => function ($query) {
                        $query->where('status', 'active');
                    }
                ])
                ->get()
                ->map(function ($category) {
                    return [
                        'code' => $category->code,
                        'title' => $category->translation->title ?? $category->code,
                        'count' => $category->blogs_count,
                    ];
                });

            $lastThree = (clone $query)
                ->defaultSort('-created_at')
                ->limit(3)
                ->get()
                ->transform(function ($blog) {
                    return [
                        'id' => $blog->id,
                        'title' => $blog->translation->title,
                        'slug' => $blog->slug,
                        'image' => $blog->image,
                        'created_at' => $blog->created_at
                    ];
                });

            return ApiResponse::success(
                "list of blogs obtained successfully",
                [
                    'pagination' => $blogs,
                    'filters' => $request->only('search'),
                    'categories' => $categories,
                    'last_three' => $lastThree
                ]
            );

        } catch (\Throwable $e) {
            \Log::error('Error en list_blogs: ' . $e->getMessage());

            return ApiResponse::error(
                "Error retrieving blog list",
                $e,
                500
            );
        }
    }

    public function get_blog($id)
    {
        try {
            $blog = Blog::with(['translation', 'category.translation'])
                ->orWhere('slug', $id)
                ->firstOrFail();

            $data = [
                'id' => $blog->id,
                'slug' => $blog->slug,
                'image' => $blog->image,
                'writer' => $blog->writer,
                'title' => $blog->translation->title ?? '',
                'content' => $blog->translation->content ?? '',
                'category' => $blog->category?->translation?->title,
                'category_code' => $blog->category->code,
                'status' => $blog->status,
                'created_at' => $blog->created_at,
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
                            'category' => $randomBlog->category?->translation?->title,
                            'created_at' => $randomBlog->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ];

            $blog->increment('views');

            return ApiResponse::success(
                "blog obtained successfully",
                $data
            );
        } catch (\Throwable $e) {
            \Log::error('Error en get_blog: ' . $e->getMessage());

            return ApiResponse::error(
                "Error retrieving blog",
                $e,
                500
            );
        }
    }

    public function subscribe(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->input('email');

            // Check if already subscribed
            $subscriber = \App\Domains\Client\Models\Subscriber::where('email', $email)->first();

            if ($subscriber) {
                if (!$subscriber->is_active) {
                    $subscriber->update(['is_active' => true]);
                    return ApiResponse::success("Re-subscribed successfully");
                }
                return ApiResponse::success("Already subscribed");
            }

            \App\Domains\Client\Models\Subscriber::create([
                'email' => $email,
                'is_active' => true,
                'subscribed_at' => now()
            ]);

            return ApiResponse::success("Subscribed successfully");

        } catch (\Throwable $e) {
            return ApiResponse::error(
                "Error subscribing",
                $e->getMessage(),
                500
            );
        }
    }

}
