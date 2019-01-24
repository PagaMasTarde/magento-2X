# Module installation <img src="https://pagamastarde.com/img/icons/logo.svg" width="100" align="right">

[![Build Status](https://travis-ci.org/PagaMasTarde/magento-2X.svg?branch=master)](https://travis-ci.org/PagaMasTarde/magento-2X)
[![Latest Stable Version](https://poser.pugx.org/pagamastarde/magento-2x/v/stable)](https://packagist.org/packages/pagamastarde/magento-2x)
[![composer.lock](https://poser.pugx.org/pagamastarde/magento-2x/composerlock)](https://packagist.org/packages/pagamastarde/magento-2x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PagaMasTarde/magento-2x/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PagaMasTarde/magento-2x/?branch=master)

## :hand: Requirements
This module supports Magento v2.1 and higher.

## :floppy_disk: Installation
To install the module of Paga+Tarde in your Magento shop, you can use Composer:

```php
    composer require pagamastarde/magento-2x --no-dev
    bin/magento module:enable DigitalOrigin_Pmt
    bin/magento setup:upgrade
```

## :gear: Configuration
Configure the module in Magento admin panel using the information found in your [Paga+Tarde profile](https://bo.pagamastarde.com/shop). 

For more information about how to config the module, see our [configuration section](/Documentation/configuration.md).

## :arrow_forward: Use
To use in a real environment you should enable your Paga+Tarde account.

For more information about how to use the module, see our [use section](/Documentation/use.md).
