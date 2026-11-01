<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'title'          => $this->title,
            'description'    => $this->description,
            'emoji'          => $this->emoji,
            'target_amount'  => $this->target_amount,
            'current_amount' => $this->current_amount,
            'target_date'    => $this->target_date?->toISOString(),
            'progress'       => $this->target_amount > 0
                ? round(($this->current_amount / $this->target_amount) * 100, 2)
                : 0,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
