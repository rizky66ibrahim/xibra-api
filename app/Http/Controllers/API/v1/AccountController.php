<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\AccountResource;
use App\Helpers\ApiResponse;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->boolean('is_active'), fn($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($accounts, 'Accounts retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255'],
            'type'     => ['required', 'string', 'in:cash,bank,ewallet'],
            'provider' => ['sometimes', 'string', 'max:100'],
            'balance'  => ['sometimes', 'numeric'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $account = Account::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new AccountResource($account), 'Account created');
    }

    public function show(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new AccountResource($account), 'Account retrieved');
    }

    public function update(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'name'     => ['sometimes', 'string', 'max:255'],
            'type'     => ['sometimes', 'string', 'in:cash,bank,ewallet'],
            'provider' => ['sometimes', 'string', 'max:100'],
            'balance'  => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $account->update($validator->validated());
        return ApiResponse::success(new AccountResource($account), 'Account updated');
    }

    public function destroy(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $account->delete();
        return ApiResponse::success(null, 'Account deleted');
    }
}
