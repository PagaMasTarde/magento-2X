<?php

namespace Pagantis\Pagantis\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SandboxType
 * @package Pagantis\Pagantis\Model\Adminhtml\Source
 */
class SandboxType implements ArrayInterface
{
    /**
     * PRODUCTION
     */
    const PRODUCTION = 1;
    /**
     * TESTING
     */
    const TESTING = 0;
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => __(' Production'),
                'value' => self::PRODUCTION,
            ),
            array(
                'label' => __(' Testing'),
                'value' => self::TESTING,
            )
        );
    }
}
