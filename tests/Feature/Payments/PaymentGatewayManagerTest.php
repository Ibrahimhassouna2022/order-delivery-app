<?php

namespace Tests\Feature\Payments;

use App\Actions\Payment\CashOnDeliveryGateway;
use App\Actions\Payment\PaymentGatewayManager;
use App\Actions\Payment\PaymentProviderUnavailableException;
use App\Enums\PaymentMethod;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    public function test_cash_on_delivery_is_the_default_enabled_method(): void
    {
        $manager = $this->app->make(PaymentGatewayManager::class);

        $this->assertSame([PaymentMethod::CashOnDelivery], $manager->enabledMethods());
        $this->assertTrue($manager->isEnabled(PaymentMethod::CashOnDelivery));
        $this->assertFalse($manager->isEnabled(PaymentMethod::Card));
        $this->assertInstanceOf(CashOnDeliveryGateway::class, $manager->default());
        $this->assertSame(PaymentMethod::CashOnDelivery, $manager->default()->method());
    }

    public function test_a_method_without_a_configured_gateway_is_rejected(): void
    {
        $this->expectException(PaymentProviderUnavailableException::class);

        $this->app->make(PaymentGatewayManager::class)->for(PaymentMethod::Card);
    }

    public function test_enabling_a_gateway_in_configuration_offers_that_method(): void
    {
        config([
            'delivery.payments.gateways.'.PaymentMethod::Card->value => CashOnDeliveryGateway::class,
        ]);

        $manager = $this->app->make(PaymentGatewayManager::class);

        $this->assertTrue($manager->isEnabled(PaymentMethod::Card));
        $this->assertContains(PaymentMethod::Card, $manager->enabledMethods());
        $this->assertInstanceOf(CashOnDeliveryGateway::class, $manager->for(PaymentMethod::Card));
    }

    public function test_default_ignores_a_configured_method_that_is_not_enabled(): void
    {
        config([
            'delivery.payments.default_method' => PaymentMethod::Card->value,
        ]);

        $this->assertSame(PaymentMethod::CashOnDelivery, $this->app->make(PaymentGatewayManager::class)->default()->method());
    }

    public function test_no_enabled_method_reports_a_configuration_error(): void
    {
        config(['delivery.payments.gateways' => []]);

        $this->expectException(PaymentProviderUnavailableException::class);

        $this->app->make(PaymentGatewayManager::class)->default();
    }
}
