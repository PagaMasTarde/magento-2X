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

        if (version_compare($context->getVersion(), '7.2.0') < 0) {
            $newConfigs = array(
                /* INSERT NEW CONFIGS PARAMS HERE:'config'=>'<config>','value'=>'<value>'*/);
            foreach ($newConfigs as $config => $value) {
                $setup->getConnection()->insert(self::CONFIG_TABLE, array('config'=>$config, 'value'=>$value));
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