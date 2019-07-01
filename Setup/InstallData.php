<?php

namespace Pagantis\Pagantis\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'Pagantis_config';

    /**
     * Variable which contains extra configuration. If you modify this array, modify it in UpdateData.php too
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
                           'PAGANTIS_TITLE_EXTRA' => 'Pay up to 12 comfortable installments with Pagantis. Completely online and sympathetic request, and the answer is immediate!',
                           'PAGANTIS_ALLOWED_COUNTRIES' => 'a:2:{i:0;s:2:"es";i:1;s:2:"it";}'
    );

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        foreach ($this->defaultConfigs as $config => $value) {
            $setup->getConnection()->insert(self::CONFIG_TABLE, array('config'=>$config, 'value'=>$value));
        }
    }
}