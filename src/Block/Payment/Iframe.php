<?php

namespace Pagantis\Pagantis\Block\Payment;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session;
use Magento\Framework\Session\SessionManagerInterface;

class Iframe extends Template
{
    /**
     * @var string
     */
    public $orderUrl;

    /**
     * @var string
     */
    public $checkoutUrl;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $session;

    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * Iframe constructor.
     *
     * @param Context                 $context
     * @param Session                 $session
     * @param SessionManagerInterface $coreSession
     * @param array                   $data
     */
    public function __construct(
        Context $context,
        Session $session,
        SessionManagerInterface $coreSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->session = $session;
        //$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /*$this->coreSession = $coreSession;

        $this->coreSession->start();
        $info = explode("&", $this->coreSession->getMessage());
        foreach ($info as $infoValue) {
            $infoParam = explode("=", $infoValue);
            $infoVar = $infoParam[0];
            $this->$infoVar = $infoParam[1];
        }
        var_dump($this->getEmail());
        die('FIIIN');*/
    }

    /**
     * @return string
     */
    public function getOrderUrl()
    {
        return $this->orderUrl;
    }

    /**
     * @param string $orderUrl
     *
     * @return Iframe
     */
    public function setOrderUrl($orderUrl)
    {
        $this->orderUrl = $orderUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->checkoutUrl;
    }

    /**
     * @param string $checkoutUrl
     *
     * @return Iframe
     */
    public function setCheckoutUrl($checkoutUrl)
    {
        $this->checkoutUrl = $checkoutUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Iframe
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

}
