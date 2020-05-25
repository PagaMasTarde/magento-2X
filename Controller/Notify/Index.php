<?php

namespace Pagantis\Pagantis\Controller\Notify;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Pagantis\ModuleUtils\Exception\MerchantOrderNotFoundException;
use Pagantis\OrdersApiClient\Client;
use Pagantis\Pagantis\Helper\Config;
use Pagantis\Pagantis\Helper\ExtraConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Ddl\Table;
use Pagantis\ModuleUtils\Exception\AmountMismatchException;
use Pagantis\ModuleUtils\Exception\ConcurrencyException;
use Pagantis\ModuleUtils\Exception\NoIdentificationException;
use Pagantis\ModuleUtils\Exception\OrderNotFoundException;
use Pagantis\ModuleUtils\Exception\QuoteNotFoundException;
use Pagantis\ModuleUtils\Exception\UnknownException;
use Pagantis\ModuleUtils\Exception\WrongStatusException;
use Pagantis\ModuleUtils\Model\Response\JsonSuccessResponse;
use Pagantis\ModuleUtils\Model\Response\JsonExceptionResponse;
use Pagantis\ModuleUtils\Exception\AlreadyProcessedException;
use Pagantis\ModuleUtils\Model\Log\LogEntry;

/**
 * Class Index
 * @package Pagantis\Pagantis\Controller\Notify
 */
class Index extends Action
{
    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** Concurrency tablename */
    const CONCURRENCY_TABLE = 'Pagantis_orders';

    /** Concurrency tablename */
    const LOGS_TABLE = 'Pagantis_logs';

    /** Payment code */
    const PAYMENT_METHOD = 'pagantis';

    /** Seconds to expire a locked request */
    const CONCURRENCY_TIMEOUT = 10;

    /**
     * EXCEPTION RESPONSES
     */
    const CPO_ERR_MSG = 'Order not confirmed';
    const CPO_OK_MSG = 'Order confirmed';

    /** @var QuoteManagement */
    protected $quoteManagement;

    /** @var PaymentInterface $paymentInterface */
    protected $paymentInterface;

    /** @var OrderRepositoryInterface $orderRepositoryInterface */
    protected $orderRepositoryInterface;

    /** @var Quote $quote */
    protected $quote;

    /** @var QuoteRepository $quoteRepository */
    protected $quoteRepository;

    /** @var mixed $config */
    protected $config;

    /** @var mixed $quoteId */
    protected $quoteId;

    /** @var array $notifyResult */
    protected $notifyResult;

    /** @var mixed $magentoOrderId */
    protected $magentoOrderId;

    /** @var mixed $pagantisOrder */
    protected $pagantisOrder;

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var Client $orderClient */
    protected $orderClient;

    /** @var mixed $pagantisOrderId */
    protected $pagantisOrderId;

    /** @var  OrderInterface $magentoOrder */
    protected $magentoOrder;

    /** @var ExtraConfig $extraConfig */
    protected $extraConfig;

    /** @var mixed $origin */
    protected $origin;

    /**
     * Index constructor.
     *
     * @param Context                  $context
     * @param Quote                    $quote
     * @param QuoteManagement          $quoteManagement
     * @param PaymentInterface         $paymentInterface
     * @param Config                   $config
     * @param QuoteRepository          $quoteRepository
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param ResourceConnection       $dbObject
     * @param Session                  $checkoutSession
     * @param ExtraConfig              $extraConfig
     */
    public function __construct(
        Context $context,
        Quote $quote,
        QuoteManagement $quoteManagement,
        PaymentInterface $paymentInterface,
        Config $config,
        QuoteRepository $quoteRepository,
        OrderRepositoryInterface $orderRepositoryInterface,
        ResourceConnection $dbObject,
        Session $checkoutSession,
        ExtraConfig $extraConfig
    ) {
        parent::__construct($context);
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->paymentInterface = $paymentInterface;
        $this->extraConfig = $extraConfig->getExtraConfig();
        $this->config = $config->getConfig();
        $this->quoteRepository = $quoteRepository;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->dbObject = $dbObject;
        $this->checkoutSession = $checkoutSession;
        $this->origin = ($_SERVER['REQUEST_METHOD'] == 'POST') ? 'Notification' : 'Order';
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws UnknownException
     */
    public function execute()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['origin'] == 'notification') {
                $returnUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
                $this->_redirect($returnUrl);
            }

