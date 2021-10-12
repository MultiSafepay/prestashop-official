<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

class GenericGateway3 extends GenericGateway1
{
    protected $name = 'Generic Gateway 3';

    public function getUniqueName(): string
    {
        return 'GENERIC3';
    }
}
