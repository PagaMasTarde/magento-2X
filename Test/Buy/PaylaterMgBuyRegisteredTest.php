<?php

namespace DigitalOrigin\Pmt\Test\Buy;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;
use Httpful\Request;
use PagaMasTarde\ModuleUtils\Exception\AlreadyProcessedException;
use PagaMasTarde\ModuleUtils\Exception\MerchantOrderNotFoundException;
use PagaMasTarde\ModuleUtils\Exception\NoIdentificationException;
use PagaMasTarde\ModuleUtils\Exception\QuoteNotFoundException;

/**
 * @requires magento-install
 * @requires magento-register
 *
 * @group magento-buy-registered
 */
class PaylaterMgBuyRegisteredTest extends AbstractMg21Selenium
{
    /**
     * @var string
     */
    protected $checkoutPrice;

    /**
     * @var string
     */
    protected $confirmationPrice;

    /**
     * @var string
     */
    protected $notifyUrl;

    /**
     * @throws  \Exception
     */
    public function testBuy()
    {
        $this->createAccount();
        $this->goToProduct();
        $this->configureProduct();
        $this->checkProductPage();
        $this->goToCheckout();
        $this->goToPayment();
        $this->setCheckoutPrice($this->preparePaymentMethod());

        $logUrl = "http://magento22-test.docker:8085/paylater/Payment/Log?secret=21e57baa97459f6a&from=20180927&to=20200928&limit=20";
        $response = Request::post($logUrl)->expects('json')->send();
        $this->assertEmpty($response->body->result, $response->body->result);

        $this->verifyPaylater();
        $this->verifyOrder();
        $this->setConfirmationPrice($this->verifyOrderInformation());
        $this->comparePrices();
        $this->checkProcessed();
        $this->quit();
    }

    private function comparePrices()
    {
        $this->assertContains($this->getCheckoutPrice(), $this->getConfirmationPrice(), "PR46");
    }

    private function checkProcessed()
    {
        $orderUrl = $this->webDriver->getCurrentURL();
        $this->assertNotEmpty($orderUrl);

        $orderArray = explode('/', $orderUrl);
        $magentoOrderId = (int)$orderArray['8'];
        $this->assertNotEmpty($magentoOrderId);
        $notifyUrl = $this->configuration['magentoUrl'].self::NOTIFICATION_FOLDER.'?'.self::NOTIFICATION_PARAMETER.'='.$magentoOrderId;
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result);
        $this->assertContains(
            NoIdentificationException::ERROR_MESSAGE,
            $response->body->result,
            "PR51=>".$response->body->result
        );

        $notifyUrl = $this->configuration['magentoUrl'].self::NOTIFICATION_FOLDER.'?'.self::NOTIFICATION_PARAMETER.'=';
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result);
        $this->assertContains(
            QuoteNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR58=>".$response->body->result
        );

        $notifyUrl = $this->configuration['magentoUrl'].self::NOTIFICATION_FOLDER.'?'.self::NOTIFICATION_PARAMETER.'=0';
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result);
        $this->assertContains(
            MerchantOrderNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR59=>".$response->body->result
        );
    }

    /**
     * @return string
     */
    public function getCheckoutPrice()
    {
        return $this->checkoutPrice;
    }

    /**
     * @param string $checkoutPrice
     */
    public function setCheckoutPrice($checkoutPrice)
    {
        $this->checkoutPrice = $checkoutPrice;
    }

    /**
     * @return string
     */
    public function getConfirmationPrice()
    {
        return $this->confirmationPrice;
    }

    /**
     * @param string $confirmationPrice
     */
    public function setConfirmationPrice($confirmationPrice)
    {
        $this->confirmationPrice = $confirmationPrice;
    }

    /**
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }
}
