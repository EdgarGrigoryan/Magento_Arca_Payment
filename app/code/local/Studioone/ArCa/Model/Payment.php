<?php

/**
 * Our test CC module adapter
 */
class Studioone_ArCa_Model_Payment extends Mage_Payment_Model_Method_Abstract {
	/**
	 * unique internal payment method identifier
	 *
	 * @var string [a-z0-9_]
	 */
	protected $_code = 'arca';
	protected $_formBlockType = 'arca/form_arca';
	protected $_infoBlockType = 'arca/info_arca';

	/**
	 * Here are examples of flags that will determine functionality availability
	 * of this module to be used by frontend and backend.
	 *
	 * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
	 *
	 * It is possible to have a custom dynamic logic by overloading
	 * public function can* for each flag respectively
	 */

	/**
	 * Is this payment method a gateway (online auth/charge) ?
	 */
	protected $_isGateway = true;

	/**
	 * Can authorize online?
	 */
	protected $_canAuthorize = true;

	/**
	 * Can capture funds online?
	 */
	protected $_canCapture = true;

	/**
	 * Can capture partial amounts online?
	 */
	protected $_canCapturePartial = false;

	/**
	 * Can refund online?
	 */
	protected $_canRefund = false;

	/**
	 * Can void transactions online?
	 */
	protected $_canVoid = true;

	/**
	 * Can use this payment method in administration panel?
	 */
	protected $_canUseInternal = false;

	/**
	 * Can show this payment method as an option on checkout payment page?
	 */
	protected $_canUseCheckout = true;

	/**
	 * Is this payment method suitable for multi-shipping checkout?
	 */
	protected $_canUseForMultishipping = true;

	/**
	 * Can save credit card information for future processing?
	 */
	protected $_canSaveCc = false;
	protected $_config = null;
	/**
	 * Here you will need to implement authorize, capture and void public methods
	 *
	 * @see examples of transaction specific public methods such as
	 * authorize, capture and void in Mage_Paygate_Model_Authorizenet
	 */

	public function assignData($data) {
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}

		$this -> getInfoInstance() -> setPoNumber($data -> getPoNumber());
		return $this;
	}

	protected function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote() {
		return $this -> getCheckout() -> getQuote();
	}

	/**
	 * Config instance getter
	 * @return Mage_Paypal_Model_Config
	 */
	public function getConfig() {
		if (null === $this -> _config) {
			$params = array($this -> _code);
			if ($store = $this -> getStore()) {
				$params[] = is_object($store) ? $store -> getId() : $store;
			}
			$this -> _config = Mage::getModel('arca/config', $params);
		}
		return $this -> _config;
	}

	/**
	 * Create main block for standard form
	 *
	 */
	public function createFormBlock($name) {
		$block = $this -> getLayout() -> createBlock('arca/standard_form', $name) -> setMethod('arca') -> setPayment($this -> getPayment()) -> setTemplate('arca/standard/form.phtml');

		return $block;
	}

	/**
	 * Return Order place redirect url
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('arca/processing', array('_secure' => true));
	}

	/**
	 * Instantiate state and set it to state object
	 * @param string $paymentAction
	 * @param Varien_Object
	 */
	public function initialize($paymentAction, $stateObject) {
		$state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$stateObject -> setState($state);
		$stateObject -> setStatus('pending_payment');
		$stateObject -> setIsNotified(false);
	}

	/**
	 * Check whether payment method can be used
	 * @param Mage_Sales_Model_Quote
	 * @return bool
	 */
	public function isAvailable($quote = null) {
		if (parent::isAvailable($quote) && $this -> getConfig() -> isMethodAvailable()) {
			return true;
		}
		return false;
	}

	/**
	 * Custom getter for payment configuration
	 *
	 * @param string $field
	 * @param int $storeId
	 * @return mixed
	 */
	public function getConfigData($field, $storeId = null) {
		return $this -> getConfig() -> $field;
	}

	/**
	 * Aggregated cart summary label getter
	 *
	 * @return string
	 */
	private function _getAggregatedCartSummary() {
		if ($this -> _config -> lineItemsSummary) {
			return $this -> _config -> lineItemsSummary;
		}
		return Mage::app() -> getStore($this -> getStore()) -> getFrontendName();
	}
	
	/**
     * Return form field array
     *
     * @return array
     */
    public function getStandardCheckoutFormFields()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        /* @var $api Mage_Paypal_Model_Api_Standard */
        $api = Mage::getModel('paypal/api_standard')->setConfigObject($this->getConfig());
        $api->setOrderId($orderIncrementId)
            ->setCurrencyCode($order->getBaseCurrencyCode())
            //->setPaymentAction()
            ->setOrder($order)
            ->setNotifyUrl(Mage::getUrl('paypal/ipn/'))
            ->setReturnUrl(Mage::getUrl('paypal/standard/success'))
            ->setCancelUrl(Mage::getUrl('paypal/standard/cancel'));

        // export address
        $isOrderVirtual = $order->getIsVirtual();
        $address = $isOrderVirtual ? $order->getBillingAddress() : $order->getShippingAddress();
        if ($isOrderVirtual) {
            $api->setNoShipping(true);
        } elseif ($address->validate()) {
            $api->setAddress($address);
        }

        // add cart totals and line items
        $api->setPaypalCart(Mage::getModel('paypal/cart', array($order)))
            ->setIsLineItemsEnabled($this->_config->lineItemsEnabled)
        ;
        $api->setCartSummary($this->_getAggregatedCartSummary());
        $api->setLocale($api->getLocaleCode());
        $result = $api->getStandardCheckoutRequest();
        return $result;
    }

}
?>