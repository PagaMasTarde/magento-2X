<?php

namespace Pagantis\Pagantis\Test\Common;

use Pagantis\Pagantis\Test\PagantisMagentoTest;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Pagantis\SeleniumFormUtils\SeleniumHelper;

/**
 * Class AbstractMg21Selenium
 * @package Pagantis\Test\Common
 */
abstract class AbstractMg21Selenium extends PagantisMagentoTest
{
    /**
     * @throws \Exception
     */
    public function loginToBackOffice()
    {
        $this->webDriver->get($this->configuration['magentoUrl'].self::BACKOFFICE_FOLDER);
        $emailElementSearch = WebDriverBy::id('username');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($emailElementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findById('username')->clear()->sendKeys($this->configuration['backofficeUsername']);
        $this->findById('login')->clear()->sendKeys($this->configuration['backofficePassword']);
        $this->findById('login-form')->submit();
        $emailElementSearch = WebDriverBy::className('page-wrapper');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($emailElementSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * @require loginToBackOffice
     *
     * @throws \Exception
     */
    public function getpagantisBackOffice()
    {
        $this->webDriver->get($this->configuration['magentoUrl'].self::BACKOFFICE_FOLDER);

        $elementSearch = WebDriverBy::linkText('STORES');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('STORES')->click();

        sleep(5);
        $elementSearch = WebDriverBy::linkText('Configuration');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Configuration')->click();

        //Confirmamos que aparece el menu de tabs
        $elementSearch = WebDriverBy::id('system_config_tabs');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);

        //Buscamos la palabra SALES dentro del div anterior
        $menuSearch = WebDriverBy::cssSelector("#system_config_tabs > div > div > strong");
        $menuElements = $this->webDriver->findElements($menuSearch);
        foreach ($menuElements as $menuElement) {
            if (strpos($menuElement->getText(), 'SALES', 0) !== false ||
                strpos($menuElement->getText(), 'sales', 0) !== false) {
                $menuElement->click();
                $this->assertContains('SALES', $menuElement->getText(), $menuElement->getText());
                break;
            }
        }

        $elementSearch = WebDriverBy::linkText('Payment Methods');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Payment Methods')->click();

        $elementSearch = WebDriverBy::id('payment_us_other_payment_methods-head');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $otherElement = $this->findById('payment_us_other_payment_methods-head');
        if ($otherElement->getAttribute('class')!='open') {
            $otherElement->click();
        }

        $verify = WebDriverBy::id('payment_us_pagantis-head');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR4");
        $pagantisElement = $this->findById('payment_us_pagantis-head');
        if ($pagantisElement->getAttribute('class')!='open') {
            $pagantisElement->click();
        }

        $verify = WebDriverBy::id('payment_us_pagantis_active');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
    }

    /**
     * @throws \Exception
     */
    public function createAccount()
    {
        $this->webDriver->get($this->configuration['magentoUrl']);
        $loginButtonSearch = WebDriverBy::linkText('Create an Account');
        $condition = WebDriverExpectedCondition::elementToBeClickable($loginButtonSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Create an Account')->click();

        $random = rand(0, 1000);
        $this->findById('firstname')->clear()->sendKeys($this->configuration['firstname']);
        $this->findById('lastname')->sendKeys($this->configuration['lastname']);
        $this->findById('email_address')->sendKeys($this->configuration['email']);
        $this->findById('password')->sendKeys($this->configuration['password']);
        $this->findById('password-confirmation')->sendKeys($this->configuration['password']);
        $this->findById('form-validate')->submit();

        $addressButton = WebDriverBy::partialLinkText('Address Book');
        $condition = WebDriverExpectedCondition::elementToBeClickable($addressButton);
        $this->webDriver->wait()->until($condition);

        $this->assertContains(
            $this->configuration['firstname'],
            $this->findByClass('block-dashboard-info')->getText()
        );
        $this->findByPartialLinkText('Address Book')->click();

        $addressLink = WebDriverBy::id('firstname');
        $condition = WebDriverExpectedCondition::presenceOfElementLocated($addressLink);
        $this->webDriver->wait()->until($condition);

        $this->webDriver->findElement(WebDriverBy::id('country'))
                        ->findElement(WebDriverBy::cssSelector("option[value='ES']"))
                        ->click();

        $this->findById('telephone')->clear()->sendKeys($this->configuration['phone']);
        $this->findById('street_1')->sendKeys($this->configuration['street']);
        $this->findById('city')->sendKeys($this->configuration['city']);
        $this->findById('zip')->sendKeys($this->configuration['zip']);

        $regionLink = WebDriverBy::id('region_id');
        $condition = WebDriverExpectedCondition::presenceOfElementLocated($regionLink);
        $this->webDriver->wait()->until($condition);

        sleep(5);
        $this->webDriver->findElement(WebDriverBy::id('region_id'))
                        ->findElement(WebDriverBy::cssSelector("option[value='161']"))
                        ->click();
        $this->findById('form-validate')->submit();
    }

    /**
     * @require createAccount
     *
     * @throws \Exception
     */
    public function loginToFrontend()
    {
        $this->webDriver->get($this->configuration['magentoUrl']);
        $loginButton = WebDriverBy::partialLinkText('Sign In');
        $condition = WebDriverExpectedCondition::elementToBeClickable($loginButton);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->webDriver->findElement($loginButton)->click();

        $verifyElement = WebDriverBy::id('login-form');
        $condition = WebDriverExpectedCondition::elementToBeClickable($verifyElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $this->findById('email')->sendKeys($this->configuration['email']);
        $this->findById('pass')->sendKeys($this->configuration['password']);
        $this->findById('login-form')->submit();
    }

    /**
     *
     * @throws \Exception
     */
    public function logoutFromFrontend()
    {
        $this->webDriver->get($this->configuration['magentoUrl'].self::LOGOUT_FOLDER);

        $validatorSearch = WebDriverBy::className('base');
        $actualString = $this->webDriver->findElement($validatorSearch)->getText();
        $compareString = (strpos($actualString, "You are signed out")) === false ? false : true;
        $this->assertTrue($compareString, "PR1-PR4");
    }

    /**
     * Configure product
     */
    public function checkProductPage()
    {
        sleep(10);
        if (version_compare($this->version, '23') < 0) {
            $this->checkSimulator();
        }

        $paymentFormElement = WebDriverBy::id('product-addtocart-button');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paymentFormElement);
        $this->webDriver->wait()->until($condition);
        $addToCartButton = $this->findById('product-addtocart-button');
        $addToCartButton->click();
        sleep(5);
    }

    /**
     * @param bool $verifySimulator
     *
     * @throws \Exception
     */
    public function goToProduct($verifySimulator = true)
    {
        $this->webDriver->get($this->configuration['magentoUrl']);
        $this->findByLinkText(self::PRODUCT_NAME)->click();
        $condition = WebDriverExpectedCondition::titleContains(self::PRODUCT_NAME);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * @require goToProduct
     *
     * @throws \Exception
     */
    public function addProduct()
    {
        $addToCartSearch = WebDriverBy::id('product-addtocart-button');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($addToCartSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->webDriver->findElement($addToCartSearch)->click();

        $validatorSearch = WebDriverBy::className('messages');
        $actualString = $this->webDriver->findElement($validatorSearch)->getText();
        $addedMessage = "You added ".self::PRODUCT_NAME." to your shopping cart";
        $compareString = (strpos($actualString, $addedMessage)) === false ? false : true;
        $this->assertTrue($compareString);

        //You added Fusion Backpack to your shopping cart.
        $shoppingCartSearch = WebDriverBy::id('shopping_cart');
        $this->webDriver->findElement($shoppingCartSearch)->click();
        $shoppingCartTitle = WebDriverBy::id('cart_title');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($shoppingCartTitle);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    public function goToCart()
    {
        $this->webDriver->get($this->configuration['magentoUrl'].self::CART_FOLDER);
        $this->findByLinkText(self::PRODUCT_NAME)->click();
        $condition = WebDriverExpectedCondition::titleContains(self::CART_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * Config product quantity
     *
     * @param $qty
     */
    public function configureProduct($qty = 1)
    {
        $qtyElements = $this->webDriver->findElements(WebDriverBy::id('qty'));
        foreach ($qtyElements as $qtyElement) {
            $qtyElement->clear()->sendKeys($qty);
        }
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function goToCheckout()
    {
        sleep(15);
        $this->webDriver->get($this->configuration['magentoUrl'].self::CHECKOUT_FOLDER);
        $condition = WebDriverExpectedCondition::titleContains(self::CHECKOUT_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool)$condition, $this->configuration['magentoUrl'].self::CHECKOUT_FOLDER);
    }

    /**
     * @throws \Exception
     */
    public function verifyUTF8()
    {
        $paymentFormElement = WebDriverBy::className('FieldsPreview-desc');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paymentFormElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->assertSame(
            $this->configuration['firstname'] . ' ' . $this->configuration['lastname'],
            $this->findByClass('FieldsPreview-desc')->getText()
        );
    }

    /**
     * Verify pagantis
     *
     * @throws \Exception
     */
    public function verifypagantis()
    {
        $condition = WebDriverExpectedCondition::titleContains(self::PAGANTIS_TITLE);
        $this->webDriver->wait(300)->until($condition, $this->webDriver->getCurrentURL());
        $this->assertTrue((bool)$condition, "PR32");

        SeleniumHelper::finishForm($this->webDriver);
    }

    /**
     * Prepare checkout, called from BuyRegistered and BuyUnregistered
     */
    public function prepareCheckout()
    {
        $firstnameElement = WebDriverBy::name('firstname');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($firstnameElement);
        $this->webDriver->wait(200)->until($condition);
        $this->assertTrue((bool) $condition);

        sleep(5);
        $countryElement = WebDriverBy::name('country_id');
        $condition = WebDriverExpectedCondition::elementToBeClickable($countryElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->webDriver->findElement(WebDriverBy::name('country_id'))
                        ->findElement(WebDriverBy::cssSelector("option[value='ES']"))
                        ->click();

        $this->findByName('postcode')->clear()->sendKeys($this->configuration['zip']);
        $this->findByName('street[0]')->clear()->sendKeys($this->configuration['street']);
        $this->findByName('city')->clear()->sendKeys($this->configuration['city']);
        $this->findById('customer-email')->clear()->sendKeys($this->configuration['email']);
        $this->findByName('firstname')->clear()->sendKeys($this->configuration['firstname']);
        $this->findByName('lastname')->clear()->sendKeys($this->configuration['lastname']);
        $this->findByName('telephone')->clear()->sendKeys($this->configuration['phone']);

        $this->webDriver->findElement(WebDriverBy::name('region_id'))
                        ->findElement(WebDriverBy::cssSelector("option[value='139']"))
                        ->click();

        $this->goToPayment();
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function goToPayment()
    {
        $shippingElement = WebDriverBy::xpath("//input[@value='flatrate_flatrate']");
        $condition = WebDriverExpectedCondition::elementToBeClickable($shippingElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        sleep(5);
        $shippingButton = $this->webDriver->findElement($shippingElement);
        if (!$shippingButton->isSelected()) {
            $shippingButton->click();
        }

        sleep(5);
        $continueElement = WebDriverBy::className('continue');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($continueElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $condition = WebDriverExpectedCondition::elementToBeClickable($continueElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $this->findByClass('continue')->click();
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function preparePaymentMethod()
    {
        $pagantisElement = WebDriverBy::id('pagantis');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($pagantisElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $condition = WebDriverExpectedCondition::elementToBeClickable($pagantisElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        sleep(10);
        $this->findById('pagantis')->click();

        sleep(2);

        $pagantisElement = WebDriverBy::className('payment-group');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($pagantisElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $menuSearch = WebDriverBy::cssSelector("#checkout-payment-method-load > .payment-methods > .payment-group > ._active > .payment-method-title");
        $menuElement = $this->webDriver->findElement($menuSearch);
        $actualString = $menuElement->getText();
        $compareString = (strstr($actualString, $this->configuration['methodTitle'])) === false ? false : true;
        $this->assertTrue($compareString, "PR25,PR26=>".$actualString.'--'.$this->configuration['methodTitle']);

        $descriptionSearch = WebDriverBy::cssSelector("#checkout-payment-method-load > .payment-methods > .payment-group > ._active > .payment-method-content");
        $descriptionElement = $this->webDriver->findElement($descriptionSearch);
        $actualString = $descriptionElement->getText();
        $this->assertContains($this->configuration['checkoutDescription'], $actualString, "PR54");

        $priceSearch = WebDriverBy::className('price');
        $priceElements = $this->webDriver->findElements($priceSearch);
        $price = $priceElements['6']->getText();

        $this->assertNotEquals($price, 0, $price);
        $this->assertNotEmpty($price);

        sleep(20);
        $checkoutButton = WebDriverBy::cssSelector("#checkout-payment-method-load > .payment-methods > .payment-group > ._active > .payment-method-content > .actions-toolbar > .primary");
        $condition = WebDriverExpectedCondition::elementToBeClickable($checkoutButton);
        $this->webDriver->wait()->until($condition);

        $menuElement = $this->webDriver->findElement($checkoutButton);
        $menuElement->click();
        return $price;
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function verifyOrder()
    {
        $condition = WebDriverExpectedCondition::titleContains(self::SUCCESS_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $menuSearch = WebDriverBy::className("base");
        $menuElement = $this->webDriver->findElement($menuSearch);
        $actualString = $menuElement->getText();
        $this->assertContains('Thank you for your purchase!', $actualString, "PR42");
    }

    /**
     * @return string
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function verifyOrderInformation()
    {
        $this->findByClass('order-number')->click();
        $condition = WebDriverExpectedCondition::titleContains(self::ORDER_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $menuSearch = WebDriverBy::cssSelector("div.block-order-details-view > div.block-content > div.box-order-billing-method > div.box-content > dl.payment-method");
        $menuElement = $this->webDriver->findElement($menuSearch);
        $actualString = $menuElement->getText();
        $compareString = (strstr($actualString, $this->configuration['methodName'])) === false ? false : true;
        $this->assertTrue($compareString, $actualString, "PR49");

        $menuSearch = WebDriverBy::cssSelector("#my-orders-table > tfoot > .grand_total > .amount > strong > .price");
        $menuElement = $this->webDriver->findElement($menuSearch);
        return $menuElement->getText();
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    public function goToOrder()
    {
        $condition = WebDriverExpectedCondition::titleContains('Order');
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    private function checkSimulator()
    {
        $simulatorElementSearch = WebDriverBy::className('pagantisSimulator');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($simulatorElementSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR19//PR28");
    }
}
