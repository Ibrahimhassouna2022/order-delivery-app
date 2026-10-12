<?php

namespace App\Actions\Customer;

use App\Models\DeliveryLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDeliveryLocationAction
{
    /**
     * @param  array{
     *   label: string,
     *   address: string,
     *   latitude?: numeric-string|int|float|null,
     *   longitude?: numeric-string|int|float|null,
     *   delivery_instructions?: string|null
     * }  $validatedData
     */
    public function execute(User $customer, array $validatedData): DeliveryLocation
    {
        return DB::transaction(function () use ($customer, $validatedData): DeliveryLocation {
            $lockedCustomer = User::query()
                ->lockForUpdate()
                ->findOrFail($customer->getKey());

            return $lockedCustomer->deliveryLocations()->create([
                ...$validatedData,
                'is_default' => ! $lockedCustomer->deliveryLocations()->exists(),
            ]);
        }, attempts: 3);
    }
}
