<?php

namespace Pagantis\Pagantis\Test\Install;

use Pagantis\Pagantis\Test\Common\AbstractMg21Selenium;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;

/**
 * Class pagantisMgInstallTest
 * @package Pagantis\Test\Install
 *
 */
class pagantisMgInstallTest extends AbstractMg21Selenium
{
    /**
     * testPagantisMg21InstallTest
     * @group magento-install
     * @throws \Exception
     */
    public function testPagantisMg21InstallTest()
    {
        $this->loginToBackOffice();
        $this->getpagantisBackOffice();
        $this->configurepagantis();
        $this->quit();
    }

    /**
     * testPagantisMg21ConfigureTest
     * @group magento-configure
     */
    public function testPagantisMg21ConfigureTest()
    {
        $this->loginToBackOffice();
        $this->configurepagantis();
        $this->quit();
    }

    /**
     * @require getpagantisBackOffice
     *
     * @throws \Exception
     */
    private function configurepagantis()
    {
        $verify = WebDriverBy::id('payment_us_pagantis_pagantis_public_key');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR5");

        $verify = WebDriverBy::id('payment_us_pagantis_pagantis_private_key');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR5");

        $activeSelect = $this->findById('payment_us_pagantis_active');
        $activeOptions = $activeSelect->findElements(WebDriverBy::xpath('option'));
        foreach ($activeOptions as $activeOption) {
            if ($activeOption->getText() == 'Yes') {
                $activeOption->click();
                break;
            }
        }

        $activeSelect = $this->findById('payment_us_pagantis_product_simulator');
        $activeOptions = $activeSelect->findElements(WebDriverBy::xpath('option'));
        foreach ($activeOptions as $activeOption) {
            if ($activeOption->getText() == 'Yes') {
                $activeOption->click();
                break;
            }
        }

        $this->findById('payment_us_pagantis_pagantis_public_key')->clear()->sendKeys($this->configuration['publicKey']);
        $this->findById('payment_us_pagantis_pagantis_private_key')->clear()->sendKeys($this->configuration['secretKey']);

        $this->findById('config-edit-form')->submit();

        $validatorSearch = WebDriverBy::className('message-success');
        $actualString = $this->webDriver->findElement($validatorSearch)->getText();
        $compareString = (strstr($actualString, 'You saved the configuration')) === false ? false : true;
        $this->assertTrue($compareString, $actualString);

        $enabledModule = $this->findByCss("select#payment_us_pagantis_active > option[selected]");
        $this->assertEquals($enabledModule->getText(), 'Yes', 'PR6');

        $verify = WebDriverBy::id('payment_us_pagantis_active');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR7");
    }
}
