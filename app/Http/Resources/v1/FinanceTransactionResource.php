<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'finance_category_id' => $this->finance_category_id,
            'category' => $this->whenLoaded('category', fn() => new FinanceCategoryResource($this->category)),
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'reference_number' => $this->reference_number,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
