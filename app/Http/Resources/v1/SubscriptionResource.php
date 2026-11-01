<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'name'           => $this->name,
            'provider'       => $this->provider,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'billing_cycle'  => $this->billing_cycle,
            'next_due_date'  => $this->next_due_date?->toISOString(),
            'status'         => $this->status,
            'category'       => $this->category,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
