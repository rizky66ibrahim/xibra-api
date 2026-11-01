<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\DebtResource;
use App\Helpers\ApiResponse;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $debts = Debt::where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($debts, 'Debts retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => ['required', 'string', 'max:255'],
            'type'          => ['required', 'string', 'in:debt,credit'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'description'   => ['nullable', 'string'],
            'due_date'      => ['sometimes', 'date'],
            'paid_amount'   => ['sometimes', 'numeric', 'min:0'],
            'status'        => ['sometimes', 'string', 'in:pending,paid,overdue'],
            'source_of_funds' => ['sometimes', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $debt = Debt::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new DebtResource($debt), 'Debt created');
    }

    public function show(Request $request, Debt $debt)
    {
        if ($debt->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new DebtResource($debt), 'Debt retrieved');
    }

    public function update(Request $request, Debt $debt)
    {
        if ($debt->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'         => ['sometimes', 'string', 'max:255'],
            'type'          => ['sometimes', 'string', 'in:debt,credit'],
            'amount'        => ['sometimes', 'numeric', 'min:0'],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'description'   => ['sometimes', 'string'],
            'due_date'      => ['sometimes', 'date'],
            'paid_amount'   => ['sometimes', 'numeric', 'min:0'],
            'status'        => ['sometimes', 'string', 'in:pending,paid,overdue'],
            'source_of_funds' => ['sometimes', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $debt->update($validator->validated());
        return ApiResponse::success(new DebtResource($debt), 'Debt updated');
    }

    public function destroy(Request $request, Debt $debt)
    {
        if ($debt->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $debt->delete();
        return ApiResponse::success(null, 'Debt deleted');
    }
}
