<?php

namespace Pagantis\Pagantis\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Pagantis\Pagantis\Helper\ExtraConfig;
use Magento\Framework\Locale\Resolver;

/**
 * Class ConfigProvider
 * @package Pagantis\Pagantis\Model\Ui
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pagantis';

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var String
     */
    protected $extraConfig;

    /**
     * @var String
     */
    protected $assetRepository;

    /**
     * @var String
     */
    protected $store;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Payment\Helper\Data             $paymentHelper
     * @param Session                                  $checkoutSession
     * @param ExtraConfig                              $extraConfig
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param Resolver                                 $resolver
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        Session $checkoutSession,
        ExtraConfig $extraConfig,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        Resolver $resolver
    ) {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->checkoutSession = $checkoutSession;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->assetRepository = $assetRepository;
        $this->resolver = $resolver;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();

        $positionSelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'];
        if ($positionSelector == 'default') {
            $positionSelector = '.pagantisSimulator';
        }

        return [
            'payment' => [
                self::CODE => [
                    'total' => $quote->getGrandTotal(),
                    'enabled' => $this->method->getConfigData('active'),
                    'title' => __($this->extraConfig['PAGANTIS_TITLE']),
                    'subtitle' => __($this->extraConfig['PAGANTIS_TITLE_EXTRA']),
                    'image' => $this->assetRepository->getUrl('Pagantis_Pagantis::logopagantis.png'),
                    'publicKey' => $this->method->getConfigData('pagantis_public_key'),
                    'locale' => strstr($this->resolver->getLocale(), '_', true),
                    'country' => strstr($this->resolver->getLocale(), '_', true),
                    'promotedAmount' => $this->getPromotedAmount($quote),
                    'thousandSeparator' => $this->extraConfig['PAGANTIS_SIMULATOR_THOUSANDS_SEPARATOR'],
                    'decimalSeparator' => $this->extraConfig['PAGANTIS_SIMULATOR_DECIMAL_SEPARATOR'],
                    'quotesStart' => $this->extraConfig['PAGANTIS_SIMULATOR_START_INSTALLMENTS'],
                    'type'      => $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE'],
                    'skin'      => $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_SKIN'],
                    'position'  => $positionSelector
                ],
            ],
        ];
    }

    /**
     * @param $quote
     *
     * @return int
     */
    private function getPromotedAmount($quote)
    {
        $promotedAmount = 0;
        $items = $quote->getAllVisibleItems();
        foreach ($items as $key => $item) {
            $promotedProduct = $this->isPromoted($item);
            if ($promotedProduct == 'true') {
                $promotedAmount+=$item->getPrice()*$item->getQty();
            }
        }

        return $promotedAmount;
    }

    /**
     * @param $item
     *
     * @return string
     */
    private function isPromoted($item)
    {
        $magentoProductId = $item->getProductId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($magentoProductId);
        return ($product->getData('pagantis_promoted') === '1') ? 'true' : 'false';
    }
}
