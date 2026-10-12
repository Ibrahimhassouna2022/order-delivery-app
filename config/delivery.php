<?php

use App\Actions\Notification\BroadcastNotifier;
use App\Actions\Notification\LogNotifier;
use App\Actions\Notification\NullNotifier;
use App\Actions\Payment\CashOnDeliveryGateway;
use App\Enums\PaymentMethod;

$fixedFees = env('DELIVERY_FIXED_FEES_JSON');

return [

    /*
    |--------------------------------------------------------------------------
    | Order Pricing
    |--------------------------------------------------------------------------
    |
    | The fixed delivery fee charged per currency. A currency that is missing
    | here cannot be quoted or ordered until it is configured.
    |
    */

    'pricing' => [
        'fixed_fees' => $fixedFees === null
            ? []
            : json_decode($fixedFees, true, flags: JSON_THROW_ON_ERROR),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Only the methods listed in "gateways" are offered to customers. To phase
    | in a new method (for example card or e-wallet), add its gateway class
    | here once a licensed provider has been verified. The rest of the
    | application depends on the gateway contract, not on a specific method.
    |
    */

    'payments' => [
        'default_method' => env('DELIVERY_PAYMENT_DEFAULT_METHOD', PaymentMethod::CashOnDelivery->value),

        'gateways' => [
            PaymentMethod::CashOnDelivery->value => CashOnDeliveryGateway::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | The notifier is resolved from this map. Swap the configured driver to
    | move from logging to a real channel (for example Pusher or WhatsApp)
    | without changing the code that raises the notifications.
    |
    */

    'notifications' => [
        'driver' => env('DELIVERY_NOTIFICATION_DRIVER', 'log'),

        'drivers' => [
            'log' => LogNotifier::class,
            'pusher' => BroadcastNotifier::class,
            'null' => NullNotifier::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dispatch
    |--------------------------------------------------------------------------
    |
    | How the nearest available driver is selected and how long an offer stays
    | open before it is re-offered. Tunable without code changes (FR-016/017).
    |
    */

    'dispatch' => [
        'offer_window_seconds' => (int) env('DELIVERY_OFFER_WINDOW_SECONDS', 30),
        'location_freshness_seconds' => (int) env('DELIVERY_LOCATION_FRESHNESS_SECONDS', 60),
        'candidate_limit' => (int) env('DELIVERY_DISPATCH_CANDIDATE_LIMIT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Personal data is purged this many days after a validated deletion
    | request (FR-035/SC-011). Order/financial records are retained but
    | de-identified for the legally required period.
    |
    */

    'retention' => [
        'personal_data_purge_days' => (int) env('DELIVERY_PERSONAL_DATA_PURGE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    |
    | The version of the privacy policy a location-consent grant was made
    | against (FR-032). Bump it when the policy content changes; the Arabic
    | content lives in the frontend pass.
    |
    */

    'privacy' => [
        'policy_version' => (string) env('DELIVERY_PRIVACY_POLICY_VERSION', '1'),
    ],

];
