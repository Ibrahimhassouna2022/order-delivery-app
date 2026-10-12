<?php

namespace App\Actions\Order;

use App\Actions\Notification\Notifier;
use App\Actions\Payment\PaymentGatewayManager;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PlaceFoodOrderAction
{
    public function __construct(
        private BuildFoodOrderQuoteAction $buildQuote,
        private PaymentGatewayManager $paymentGateways,
        private Notifier $notifier,
    ) {}

    /**
     * @param  array{
     *   restaurant_id: int,
     *   delivery_location_id: int,
     *   items: list<array{menu_item_id: int, quantity: int}>,
     *   idempotency_key: string
     * }  $validatedData
     * @return array{order: Order, created: bool}
     */
    public function execute(User $customer, array $validatedData): array
    {
        $requestHash = $this->fingerprint($validatedData);

        try {
            return DB::transaction(function () use ($customer, $validatedData, $requestHash): array {
                $existingOrder = $this->findOrder($customer, $validatedData['idempotency_key']);

                if ($existingOrder !== null) {
                    return $this->resolveExistingOrder($existingOrder, $requestHash);
                }

                $quote = $this->buildQuote->execute($customer, $validatedData);
                $order = $customer->customerOrders()->create([
                    'restaurant_id' => $quote['restaurant_id'],
                    'delivery_location_id' => $quote['delivery_location_id'],
                    'type' => 'food',
                    'status' => OrderStatus::Placed,
                    'idempotency_key' => $validatedData['idempotency_key'],
                    'idempotency_hash' => $requestHash,
                    'currency_code' => $quote['currency_code'],
                    'items_subtotal' => $quote['items_subtotal'],
                    'delivery_fee' => $quote['delivery_fee'],
                    'total' => $quote['total'],
                    'placed_at' => now(),
                ]);

                $order->items()->createMany($quote['items']);
                $this->paymentGateways->default()->recordIntent($order);

                // Broadcasts after commit (OrderPlaced → ShouldDispatchAfterCommit)
                // so restaurant members and operations never see a rolled-back order.
                $this->notifier->orderPlaced($order);

                return [
                    'order' => $order->load(['items', 'paymentAttempts']),
                    'created' => true,
                ];
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existingOrder = $this->findOrder($customer, $validatedData['idempotency_key']);

            if ($existingOrder === null) {
                throw $exception;
            }

            return $this->resolveExistingOrder($existingOrder, $requestHash);
        }
    }

    /**
     * @param  array{
     *   restaurant_id: int,
     *   delivery_location_id: int,
     *   items: list<array{menu_item_id: int, quantity: int}>,
     *   idempotency_key: string
     * }  $validatedData
     */
    private function fingerprint(array $validatedData): string
    {
        $items = collect($validatedData['items'])
            ->map(fn (array $item): array => [
                'menu_item_id' => (int) $item['menu_item_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->sortBy('menu_item_id')
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'restaurant_id' => (int) $validatedData['restaurant_id'],
            'delivery_location_id' => (int) $validatedData['delivery_location_id'],
            'items' => $items,
        ], JSON_THROW_ON_ERROR));
    }

    private function findOrder(User $customer, string $idempotencyKey): ?Order
    {
        return $customer->customerOrders()
            ->where('idempotency_key', $idempotencyKey)
            ->with(['items', 'paymentAttempts'])
            ->first();
    }

    /**
     * @return array{order: Order, created: bool}
     */
    private function resolveExistingOrder(Order $order, string $requestHash): array
    {
        if (! hash_equals((string) $order->idempotency_hash, $requestHash)) {
            throw new IdempotencyKeyConflictException(
                'This idempotency key has already been used for a different order.',
            );
        }

        return [
            'order' => $order,
            'created' => false,
        ];
    }
}
