<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'reminder_at' => $this->reminder_at?->toISOString(),
            'recurring' => $this->recurring,
            'recurring_pattern' => $this->recurring_pattern,
            'delivery_status' => $this->delivery_status,
            'dedupe_key' => $this->dedupe_key,
            'is_sent' => $this->is_sent,
            'sent_at' => $this->sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
