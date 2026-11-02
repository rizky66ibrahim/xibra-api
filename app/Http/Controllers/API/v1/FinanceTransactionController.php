<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FinanceTransactionResource;
use App\Helpers\ApiResponse;
use App\Models\FinanceTransaction;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinanceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = FinanceTransaction::where('user_id', $request->user()->id)
            ->with('category')
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('finance_category_id'), fn($q) => $q->where('finance_category_id', $request->finance_category_id))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($transactions, 'Finance transactions retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'                => ['required', 'string', 'in:income,expense'],
            'amount'              => ['required', 'numeric', 'min:0'],
            'currency'            => ['sometimes', 'string', 'size:3'],
            'description'         => ['nullable', 'string'],
            'transaction_date'    => ['required', 'date'],
            'reference_number'    => ['nullable', 'string', 'max:100'],
            'finance_category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('finance_categories', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $transaction = FinanceTransaction::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new FinanceTransactionResource($transaction->load('category')), 'Finance transaction created');
    }

    public function show(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new FinanceTransactionResource($transaction->load('category')), 'Finance transaction retrieved');
    }

    public function update(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'type'                => ['sometimes', 'string', 'in:income,expense'],
            'amount'              => ['sometimes', 'numeric', 'min:0'],
            'currency'            => ['sometimes', 'string', 'size:3'],
            'description'         => ['sometimes', 'string'],
            'transaction_date'    => ['sometimes', 'date'],
            'reference_number'    => ['sometimes', 'string', 'max:100'],
            'finance_category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('finance_categories', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $transaction->update($validator->validated());
        return ApiResponse::success(new FinanceTransactionResource($transaction->load('category')), 'Finance transaction updated');
    }

    public function destroy(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $transaction->delete();
        return ApiResponse::success(null, 'Finance transaction deleted');
    }
}
