<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderItem $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'menu_item_id' => $item->menu_item_id,
            'item_name' => $item->item_name,
            'unit_price' => $item->unit_price,
            'quantity' => $item->quantity,
            'line_total' => $item->line_total,
        ];
    }
}
