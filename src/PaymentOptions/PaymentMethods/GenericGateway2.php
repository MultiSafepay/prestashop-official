<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

class GenericGateway2 extends GenericGateway1
{
    protected $name = 'Generic Gateway 2';

    public function getUniqueName(): string
    {
        return 'GENERIC2';
    }
}
