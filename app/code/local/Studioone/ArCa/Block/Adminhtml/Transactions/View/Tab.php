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

class Studioone_ArCa_Block_Adminhtml_Transactions_View_Tab extends Mage_Adminhtml_Block_Widget_Tabs {
	public function __construct() {
		parent::__construct();
		$this -> setId('sales_order_view_tabs');
		$this -> setDestElementId('sales_order_view');
		$this -> setTitle(Mage::helper('sales') -> __('Transaction View'));

		$_transaction = $this -> getTransaction();

		$state = $_transaction -> getState();

		switch ($state) {
			case 'pending' :
			case 'success' :
				$this -> _addButton('Check status', array('label' => $this -> __("Check Transaction status")));
				break;
			case 'checked' :
				$this -> _addButton('Confirm Transaction', array('label' => $this -> __("Confirm Transaction ")));
				$this -> _addButton('Cancel Transaction', array('label' => $this -> __("Cancel Transaction ")));

				break;

			default :
				break;
		}
	}

	/**
	 * Retrieve order model object
	 *
	 * @return Mage_Sales_Order
	 */
	public function getOrder() {
		return Mage::registry('arca_transaction_order');
	}

	/**
	 * @return Studioone_ArCa_Model_Transactions
	 */
	public function getTransaction() {

		return $arca_transaction = Mage::registry('arca_transaction');
	}

	public function getCustomer() {
		$arca_transaction = Mage::registry('arca_transaction');
		$customer_id = $arca_transaction -> getCustomerId();
		return Mage::getModel('customer/customer') -> load($customer_id);

	}

	/**
	 * Return back url for view grid
	 *
	 * @return string
	 */
	public function getBackUrl() {
		return $this -> getUrl('*/*/index');
	}

	public function getHeaderText() {
		return Mage::helper('sales') -> __('Transaction # %s | %s, Order #%s', $this -> getTransaction() -> getId(), $this -> formatDate($this -> getTransaction() -> getCreateDate(), 'medium', true), $this -> getOrder() -> getId());
	}

}
