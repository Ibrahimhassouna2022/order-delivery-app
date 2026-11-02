<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Models\DeliveryZone;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentSetting;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualBankTransferPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_creates_bank_transfer_order_and_submits_a_receipt(): void
    {
        $context = $this->createBankTransferOrder();

        $this->assertSame('20.000', $context['order']->items_subtotal);
        $this->assertSame('6.000', $context['order']->delivery_fee);
        $this->assertSame('26.000', $context['order']->total);
        $this->assertSame('غزة - الرمال، شارع عمر المختار', $context['order']->delivery_address);
        $this->assertNull($context['order']->invoice_number);
        $this->assertNull($context['order']->payment_approved_at);
        $this->assertDatabaseHas('payment_attempts', [
            'order_id' => $context['order']->id,
            'method' => PaymentMethod::BankTransfer->value,
            'status' => PaymentStatus::Pending->value,
            'amount' => '20.000',
        ]);

        $response = $this->withToken($context['customer_token'])
            ->post("/api/v1/orders/{$context['order']->id}/payment-proof", [
                'proof' => UploadedFile::fake()->image('transfer-receipt.png'),
                'transfer_reference' => 'TRX-99881',
            ])
            ->assertCreated()
            ->assertJsonPath('data.payment_method', PaymentMethod::BankTransfer->value)
            ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value);

        $this->assertNotNull($response->json('data.payment_proof_url'));

        $attempt = PaymentAttempt::query()->where('order_id', $context['order']->id)->firstOrFail();
        $this->assertNotNull($attempt->proof_path);
        Storage::disk('public')->assertExists($attempt->proof_path);
        $this->assertSame('TRX-99881', $attempt->metadata['transfer_reference']);
    }

    public function test_restaurant_can_review_the_receipt_and_customer_receives_an_invoice(): void
    {
        $context = $this->createBankTransferOrder();
        $this->submitProof($context);

        $this->asToken($context['restaurant_token'])
            ->getJson('/api/v1/restaurant/payment-reviews')
            ->assertOk()
            ->assertJsonPath('data.0.id', $context['order']->id);

        $this->asToken($context['restaurant_token'])
            ->patchJson("/api/v1/restaurant/orders/{$context['order']->id}/status", [
                'status' => OrderStatus::RestaurantAccepted->value,
            ])
            ->assertConflict();

        $response = $this->withToken($context['restaurant_token'])
            ->postJson("/api/v1/restaurant/orders/{$context['order']->id}/payment-review", [
                'decision' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('data.payment.status', PaymentStatus::Succeeded->value);

        $invoiceNumber = $response->json('data.invoice_number');
        $this->assertStringStartsWith('INV-', $invoiceNumber);
        $this->assertNotNull($response->json('data.payment_approved_at'));

        $this->asToken($context['customer_token'])
            ->getJson("/api/v1/orders/{$context['order']->id}")
            ->assertOk()
            ->assertJsonPath('data.invoice_number', $invoiceNumber)
            ->assertJsonPath('data.payment.status', PaymentStatus::Succeeded->value)
            ->assertJsonPath('data.support_phone', '+970 59 900 5678');

        $this->asToken($context['restaurant_token'])
            ->patchJson("/api/v1/restaurant/orders/{$context['order']->id}/status", [
                'status' => OrderStatus::RestaurantAccepted->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::RestaurantAccepted->value);
    }

    public function test_admin_can_reject_a_receipt_and_customer_can_resubmit_it(): void
    {
        $context = $this->createBankTransferOrder();
        $this->submitProof($context);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $adminToken = $admin->createToken('admin')->plainTextToken;

        $this->asToken($adminToken)
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/payment-review", [
                'decision' => 'rejected',
                'rejection_reason' => 'المبلغ المحول لا يطابق قيمة الطلب.',
            ])
            ->assertOk()
            ->assertJsonPath('data.payment.status', PaymentStatus::Failed->value)
            ->assertJsonPath('data.payment.rejection_reason', 'المبلغ المحول لا يطابق قيمة الطلب.');

        $this->asToken($context['customer_token'])
            ->getJson("/api/v1/orders/{$context['order']->id}")
            ->assertOk()
            ->assertJsonPath('data.payment.rejection_reason', 'المبلغ المحول لا يطابق قيمة الطلب.');

        $this->asToken($context['customer_token'])
            ->post("/api/v1/orders/{$context['order']->id}/payment-proof", [
                'proof' => UploadedFile::fake()->image('new-receipt.png'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value)
            ->assertJsonPath('data.payment_rejection_reason', null);

        $this->asToken($adminToken)
            ->postJson("/api/v1/operations/orders/{$context['order']->id}/payment-review", [
                'decision' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('data.payment.status', PaymentStatus::Succeeded->value);
    }

    public function test_a_different_restaurant_cannot_review_the_receipt(): void
    {
        $context = $this->createBankTransferOrder();
        $this->submitProof($context);

        $otherOwner = User::factory()->create(['role' => UserRole::Restaurant]);
        $otherRestaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Active]);
        $otherRestaurant->users()->attach($otherOwner, ['membership_role' => 'owner']);
        $otherToken = $otherOwner->createToken('restaurant')->plainTextToken;

        $this->asToken($otherToken)
            ->postJson("/api/v1/restaurant/orders/{$context['order']->id}/payment-review", [
                'decision' => 'approved',
            ])
            ->assertForbidden();

        $this->assertSame(
            PaymentStatus::Pending,
            PaymentAttempt::query()->where('order_id', $context['order']->id)->firstOrFail()->status,
        );
    }

    public function test_operations_can_manage_payment_settings_and_delivery_zones(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $adminToken = $admin->createToken('admin')->plainTextToken;
        PaymentSetting::factory()->create(['support_phone' => '+970 59 111 1111']);

        $this->asToken($adminToken)
            ->putJson('/api/v1/operations/payment-settings', [
                'phone' => '+970 59 222 2222',
                'iban' => 'PS00 TEST 0000 0000 0000 0000 0000 0',
                'account_name' => 'Wasalli Test',
                'support_phone' => '+970 59 333 3333',
                'instructions' => 'حوّل ثم ارفع الإشعار.',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '+970 59 222 2222');

        $this->asToken($adminToken)
            ->postJson('/api/v1/operations/delivery-zones', [
                'name' => 'منطقة اختبار',
                'fee' => '9.500',
                'currency_code' => 'ILS',
            ])
            ->assertCreated()
            ->assertJsonPath('data.fee', '9.500');

        $zoneId = DeliveryZone::query()->where('name', 'منطقة اختبار')->value('id');

        $this->getJson('/api/v1/delivery-zones')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'منطقة اختبار');

        $this->asToken($adminToken)
            ->patchJson("/api/v1/operations/delivery-zones/{$zoneId}", [
                'fee' => '10.000',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.fee', '10.000')
            ->assertJsonPath('data.is_active', false);

        $this->getJson('/api/v1/delivery-zones')->assertOk()->assertJsonCount(0, 'data');

        $this->asToken($adminToken)
            ->deleteJson("/api/v1/operations/delivery-zones/{$zoneId}")
            ->assertNoContent();
    }

    /**
     * @return array{
     *   customer: User,
     *   customer_token: string,
     *   restaurant: Restaurant,
     *   restaurant_token: string,
     *   order: Order
     * }
     */
    private function createBankTransferOrder(): array
    {
        Storage::fake('public');
        PaymentSetting::factory()->create([
            'phone' => '+970 59 900 1234',
            'iban' => 'PS92 PALS 0000 0000 0000 0000 0000 0',
            'support_phone' => '+970 59 900 5678',
        ]);

        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Active]);
        $owner = User::factory()->create(['role' => UserRole::Restaurant]);
        $restaurant->users()->attach($owner, ['membership_role' => 'owner']);
        $menuItem = MenuItem::factory()->for($restaurant)->create([
            'name' => 'وجبة اختبار',
            'price' => '20.000',
            'currency_code' => 'ILS',
            'is_available' => true,
        ]);
        $zone = DeliveryZone::factory()->create([
            'name' => 'الرمال',
            'fee' => '6.000',
            'currency_code' => 'ILS',
            'is_active' => true,
        ]);

        $customerToken = $customer->createToken('customer')->plainTextToken;
        $restaurantToken = $owner->createToken('restaurant')->plainTextToken;

        $payload = [
            'restaurant_id' => $restaurant->id,
            'fulfillment_type' => 'delivery',
            'delivery_zone_id' => $zone->id,
            'delivery_address' => 'غزة - الرمال، شارع عمر المختار',
            'delivery_phone' => '+970 59 912 3456',
            'payment_method' => PaymentMethod::BankTransfer->value,
            'notes' => 'اتصل قبل الوصول',
            'items' => [
                ['menu_item_id' => $menuItem->id, 'quantity' => 1],
            ],
        ];

        $this->asToken($customerToken)
            ->postJson('/api/v1/orders/quote', $payload)
            ->assertOk()
            ->assertJsonPath('data.items_subtotal', '20.000')
            ->assertJsonPath('data.delivery_fee', '6.000')
            ->assertJsonPath('data.total', '26.000')
            ->assertJsonPath('data.payment_method', PaymentMethod::BankTransfer->value)
            ->assertJsonPath('data.delivery_zone.name', 'الرمال');

        $response = $this->withHeaders(['Idempotency-Key' => 'bank-transfer-order-'.$customer->id])
            ->withToken($customerToken)
            ->postJson('/api/v1/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.payment_method', PaymentMethod::BankTransfer->value)
            ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value)
            ->assertJsonPath('data.invoice_number', null);

        return [
            'customer' => $customer,
            'customer_token' => $customerToken,
            'restaurant' => $restaurant,
            'restaurant_token' => $restaurantToken,
            'order' => Order::query()->findOrFail($response->json('data.id')),
        ];
    }

    private function asToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    /**
     * @param  array{order: Order, customer_token: string}  $context
     */
    private function submitProof(array $context): void
    {
        $this->asToken($context['customer_token'])
            ->post("/api/v1/orders/{$context['order']->id}/payment-proof", [
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertCreated();
    }
}
