<?php

class Studioone_ArCa_Model_Interface  extends Mage_Payment_Model_Method_Abstract {
	public function throwError($val) {
		
		$debug = debug_backtrace();
		Mage::log($val."\n".print_r($debug,1), null, __CLASS__ . '.log');
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
			Mage::log(curl_errno($ch) . "#" . curl_error($ch), null, __CLASS__ . '.log');

			return $ret;
		}

		curl_close($ch);
		if ($ret !== false) {
			$decoded = $this -> convertResponse($ret);
		}
		return $decoded;
	}

	// convert arca response into array
	public function convertResponse($data) 
	{
		
		echo '<pre>';
		if(strstr($data, '}{'))
		{ 
			$lines = explode('}{', $data);
		}else
		{
			$lines = array($data);
		}
		foreach ($lines as $key => $value) {
			
			
			if(substr($value, 0,1) !== '{')
			{
				$value= '{'.$value;
			}
			
			
			if(substr($value, -1,1) !== '}')
			{
				$value= $value.'}';
			}
			
			 
			$decoded = json_decode($value);	
			 
			$results[]= $decoded->result;
		}

		
		
		 

		return $results;
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
	public function checkBuilding($arca_out_arr)
	{
		
		//Image building
		Mage::log(print_r($arca_out_arr, 1), null, __FUNCTION__.'__'.__CLASS__ . '.log');
		$isTestMode = Mage::getStoreConfig('payment/arca/testmode') == '1' ? 'test_' : '';
		$resDir = Mage::getBaseDir('media').'/arca/default/';
		$chekDir = Mage::getBaseDir('media').'/arca/orders/';
		$im_in = imagecreatefromjpeg($resDir."check.jpg");
		$im_out = ImageCreateTrueColor(320, 550);
		ImageCopyResized($im_out, $im_in, 0, 0, 0, 0, 320, 550, 320, 550);
		$font = $resDir."arial.ttf";
		$bbox = ImageTTFBBox(11, 0,$font, "");
		ImageTTFText($im_out, 10, 0, 150, 92, 50,$font, $arca_out_arr->datetime);
		ImageTTFText($im_out, 10, 0, 150, 110, 50,$font, $arca_out_arr->amount ." AMD" );
		ImageTTFText($im_out, 10, 0, 150, 145, 50,$font, $arca_out_arr->card_number);
		ImageTTFText($im_out, 10, 0, 20, 185, 50,$font, $arca_out_arr->clientName);
		ImageTTFText($im_out, 10, 0, 150, 218, 50,$font, $arca_out_arr->orderID);
		ImageTTFText($im_out, 10, 0, 150, 236, 50,$font, Mage::getStoreConfig("payment/arca/{$isTestMode}mid"));
		ImageTTFText($im_out, 10, 0, 150, 254, 50,$font, Mage::getStoreConfig("payment/arca/{$isTestMode}hostID"));
		ImageTTFText($im_out, 10, 0, 150, 272, 50,$font, $arca_out_arr->stan);
		ImageTTFText($im_out, 10, 0, 150, 290, 50,$font, $arca_out_arr->authcode);
		ImageTTFText($im_out, 10, 0, 150, 308, 50,$font, $arca_out_arr->rrn);
		ImageTTFText($im_out, 10, 0, 20, 374, 50,$font,   $arca_out_arr->trxnDetails);
		$imgOriginal = "$chekDir/check_". $arca_out_arr->orderID .".jpg";
		ImageJpeg($im_out, $imgOriginal, 100);
		ImageDestroy($im_out);
		ImageDestroy($im_in);
		return 	$imgOriginal;
	}
	

}

//$this->LOAD('Services_JSON');
?>
