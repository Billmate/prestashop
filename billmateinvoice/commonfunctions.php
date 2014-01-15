<?php
@session_start();

if(!function_exists('billmate_log_data')){
	define('BILLMATE_VERSION',  "PHP:Prestashop:1.24" );

	function getCountryID(){
		return 209;
		$country = strtoupper(Configuration::get('PS_SHOP_COUNTRY'));
		switch($country){
			case 'SWEDEN': return 209;
			case 'FINLAND': return 73;
			case 'DENMARK': return 59;
			case 'NORWAY': return 164;
			default :
				return 209;
		}
		/*if( in_array( Configuration::get('PS_SHOP_COUNTRY'), array('Sweden','Finland','Denmark','Norway'))){
		
		} else {
			return 209;
		}
		Sweden: 209 Finland: 73 Denmark: 59 Norway: 164*/

	}
	function billmate_log_data($data, $eid, $type='', $response="", $duration=0, $status=0){
        $sock = @fsockopen('udp://stat.billmate.se', 51000, $errno, $errstr, 1500);
        if ($sock) {
			$uniqueId = $_SESSION["uniqueId"];
			if($uniqueId==""){
				$uniqueId = $_SESSION["uniqueId"] = microtime(true)."-".rand(123456789, 987654321);
			}
			$values = array(
				"type"=>$type,
				"timestamp"=>date("Y-m-d H:i:s"),
				"data"=>$data,
				"response"=>$response,
				"duration"=>$duration,
				"server"=>$_SERVER,
				"eid"=>$eid,
				"client"=>BILLMATE_VERSION,
				"uniqueId"=>$uniqueId
			);
			@fwrite($sock,json_encode($values));
			@fclose($sock);
        }
	}
	/*function billmate_log_data($data_raw, $eid ='', $type = 'error'){
		
		$host = 'api.billmate.se/logs/index.php';
		$server = array('HTTP_USER_AGENT','SERVER_SOFTWARE','DOCUMENT_ROOT','SCRIPT_FILENAME','SERVER_PROTOCOL','REQUEST_METHOD','QUERY_STRING','REQUEST_TIME');
		$data['data'] = $data_raw;
		$data['server_info'] = array();
		foreach($server as $item ){
			$data['server_info'][$item] = $_SERVER[$item];
		}
		if( empty( $eid ) && defined('BILLMATE_INVOICE_EID')){
			$eid = BILLMATE_INVOICE_EID;
		}
		

		$data2 = array('cmd'=>$type, 'eid'=> $eid, 'client' => BILLMATE_VERSION,'host'=> $_SERVER['SERVER_NAME'],'data' => '<pre>Time:'.date('H:i:s')."\n".htmlentities(var_export($data,1)).'</pre>');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data2));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		$server_output = curl_exec ($ch);
		curl_close ($ch);

	}*/
	
	function call_log_billmate($error_no, $errstr, $errfile, $errline, $errcontext){
		billmate_log_data(
			array(
				'error_number' => $error_no,
				'error_message'=> $errstr,
				'error_file'   => $errfile,
				'error_line'   => $errline,
			)
		);
		return true;
	}
	function exception_billmate($exception){
		billmate_log_data(array('error_exception'=> $exception->getMessage()));
	}
}

if( defined('BILLMATE_DEBUG')){
	error_reporting(E_ALL);
	ini_set('display_errors', 1	);
}else{
	error_reporting(NULL);
	ini_set('display_errors', 0	);
}
//set_error_handler('call_log_billmate');
//set_exception_handler('exception_billmate');

?>