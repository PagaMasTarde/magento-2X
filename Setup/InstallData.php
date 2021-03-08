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
    public $defaultConfigs = array(
       'PAGANTIS_TITLE'=>'Instant financing',
       'PAGANTIS_SIMULATOR_DISPLAY_TYPE'=>'sdk.simulator.types.PRODUCT_PAGE',
       'PAGANTIS_SIMULATOR_DISPLAY_TYPE_CHECKOUT'=>'sdk.simulator.types.CHECKOUT_PAGE',
       'PAGANTIS_SIMULATOR_DISPLAY_SKIN'=>'sdk.simulator.skins.BLUE',
       'PAGANTIS_SIMULATOR_DISPLAY_POSITION'=>'hookDisplayProductButtons',
       'PAGANTIS_SIMULATOR_START_INSTALLMENTS'=>3,
       'PAGANTIS_SIMULATOR_MAX_INSTALLMENTS'=>12,
       'PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'=>'default',
       'PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION'=>'sdk.simulator.positions.INNER',
       'PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR'=>'default',
       'PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR'=>'default',
       'PAGANTIS_FORM_DISPLAY_TYPE'=>0,
       'PAGANTIS_DISPLAY_MIN_AMOUNT'=>1,
       'PAGANTIS_DISPLAY_MAX_AMOUNT'=>0,
       'PAGANTIS_URL_OK'=>'',
       'PAGANTIS_URL_KO'=>'',
       'PAGANTIS_TITLE_EXTRA' => 'Pay up to 12 comfortable installments with Pagantis. Completely online and sympathetic request, and the answer is immediate!',
       'PAGANTIS_ALLOWED_COUNTRIES' => 'a:3:{i:0;s:2:"es";i:1;s:2:"it";i:2;s:2:"fr";}',
       'PAGANTIS_PROMOTION_EXTRA' => '<p class="promoted">Finance this product <span class="pg-no-interest">without interest!</span></p>',
       'PAGANTIS_SIMULATOR_THOUSANDS_SEPARATOR' => '.',
       'PAGANTIS_SIMULATOR_DECIMAL_SEPARATOR' => ',',
       'PAGANTIS_SIMULATOR_DISPLAY_SITUATION' => '',
        //4x
        'PAGANTIS_DISPLAY_MIN_AMOUNT_4x'=>0,
        'PAGANTIS_DISPLAY_MAX_AMOUNT_4x'=>800,
        'PAGANTIS_TITLE_4x'=>'Until 4 installments, without fees',
    );

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $prefixedTableName = $setup->getTable(self::CONFIG_TABLE);
        if ($setup->tableExists($prefixedTableName)) {
            foreach ($this->defaultConfigs as $config => $value) {
                $setup->getConnection()
                      ->insert($prefixedTableName, array('config' => $config, 'value' => $value));
            }
        }
    }
}