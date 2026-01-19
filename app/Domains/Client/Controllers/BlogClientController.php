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
            $perPage = 9;

            $blogs = QueryBuilder::for(Blog::class)
                ->select('id', 'slug', 'status', 'writer', 'image', 'category_code', 'created_at')
                ->allowedFilters([
                    AllowedFilter::scope('search', 'RelationTitle'),
                    AllowedFilter::exact('category_code')
                ])
                ->defaultSort('-views')
                ->with(['translation', 'category.translation'])
                ->where('status', 'active')
                ->whereHas('category')
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

            return ApiResponse::success(
                "list of blogs obtained successfully",
                [
                    'pagination' => $blogs,
                    'filters' => $request->only('search'),
                    'categories' => $categories
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
}
