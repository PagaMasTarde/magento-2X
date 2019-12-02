<?php

namespace Pagantis\Pagantis\Model\Adminhtml\Source;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class ConfigButtonLinkType
 * @package Pagantis\Pagantis\Model\Adminhtml\Source
 */
class ConfigButtonLinkType extends Field
{
    /**
     * Path to block template
     */
    const WIZARD_TEMPLATE = 'Pagantis_Pagantis::button.phtml';

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $label = __($originalData['button_label']);
        $url =   $this->escapeHtml($originalData['button_url']);
        $labelCredentials = __($originalData['button_credentials_label']);
        $urlCredentials =   $this->escapeHtml($originalData['button_credentials_url']);
        return <<<EOD
<div class="pp-buttons-container">
    <button onclick="javascript:window.open('$url')" class="scalable" type="button" id="bo_pagantis">
        <span>$label</span>
    </button>
    <button onclick="javascript:window.open('$urlCredentials')" class="scalable" type="button" id="api_pagantis">
        <span>$labelCredentials</span>
    </button>
</div>
EOD;
    }
}
