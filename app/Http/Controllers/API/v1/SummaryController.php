<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SummaryResource;
use App\Helpers\ApiResponse;
use App\Models\Summary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $summaries = Summary::where('user_id', $request->user()->id)
            ->when($request->filled('period'), fn($q) => $q->where('period', $request->period))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($summaries, 'Summaries retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period'      => ['required', 'string', 'in:daily,weekly,monthly'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'total_income' => ['sometimes', 'numeric', 'min:0'],
            'total_expense' => ['sometimes', 'numeric', 'min:0'],
            'net'         => ['sometimes', 'numeric'],
            'net_worth'   => ['sometimes', 'numeric'],
            'breakdown'   => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $summary = Summary::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new SummaryResource($summary), 'Summary created');
    }

    public function show(Request $request, Summary $summary)
    {
        if ($summary->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new SummaryResource($summary), 'Summary retrieved');
    }

    public function update(Request $request, Summary $summary)
    {
        if ($summary->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'period'      => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'start_date'  => ['sometimes', 'date'],
            'end_date'    => ['sometimes', 'date', 'after_or_equal:start_date'],
            'total_income' => ['sometimes', 'numeric', 'min:0'],
            'total_expense' => ['sometimes', 'numeric', 'min:0'],
            'net'         => ['sometimes', 'numeric'],
            'net_worth'   => ['sometimes', 'numeric'],
            'breakdown'   => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $summary->update($validator->validated());
        return ApiResponse::success(new SummaryResource($summary), 'Summary updated');
    }

    public function destroy(Request $request, Summary $summary)
    {
        if ($summary->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $summary->delete();
        return ApiResponse::success(null, 'Summary deleted');
    }
}