            if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['origin'] == 'redirect') {
                $redirectMessage = sprintf(
                    "[%s][%s]",
                    $_GET['origin'],
                    $this->getRequest()->getParam('quoteId')
                );
                $this->insertLog(null, $redirectMessage);
            }

            $this->checkConcurrency();
            $this->getMerchantOrder();
            $this->getPagantisOrderId();
            $this->getPagantisOrder();
            $this->checkOrderStatus();
            $this->checkMerchantOrderStatus();
            $this->validateAmount();
            $this->processMerchantOrder();
        } catch (\Exception $exception) {
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->magentoOrderId);
            $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $response = $jsonResponse->toJson();
            $this->insertLog($exception);
        }

        try {
            if (!isset($response)) {
                $this->confirmpagantisOrder();
                $jsonResponse = new JsonSuccessResponse();
                $jsonResponse->setMerchantOrderId($this->magentoOrderId);
                $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
            }
        } catch (\Exception $exception) {
            $this->rollbackMerchantOrder();
            $jsonResponse = new JsonExceptionResponse();
            $jsonResponse->setMerchantOrderId($this->magentoOrderId);
            $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
            $jsonResponse->setException($exception);
            $jsonResponse->toJson();
            $this->insertLog($exception);
        }

        $this->unblockConcurrency(true);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $jsonResponse->printResponse();
        } else {
            $returnUrl = $this->getRedirectUrl();
            $returnMessage = sprintf(
                "[%s][%s][%s][%s]",
                $_GET['origin'],
                $this->quoteId,
                $this->magentoOrderId,
                $returnUrl
            );
            $this->insertLog(null, $returnMessage);
            $this->_redirect($returnUrl);
        }
    }

    /**
     * COMMON FUNCTIONS
     */

    /**
     * @throws ConcurrencyException
     * @throws QuoteNotFoundException
     * @throws UnknownException
     */
    private function checkConcurrency()
    {
        $this->getQuoteId();
        $this->checkDbTable();
        $this->unblockConcurrency();
        $this->blockConcurrency();
    }

    /**
     * @throws MerchantOrderNotFoundException
     */
    private function getMerchantOrder()
    {
        try {
            /** @var Quote quote */
            $this->quote = $this->quoteRepository->get($this->quoteId);
        } catch (\Exception $e) {
            throw new MerchantOrderNotFoundException();
        }
    }

    /**
     * @throws UnknownException
     */
    private function getPagantisOrderId()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection     = $this->dbObject->getConnection();
            $tableName        = $this->dbObject->getTableName(self::ORDERS_TABLE);
            $query            = "select order_id from $tableName where id='$this->quoteId'";
            $queryResult      = $dbConnection->fetchRow($query);
            $this->pagantisOrderId = $queryResult['order_id'];
            if ($this->pagantisOrderId == '') {
                throw new NoIdentificationException();
            }
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @throws OrderNotFoundException
     */
    private function getPagantisOrder()
    {
        try {
            $this->orderClient = new Client(
                $this->config['pagantis_public_key'],
                $this->config['pagantis_private_key']
            );
            $this->pagantisOrder = $this->orderClient->getOrder($this->pagantisOrderId);
        } catch (\Exception $e) {
            throw new OrderNotFoundException();
        }
    }

    /**
     * @throws AlreadyProcessedException
     * @throws WrongStatusException
     */
    private function checkOrderStatus()
    {
        try {
            $this->checkPagantisStatus(array('AUTHORIZED'));
        } catch (\Exception $e) {
            $this->getMagentoOrderId();
            if ($this->magentoOrderId!='') {
                throw new AlreadyProcessedException();
            } else {
                throw new WrongStatusException($this->pagantisOrder->getStatus());
            }
        }
    }

    /**
     * @throws AlreadyProcessedException
     */
    private function checkMerchantOrderStatus()
    {
        if ($this->quote->getIsActive()=='0') {
            $this->getMagentoOrderId();
            throw new AlreadyProcessedException();
        }
    }

    /**
     * @throws AmountMismatchException
     */
    private function validateAmount()
    {
        $pagantisAmount = $this->pagantisOrder->getShoppingCart()->getTotalAmount();
        $merchantAmount = intval(strval(100 * $this->quote->getGrandTotal()));
        if ($pagantisAmount != $merchantAmount) {
            throw new AmountMismatchException($pagantisAmount, $merchantAmount);
        }
    }

    /**
     * @throws UnknownException
     */
    private function processMerchantOrder()
    {
        try {
            $this->saveOrder();
            $this->updateBdInfo();
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @return false|string
     * @throws UnknownException
     */
    private function confirmpagantisOrder()
    {
        try {
            $this->pagantisOrder = $this->orderClient->confirmOrder($this->pagantisOrderId);
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }

        $jsonResponse = new JsonSuccessResponse();
        $jsonResponse->setStatusCode(200);
        $jsonResponse->setMerchantOrderId($this->magentoOrderId);
        $jsonResponse->setpagantisOrderId($this->pagantisOrderId);
        $jsonResponse->setResult(self::CPO_OK_MSG);
        return $jsonResponse->toJson();
    }

    /**
     * UTILS FUNCTIONS
     */

    /** STEP 1 CC - Check concurrency
     * @throws QuoteNotFoundException
     */
    private function getQuoteId()
    {
        $this->quoteId = $this->getRequest()->getParam('quoteId');
        if ($this->quoteId == '') {
            throw new QuoteNotFoundException();
        }
    }

    /**
     * @return \Zend_Db_Statement_Interface
     * @throws UnknownException
     */
    private function checkDbTable()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            $query        = "CREATE TABLE IF NOT EXISTS $tableName(`id` int not null,`timestamp` int not null,PRIMARY KEY (`id`))";

            return $dbConnection->query($query);
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @return void|\Zend_Db_Statement_Interface
     * @throws UnknownException
     */
    private function checkDbLogTable()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName = $this->dbObject->getTableName(self::LOGS_TABLE);
            if (!$dbConnection->isTableExists($tableName)) {
                $table = $dbConnection
                    ->newTable($tableName)
                    ->addColumn(
                        'id',
                        Table::TYPE_SMALLINT,
                        null,
                        array('nullable'=>false, 'auto_increment'=>true, 'primary'=>true)
                    )
                    ->addColumn('log', Table::TYPE_TEXT, null, array('nullable'=>false))
                    ->addColumn(
                        'createdAt',
                        Table::TYPE_TIMESTAMP,
                        null,
                        array('nullable'=>false, 'default'=>Table::TIMESTAMP_INIT)
                    );
                return $dbConnection->createTable($table);
            }

            return;
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @param bool $mode
     *
     * @throws \Exception
     */
    private function unblockConcurrency($mode = false)
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
            if ($mode == false) {
                $dbConnection->delete($tableName, "timestamp<".(time() - 5));
            } elseif ($this->quoteId!='') {
                $dbConnection->delete($tableName, "id=".$this->quoteId);
            }
        } catch (Exception $exception) {
            throw new ConcurrencyException();
        }
    }

    /**
     * @throws ConcurrencyException
     * @throws UnknownException
     */
    private function blockConcurrency()
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
        $dbConnection = $this->dbObject->getConnection();
        $tableName    = $this->dbObject->getTableName(self::CONCURRENCY_TABLE);
        $query = "SELECT timestamp FROM $tableName where id='$this->quoteId'";
        $resultsSelect = $dbConnection->fetchRow($query);
        if (isset($resultsSelect['timestamp'])) {
            if ($this->getOrigin() == 'Notification') {
                throw new ConcurrencyException();
            } else {
                $query = sprintf(
                    "SELECT timestamp - %s as rest FROM %s %s",
                    (time() - self::CONCURRENCY_TIMEOUT),
                    $tableName,
                    "WHERE id='".$this->quoteId."'"
                );
                $resultsSelect = $dbConnection->fetchRow($query);
                $restSeconds   = isset($resultsSelect['rest']) ? ($resultsSelect['rest']) : 0;
                $expirationSec = ($restSeconds > self::CONCURRENCY_TIMEOUT) ? self::CONCURRENCY_TIMEOUT : $restSeconds;
                if ($expirationSec > 0) {
                    sleep($expirationSec + 1);
                }

                $logMessage  = sprintf(
                    "User waiting %s seconds, default seconds %s, bd time to expire %s seconds",
                    $expirationSec,
                    self::CONCURRENCY_TIMEOUT,
                    $restSeconds
                );
                throw new UnknownException($logMessage);
            }
        } else {
            $dbConnection->insert($tableName, array('id'=>$this->quoteId, 'timestamp'=>time()));
        }
    }

    /** STEP 2 GMO - Get Merchant Order */
    /** STEP 3 GPOI - Get Pagantis OrderId */
    /** STEP 4 GPO - Get Pagantis Order */
    /** STEP 5 COS - Check Order Status */
    /**
     * @param $statusArray
     *
     * @throws \Exception
     */
    private function checkPagantisStatus($statusArray)
    {
        $pagantisStatus = array();
        foreach ($statusArray as $status) {
            $pagantisStatus[] = constant("\Pagantis\OrdersApiClient\Model\Order::STATUS_$status");
        }

        $payed = in_array($this->pagantisOrder->getStatus(), $pagantisStatus);
        if (!$payed) {
            throw new WrongStatusException($this->pagantisOrder->getStatus());
        }
    }

    /** STEP 6 CMOS - Check Merchant Order Status */
    /**
     * @throws \Exception
     */
    private function getMagentoOrderId()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);

            if ($this->pagantisOrderId == '') {
                $this->getPagantisOrderId();
            }
            $pagantisOrderId   = $this->pagantisOrderId;

            $query        = "select mg_order_id from $tableName where id='$this->quoteId' and order_id='$pagantisOrderId'";
            $queryResult  = $dbConnection->fetchRow($query);
            $this->magentoOrderId = $queryResult['mg_order_id'];
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /** STEP 7 VA - Validate Amount */
    /** STEP 8 PMO - Process Merchant Order */
    /**
     * @throws UnknownException
     */
    private function saveOrder()
    {
        try {
            $this->paymentInterface->setMethod(self::PAYMENT_METHOD);
            $this->magentoOrderId = $this->quoteManagement->placeOrder($this->quoteId, $this->paymentInterface);

            /** @var OrderRepositoryInterface magentoOrder */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);
            $metadataOrder = $this->pagantisOrder->getMetadata();
            $metadataInfo = null;
            foreach ($metadataOrder as $metadataKey => $metadataValue) {
                if ($metadataKey == 'promotedProduct') {
                    $metadataInfo.= " Producto promocionado = $metadataValue //";
                }
            }

            $this->magentoOrder->addStatusHistoryComment($metadataInfo)
                               ->setIsCustomerNotified(false)
                               ->setEntityName('order')
                               ->save();

            $comment = 'pagantisOrderId: '.$this->pagantisOrder->getId(). ' ' .
                       'pagantisOrderStatus: '.$this->pagantisOrder->getStatus(). ' ' .
                       'via: '.$this->origin;
            $this->magentoOrder->addStatusHistoryComment($comment)
                               ->setIsCustomerNotified(false)
                               ->setEntityName('order')
                               ->save();

            if ($this->magentoOrderId == '') {
                throw new UnknownException('Order can not be saved');
            }
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @throws UnknownException
     */
    private function updateBdInfo()
    {
        try {
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            $tableName    = $this->dbObject->getTableName(self::ORDERS_TABLE);
            $pagantisOrderId   = $this->pagantisOrder->getId();
            $dbConnection->update(
                $tableName,
                array('mg_order_id' => $this->magentoOrderId),
                "order_id='$pagantisOrderId' and id='$this->quoteId'"
            );
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /** STEP 9 CPO - Confirmation Pagantis Order */
    /**
     * @throws UnknownException
     */
    private function rollbackMerchantOrder()
    {
        try {
            $this->magentoOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
            $this->magentoOrder->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $this->magentoOrder->save();
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param mixed $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * @return string
     * @throws UnknownException
     */
    private function getRedirectUrl()
    {
        //$returnUrl = 'checkout/#payment';
        $returnUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);

        if ($this->pagantisOrderId == '') {
            $this->getPagantisOrderId();
        }

        if ($this->magentoOrderId!='') {
            /** @var Order $this->magentoOrder */
            $this->magentoOrder = $this->orderRepositoryInterface->get($this->magentoOrderId);
            if (!$this->_objectManager->get(\Magento\Checkout\Model\Session\SuccessValidator::class)->isValid()) {
                $this->checkoutSession
                    ->setLastOrderId($this->magentoOrderId)
                    ->setLastRealOrderId($this->magentoOrder->getIncrementId())
                    ->setLastQuoteId($this->quoteId)
                    ->setLastSuccessQuoteId($this->quoteId)
                    ->setLastOrderStatus($this->magentoOrder->getStatus());
            }

            //Magento status flow => https://docs.magento.com/m2/ce/user_guide/sales/order-status-workflow.html
            //Order Workflow => https://docs.magento.com/m2/ce/user_guide/sales/order-workflow.html
            $orderStatus    = strtolower($this->magentoOrder->getStatus());
            $acceptedStatus = array('processing', 'completed');
            if (in_array($orderStatus, $acceptedStatus)) {
                if (isset($this->extraConfig['PAGANTIS_OK_URL']) &&  $this->extraConfig['PAGANTIS_OK_URL']!= '') {
                    $returnUrl = $this->extraConfig['PAGANTIS_OK_URL'];
                } else {
                    $returnUrl = 'checkout/onepage/success';
                }
            } else {
                if (isset($this->extraConfig['PAGANTIS_KO_URL']) && $this->extraConfig['PAGANTIS_KO_URL'] != '') {
                    $returnUrl = $this->extraConfig['PAGANTIS_KO_URL'];
                } else {
                    //$returnUrl = 'checkout/#payment';
                    $returnUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
                }
            }
        }
        return $returnUrl;
    }

    /**
     * @param null $exceptionMessage
     * @param null $logMessage
     *
     * @throws UnknownException
     */
    private function insertLog($exceptionMessage = null, $logMessage = null)
    {
        try {
            $this->checkDbLogTable();
            $logEntryJson = '';
            if ($exceptionMessage instanceof \Exception) {
                $logEntry     = new LogEntry();
                $logEntryJson = $logEntry->error($exceptionMessage)->toJson();
            } elseif ($logMessage != null) {
                $logEntry     = new LogEntry();
                $logEntryJson = $logEntry->info($logMessage)->toJson();
            }

            if ($logEntryJson != '') {
                /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
                $dbConnection = $this->dbObject->getConnection();
                $tableName    = $this->dbObject->getTableName(self::LOGS_TABLE);
                $dbConnection->insert($tableName, array('log' => $logEntryJson));
            }
        } catch (\Exception $e) {
            throw new UnknownException($e->getMessage());
        }
    }
}
