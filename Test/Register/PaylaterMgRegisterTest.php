<?php

namespace Pagantis\Pagantis\Test\Register;

use Pagantis\Pagantis\Test\Common\AbstractMg21Selenium;

/**
 * Class pagantisMgInstallTest
 * @package Pagantis\Test\Register
 *
 * @group magento-register
 */
class pagantisMgRegisterTest extends AbstractMg21Selenium
{
    /**
     * @require configurepagantis
     *
     * @throws \Exception
     */
    public function testRegisterAndLogin()
    {
        $this->createAccount();
        $this->logoutFromFrontend();
        $this->loginToFrontend();
        $this->logoutFromFrontend();
        $this->quit();
    }
}
