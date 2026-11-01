<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SubscriptionResource;
use App\Helpers\ApiResponse;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = Subscription::where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($subscriptions, 'Subscriptions retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:255'],
            'provider'      => ['required', 'string', 'max:100'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'billing_cycle' => ['sometimes', 'string', 'in:monthly,yearly'],
            'next_due_date' => ['required', 'date'],
            'status'        => ['sometimes', 'string', 'in:active,cancelled,paused'],
            'category'      => ['sometimes', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $subscription = Subscription::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new SubscriptionResource($subscription), 'Subscription created');
    }

    public function show(Request $request, Subscription $subscription)
    {
        if ($subscription->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new SubscriptionResource($subscription), 'Subscription retrieved');
    }

    public function update(Request $request, Subscription $subscription)
    {
        if ($subscription->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['sometimes', 'string', 'max:255'],
            'provider'      => ['sometimes', 'string', 'max:100'],
            'amount'        => ['sometimes', 'numeric', 'min:0'],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'billing_cycle' => ['sometimes', 'string', 'in:monthly,yearly'],
            'next_due_date' => ['sometimes', 'date'],
            'status'        => ['sometimes', 'string', 'in:active,cancelled,paused'],
            'category'      => ['sometimes', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $subscription->update($validator->validated());
        return ApiResponse::success(new SubscriptionResource($subscription), 'Subscription updated');
    }

    public function destroy(Request $request, Subscription $subscription)
    {
        if ($subscription->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $subscription->delete();
        return ApiResponse::success(null, 'Subscription deleted');
    }
}
