<?php

namespace Pagantis\Pagantis\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Pagantis\Pagantis\Helper\Config;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'Pagantis_config';

    /** Orders tablename */
    const ORDERS_TABLE = 'cart_process';

    /** @var Config */
    public $config;

    /** @var string  */
    public $code;

    /** @var string  */
    public $group;

    /** @var string  */
    public $label;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * UpgradeData constructor.
     *
     * @param Config          $config
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(Config $config, EavSetupFactory $eavSetupFactory)
    {
        $this->config = $config;
        $this->code = 'pagantis_promoted';
        $this->group = 'General';
        $this->label = 'Pagantis Promoted';
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $prefixedTableName = $setup->getTable(self::CONFIG_TABLE);
        $prefixedOrdersTableName = $setup->getTable(self::ORDERS_TABLE);
        if ($setup->tableExists($prefixedTableName)) {
            if (version_compare($context->getVersion(), '8.3.1') < 0) {
                $newConfigs = array(
                    /* INSERT NEW CONFIGS PARAMS HERE:'config'=>'<value>'*/
                    'PAGANTIS_DISPLAY_MAX_AMOUNT' => 0
                );
                foreach ($newConfigs as $config => $value) {
                    $setup->getConnection()
                          ->insert($prefixedTableName, array('config' => $config, 'value' => $value));
                }
            }

            if (version_compare($context->getVersion(), '8.3.2') < 0) {
                $newConfigs = array(
                    /* INSERT NEW CONFIGS PARAMS HERE:'config'=>'<value>'*/
                    'PAGANTIS_SIMULATOR_DISPLAY_TYPE_CHECKOUT' => 'sdk.simulator.types.CHECKOUT_PAGE'
                );
                foreach ($newConfigs as $config => $value) {
                    $setup->getConnection()
                          ->insert($prefixedTableName, array('config' => $config, 'value' => $value));
                }
                $setup->getConnection()
                      ->update(
                          $prefixedTableName,
                          array('value' => 'sdk.simulator.types.PRODUCT_PAGE'),
                          "config='PAGANTIS_SIMULATOR_DISPLAY_TYPE'"
                      );

            }

            if (version_compare($context->getVersion(), '8.6.0') < 0) {
                $newConfigs = array(
                    /* INSERT NEW CONFIGS PARAMS HERE:'config'=>'<value>'*/
                    'PAGANTIS_DISPLAY_MIN_AMOUNT_4x'=>0,
                    'PAGANTIS_DISPLAY_MAX_AMOUNT_4x'=>800,
                    'PAGANTIS_TITLE_4x'=>'Until 4 installments, without fees',
                    'PAGANTIS_SIMULATOR_DISPLAY_SITUATION' => ''
                );
                foreach ($newConfigs as $config => $value) {
                    $setup->getConnection()
                          ->insert($prefixedTableName, array('config' => $config, 'value' => $value));
                }
                $setup->getConnection()
                      ->update(
                          $prefixedTableName,
                          array('value' => 'Instant financing'),
                          "config='PAGANTIS_TITLE'"
                      );
            }

            if (version_compare($context->getVersion(), '8.6.1') < 0) {
                if ($setup->tableExists($prefixedOrdersTableName)) {
                    $query = "ALTER TABLE $prefixedOrdersTableName ADD COLUMN token VARCHAR(32) NOT NULL AFTER order_id";
                    $setup->getConnection()->query($query);

                    $query = "ALTER TABLE $prefixedOrdersTableName DROP PRIMARY KEY, ADD PRIMARY KEY(id, order_id)";
                    $setup->getConnection()->query($query);
                }
            }
        }
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $this->code,
            [
                'group' => $this->group,
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => $this->label,
                'input' => 'boolean',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '0',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $setup->endSetup();
    }
}