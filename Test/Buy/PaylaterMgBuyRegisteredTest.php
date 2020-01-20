<?php

namespace Pagantis\Pagantis\Test\Buy;

use Pagantis\ModuleUtils\Exception\NoIdentificationException;
use Pagantis\Pagantis\Test\Common\AbstractMg21Selenium;
use Httpful\Request;
use Httpful\Mime;
use Pagantis\ModuleUtils\Exception\AlreadyProcessedException;
use Pagantis\ModuleUtils\Exception\MerchantOrderNotFoundException;
use Pagantis\ModuleUtils\Exception\QuoteNotFoundException;

/**
 * @requires magento-install
 * @requires magento-register
 *
 * @group magento-buy-registered
 */
class pagantisMgBuyRegisteredTest extends AbstractMg21Selenium
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
     * @var array $configs
     */
    protected $configs = array(
        "PAGANTIS_TITLE",
        "PAGANTIS_SIMULATOR_DISPLAY_TYPE",
        "PAGANTIS_SIMULATOR_DISPLAY_SKIN",
        "PAGANTIS_SIMULATOR_DISPLAY_POSITION",
        "PAGANTIS_SIMULATOR_START_INSTALLMENTS",
        "PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR",
        "PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION",
        "PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR",
        "PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR",
        "PAGANTIS_FORM_DISPLAY_TYPE",
        "PAGANTIS_DISPLAY_MIN_AMOUNT",
        "PAGANTIS_URL_OK",
        "PAGANTIS_URL_KO",
    );

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
        $this->verifypagantis();
        $this->verifyOrder();
        $this->setConfirmationPrice($this->verifyOrderInformation());
        $this->comparePrices();
        $this->checkNotifications();
        $this->checkControllers();
        $this->quit();
    }

    private function comparePrices()
    {
        $this->assertContains($this->getCheckoutPrice(), $this->getConfirmationPrice(), "PR46");
    }

    private function checkNotifications()
    {
        $orderUrl = $this->webDriver->getCurrentURL();
        $this->assertNotEmpty($orderUrl);

        $orderArray     = explode('/', $orderUrl);
        $magentoOrderId = (int)$orderArray['8'];
        $this->assertNotEmpty($magentoOrderId);
        $notifyFile = 'index/';
        $quoteId    = ($magentoOrderId) - 1;
        $version    = '';

        if (version_compare($this->version, '23') >= 0) {
            $notifyFile = 'indexV2/';
            $quoteId    = $magentoOrderId;
            $version    = "V2";
        }

        $notifyUrl = sprintf(
            "%s%s%s%s%s%s",
            $this->configuration['magentoUrl'],
            self::NOTIFICATION_FOLDER,
            $notifyFile,
            '?',
            self::NOTIFICATION_PARAMETER,
            '='
        );

        $response = Request::post($notifyUrl.$quoteId)->expects('json')->send();
        $this->assertNotNull($response->body->result, print_r($response, true));

        $this->assertThat(
            $response->body->result,
            $this->logicalOr(
                $this->stringContains(AlreadyProcessedException::ERROR_MESSAGE),
                $this->stringContains(NoIdentificationException::ERROR_MESSAGE)
            ),
            "PR51=>".$notifyUrl.$quoteId." = ".$response->body->result
        );

        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, print_r($response, true));
        $this->assertContains(
            QuoteNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR58=>".$notifyUrl.$quoteId." = ".$response->body->result
        );

        $quoteId  = 0;
        $response = Request::post($notifyUrl.$quoteId)->expects('json')->send();
        $this->assertNotEmpty($response->body->result, print_r($response, true));
        $this->assertContains(
            MerchantOrderNotFoundException::ERROR_MESSAGE,
            $response->body->result,
            "PR51=>".$notifyUrl.$quoteId." = ".$response->body->result
        );
    }

    private function checkControllers()
    {
        $version = '';
        if (version_compare($this->version, '23') >= 0) {
            $version    = "V2";
        }

        $logUrl = sprintf(
            "%s%s%s%s%s",
            $this->configuration['magentoUrl'],
            self::LOG_FOLDER,
            $version,
            '?secret=',
            $this->configuration['secretKey']
        );
        $response = Request::get($logUrl)->expects('json')->send();
        $this->assertEquals(3, count($response->body), "PR57=>".$logUrl." = ".print_r($response->body, true));

        $configUrl = sprintf(
            "%s%s%s%s%s",
            $this->configuration['magentoUrl'],
            self::CONFIG_FOLDER,
            $version,
            '?secret=',
            $this->configuration['secretKey']
        );

        $response = Request::get($configUrl)->expects('json')->send();
        $content = $response->body;
        foreach ($this->configs as $config) {
            $this->assertArrayHasKey($config, (array) $content, "PR61=>".print_r($content, true));
        }

        $body = array('PAGANTIS_TITLE' => 'changed');
        $response = Request::post($configUrl)
                            ->expects('json')
                            ->body($body, Mime::FORM)
                            ->send();
        $response = json_decode($response->raw_body);
        $title = $response->PAGANTIS_TITLE;
        $this->assertEquals('changed', $title, "PR62=>".$configUrl." = ".$title);
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
