<?php

namespace Pagantis\Pagantis\Test\Basic;

use Pagantis\Pagantis\Test\Common\AbstractMg21Selenium;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Class pagantisMgBasicTest
 * @package Pagantis\Test\Basic
 *
 * @group magento-basic
 */
class pagantisMgBasicTest extends AbstractMg21Selenium
{
    /**
     * String
     */
    const TITLE = 'Home';

    /**
     * String
     */
    const BACKOFFICE_TITLE = 'Admin';

    /**
     * testMagentoOpen
    */
    public function testpagantisMg21BasicTest()
    {
        $this->webDriver->get($this->configuration['magentoUrl']);
        $condition = WebDriverExpectedCondition::titleContains(self::TITLE);
        $first = $this->webDriver->getPageSource();
        $this->webDriver->getPageSource();
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, $this->configuration['magentoUrl']);
        $this->quit();
    }

    /**
     * testBackofficeOpen
     */
    public function testBackofficeOpen()
    {
        $this->webDriver->get($this->configuration['magentoUrl'].self::BACKOFFICE_FOLDER);
        $condition = WebDriverExpectedCondition::titleContains(self::BACKOFFICE_TITLE);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->quit();
    }
}
