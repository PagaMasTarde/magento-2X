<?php

namespace Pagantis\Pagantis\Block\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Pagantis\Pagantis\Helper\ExtraConfig;
use Magento\Framework\Locale\Resolver;
use Zend\Db\Sql\Ddl\Column\Boolean;

/**
 * Class Simulator
 * @package Pagantis\Pagantis\Block\Product
 */
class Simulator extends Template
{
    const PROMOTIONS_CATEGORY = 'pagantis-promotion-product';

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
     */
    protected $productSimulator;

    /**
     * @var string
     */
    protected $promotionProductExtra;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var float
     */
    protected $minAmount;

    /**
     * @var int
     */
    protected $minInstallments;

    /**
     * @var string
     */
    protected $priceSelector;

    /**
     * @var string
     */
    protected $quantitySelector;

    /**
     * @var String
     */
    protected $positionSelector;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Config
     */
    protected $extraConfig;

    /**
     * @var String
     */
    protected $simulatorType;

    /**
     * @var String
     */
    protected $store;

    /**
     * @var Boolean
     */
    protected $promoted;

    /**
     * @var String
     */
    protected $promotedMessage;

    /**
     * @var String
     */
    protected $thousandSeparator;

    /**
     * @var String
     */
    protected $decimalSeparator;

    /**
     * Simulator constructor.
     *
     * @param Context        $context
     * @param Registry       $registry
     * @param ExtraConfig    $extraConfig
     * @param Resolver $store
     * @param array          $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtraConfig $extraConfig,
        Resolver $store,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->store = $store;
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->_scopeConfig;
        $config = $scopeConfig->getValue('payment/pagantis');

        $this->enabled = $config['active'];
        $this->publicKey = isset($config['pagantis_public_key']) ? $config['pagantis_public_key'] : '';
        $this->productSimulator = $config['product_simulator'];
        $this->extraConfig = $extraConfig->getExtraConfig();

        $this->minAmount = $this->extraConfig['PAGANTIS_DISPLAY_MIN_AMOUNT'];
        $this->minInstallments = $this->extraConfig['PAGANTIS_SIMULATOR_START_INSTALLMENTS'];
        $this->priceSelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR'];
        $this->quantitySelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR'];
        $this->positionSelector = $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'];
        $this->simulatorType = $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE'];
        $this->promotedMessage = $this->extraConfig['PAGANTIS_PROMOTION_EXTRA'];
        $this->thousandSeparator = $this->extraConfig['PAGANTIS_SIMULATOR_THOUSANDS_SEPARATOR'];
        $this->decimalSeparator = $this->extraConfig['PAGANTIS_SIMULATOR_DECIMAL_SEPARATOR'];

        $this->promoted = $this->isProductInPromotion();
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return strstr($this->store->getLocale(), '_', true);
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return strstr($this->store->getLocale(), '_', true);
    }

    /**
     * @param $locale
     *
     * @return bool
     */
    public function getAllowedCountry($locale)
    {
        $locale = strtolower($locale);
        $allowedCountries = unserialize($this->extraConfig['PAGANTIS_ALLOWED_COUNTRIES']);
        return (in_array(strtolower($locale), $allowedCountries));
    }

    /**
     * @return Product
     */
    protected function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->registry->registry('product');
        }

        return $this->product;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getPromotionProductExtra()
    {
        return $this->promotionProductExtra;
    }

    /**
     * @return bool
     */
    public function isProductInPromotion()
    {
        try {
            return ($this->getProduct()->getData('pagantis_promoted') === '1') ? 'true' : 'false';
        } catch (\Exception $exception) {
            return 'false';
        }
    }

    /**
     * @return float
     */
    public function getFinalPrice()
    {
        return $this->getProduct()->getFinalPrice();
    }

    /**
     * @return array|false|string
     */
    public function getProductSimulator()
    {
        return $this->productSimulator;
    }

    /**
     * @param int $productSimulator
     */
    public function setProductSimulator($productSimulator)
    {
        $this->productSimulator = $productSimulator;
    }

    /**
     * @return float
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param float $minAmount
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return int
     */
    public function getMinInstallments()
    {
        return $this->minInstallments;
    }

    /**
     * @param int $minInstallments
     */
    public function setMinInstallments($minInstallments)
    {
        $this->minInstallments = $minInstallments;
    }

    /**
     * @return string
     */
    public function getPriceSelector()
    {
        return $this->priceSelector;
    }

    /**
     * @param string $priceSelector
     */
    public function setPriceSelector($priceSelector)
    {
        $this->priceSelector = $priceSelector;
    }

    /**
     * @return string
     */
    public function getQuantitySelector()
    {
        return $this->quantitySelector;
    }

    /**
     * @param string $quantitySelector
     */
    public function setQuantitySelector($quantitySelector)
    {
        $this->quantitySelector = $quantitySelector;
    }

    /**
     * @return String
     */
    public function getPositionSelector()
    {
        return $this->positionSelector;
    }

    /**
     * @param String $positionSelector
     */
    public function setPositionSelector($positionSelector)
    {
        $this->positionSelector = $positionSelector;
    }


    /**
     * @return String
     */
    public function getSimulatorType()
    {
        return $this->simulatorType;
    }

    /**
     * @param String $simulatorType
     */
    public function setSimulatorType($simulatorType)
    {
        $this->simulatorType = $simulatorType;
    }

    /**
     * @return Boolean
     */
    public function getPromoted()
    {
        return $this->promoted;
    }

    /**
     * @param Boolean $promoted
     */
    public function setPromoted($promoted)
    {
        $this->promoted = $promoted;
    }

    /**
     * @return String
     */
    public function getPromotedMessage()
    {
        return $this->promotedMessage;
    }

    /**
     * @param String $promotedMessage
     */
    public function setPromotedMessage($promotedMessage)
    {
        $this->promotedMessage = $promotedMessage;
    }

    /**
     * @return String
     */
    public function getThousandSeparator()
    {
        return $this->thousandSeparator;
    }

    /**
     * @param String $thousandSeparator
     */
    public function setThousandSeparator($thousandSeparator)
    {
        $this->thousandSeparator = $thousandSeparator;
    }

    /**
     * @return String
     */
    public function getDecimalSeparator()
    {
        return $this->decimalSeparator;
    }

    /**
     * @param String $decimalSeparator
     */
    public function setDecimalSeparator($decimalSeparator)
    {
        $this->decimalSeparator = $decimalSeparator;
    }
}
