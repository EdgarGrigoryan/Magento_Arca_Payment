<?php
class Studioone_ArCa_ProcessingController extends Mage_Core_Controller_Front_Action {

	protected $_message;
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

	public function errorAction() {

	}

	public function resultAction() {

		$postData = Mage::app() -> getRequest() -> getPost();
		$order_id = Mage::app() -> getRequest() -> getParam('order_id');

		if (!empty($postData)) {

			Mage::log(print_r($postData, 1), null, __CLASS__ . '.log');

			$url = "https://www.arca.am:8194/ssljson.yaws";
			// Merchant should use the URL provided by ArCa!!!
			$url_test = "https://91.199.226.106/ssljson.php";
			// Merchant should use the URL provided by ArCa!!!
			$orderID = Mage::app() -> getRequest() -> getParam('order_id');
			$respcode = Mage::app() -> getRequest() -> getParam('respcode');
			$opaque = Mage::app() -> getRequest() -> getParam('opaque');
			$transaction = Mage::getModel('arca/transactions') -> load($orderID);
			Mage::log(print_r($transaction, 1), null, __CLASS__ . 'transaction.log');

			if ($respcode == '00') {
				$transaction -> setStatus('success');
				$transaction -> save();
			} else {
				$transaction -> setStatus('error');
				$transaction -> save();
				$url = Mage::getModel('core/url') -> getUrl("arca/processing/error");

				Mage::app() -> getResponse() -> setRedirect($url);

			}

			$isTestMode = Mage::getStoreConfig('payment/arca/testmode') == '1' ? 'test_' : '';
			$checkUrl = Mage::getStoreConfig('payment/arca/testmode') == '1' ? $url_test : $url;

			$json_params = array('hostID' => Mage::getStoreConfig("payment/arca/{$isTestMode}hostID"), 'mid' => Mage::getStoreConfig("payment/arca/{$isTestMode}mid"), 'tid' => Mage::getStoreConfig("payment/arca/{$isTestMode}tid"), 'mtpass' => "123456789", 'orderID' => $transaction -> getId(), 'amount' => $transaction -> getTotal(), 'currency' => "051", 'trxnDetails' => "Product(s) payment.");

			Mage::log(print_r($json_params, 1), null, __CLASS__ . '.log');
			$arcaInterface = Mage::getModel('arca/Interface');
			$arcaRespons = $arcaInterface -> check($json_params, 'merchant_check', $checkUrl);
			Mage::log(print_r($arcaRespons, 1), null, __CLASS__ . '.log');
			$logModel = Mage::getModel('arca/transactions_log');

			foreach ($arcaRespons as $key => $value) {
				if (isset($value -> respcode)) {
					try {

						$logModel -> setTransactionId($transaction -> getId());
						$logModel -> setRespcode($value -> respcode);
						$logModel -> setDescr($value -> descr);
						$logModel -> save();
					} catch(Exception $e) {
						printf('<pre>%s</pre>', print_r($e, 1));
						die ;
					}
					break;
				}
			}

			switch ($logModel->getRespcode()) {

				case '00' :
					$this -> _successAction($logModel -> getTransactionId());
					break;
				default :
					$this -> _cancelAction($logModel -> getTransactionId());
					break;
			}
			Mage::log(print_r($arcaRespons, 1), null, __CLASS__ . '.log');
		} elseif ($order_id) {
			$this -> _successAction($order_id);
			//$this -> _cancelAction($order_id);

		} else {

		}

		$this -> loadLayout() -> _initLayoutMessages('checkout/session') -> _initLayoutMessages('catalog/session') -> getLayout() -> getBlock('head') -> getLayout() -> getBlock('arca.result') -> setMessage($this -> _message) -> setTransactionId($logModel -> getTransactionId()) -> setTitle($this -> __('Arca Payment Result'));

		$this -> renderLayout();
	}

