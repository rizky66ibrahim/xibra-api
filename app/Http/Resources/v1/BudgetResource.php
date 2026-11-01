<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'name'        => $this->name,
            'category'    => $this->category,
            'amount'      => $this->amount,
            'period'      => $this->period,
            'start_date'  => $this->start_date?->toISOString(),
            'end_date'    => $this->end_date?->toISOString(),
            'spent_amount' => $this->spent_amount,
            'remaining'   => $this->amount - $this->spent_amount,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
