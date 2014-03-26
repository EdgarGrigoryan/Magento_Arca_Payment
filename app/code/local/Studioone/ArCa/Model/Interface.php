<?php

class Studioone_ArCa_Model_Interface  extends Mage_Payment_Model_Method_Abstract {
	public function throwError($val) {
		Mage::log($val, null, __CLASS__ . '.log');
		die ;
	}

	public function normalizePayment($sum) {
		if ($sum <= 0)
			return $this -> throwError('ERR_NEGATIVEPMNT cannot normalize negative number');

		if ($sum > 9999999999.99)
			return $this -> throwError('ERR_BIGPMNT cannot normalize huge number');

		return $sum;
		return substr('00000000000000' . ((string)$sum * 100), -12);
	}

	public function normalizeOrderId($id) {
		if (strlen($id) > 10)
			return $this -> throwError('ERR_BIGID cannot normalize huge id');

		return $id;
		return substr('00000000000' . ((string)$id), -10);
	}

	public function checkOnArCa($arr) {
		return $this -> _checkOnArca($arr, "merchant_check");
	}

	public function arcaBatchOk($arr) {
		return $this -> _checkOnArca($arr, "confirmation");
	}

	public function arcaBatchCancel($arr) {
		return $this -> _checkOnArca($arr, "refuse");
	}

	public function check($arr, $method, $url) {

		//var_dump($arr);
		if (!isset($arr['tid'])) {
			return $this -> throwError('ERR_MISSINGDATA data missing tid');
		}
		if (!isset($arr['mid'])) {
			return $this -> throwError('ERR_MISSINGDATA data missing mid');
		}
		if (!isset($arr['amount'])) {
			return $this -> throwError('ERR_MISSINGDATA data missing amount');
		}
		if (!isset($arr['mtpass'])) {
			return $this -> throwError('ERR_MISSINGDATA data missing mtpass');
		}
		if (!isset($arr['orderID'])) {
			return $this -> throwError('ERR_MISSINGDATA data missing orderID');
		}

		$arr['amount'] = $this -> normalizePayment($arr['amount']);
		$arr['orderID'] = $this -> normalizeOrderId($arr['orderID']);

		//$arr['currency'] 	= CONFIG::CURRENCY();
		return $this -> _callRpc($arr, $method, $url);
	}

	public function _callRpc($arr, $method, $url) {

		$postData = array("id" => "remoteRequest", "method" => $method, "params" => array($arr));

		$postData = json_encode($postData);

		/// ----------------- JSONRPC ----------------- ///

		$request = $postData;

		#	SSL module
		$private_cert = "menu.am.crt";
		// Path to the .cert file provided by ArCa
		$private_key = "menu.am.key";
		// Path to the .key file provided by ArCa
		$private_pass = "X3m1Z9N4F";
		// Merchant should use the certificate password provided by ArCa

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if (Mage::getStoreConfig('payment/arca/testmode') !== '1') 
		{
			curl_setopt($ch, CURLOPT_SSLCERT, $private_cert);
			curl_setopt($ch, CURLOPT_SSLKEY, $private_key);
			curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $private_pass);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

		$ret = curl_exec($ch);
		Mage::log($ret, null, __CLASS__ . '.log');
		if ($ret === false) {
			Mage::log(curl_errorno($ch) . "#" . curl_error($ch), null, __CLASS__ . '.log');

			return $ret;
		}

		curl_close($ch);
		if ($ret !== false) {
			$decoded = $this -> convertResponse($ret);
		}
		return $decoded;
	}

	// convert arca response into array
	public function convertResponse($lines) {
		$result = json_decode($lines,true);
		
		echo '<pre>';var_dump($result);
die($lines);
		return get_object_vars($result -> result);
	}

	public function arca_batch_ok($order_id) {
		return 'batch_ok';
	}

	public function arca_batch_cancel($order_id) {
		return 'batch_cancel';
	}

	public function gArcaData($orderid, $amount) {
		//		$amount = trim($this->filter['doaction']['amount']*100);
		//		$orderID = trim($this->orderID);
		$arr = array('orderID' => $orderid, 'amount' => $amount, 'tid' => $this -> tid, 'mid' => $this -> mid, 'mtpass' => $this -> mpass, );

		$arr['orderID'] = $this -> normalizeOrderId($orderid);

		return $arr;
	}

}

//$this->LOAD('Services_JSON');
?>
