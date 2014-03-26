<?php
class Studioone_ArCa_ProcessingController extends Mage_Core_Controller_Front_Action {

	/**
	 * Order instance
	 */
	protected $_order;

	/**
	 *  Get order
	 *
	 *  @return	  Mage_Sales_Model_Order
	 */
	public function getOrder() {
		if ($this -> _order == null) {
		}
		return $this -> _order;
	}

	/**
	 * Send expire header to ajax response
	 *
	 */
	protected function _expireAjax() {
		if (!Mage::getSingleton('checkout/session') -> getQuote() -> hasItems()) {
			$this -> getResponse() -> setHeader('HTTP/1.1', '403 Session Expired');
			exit ;
		}
	}

	public function resultAction() {

		$postData = Mage::app() -> getRequest() -> getPost();
		Mage::log(print_r($postData, 1), null, __CLASS__ . '.log');

		$url = "https://www.arca.am:8194/ssljson.yaws";
		// Merchant should use the URL provided by ArCa!!!
		$url_test = "https://91.199.226.106/ssljson.php";
		// Merchant should use the URL provided by ArCa!!!
		$orderID = Mage::app() -> getRequest() -> getParam('orderID');
		$respcode = Mage::app() -> getRequest() -> getParam('respcode');
		$opaque = Mage::app() -> getRequest() -> getParam('opaque');

		$order = Mage::getModel('sales/order') -> load($orderID);

		Mage::log("$orderID,$respcode,$opaque,{$order->getId()}", null, __CLASS__ . '.log');
		$isTestMode = Mage::getStoreConfig('payment/arca/testmode') == '1' ? 'test_' : '';
		$checkUrl = Mage::getStoreConfig('payment/arca/testmode') == '1' ? $url_test : $url;

		$json_params = array('hostID' => Mage::getStoreConfig("payment/arca/{$isTestMode}hostID"), 'mid' => Mage::getStoreConfig("payment/arca/{$isTestMode}mid"), 'tid' => Mage::getStoreConfig("payment/arca/{$isTestMode}tid"), 'mtpass' => "123456789", 'orderID' => $order -> getId(), 'amount' => $order -> getGrandTotal(), 'currency' => "051", 'trxnDetails' => "Product(s) payment.");

		Mage::log(print_r($json_params, 1), null, __CLASS__ . '.log');
		$arcaInterface = Mage::getModel('arca/Interface');
		$arcaRespons = $arcaInterface -> check($json_params, 'merchant_check', $checkUrl);
		Mage::log(print_r($arcaRespons, 1), null, __CLASS__ . '.log');

		$this -> loadLayout() -> _initLayoutMessages('checkout/session') -> _initLayoutMessages('catalog/session') -> getLayout() -> getBlock('head') -> setTitle($this -> __('Arca Payment'));
		$this -> renderLayout();
	}

	protected function _getOrder($orderIncrementId) {

		return $order = Mage::getModel('sales/order') -> loadByIncrementId($orderIncrementId);
	}

	/**
	 * @return Mage_Customer_Model_Customer
	 */
	protected function _getCustomer() {
		return Mage::getSingleton('customer/session') -> getCustomer();
	}

	public function indexAction() {

		$this -> loadLayout() -> _initLayoutMessages('checkout/session') -> _initLayoutMessages('catalog/session') -> getLayout() -> getBlock('head') -> setTitle($this -> __('Arca Payment'));
		$this -> renderLayout();
		if (false == ($transaction = $this -> _saveArcaTransaction())) {
			Mage::getSingleton('checkout/session') -> setArcaTransactionId($transaction -> getTransactionId());
		};

		Mage::log($_getCheckout -> getLastOrderId(), null, __CLASS__ . '.log');

	}

	public function _saveArcaTransaction() {

		$_getCheckout = $this -> _getCheckout();

		Mage::log($_getCheckout -> getLastOrderId(), null, __CLASS__ . '.log');
		$order = $this -> _getOrder($_getCheckout -> getLastOrderId());
		Mage::log($order -> getGrandTotal(), null, __CLASS__ . '.log');

		try {
			$model = Mage::getModel('arca/transactions');
			$model -> setCustomerId($this -> _getCustomer() -> getId());
			$model -> setStoreId(Mage::app() -> getStore() -> getId());
			$model -> setOrderId($_getCheckout -> getLastOrderId());
			$model -> setCreateDate(date('Y-m-d h:i:s'));
			$model -> setUpdateDate(date('Y-m-d h:i:s'));
			$model -> setTotal($order -> getGrandTotal());
			$model -> setStatus('pendding');
			$model -> save();
			return $model;
		} catch(Mage_Core_Exception $e) {
			Mage::log($e -> getMessage(), null, __CLASS__ . '.log');
			Mage::log(print_r($e,1), null, __CLASS__ . '.log');
		}

		return false;
	}

	public function processingAction() {
		Mage::log(__FUNCTION__, null, __CLASS__ . '.log');
		/*$session = Mage::getSingleton('checkout/session');
		 $session->setPaypalStandardQuoteId($session->getQuoteId());
		 $this->getResponse()->setBody($this->getLayout()->createBlock('arca/redirect')->toHtml());
		 $session->unsQuoteId();
		 $session->unsRedirectUrl();*/
		echo __CLAS__ . __FUNCTION__;
	}

	/**
	 * When a customer chooses Paypal on Checkout/Payment page
	 *
	 */
	public function redirectAction() {
		/*$session = Mage::getSingleton('checkout/session');
		 $session->setPaypalStandardQuoteId($session->getQuoteId());
		 $this->getResponse()->setBody($this->getLayout()->createBlock('arca/redirect')->toHtml());
		 $session->unsQuoteId();
		 $session->unsRedirectUrl();*/
	}

	/**
	 * When a customer cancel payment from paypal.
	 */
	public function cancelAction() {
		/*$session = Mage::getSingleton('checkout/session');
		 $session->setQuoteId($session->getPaypalStandardQuoteId(true));
		 if ($session->getLastRealOrderId()) {
		 $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
		 if ($order->getId()) {
		 $order->cancel()->save();
		 }
		 }
		 $this->_redirect('checkout/cart');*/
	}

	/**
	 * when paypal returns
	 * The order information at this point is in POST
	 * variables.  However, you don't want to "process" the order until you
	 * get validation from the IPN.
	 */
	public function successAction() {
		/*$session = Mage::getSingleton('checkout/session');
		 $session->setQuoteId($session->getPaypalStandardQuoteId(true));
		 Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
		 $this->_redirect('checkout/onepage/success', array('_secure'=>true));*/
	}

	protected function _getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

}
