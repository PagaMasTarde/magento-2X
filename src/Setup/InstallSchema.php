<?php

namespace Pagantis\Pagantis\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /** Config tablename */
    const CONFIG_TABLE = 'Pagantis_config';

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()
                           ->newTable($installer->getTable(self::CONFIG_TABLE))
                           ->addColumn(
                               'id',
                               \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                               null,
                               ['identity' => true, 'unsigned' => true, 'nullable' =>
                                   false, 'primary' => true],
                               'Entity ID'
                           )
                           ->addColumn(
                               'config',
                               \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                               60,
                               ['nullable' => false],
                               'Config'
                           )
                        ->addColumn(
                            'value',
                            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            1000,
                            ['nullable' => false],
                            'Value'
                        )
                           ->setComment('Pagantis config table');

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
