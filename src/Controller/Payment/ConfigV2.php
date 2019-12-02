<?php
namespace Pagantis\Pagantis\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class ConfigV2 extends Action implements CsrfAwareActionInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'Pagantis_config';

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /**
     * Variable which contains extra configuration.
     * @var array $defaultConfigs
     */
    public $defaultConfigs = array('PAGANTIS_TITLE'=>'Instant Financing',
                                   'PAGANTIS_SIMULATOR_DISPLAY_TYPE'=>'pgSDK.simulator.types.SIMPLE',
                                   'PAGANTIS_SIMULATOR_DISPLAY_SKIN'=>'pgSDK.simulator.skins.BLUE',
                                   'PAGANTIS_SIMULATOR_DISPLAY_POSITION'=>'hookDisplayProductButtons',
                                   'PAGANTIS_SIMULATOR_START_INSTALLMENTS'=>3,
                                   'PAGANTIS_SIMULATOR_MAX_INSTALLMENTS'=>12,
                                   'PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'=>'default',
                                   'PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION'=>'pgSDK.simulator.positions.INNER',
                                   'PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR'=>'default',
                                   'PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR'=>'default',
                                   'PAGANTIS_FORM_DISPLAY_TYPE'=>0,
                                   'PAGANTIS_DISPLAY_MIN_AMOUNT'=>1,
                                   'PAGANTIS_URL_OK'=>'',
                                   'PAGANTIS_URL_KO'=>'',
                                   'PAGANTIS_TITLE_EXTRA' => 'Pay up to 12 comfortable installments with Pagantis. Completely online and sympathetic request, and the answer is immediate!'
    );

    /**
     * Log constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Pagantis\Pagantis\Helper\Config      $pagantisConfig
     * @param ResourceConnection                    $dbObject
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Pagantis\Pagantis\Helper\Config $pagantisConfig,
        ResourceConnection $dbObject
    ) {
        $this->config = $pagantisConfig->getConfig();
        $this->dbObject = $dbObject;

        // CsrfAwareAction Magento2.3 compatibility
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof HttpRequest && $request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }

        return parent::__construct($context);
    }

    /**
     * Main function
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $response = array('status'=>null);
            $tableName = $this->dbObject->getTableName(self::CONFIG_TABLE);
            $secretKey = $this->getRequest()->getParam('secret');
            $privateKey = isset($this->config['pagantis_private_key']) ? $this->config['pagantis_private_key'] : null;

            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $dbConnection */
            $dbConnection = $this->dbObject->getConnection();
            if ($privateKey != $secretKey) {
                $response['status'] = 401;
                $response['result'] = 'Unauthorized';
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if (count($_POST)) {
                    foreach ($_POST as $config => $value) {
                        if (isset($this->defaultConfigs[$config]) && $response['status']==null) {
                            $dbConnection->update(
                                $tableName,
                                array('value' => $value),
                                "config='$config'"
                            );
                        } else {
                            $response['status'] = 400;
                            $response['result'] = 'Bad request';
                        }
                    }
                } else {
                    $response['status'] = 422;
                    $response['result'] = 'Empty data';
                }
            }

            $formattedResult = array();
            if ($response['status']==null) {
                $dbResult = $dbConnection->fetchAll("select * from $tableName");
                foreach ($dbResult as $value) {
                    $formattedResult[$value['config']] = $value['value'];
                }
                $response['result'] = $formattedResult;
            }
            $result = json_encode($response['result']);
            header("HTTP/1.1 ".$response['status'], true, $response['status']);
            header('Content-Type: application/json', true);
            header('Content-Length: '.strlen($result));
            echo($result);
            exit();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
