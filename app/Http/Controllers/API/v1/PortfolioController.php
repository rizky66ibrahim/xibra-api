<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\PortfolioResource;
use App\Helpers\ApiResponse;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        $portfolios = Portfolio::where('user_id', $request->user()->id)
            ->when($request->boolean('featured'), fn($q) => $q->where('is_featured', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($portfolios, 'Portfolios retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url'   => ['nullable', 'url', 'max:500'],
            'is_public'   => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $portfolio = Portfolio::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new PortfolioResource($portfolio), 'Portfolio created');
    }

    public function show(Request $request, Portfolio $portfolio)
    {
        if ($portfolio->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new PortfolioResource($portfolio), 'Portfolio retrieved');
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        if ($portfolio->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'image_url'   => ['sometimes', 'url', 'max:500'],
            'is_public'   => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $portfolio->update($validator->validated());

        return ApiResponse::success(new PortfolioResource($portfolio), 'Portfolio updated');
    }

    public function destroy(Request $request, Portfolio $portfolio)
    {
        if ($portfolio->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $portfolio->delete();
        return ApiResponse::success(null, 'Portfolio deleted');
    }
}
