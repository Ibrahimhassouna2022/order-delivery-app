<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DriverWalletLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverWalletEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DriverWalletLedgerEntry $entry */
        $entry = $this->resource;

        return [
            'id' => $entry->id,
            'order_id' => $entry->order_id,
            'entry_type' => $entry->entry_type->value,
            'amount' => $entry->amount,
            'currency_code' => $entry->currency_code,
            'created_at' => $entry->created_at?->toISOString(),
        ];
    }
}