	protected function _successAction($transactionId) {
		try {

			$transaction = Mage::getModel('arca/transactions') -> load($transactionId);
			$transaction -> setStatus('cheched');
			$transaction -> save();

			$url = "https://www.arca.am:8194/ssljson.yaws";
			// Merchant should use the URL provided by ArCa!!!
			$url_test = "https://91.199.226.106/ssljson.php";
			$transaction = Mage::getModel('arca/transactions') -> load($transactionId);
			$isTestMode = Mage::getStoreConfig('payment/arca/testmode') == '1' ? 'test_' : '';
			$checkUrl = Mage::getStoreConfig('payment/arca/testmode') == '1' ? $url_test : $url;
			$json_params = array('hostID' => Mage::getStoreConfig("payment/arca/{$isTestMode}hostID"), 'mid' => Mage::getStoreConfig("payment/arca/{$isTestMode}mid"), 'tid' => Mage::getStoreConfig("payment/arca/{$isTestMode}tid"), 'mtpass' => "123456789", 'orderID' => $transaction -> getId(), 'amount' => $transaction -> getTotal(), 'currency' => "051", 'trxnDetails' => "Product(s) payment.");
			$arcaInterface = Mage::getModel('arca/Interface');
			$arcaRespons = $arcaInterface -> check($json_params, 'confirmation', $checkUrl);
			Mage::log(print_r($arcaRespons, 1), null, __CLASS__ . '.log');
			$arcaResponsData = array_shift($arcaRespons);

			if ($arcaResponsData -> respcode == '00') {
				$arcaInterface -> checkBuilding($arcaResponsData);
				$transaction -> setStatus('confirmed');
				$transaction -> save();
				$this -> _updateInvoce($transactionId);

			}
			$this -> _message = $this -> __('Transaction For Order %s compltetd ', $transaction -> getOrderId());

		} catch(Exception $e) {
			Mage::log(print_r($e, 1), null, 'Exception' . __CLASS__ . '.log');
			$this -> _message = $this -> __('Error during processing %s ', $e -> getMessage() . $e -> getFile() . $e -> getLine());
		}
	}

	protected function _updateInvoce($transactionId) 
	{
		$transaction = Mage::getModel('arca/transactions') -> load($transactionId);
		$order_id = $transaction -> getOrderId();
		echo $transaction -> getInvoiceId();
		
		die;
		$invoice = Mage::getModel('sales/service_order', $order) ->load($transaction -> getInvoiceId());
		$invoice -> setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
		$transactionSave = Mage::getModel('core/resource_transaction') -> addObject($invoice) -> addObject($invoice -> getOrder());
		$transactionSave -> save();
		
	}

	protected function _createInvoce($transactionId) {
		$transaction = Mage::getModel('arca/transactions') -> load($transactionId);

		$order_id = $transaction -> getOrderId();

		$order = Mage::getModel("sales/order") -> load($order_id);

		if (!$order -> canInvoice()) {

			Mage::throwException(Mage::helper('core') -> __('Cannot create an invoice.'));
		}

		$invoice = Mage::getModel('sales/service_order', $order) -> prepareInvoice();
		if (!$invoice -> getTotalQty()) {
			Mage::throwException(Mage::helper('core') -> __('Cannot create an invoice without products.'));
		}
		//$invoice -> setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
		$invoice -> register();

		$transactionSave = Mage::getModel('core/resource_transaction') -> addObject($invoice) -> addObject($invoice -> getOrder());

		$transactionSave -> save();
		$this -> _message = $this -> __('Order %s payment complete', $order_id);
		// $order -> setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true) -> save();

		return $invoice -> getId();

	}

	protected function _cancelAction($transactionId) {

		try {

			$transaction = Mage::getModel('arca/transactions') -> load($transactionId, 'transaction_id');
			$transaction -> setUpdateDate(date('Y-m-d h:i:s'));
			$transaction -> setStatus('cancel');
			$transaction -> save();
			$order = Mage::getModel('sales/order') -> load($transaction -> getOrderId());
			$order -> setState(Mage_Sales_Model_Order::STATE_CANCELED, true) -> save();
			$this -> _message = $this -> __('Order %s was canceld', $order -> getId());
		} catch(Exception $e) {

			$this -> _message = $this -> __('Error during processing %s ', $e -> getMessage());
		}

	}

	protected function _getOrder($orderIncrementId) {

		return $order = Mage::getModel('sales/order') -> load($orderIncrementId);
	}

	/**
	 * @return Mage_Customer_Model_Customer
	 */
	protected function _getCustomer() {
		return Mage::getSingleton('customer/session') -> getCustomer();
	}

	public function indexAction() {

		if (false == ($transaction = $this -> _saveArcaTransaction())) {
			Mage::getSingleton('checkout/session') -> setArcaTransactionId($transaction -> getTransactionId());
		}

		$this -> loadLayout() -> _initLayoutMessages('checkout/session') -> _initLayoutMessages('catalog/session') -> getLayout() -> getBlock('head') -> setTitle($this -> __('Arca Payment')) -> getLayout() -> getBlock('form.arca') -> setArcaTransactionId($transaction -> getTransactionId());

		$this -> renderLayout();
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
			$model -> setCreateDate(date('Y-m-d H:i:s'));
			$model -> setUpdateDate(date('Y-m-d H:i:s'));
			$model -> setTotal($order -> getGrandTotal());
			$model -> setStatus('pendding');
			$model -> save();
			$InvoceId = $this -> _createInvoce($model -> getId());
			//$model -> setInvoceId($InvoceId);
			$model -> save();

			return $model;
		} catch(Mage_Core_Exception $e) {
			Mage::log($e -> getMessage(), null, __CLASS__ . '.log');
			Mage::log(print_r($e, 1), null, __CLASS__ . '.log');
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

	protected function _getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

}
