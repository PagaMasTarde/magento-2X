<?php

namespace Pagantis\Pagantis\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Checkout\Model\Session;
use Pagantis\OrdersApiClient\Model\Order;
use Pagantis\Pagantis\Helper\Config;
use Pagantis\Pagantis\Helper\ExtraConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Api\Data\StoreInterface;
use Pagantis\OrdersApiClient\Model\Order\User\Address;
use Magento\Framework\DB\Ddl\Table;
use Pagantis\OrdersApiClient\Model\Order\User;
use Pagantis\OrdersApiClient\Model\Order\User\OrderHistory;
use Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details;
use Pagantis\OrdersApiClient\Model\Order\ShoppingCart;
use Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details\Product;
use Pagantis\OrdersApiClient\Model\Order\Metadata;
use Pagantis\OrdersApiClient\Model\Order\Configuration\Urls;
use Pagantis\OrdersApiClient\Model\Order\Configuration\Channel;
use Pagantis\OrdersApiClient\Model\Order\Configuration;
use Pagantis\OrdersApiClient\Client;

/**
 * Class Index
 * @package Pagantis\Pagantis\Controller\Payment
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const LOGS_TABLE = 'Pagantis_logs';

    /** @var Context $context */
    protected $context;

    /** @var QuoteRepository  $quoteRepository */
    protected $quoteRepository;

    /** @var OrderCollection $orderCollection */
    protected $orderCollection;

    /** @var Session $session */
    protected $session;

    /** @var mixed $config */
    protected $config;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var ProductMetadataInterface $productMetadataInterface */
    protected $productMetadataInterface;

    /** @var ModuleList $moduleList */
    protected $moduleList;

    /** @var ExtraConfig $extraConfig */
    protected $extraConfig;
    
    /** @var StoreInterface $store */
    protected $store;

    /**
     * Index constructor.
     *
     * @param Context                  $context
     * @param QuoteRepository          $quoteRepository
     * @param OrderCollection          $orderCollection
     * @param Session                  $session
     * @param Config                   $config
     * @param ResourceConnection       $dbObject
     * @param ProductMetadataInterface $productMetadataInterface
     * @param ModuleList               $moduleList
     * @param ExtraConfig              $extraConfig
     * @param StoreInterface           $storeInterface
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        OrderCollection $orderCollection,
        Session $session,
        Config $config,
        ResourceConnection $dbObject,
        ProductMetadataInterface $productMetadataInterface,
        ModuleList $moduleList,
        ExtraConfig $extraConfig,
        StoreInterface $storeInterface
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->context = $context;
        $this->config = $config->getConfig();
        $this->quoteRepository = $quoteRepository;
        $this->orderCollection = $orderCollection;
        $this->dbObject = $dbObject;
        $this->moduleList = $moduleList;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->store = $storeInterface;
    }

    /**
     * Main function
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Zend_Db_Exception
     */
    public function execute()
    {
        try {
            $cancelUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
            $quote = $this->session->getQuote();
            /** @var Order $order */
            $lastOrder = $this->session->getLastRealOrder();
            $params = $this->getRequest()->getParams();
            $customer = $quote->getCustomer();
            $shippingAddress = $quote->getShippingAddress();

            if (isset($params['email']) && $params['email']!='') {
                $this->session->setEmail($params['email']); //Get guest email after refresh page
                $customer->setEmail($params['email']);
                $quote->setCheckoutMethod('guest');
                $quote->getBillingAddress()->setEmail($params['email']);
            } elseif ($customer->getEmail()=='') {
                $customer->setEmail($this->session->getEmail());
                $quote->setCheckoutMethod('guest');
                $quote->getBillingAddress()->setEmail($this->session->getEmail());
            }

            /** @var Quote $currentQuote */
            $currentQuote = $this->quoteRepository->get($quote->getId());
            $currentQuote->setCustomerEmail($customer->getEmail());
            $this->quoteRepository->save($currentQuote);

            $userAddress =  new Address();
            $userAddress
                ->setZipCode($shippingAddress->getPostcode())
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setCountryCode($shippingAddress->getCountry())
                ->setCity($shippingAddress->getCity())
                ->setAddress($shippingAddress->getStreetFull())
            ;

            $tax_id = $this->getTaxId($quote->getBillingAddress());
            $orderShippingAddress = new Address();
            $orderShippingAddress
                ->setZipCode($shippingAddress->getPostcode())
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setCountryCode($shippingAddress->getCountry())
                ->setCity($shippingAddress->getCity())
                ->setAddress($shippingAddress->getStreetFull())
                ->setFixPhone($shippingAddress->getTelephone())
                ->setMobilePhone($shippingAddress->getTelephone())
                ->setTaxId($tax_id)
            ;

            $orderBillingAddress =  new Address();
            $billingAddress = $quote->getBillingAddress();
            $orderBillingAddress
                ->setZipCode($billingAddress->getPostcode())
                ->setFullName($billingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setCountryCode($billingAddress->getCountry())
                ->setCity($billingAddress->getCity())
                ->setAddress($billingAddress->getStreetFull())
                ->setFixPhone($billingAddress->getTelephone())
                ->setMobilePhone($billingAddress->getTelephone())
                ->setTaxId($tax_id)
            ;

            $orderUser = new User();
            $billingAddress->setEmail($customer->getEmail());
            $orderUser
                ->setAddress($userAddress)
                ->setFullName($shippingAddress->getFirstname()." ".$shippingAddress->getLastname())
                ->setBillingAddress($orderBillingAddress)
                ->setEmail($customer->getEmail())
                ->setFixPhone($shippingAddress->getTelephone())
                ->setMobilePhone($shippingAddress->getTelephone())
                ->setShippingAddress($orderShippingAddress)
                ->setTaxId($tax_id)
            ;

            if ($customer->getDob()) {
                $orderUser->setDateOfBirth($customer->getDob());
            }
            if ($customer->getTaxvat()!='') {
                $orderUser->setDni($customer->getTaxvat());
                $orderBillingAddress->setDni($customer->getTaxvat());
                $orderShippingAddress->setDni($customer->getTaxvat());
                $orderUser->setNationalId($customer->getTaxvat());
                $orderBillingAddress->setNationalId($customer->getTaxvat());
                $orderShippingAddress->setNationalId($customer->getTaxvat());
            }

            $previousOrders = $this->getOrders($customer->getId());
            foreach ($previousOrders as $orderElement) {
                $orderHistory = new OrderHistory();
                $orderHistory
                    ->setAmount(intval(100 * $orderElement['grand_total']))
                    ->setDate(new \DateTime($orderElement['created_at']))
                ;
                $orderUser->addOrderHistory($orderHistory);
            }

            $metadataOrder = new Metadata();
            $metadata = $this->getMetadata();
            foreach ($metadata as $key => $metadatum) {
                $metadataOrder->addMetadata($key, $metadatum);
            }

            $details = new Details();
            $shippingCost = $quote->collectTotals()->getTotals()['shipping']->getData('value');
            $details->setShippingCost(intval(strval(100 * $shippingCost)));
            $items = $quote->getAllVisibleItems();
            $promotedAmount = 0;
            foreach ($items as $key => $item) {
                $product = new Product();
                $product
                    ->setAmount(intval(100 * $item->getPrice()))
                    ->setQuantity($item->getQty())
                    ->setDescription($item->getName());
                $details->addProduct($product);

                $promotedProduct = $this->isPromoted($item);
                if ($promotedProduct == 'true') {
                    $promotedAmount+=$product->getAmount()*$item->getQty();
                    $promotedMessage = 'Promoted Item: ' . $item->getName() .
                                       ' Price: ' . $item->getPrice() .
                                       ' Qty: ' . $item->getQty() .
                                       ' Item ID: ' . $item->getItemId();
                    $metadataOrder->addMetadata('promotedProduct', $promotedMessage);
                }
            }

            $orderShoppingCart = new ShoppingCart();
            $orderShoppingCart
                ->setDetails($details)
                ->setOrderReference($quote->getId())
                ->setPromotedAmount(0)
                ->setTotalAmount(intval(strval(100 * $quote->getGrandTotal())))
            ;

            $orderConfigurationUrls = new Urls();
            $quoteId = $quote->getId();
            $okUrl = $this->_url->getUrl(
                'pagantis/notify/index',
                ['_query' => ['quoteId'=>$quoteId]]
            );
            if (version_compare($metadata['magento'], '2.3.0') >= 0) {
                $okUrl = $this->_url->getUrl('pagantis/notify/indexV2', ['_query' => ['quoteId'=>$quoteId]]);
            }

            $orderConfigurationUrls
                ->setCancel($cancelUrl)
                ->setKo($okUrl)
                ->setAuthorizedNotificationCallback($okUrl)
                ->setRejectedNotificationCallback($okUrl)
                ->setOk($okUrl)
            ;

            $orderChannel = new Channel();
            $orderChannel
                ->setAssistedSale(false)
                ->setType(Channel::ONLINE)
            ;

            $haystack  = ($this->store->getLocale()!=null) ? $this->store->getLocale() : $this->getResolverCountry();
            $language = strstr($haystack, '_', true);
            $orderConfiguration = new Configuration();
            $orderConfiguration
                ->setChannel($orderChannel)
                ->setUrls($orderConfigurationUrls)
                ->setPurchaseCountry($language)
            ;


            $order = new Order();
            $order
                ->setConfiguration($orderConfiguration)
                ->setMetadata($metadataOrder)
                ->setShoppingCart($orderShoppingCart)
                ->setUser($orderUser)
            ;

            if ($this->config['pagantis_public_key']=='' || $this->config['pagantis_private_key']=='') {
                throw new \Exception('Public and Secret Key not found');
            }

            $orderClient = new Client(
                $this->config['pagantis_public_key'],
                $this->config['pagantis_private_key']
            );

            $order = $orderClient->createOrder($order);
            if ($order instanceof Order) {
                $url = $order->getActionUrls()->getForm();
                $result = $this->insertRow($quote->getId(), $order->getId());
                if (!$result) {
                    throw new \Exception('Unable to save pagantis-order-id');
                }
            } else {
                throw new \Exception('Order not created');
            }
        } catch (\Exception $exception) {
            $this->insertLog($exception);
            echo $cancelUrl;
            exit;
        }

        $displayMode = $this->extraConfig['PAGANTIS_FORM_DISPLAY_TYPE'];
        if ($displayMode==='0') {
            echo $url;
            exit;
        } else {
            $iframeUrl = $this->_url->getUrl(
                "pagantis/Payment/iframe",
                ['_query' => ["orderId"=>$order->getId()]]
            );
            echo $iframeUrl;
            exit;
        }
    }

    /**
     * Get the orders of a customer
     * @param $customerId
     *
     * @return array
     */
    private function getOrders($customerId)
    {
        $orderCollection = array();
        if ($customerId!='') {
            $this->orderCollection->addAttributeToFilter('customer_id', $customerId)
                            ->addAttributeToFilter(
                                'status',
                                ['in' => ['processing','pending','complete']]
                            )
                            ->load();
            $orderCollection = $this->orderCollection->getData();
        }
        return $orderCollection;
    }

    /**
     * @return void|\Zend_Db_Statement_Interface
     * @throws \Zend_Db_Exception
     */
    private function checkDbTable()
    {
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::ORDERS_TABLE);
        if (!$dbConnection->isTableExists($tableName)) {
            $table = $dbConnection
                ->newTable($tableName)
                ->addColumn('id', Table::TYPE_SMALLINT, null, array('primary'=>true, 'nullable' => false))
                ->addColumn('order_id', Table::TYPE_TEXT, 50)
                ->addColumn('mg_order_id', Table::TYPE_TEXT, 50);
            return $dbConnection->createTable($table);
        }

        return;
    }

    /**
     * Create relationship between quote_id & Pagantis_order_id
     * @param $quoteId
     * @param $pagantisOrderId
     *
     * @return int
     * @throws \Zend_Db_Exception
     */
    private function insertRow($quoteId, $pagantisOrderId)
    {
        $this->checkDbTable();
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::ORDERS_TABLE);
        return $dbConnection->insertOnDuplicate(
            $tableName,
            array('id'=>$quoteId,'order_id'=>$pagantisOrderId),
            array('order_id')
        );
    }

    /**
     * @return array
     */
    private function getMetadata()
    {
        $curlInfo = curl_version();
        $curlVersion = $curlInfo['version'];
        $magentoVersion = $this->productMetadataInterface->getVersion();
        $moduleInfo = $this->moduleList->getOne('Pagantis_Pagantis');
        return array(  'magento' => $magentoVersion,
                       'pagantis' => $moduleInfo['setup_version'],
                       'php' => phpversion(),
                       'curl' => $curlVersion);
    }

    /**
     * Check if log table exists, otherwise create it
     *
     * @return void|\Zend_Db_Statement_Interface
     * @throws \Zend_Db_Exception
     */
    private function checkDbLogTable()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName = $this->dbObject->getTableName(self::LOGS_TABLE);
        if (!$dbConnection->isTableExists($tableName)) {
            $table = $dbConnection
                ->newTable($tableName)
                ->addColumn('id', Table::TYPE_SMALLINT, null, array('nullable'=>false, 'auto_increment'=>true, 'primary'=>true))
                ->addColumn('log', Table::TYPE_TEXT, null, array('nullable'=>false))
                ->addColumn('createdAt', Table::TYPE_TIMESTAMP, null, array('nullable'=>false, 'default'=>Table::TIMESTAMP_INIT));
            return $dbConnection->createTable($table);
        }

        return;
    }

    /**
     * @param $exceptionMessage
     *
     * @throws \Zend_Db_Exception
     */
    private function insertLog($exceptionMessage)
    {
        if ($exceptionMessage instanceof \Exception) {
            $this->checkDbLogTable();
            $logObject          = new \stdClass();
            $logObject->message = $exceptionMessage->getMessage();
            $logObject->code    = $exceptionMessage->getCode();
            $logObject->line    = $exceptionMessage->getLine();
            $logObject->file    = $exceptionMessage->getFile();
            $logObject->trace   = $exceptionMessage->getTraceAsString();

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
            $dbConnection->insert($tableName, array('log' => json_encode($logObject)));
        }
    }

    /**
     * @param $billingAdd
     *
     * @return null
     */
    private function getTaxId($billingAdd)
    {
        if (isset($billingAdd['vat_id'])) {
            return $billingAdd['vat_id'];
        } elseif (isset($billingAdd['cod_fisc'])) {
            return $billingAdd['cod_fisc'];
        } else {
            return null;
        }
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

    /**
     * @return mixed
     */
    private function getResolverCountry()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Framework\Locale\Resolver');

        if (method_exists($store, 'getLocale')) {
            return $store->getLocale();
        }

        return null;
    }
}
