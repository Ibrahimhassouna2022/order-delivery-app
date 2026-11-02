<?php

namespace Database\Seeders;

use App\Enums\DriverAssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantOnboardingMethod;
use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Enums\WalletEntryType;
use App\Models\AuditLog;
use App\Models\DeliveryLocation;
use App\Models\DeliveryZone;
use App\Models\DriverAssignment;
use App\Models\DriverLocation;
use App\Models\DriverProfile;
use App\Models\DriverRating;
use App\Models\DriverWalletLedgerEntry;
use App\Models\LocationConsent;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentAttempt;
use App\Models\PaymentSetting;
use App\Models\Restaurant;
use App\Models\RestaurantRating;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's official default data for Gaza.
     */
    public function run(): void
    {
        // -----------------------------------------------------------------
        // 1. Demo accounts — one or more per role. Password: "password".
        // -----------------------------------------------------------------
        $admin = $this->upsertUser('admin@wasalli.app', 'فريق العمليات والرقابة', UserRole::Admin);

        $customer = $this->upsertUser('customer@wasalli.app', 'أحمد خليل', UserRole::Customer);
        $customerB = $this->upsertUser('customer2@wasalli.app', 'سارة عبد الله', UserRole::Customer);

        $driver = $this->upsertUser('driver@wasalli.app', 'محمود النجار', UserRole::Driver);
        $driverB = $this->upsertUser('driver2@wasalli.app', 'يوسف الحسن', UserRole::Driver);
        $driverC = $this->upsertUser('driver3@wasalli.app', 'خالد أبو ندى', UserRole::Driver);
        $driverD = $this->upsertUser('driver4@wasalli.app', 'سامي مقداد', UserRole::Driver);

        $restaurantManager = $this->upsertUser('restaurant@wasalli.app', 'إدارة مطعم بالميرا', UserRole::Restaurant);
        $pizzaManager = $this->upsertUser('restaurant2@wasalli.app', 'إدارة بيتزا روما', UserRole::Restaurant);
        $ameerManager = $this->upsertUser('restaurant3@wasalli.app', 'إدارة شاورما الأمير', UserRole::Restaurant);
        $bahrManager = $this->upsertUser('restaurant4@wasalli.app', 'إدارة مطعم البحار', UserRole::Restaurant);

        // -----------------------------------------------------------------
        // 2. Customer delivery locations.
        // -----------------------------------------------------------------
        $customerHome = $this->upsertLocation($customer, 'المنزل', [
            'address' => 'غزة - حي الرمال، مفترق السرايا بجوار برج الشروق',
            'latitude' => 31.5140,
            'longitude' => 34.4480,
            'delivery_instructions' => 'الاتصال عند الوصول إلى السرايا',
            'is_default' => true,
        ]);

        $this->upsertLocation($customer, 'العمل', [
            'address' => 'غزة - شارع عمر المختار، برج الجلاء، الطابق الرابع',
            'latitude' => 31.5185,
            'longitude' => 34.4530,
            'delivery_instructions' => 'تسليم الاستقبال في الطابق الأرضي',
            'is_default' => false,
        ]);

        $customerBHome = $this->upsertLocation($customerB, 'المنزل', [
            'address' => 'غزة - حي النصر، شارع الوحدة، عمارة الأمل',
            'latitude' => 31.5240,
            'longitude' => 34.4590,
            'delivery_instructions' => 'الطابق الثاني - جرس عائلة عبد الله',
            'is_default' => true,
        ]);

        // -----------------------------------------------------------------
        // 3. Restaurants, managers and menus.
        // -----------------------------------------------------------------
        $palmyra = $this->upsertRestaurant([
            'name' => 'مطعم بالميرا',
            'description' => 'شاورما على الفحم • مشاوي • وجبات سريعة وعائلية',
            'address' => 'غزة - شارع عمر المختار',
            'phone' => '+970 59 111 2233',
            'latitude' => 31.5200,
            'longitude' => 34.4550,
            'status' => RestaurantStatus::Active,
        ], $restaurantManager, [
            ['name' => 'وجبة شاورما عربي دبل', 'price' => 22.000, 'description' => 'شاورما دجاج مشوية مع ثومية، بطاطا مقلية ومخللات شامية'],
            ['name' => 'ساندويش شاورما صاروخ صاج', 'price' => 14.000, 'description' => 'خبز صاج مقرمش، طحينية وسلطة خضار'],
            ['name' => 'صحن ميكس مشاوي كباب وشقف', 'price' => 45.000, 'description' => 'نصف كيلو كباب ولحم مشوي على الفحم مع مقبلات وسلطات'],
            ['name' => 'كولا بارد 330 مل', 'price' => 4.000, 'description' => 'مشروب غازي مثلج'],
        ]);

        $pizzaRoma = $this->upsertRestaurant([
            'name' => 'بيتزا روما الإيطالية',
            'description' => 'بيتزا إيطالية بالفرن الحجري • معجنات وكالزوني',
            'address' => 'غزة - شارع النصر، بالقرب من مفترق اللبابيدي',
            'phone' => '+970 59 444 5566',
            'latitude' => 31.5280,
            'longitude' => 34.4600,
            'status' => RestaurantStatus::Active,
        ], $pizzaManager, [
            ['name' => 'بيتزا مارغريتا عائلية', 'price' => 25.000, 'description' => 'صلصة طماطم فاخرة، جبنة موزاريلا إيطالية وريحان طازج'],
            ['name' => 'بيتزا خضار مشكلة سوبريم', 'price' => 28.000, 'description' => 'فطر طازج، فلفل رومي، زيتون أسود وجبنة موزاريلا'],
            ['name' => 'كالزوني دجاج بالجبنة الذائبة', 'price' => 20.000, 'description' => 'عجينة محشوة دجاج متبل وجبنة ذائبة بالفرن'],
            ['name' => 'عصير ليمون بالنعناع', 'price' => 7.000, 'description' => 'ليمون طازج مع نعناع مثلج'],
        ]);

        $alAmeer = $this->upsertRestaurant([
            'name' => 'مطعم شاورما الأمير',
            'description' => 'أشهى ساندويشات الشاورما والبرجر الطازج',
            'address' => 'غزة - حي الرمال، بالقرب من مفترق ضبيط',
            'phone' => '+970 59 777 8899',
            'latitude' => 31.5120,
            'longitude' => 34.4440,
            'status' => RestaurantStatus::Active,
        ], $ameerManager, [
            ['name' => 'وجبة شاورما عربي سوبر', 'price' => 20.000, 'description' => 'شاورما مع بطاطا وسلطات وثومية حارة'],
            ['name' => 'برجر لحم بلدي دبل تشيز', 'price' => 18.000, 'description' => 'شريحتا لحم مع جبنة شيدر وبطاطا مقلية'],
            ['name' => 'عصير برتقال طبيعي طازج', 'price' => 6.000, 'description' => 'كأس عصير برتقال طبيعي بارد'],
        ]);

        $alBahr = $this->upsertRestaurant([
            'name' => 'مطعم البحار للأسماك والمأكولات البحرية',
            'description' => 'سمك طازج مقلي ومشوي • جمبري وسلطات بحرية',
            'address' => 'غزة - شارع الرشيد الساحلي، بجوار الميناء',
            'phone' => '+970 59 222 3344',
            'latitude' => 31.5320,
            'longitude' => 34.4460,
            'status' => RestaurantStatus::Active,
        ], $bahrManager, [
            ['name' => 'وجبة دنيس بلدي مشوي على الفحم', 'price' => 40.000, 'description' => 'سمكة دنيس مشوية مع أرز صيادية وسلطات وطحينة'],
            ['name' => 'صحن جمبري مقلي كرسبي', 'price' => 35.000, 'description' => 'جمبري طازج مقرمش مع صوص التارتار والبطاطا'],
            ['name' => 'سلطة بحرية', 'price' => 12.000, 'description' => 'خضار طازجة مع جمبري وليمون'],
        ]);

        $this->upsertRestaurant([
            'name' => 'مطعم النخبة الجديد',
            'description' => 'مطعم عائلي جديد بانتظار اعتماد العمليات',
            'address' => 'غزة - شارع الجندي، مقابل مستشفى الشفاء',
            'phone' => '+970 59 333 4455',
            'latitude' => 31.5245,
            'longitude' => 34.4455,
            'status' => RestaurantStatus::Pending,
        ]);

        // -----------------------------------------------------------------
        // 4. Manual bank-transfer account and Gaza delivery zones.
        // -----------------------------------------------------------------
        PaymentSetting::query()->updateOrCreate([], [
            'phone' => '+970 59 900 1234',
            'iban' => 'PS92 PALS 0000 0000 0000 0000 0000 0',
            'account_name' => 'Wasalli Delivery',
            'support_phone' => '+970 59 900 5678',
            'instructions' => 'حوّل قيمة الطلب إلى الرقم أو الآيبان أعلاه، ثم ارفع صورة إشعار التحويل. لا تشمل الحوالة أجرة التوصيل، وتُدفع نقداً للسائق عند التسليم.',
        ]);

        foreach ([
            ['name' => 'الرمال', 'fee' => '5.000', 'estimated_time' => '15 - 20 دقيقة', 'sort_order' => 1],
            ['name' => 'تل الهوا', 'fee' => '6.000', 'estimated_time' => '20 - 25 دقيقة', 'sort_order' => 2],
            ['name' => 'الشيخ رضوان', 'fee' => '6.000', 'estimated_time' => '20 - 25 دقيقة', 'sort_order' => 3],
            ['name' => 'التفاح', 'fee' => '5.500', 'estimated_time' => '20 - 25 دقيقة', 'sort_order' => 4],
            ['name' => 'الزيتون', 'fee' => '6.500', 'estimated_time' => '25 - 30 دقيقة', 'sort_order' => 5],
            ['name' => 'الشجاعية', 'fee' => '7.000', 'estimated_time' => '25 - 30 دقيقة', 'sort_order' => 6],
            ['name' => 'جباليا', 'fee' => '8.000', 'estimated_time' => '30 - 35 دقيقة', 'sort_order' => 7],
            ['name' => 'خان يونس', 'fee' => '12.000', 'estimated_time' => '40 - 50 دقيقة', 'sort_order' => 8],
            ['name' => 'رفح', 'fee' => '15.000', 'estimated_time' => '50 - 60 دقيقة', 'sort_order' => 9],
        ] as $zone) {
            DeliveryZone::query()->updateOrCreate(
                ['name' => $zone['name']],
                [
                    'fee' => $zone['fee'],
                    'estimated_time' => $zone['estimated_time'],
                    'currency_code' => 'ILS',
                    'is_active' => true,
                    'sort_order' => $zone['sort_order'],
                ],
            );
        }

        // -----------------------------------------------------------------
        // 5. Driver profiles, live locations and consent records.
        // Two active available drivers for testing fair dispatch & timeout:
        // Driver 1 (Mahmoud) has 0 deliveries -> selected first.
        // Driver 2 (Yousef) has 1 delivery -> selected if Driver 1 times out.
        // -----------------------------------------------------------------
        $this->upsertDriver($driver, [
            'is_available' => true,
            'completed_deliveries_count' => 0,
            'phone' => '+970 59 888 1234',
            'latitude' => 31.5180,
            'longitude' => 34.4520,
        ]);

        $this->upsertDriver($driverB, [
            'is_available' => true,
            'completed_deliveries_count' => 1,
            'phone' => '+970 59 777 5678',
            'latitude' => 31.5265,
            'longitude' => 34.4565,
        ]);

        $this->upsertDriver($driverC, [
            'is_available' => true,
            'completed_deliveries_count' => 12,
            'latitude' => 31.5160,
            'longitude' => 34.4470,
        ]);

        // Free driver with the lowest workload: auto-dispatch selects him first.
        $this->upsertDriver($driverD, [
            'is_available' => true,
            'completed_deliveries_count' => 0,
            'latitude' => 31.5210,
            'longitude' => 34.4500,
        ]);

        $this->upsertConsent($customer);
        $this->upsertConsent($driver);
        $this->upsertConsent($driverB);
        $this->upsertConsent($driverC);
        $this->upsertConsent($driverD);

        // -----------------------------------------------------------------
        // 6. Clean state: No pre-seeded demo orders so testing starts from 0.
        // -----------------------------------------------------------------
        $this->upsertAudit($admin, 'restaurant.onboarded', $palmyra, [
            'method' => RestaurantOnboardingMethod::Assisted->value,
            'status' => RestaurantStatus::Active->value,
        ]);

        $this->upsertAudit($admin, 'restaurant.pending_review', Restaurant::query()->where('name', 'مطعم النخبة الجديد')->firstOrFail(), [
            'status' => RestaurantStatus::Pending->value,
        ]);
    }

    /**
     * Create or refresh a demo account and force its role.
     *
     * The `role` attribute is intentionally not mass assignable, so it is
     * always written explicitly after the user record exists.
     */
    private function upsertUser(string $email, string $name, UserRole $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertLocation(User $customer, string $label, array $attributes): DeliveryLocation
    {
        return DeliveryLocation::query()->updateOrCreate(
            ['user_id' => $customer->id, 'label' => $label],
            $attributes,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{name: string, price: float, description: string}>  $items
     */
    private function upsertRestaurant(array $attributes, ?User $manager = null, array $items = []): Restaurant
    {
        $restaurant = Restaurant::query()->updateOrCreate(
            ['name' => $attributes['name']],
            [
                ...$attributes,
                'onboarding_method' => RestaurantOnboardingMethod::Assisted,
                'assisted_onboarding_completed_at' => ($attributes['status'] ?? null) === RestaurantStatus::Active
                    ? now()
                    : null,
            ],
        );

        if ($manager !== null) {
            $restaurant->users()->syncWithoutDetaching([
                $manager->id => ['membership_role' => 'manager'],
            ]);
        }

        foreach ($items as $item) {
            MenuItem::query()->updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => $item['name']],
                [
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'currency_code' => 'ILS',
                    'is_available' => true,
                ],
            );
        }

        return $restaurant;
    }

    /**
     * @param  array<string, mixed>  $profileAttributes
     */
    private function upsertDriver(User $driver, array $profileAttributes): void
    {
        DriverProfile::query()->updateOrCreate(
            ['user_id' => $driver->id],
            [
                'phone' => $profileAttributes['phone'] ?? '+970 59 888 1234',
                'is_available' => $profileAttributes['is_available'],
                'availability_updated_at' => now(),
                'completed_deliveries_count' => $profileAttributes['completed_deliveries_count'],
            ],
        );

        DriverLocation::query()->updateOrCreate(
            ['driver_user_id' => $driver->id],
            [
                'latitude' => $profileAttributes['latitude'],
                'longitude' => $profileAttributes['longitude'],
                'recorded_at' => now(),
            ],
        );
    }

    private function upsertConsent(User $user): void
    {
        LocationConsent::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'granted_at' => now()->subDays(30),
                'policy_version' => '2026-01',
            ],
        );
    }

    /**
     * @param  array<int, array{name: string, quantity: int}>  $lines
     */
    private function upsertOrder(
        User $customer,
        Restaurant $restaurant,
        DeliveryLocation $location,
        string $idempotencyKey,
        OrderStatus $status,
        array $lines,
        CarbonInterface $placedAt,
        float $deliveryFee,
        ?User $cancelledBy = null,
        ?CarbonInterface $cancelledAt = null,
    ): Order {
        $menuItems = $restaurant->menuItems()
            ->whereIn('name', array_column($lines, 'name'))
            ->get()
            ->keyBy('name');

        $subtotal = 0.0;
        $orderLines = [];

        foreach ($lines as $line) {
            $menuItem = $menuItems->get($line['name']);

            if ($menuItem === null) {
                throw new RuntimeException("Menu item [{$line['name']}] is missing for restaurant [{$restaurant->name}].");
            }

            $lineTotal = (float) $menuItem->price * $line['quantity'];
            $subtotal += $lineTotal;

            $orderLines[] = [
                'menu_item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'unit_price' => $menuItem->price,
                'quantity' => $line['quantity'],
                'line_total' => number_format($lineTotal, 3, '.', ''),
            ];
        }

        $order = Order::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'idempotency_key' => $idempotencyKey],
            [
                'restaurant_id' => $restaurant->id,
                'delivery_location_id' => $location->id,
                'type' => 'food',
                'status' => $status,
                'idempotency_hash' => hash('sha256', $idempotencyKey),
                'currency_code' => 'ILS',
                'items_subtotal' => number_format($subtotal, 3, '.', ''),
                'delivery_fee' => number_format($deliveryFee, 3, '.', ''),
                'total' => number_format($subtotal + $deliveryFee, 3, '.', ''),
                'placed_at' => $placedAt,
                'cancelled_by_user_id' => $cancelledBy?->id,
                'cancelled_at' => $cancelledAt,
            ],
        );

        $order->items()->delete();

        foreach ($orderLines as $orderLine) {
            OrderItem::query()->create(['order_id' => $order->id, ...$orderLine]);
        }

        return $order;
    }

    /**
     * @param  array{expires_at?: CarbonInterface, responded_at?: CarbonInterface, picked_up_at?: CarbonInterface, delivered_at?: CarbonInterface}  $timestamps
     */
    private function upsertAssignment(
        Order $order,
        User $driver,
        DriverAssignmentStatus $status,
        array $timestamps = [],
    ): DriverAssignment {
        return DriverAssignment::query()->updateOrCreate(
            ['order_id' => $order->id, 'driver_user_id' => $driver->id],
            [
                'status' => $status,
                'distance_meters' => 2500,
                'fare' => $order->delivery_fee,
                'currency_code' => $order->currency_code,
                'offered_at' => $order->placed_at?->copy()->addMinute() ?? now()->subMinute(),
                'responded_at' => $timestamps['responded_at'] ?? null,
                'picked_up_at' => $timestamps['picked_up_at'] ?? null,
                'delivered_at' => $timestamps['delivered_at'] ?? null,
                'expires_at' => $timestamps['expires_at'] ?? null,
            ],
        );
    }

    private function creditDeliveredOrder(Order $order, User $driver, float $amount): void
    {
        DriverWalletLedgerEntry::query()->updateOrCreate(
            ['delivery_credit_order_id' => $order->id],
            [
                'driver_user_id' => $driver->id,
                'order_id' => $order->id,
                'entry_type' => WalletEntryType::DeliveryCredit,
                'amount' => number_format($amount, 3, '.', ''),
                'currency_code' => $order->currency_code,
                'idempotency_key' => 'delivery-credit-order-'.$order->id,
            ],
        );
    }

    private function upsertPayment(Order $order, PaymentMethod $method, PaymentStatus $status): void
    {
        PaymentAttempt::query()->updateOrCreate(
            ['order_id' => $order->id, 'idempotency_key' => 'demo-payment-'.$order->idempotency_key],
            [
                'method' => $method,
                'provider' => $method === PaymentMethod::Card ? 'stripe' : null,
                'status' => $status,
                'amount' => $order->total,
                'currency_code' => $order->currency_code,
                'provider_reference' => $method === PaymentMethod::Card ? 'pi_demo_'.$order->id : null,
                'metadata' => [
                    'seeded' => true,
                    'source' => 'demo_dataset',
                ],
            ],
        );
    }

    private function upsertRatings(
        Order $order,
        User $customer,
        User $driver,
        int $restaurantScore,
        int $driverScore,
        string $restaurantComment,
        string $driverComment,
    ): void {
        RestaurantRating::query()->updateOrCreate(
            ['order_id' => $order->id, 'customer_id' => $customer->id],
            [
                'restaurant_id' => $order->restaurant_id,
                'score' => $restaurantScore,
                'comment' => $restaurantComment,
            ],
        );

        DriverRating::query()->updateOrCreate(
            ['order_id' => $order->id, 'customer_id' => $customer->id],
            [
                'driver_user_id' => $driver->id,
                'score' => $driverScore,
                'comment' => $driverComment,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function upsertAudit(User $actor, string $action, object $subject, array $metadata): void
    {
        AuditLog::query()->firstOrCreate(
            [
                'actor_user_id' => $actor->id,
                'action' => $action,
                'subject_type' => $subject::class,
                'subject_id' => (string) $subject->getKey(),
            ],
            ['metadata' => $metadata],
        );
    }
}
