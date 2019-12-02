define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        '//cdn.pagantis.com/js/pg-v2/sdk.js',
        '//cdn.pagamastarde.com/js/pmt-v2/sdk.js',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, quote, pgSDK, pmtSDK, selectPaymentMethodAction, checkoutData, totals, priceUtils) {
        'use strict';

        window.checkoutConfig.payment.pagantis.guestEmail = quote.guestEmail;

        return Component.extend({
            defaults: {
                template: 'Pagantis_Pagantis/payment/checkout-form'
            },

            redirectAfterPlaceOrder: false,

            loadSimulator: function () {
                window.loadingSimulator = setTimeout(function () {
                    if (window.checkoutConfig.payment.pagantis.enabled  !='0' &&
                        window.checkoutConfig.payment.pagantis.publicKey!=''  &&
                        window.checkoutConfig.payment.pagantis.secretKey!='') {
                        var locale = window.checkoutConfig.payment.pagantis.locale;
                        if (locale=='es'|| locale=='') {
                            var sdk = pmtSDK;
                        } else {
                            var sdk = pgSDK;
                        }

                        var simulator_options = {
                            numInstalments : window.checkoutConfig.payment.pagantis.quotesStart,
                            type : eval(window.checkoutConfig.payment.pagantis.type),
                            skin : eval(window.checkoutConfig.payment.pagantis.skin),
                            publicKey: window.checkoutConfig.payment.pagantis.publicKey,
                            selector: window.checkoutConfig.payment.pagantis.position,
                            totalAmount: window.checkoutConfig.payment.pagantis.total,
                            locale: window.checkoutConfig.payment.pagantis.locale,
                            country: window.checkoutConfig.payment.pagantis.country,
                            totalPromotedAmount : window.checkoutConfig.payment.pagantis.promotedAmount
                        };

                        if (typeof sdk !== 'undefined') {
                            window.MGSimulatorId = sdk.simulator.init(simulator_options);
                            return false;
                        }
                    }
                }, 3000);
            },

            getTitle: function () {
                return window.checkoutConfig.payment.pagantis.title
            },

            getSubtitle: function () {
                return window.checkoutConfig.payment.pagantis.subtitle
            },

            getDisplayMode: function () {
                return window.checkoutConfig.payment.pagantis.displayMode
            },

            getImage: function () {
                return window.checkoutConfig.payment.pagantis.image
            },

            placeOrder: function () {
                var paymentUrl = url.build('pagantis/Payment');
                $.post(paymentUrl, { email: window.checkoutConfig.payment.pagantis.guestEmail }, 'json')
                    .done(function (response) {
                        window.location.replace(response);
                    })
                    .fail(function (response) {
                        window.location.replace(response);
                    })
            },
            });
    }
);
