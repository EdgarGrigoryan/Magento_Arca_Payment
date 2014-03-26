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


/**
 * use Mage_Core_Model_Mysql4_Abstract for compatibility with old versions
 */
class Studioone_ArCa_Model_Transactions extends Mage_Core_Model_Abstract
{
	/**
     * Initialize resources
     */
    protected function _construct()
    {
    	parent::_construct();
		
        $this->_init('studioone_arca/transactions');
    }
      
}