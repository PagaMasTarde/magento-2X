<?php

namespace Pagantis\Pagantis\Test\Buy;

use Pagantis\Pagantis\Test\Common\AbstractMg21Selenium;


/**
 * Class pagantisMgBuyUnregisteredTest
 *
 * @group magento-buy-unregistered
 */
class pagantisMgBuyUnregisteredTest extends AbstractMg21Selenium
{
    /**
     * @throws \Exception
     */
    public function testBuy()
    {
        $this->goToProduct();
        $this->configureProduct();
        $this->checkProductPage();
        $this->goToCheckout();
        $this->prepareCheckout();
        $this->preparePaymentMethod();
        $this->verifypagantis();
        $this->verifyOrder();
        $this->quit();
    }
}