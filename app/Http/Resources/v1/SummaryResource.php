<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'period'      => $this->period,
            'start_date'  => $this->start_date?->toISOString(),
            'end_date'    => $this->end_date?->toISOString(),
            'total_income' => $this->total_income,
            'total_expense' => $this->total_expense,
            'net'         => $this->net,
            'net_worth'   => $this->net_worth,
            'breakdown'   => $this->breakdown,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
