<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Smasoft
 * @package     Smasoft_Oneclikorder
 * @copyright   Copyright (c) 2013 Slabko Michail. <l.nagash@gmail.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

    $installer->run("
    DROP TABLE IF EXISTS  `{$installer->getTable('arca_transactions')}`;
    CREATE TABLE `{$installer->getTable('arca_transactions')}` (
       `transaction_id` int(10) unsigned NOT NULL auto_increment,
       `order_id` int(10) unsigned NOT NULL,
       `store_id` int(10) unsigned NOT NULL,
       `order_id` int(10) unsigned NOT NULL,
       `customer_id` int(10) unsigned NOT NULL,
       `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       `update_date` timestamp NOT NULL,
       `total` decimal(10,2) NOT NULL,
       `status` varchar(256) NOT NULL,
       PRIMARY KEY  (`transaction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    
");

$installer->endSetup();
