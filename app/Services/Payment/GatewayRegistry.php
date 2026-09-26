<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

/**
 * Config-driven registry of all available payment gateways.
 *
 * Reads the gateways array from config/payments.php and resolves each
 * class from the Laravel container, so gateways can typehint dependencies.
 *
 * Bound as a singleton in AppServiceProvider so it's only built once per request.
 */
class GatewayRegistry
{
    /** @var array<string, PaymentGateway> keyed by gateway slug */
    private array $gateways = [];

    public function __construct()
    {
        foreach (config('payments.gateways', []) as $class) {
            /** @var PaymentGateway $gateway */
            $gateway = app($class);
            $this->gateways[$gateway->slug()] = $gateway;
        }
    }

    /**
     * Retrieve a gateway by its slug.
     *
     * @throws InvalidArgumentException if the slug is not registered
     */
    public function get(string $slug): PaymentGateway
    {
        if (! isset($this->gateways[$slug])) {
            throw new InvalidArgumentException("Unknown payment gateway: {$slug}");
        }

        return $this->gateways[$slug];
    }

    /** @return array<string, PaymentGateway> all registered gateways keyed by slug */
    public function all(): array
    {
        return $this->gateways;
    }
}
