<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FinanceCategoryResource;
use App\Helpers\ApiResponse;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinanceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = FinanceCategory::where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($categories, 'Finance categories retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'   => ['required', 'string', 'max:100'],
            'type'   => ['required', 'string', 'in:income,expense'],
            'color'  => ['sometimes', 'string', 'max:20'],
            'icon'   => ['sometimes', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $category = FinanceCategory::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new FinanceCategoryResource($category), 'Finance category created');
    }

    public function show(Request $request, FinanceCategory $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new FinanceCategoryResource($category), 'Finance category retrieved');
    }

    public function update(Request $request, FinanceCategory $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name'  => ['sometimes', 'string', 'max:100'],
            'type'  => ['sometimes', 'string', 'in:income,expense'],
            'color' => ['sometimes', 'string', 'max:20'],
            'icon'  => ['sometimes', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $category->update($validator->validated());
        return ApiResponse::success(new FinanceCategoryResource($category), 'Finance category updated');
    }

    public function destroy(Request $request, FinanceCategory $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $category->delete();
        return ApiResponse::success(null, 'Finance category deleted');
    }
}
