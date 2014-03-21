<?php 
/*
 * Array
(
    [respcode] => 99
    [additional] => merhchant_not_passed
    [sessionID] => 
    [sid] => 
    [hostID] => 150999
    [additionalURL] => /arca/processing/result/order_id/31/
    [orderID] => 31
    [opaque] => 
    [cancel] => CANCEL
)
 * */

$query_data = array("orderID" => 31,"respcode" => "00",'opaque'=>'');
echo http_build_query($query_data);

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_URL, "http://onlineyerevan.one/arca/processing/result/order_id/31/");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_data));
if(($content=curl_exec($ch)) === false)
{
	echo curl_error($ch);
}


curl_close($ch);
var_dump($content);
?>