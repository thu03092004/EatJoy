define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'onepay',
                component: 'Efom_OnePay/js/view/payment/method-renderer/onepay-method'
            },
            {
                type: 'onepayinternational',
                component: 'Efom_OnePay/js/view/payment/method-renderer/onepay-international-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
