<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BlogResource;
use App\Helpers\ApiResponse;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $blogs = Blog::where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($blogs, 'Blogs retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => ['required', 'string', 'max:255'],
            'slug'         => ['sometimes', 'string', 'max:255', 'unique:blogs'],
            'excerpt'      => ['nullable', 'string'],
            'content'      => ['required', 'string'],
            'status'       => ['sometimes', 'string', 'in:draft,published'],
            'featured_image' => ['sometimes', 'url', 'max:500'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $blog = Blog::create(array_merge($data, ['user_id' => $request->user()->id]));

        return ApiResponse::created(new BlogResource($blog), 'Blog created');
    }

    public function show(Request $request, Blog $blog)
    {
        if ($blog->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new BlogResource($blog), 'Blog retrieved');
    }

    public function update(Request $request, Blog $blog)
    {
        if ($blog->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'        => ['sometimes', 'string', 'max:255'],
            'slug'         => ['sometimes', 'string', 'max:255', 'unique:blogs,slug,' . $blog->id],
            'excerpt'      => ['sometimes', 'string'],
            'content'      => ['sometimes', 'string'],
            'status'       => ['sometimes', 'string', 'in:draft,published'],
            'featured_image' => ['sometimes', 'url', 'max:500'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        if ($request->filled('title') && empty($request->slug)) {
            $validator->validated()['slug'] = Str::slug($request->title);
        }
        if ($request->boolean('status') === 'published' && empty($blog->published_at)) {
            $validator->validated()['published_at'] = now();
        }

        $blog->update($validator->validated());
        return ApiResponse::success(new BlogResource($blog), 'Blog updated');
    }

    public function destroy(Request $request, Blog $blog)
    {
        if ($blog->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $blog->delete();
        return ApiResponse::success(null, 'Blog deleted');
    }
}
