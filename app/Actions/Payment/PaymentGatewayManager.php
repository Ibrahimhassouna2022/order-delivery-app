<?php

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the payment gateway for a method from configuration. Methods that
 * are not configured are simply not offered, so payment phasing is a
 * configuration change rather than a code change.
 */
class PaymentGatewayManager
{
    public function __construct(private Container $container) {}

    /**
     * @return list<PaymentMethod>
     */
    public function enabledMethods(): array
    {
        $methods = [];

        foreach (PaymentMethod::cases() as $method) {
            if ($this->isEnabled($method)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    public function isEnabled(PaymentMethod $method): bool
    {
        return $this->gatewayClass($method) !== null;
    }

    public function for(PaymentMethod $method): PaymentGateway
    {
        $class = $this->gatewayClass($method);

        if ($class === null) {
            throw new PaymentProviderUnavailableException(
                "The \"{$method->value}\" payment method is not enabled.",
            );
        }

        /** @var PaymentGateway $gateway */
        $gateway = $this->container->make($class);

        return $gateway;
    }

    public function default(): PaymentGateway
    {
        $configured = PaymentMethod::tryFrom((string) config('delivery.payments.default_method'));

        if ($configured !== null && $this->isEnabled($configured)) {
            return $this->for($configured);
        }

        $fallback = $this->enabledMethods()[0] ?? null;

        if ($fallback === null) {
            throw new PaymentProviderUnavailableException('No payment method is enabled.');
        }

        return $this->for($fallback);
    }

    private function gatewayClass(PaymentMethod $method): ?string
    {
        $class = config('delivery.payments.gateways.'.$method->value);

        return is_string($class) && class_exists($class) ? $class : null;
    }
}
