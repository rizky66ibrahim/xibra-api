<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'title'          => $this->title,
            'type'           => $this->type,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'description'    => $this->description,
            'due_date'       => $this->due_date?->toISOString(),
            'paid_amount'    => $this->paid_amount,
            'status'         => $this->status,
            'source_of_funds' => $this->source_of_funds,
            'remaining'      => $this->amount - $this->paid_amount,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
