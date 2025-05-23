<?php

declare(strict_types=1);

/*
 * PaypalServerSdkLib
 *
 * This file was automatically generated by APIMATIC v3.0 ( https://www.apimatic.io ).
 */

namespace PaypalServerSdkLib\Models\Builders;

use Core\Utils\CoreHelper;
use PaypalServerSdkLib\Models\Customer;
use PaypalServerSdkLib\Models\PaymentTokenRequest;
use PaypalServerSdkLib\Models\PaymentTokenRequestPaymentSource;

/**
 * Builder for model PaymentTokenRequest
 *
 * @see PaymentTokenRequest
 */
class PaymentTokenRequestBuilder
{
    /**
     * @var PaymentTokenRequest
     */
    private $instance;

    private function __construct(PaymentTokenRequest $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new Payment Token Request Builder object.
     *
     * @param PaymentTokenRequestPaymentSource $paymentSource
     */
    public static function init(PaymentTokenRequestPaymentSource $paymentSource): self
    {
        return new self(new PaymentTokenRequest($paymentSource));
    }

    /**
     * Sets customer field.
     *
     * @param Customer|null $value
     */
    public function customer(?Customer $value): self
    {
        $this->instance->setCustomer($value);
        return $this;
    }

    /**
     * Initializes a new Payment Token Request object.
     */
    public function build(): PaymentTokenRequest
    {
        return CoreHelper::clone($this->instance);
    }
}
