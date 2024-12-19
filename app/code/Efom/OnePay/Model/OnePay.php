<?php

namespace Efom\OnePay\Model;

/**
 * Class OnePay
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class OnePay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_ONEPAY_CODE = 'onepay';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_ONEPAY_CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;


}
