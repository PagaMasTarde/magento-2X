<?php

namespace Pagantis\Pagantis\Helper;

use Magento\Framework\App\ResourceConnection;

/**
 * Class ExtraConfig
 * @package Pagantis\Pagantis\Helper
 */
class ExtraConfig
{
    /** Config tablename */
    const CONFIG_TABLE = 'Pagantis_config';

    /** @var ResourceConnection $dbObject */
    protected $dbObject;

    /**
     * ExtraConfig constructor.
     *
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->dbObject = $resource;
    }

    /**
     * @return array
     */
    public function getExtraConfig()
    {
        $data = array();
        $dbConnection = $this->dbObject->getConnection();
        $tableName = self::CONFIG_TABLE;
        if ($dbConnection->isTableExists($tableName)) {
            $result = $dbConnection->fetchAll("select * from $tableName");
            if (count($result)) {
                foreach ($result as $value) {
                    $data[$value['config']] = $value['value'];
                }
            }
        }

        return $data;
    }
}