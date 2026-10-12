<?php

namespace App\Actions\Order;

use App\Actions\Payment\PaymentGatewayManager;
use App\Enums\RestaurantStatus;
use App\Models\DeliveryLocation;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class BuildFoodOrderQuoteAction
{
    private const int MAX_AMOUNT_IN_MILLIUNITS = 999_999_999_999;

    public function __construct(private PaymentGatewayManager $paymentGateways) {}

    /**
     * @param  array{
     *   restaurant_id: int,
     *   delivery_location_id: int,
     *   items: list<array{menu_item_id: int, quantity: int}>
     * }  $validatedData
     * @return array{
     *   restaurant_id: int,
     *   delivery_location_id: int,
     *   currency_code: string,
     *   payment_method: string,
     *   items_subtotal: string,
     *   delivery_fee: string,
     *   total: string,
     *   items: list<array{menu_item_id: int, item_name: string, unit_price: string, quantity: int, line_total: string}>
     * }
     */
    public function execute(User $customer, array $validatedData): array
    {
        return DB::transaction(function () use ($customer, $validatedData): array {
            $restaurant = Restaurant::query()
                ->lockForUpdate()
                ->find($validatedData['restaurant_id']);

            if ($restaurant?->status !== RestaurantStatus::Active) {
                throw ValidationException::withMessages([
                    'restaurant_id' => 'The selected restaurant is not available.',
                ]);
            }

            $location = DeliveryLocation::query()
                ->whereBelongsTo($customer)
                ->lockForUpdate()
                ->find($validatedData['delivery_location_id']);

            if ($location === null) {
                throw ValidationException::withMessages([
                    'delivery_location_id' => 'The selected delivery location is not available.',
                ]);
            }

            $menuItems = MenuItem::query()
                ->whereKey(collect($validatedData['items'])->pluck('menu_item_id')->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $quoteItems = [];
            $itemsSubtotal = 0;
            $currencyCode = null;

            foreach ($validatedData['items'] as $requestedItem) {
                /** @var MenuItem|null $menuItem */
                $menuItem = $menuItems->get($requestedItem['menu_item_id']);

                if (
                    $menuItem === null
                    || $menuItem->restaurant_id !== $restaurant->id
                    || ! $menuItem->is_available
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected menu items are no longer available.',
                    ]);
                }

                if ($currencyCode !== null && $menuItem->currency_code !== $currencyCode) {
                    throw ValidationException::withMessages([
                        'items' => 'All selected menu items must use the same currency.',
                    ]);
                }

                $currencyCode = $menuItem->currency_code;
                $unitPrice = $this->toMilliunits($menuItem->price);
                $lineTotal = $unitPrice * $requestedItem['quantity'];

                if ($lineTotal > self::MAX_AMOUNT_IN_MILLIUNITS - $itemsSubtotal) {
                    throw ValidationException::withMessages([
                        'items' => 'The order amount exceeds the supported limit.',
                    ]);
                }

                $itemsSubtotal += $lineTotal;
                $quoteItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'unit_price' => $this->fromMilliunits($unitPrice),
                    'quantity' => $requestedItem['quantity'],
                    'line_total' => $this->fromMilliunits($lineTotal),
                ];
            }

            if ($currencyCode === null) {
                throw ValidationException::withMessages([
                    'items' => 'At least one available menu item is required.',
                ]);
            }

            $fixedFees = config('delivery.pricing.fixed_fees');

            if (! is_array($fixedFees) || ! array_key_exists($currencyCode, $fixedFees)) {
                throw new DeliveryPricingUnavailableException('Order pricing is not configured.');
            }

            $deliveryFee = $this->toMilliunits((string) $fixedFees[$currencyCode], allowZero: true);

            if ($deliveryFee > self::MAX_AMOUNT_IN_MILLIUNITS - $itemsSubtotal) {
                throw new LogicException('Configured delivery pricing exceeds the supported amount.');
            }

            $total = $itemsSubtotal + $deliveryFee;

            return [
                'restaurant_id' => $restaurant->id,
                'delivery_location_id' => $location->id,
                'currency_code' => $currencyCode,
                'payment_method' => $this->paymentGateways->default()->method()->value,
                'items_subtotal' => $this->fromMilliunits($itemsSubtotal),
                'delivery_fee' => $this->fromMilliunits($deliveryFee),
                'total' => $this->fromMilliunits($total),
                'items' => $quoteItems,
            ];
        }, attempts: 3);
    }

    private function toMilliunits(string $amount, bool $allowZero = false): int
    {
        if (! preg_match('/^\d{1,9}(?:\.\d{1,3})?$/D', $amount)) {
            throw new LogicException('An amount must be a decimal value with at most three places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $milliunits = ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');

        if ((! $allowZero && $milliunits <= 0) || $milliunits > self::MAX_AMOUNT_IN_MILLIUNITS) {
            throw new LogicException('An amount is outside the supported range.');
        }

        return $milliunits;
    }

    private function fromMilliunits(int $amount): string
    {
        return sprintf('%d.%03d', intdiv($amount, 1000), $amount % 1000);
    }
}
