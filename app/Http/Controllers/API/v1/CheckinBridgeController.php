<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\CheckinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckinBridgeController extends Controller
{
    public function __construct(private CheckinService $checkins) {}

    // GET /integrations/telegram/checkins/due?slot=morning|evening (secret only)
    public function due(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slot' => ['required', 'string', 'in:morning,evening'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        return ApiResponse::success(
            $this->checkins->forSlot($validator->validated()['slot']),
            'Check-ins retrieved'
        );
    }
}
