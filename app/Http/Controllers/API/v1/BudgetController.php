<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BudgetResource;
use App\Helpers\ApiResponse;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $budgets = Budget::where('user_id', $request->user()->id)
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->boolean('is_active'), fn($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($budgets, 'Budgets retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => ['required', 'string', 'max:255'],
            'category'   => ['required', 'string', 'max:100'],
            'amount'     => ['required', 'numeric', 'min:0'],
            'period'     => ['sometimes', 'string', 'in:monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['sometimes', 'date'],
            'is_active'  => ['sometimes', 'boolean'],
            'finance_category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('finance_categories', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $budget = Budget::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id, 'spent_amount' => 0]
        ));

        return ApiResponse::created(new BudgetResource($budget), 'Budget created');
    }

    public function show(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new BudgetResource($budget), 'Budget retrieved');
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name'       => ['sometimes', 'string', 'max:255'],
            'category'   => ['sometimes', 'string', 'max:100'],
            'amount'     => ['sometimes', 'numeric', 'min:0'],
            'period'     => ['sometimes', 'string', 'in:monthly,yearly'],
            'start_date' => ['sometimes', 'date'],
            'end_date'   => ['sometimes', 'date'],
            'is_active'  => ['sometimes', 'boolean'],
            'finance_category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('finance_categories', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $budget->update($validator->validated());
        return ApiResponse::success(new BudgetResource($budget), 'Budget updated');
    }

    public function destroy(Request $request, Budget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $budget->delete();
        return ApiResponse::success(null, 'Budget deleted');
    }
}
