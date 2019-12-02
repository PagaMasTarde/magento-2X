<?php

namespace Pagantis\Pagantis\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class DisplayType
 * @package Pagantis\Pagantis\Model\Adminhtml\Source
 */
class DisplayType implements ArrayInterface
{
    /**
     * IFRAME
     */
    const IFRAME = 1;
    /**
     * REDIRECT
     */
    const REDIRECT = 0;
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => __(' Iframe'),
                'value' => self::IFRAME,
            ),
            array(
                'label' => __(' Redirect'),
                'value' => self::REDIRECT,
            )
        );
    }
}
