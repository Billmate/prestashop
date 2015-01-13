<?php
/**
 * BillMate Class
 *
 * LICENSE: This source file is part of BillMate, that is fully owned by Billmate AB
 * This is not open source. For licensing queries, please contact at info@efinance.se
 *
 * @category Billmate
 * @package Billmate
 * @author Yuksel Findik <yuksel@efinance.se>
 * @copyright 2013-2014 Billmate AB
 * @license Proprietary and fully owned by Billmate AB
 * @version 0.5.8
 * @link http://www.efinance.se
 *
 * History:
 * 0.0.1 20130318 Yuksel Findik: First Version
 * Dependencies:
 *
 *  xmlrpc-3.0.0.beta/lib/xmlrpc.inc
 *      from {@link http://phpxmlrpc.sourceforge.net/}
 *
 * xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc
 *      from {@link http://phpxmlrpc.sourceforge.net/}
 *
 */
 class BillMate{
 	var $SERVER = "0.5.9";
 	var $CLIENT = "";
 	var $URL = "api.billmate.se";
 	var $URL_TEST = "apitest.billmate.se";
 	var $STAT  = "stat.billmate.se";
 	protected $ssl;
 	protected $xmlrpc;
 	protected $debugmode;
 	protected $testmode;
 	function BillMate($eid,$key,$ssl=true,$debug=false,$test=false){
 		$this->encoding = 2;
 		$this->eid = $eid;
 		$this->key = $key;
                defined('BILLMATE_VERSION') || define('BILLMATE_VERSION',  "PHP:BillMate:0.5.8" );
		$this->CLIENT = BILLMATE_VERSION;
 		$this->ssl = $ssl;
 		$this->debugmode = $debug;
 		$this->testmode = $test;
 		if ($this->ssl) $this->port = 443;
 		else $this->port = 80;
 		$url = $test?$this->URL_TEST:$this->URL;
 		$this->xmlrpc = new xmlrpc_client("/",$url,$this->port,$this->ssl?'https':'http');
        $this->xmlrpc->request_charset_encoding = 'ISO-8859-1';
 	}
 	function ActivateInvoice($no,$additionalInfo=array("pclass"=>-1,"shipInfo"=>array())) {
		$params = array(
            $this->eid,
            $no,
            $this->hash(array($this->eid, $no, $this->key)),
            $additionalInfo["pclass"],
            $additionalInfo["shipInfo"]
        );
        $result = $this->call('activate_invoice', $params);
        return $result;
 	}
 	function ActivateReservation($reservationno,$pno,$billing,$shipping,$articles,$additionalinfo){
 		$tobehashed = array($this->eid,$pno);
 		
 		foreach ($articles as $article){
 			$tobehashed[] = $article['goods']['artno'];
 			$tobehashed[] = $article["qty"];
 		}
 		$tobehashed[] = $this->key;
 		
 		$params = array(
 			$reservationno,
 			$additionalinfo["ocr"],
 		    $pno,
 		    $additionalinfo["gender"],
 		    $additionalinfo["reference"],
 		    $additionalinfo["reference_code"],
 		    $additionalinfo["order1"],
 		    $additionalinfo["order1"],
 		    $shipping,
 		    $billing,
 		    $this->IP(),
 		    $additionalinfo["flags"],
 		    $additionalinfo["currency"],
 		    $additionalinfo["country"],
 		    $additionalinfo["language"],
 		    $this->eid,
 		    $this->hash($tobehashed),
 		    $this->encoding,
 		    $additionalinfo["pclass"],
 		    $articles,
 		    $additionalinfo["comment"],
 		    $additionalinfo["shipInfo"],
 		    $additionalinfo["travelInfo"],
 		    $additionalinfo["incomeInfo"],
 		    $additionalinfo["bankInfo"],
 		    $additionalinfo["extraInfo"]
 		);
 		
 		$result = $this->call('activate_reservation', $params);
 		return $result;
 	}
 	function Update($rno,$billingaddress,$shippingaddress,$articles,$order1,$order2){
 		$tobehashed = array(
 		    str_replace('.', ':', $this->SERVER),
 		    $this->CLIENT,
 		    $this->eid,
 		    $rno
 		);
 		$tobehashed = array_merge($tobehashed, $this->addressDigestPart($shippingaddress));
 		$tobehashed = array_merge($tobehashed, $this->addressDigestPart($billingaddress));
 		
 		foreach ($articles as $article){
 			$tobehashed[] = $article['goods']['artno'];
 			$tobehashed[] = $article['goods']['title'];
 			$tobehashed[] = $article["qty"];
 		}
 		$tobehashed[] = $order1;
 		$tobehashed[] = $order2;
 		$tobehashed[] = $this->key;
 		$params = array(
 		    $this->eid,
 		    $this->hash($tobehashed),
 		    $rno,
 		    array(
 		        'goods_list' => $articles,
 		        'dlv_addr' => $shippingaddress,
 		        'bill_addr' => $billingaddress,
 		        'orderid1' => $order1,
 		        'orderid2' => $order2
 		    )
 		);
 		
 		$result = $this->call('update', $params);	        
 		return $result;
 	}
 	function UpdateOrderNo($invno, $orderid){
 		$params = array(
 		    $this->eid,
 		    $this->hash(array($invno, $orderid, $this->key)),
 		    $invno,
 		    $orderid
 		);
 		
 		$result = $this->call('update_orderno', $params);
 		
 		return $result;
 	}
 	function addressDigestPart($address){
        if ($address === null) {
            return array();
        }
        $keys = array('careof', 'street', 'zip', 'city', 'country', 'fname', 'lname');
        $digest = array();
        foreach ($keys as $key) {
            if ($address[$key] != "")
                $digest[] = $address[$key];
        }
        return $digest;
    }
 	function AddInvoice($pno,$billing,$shipping,$articles,$additionalinfo){
 		$tobehashed = array();
 		foreach ($articles as $article) $tobehashed[] = $article['goods']['title'];
 		$tobehashed[] = $this->key;
 		$params = array(
 		    $pno,
 		    strval($additionalinfo["gender"]),
 		    $additionalinfo["reference"],
 		    $additionalinfo["reference_code"],
 		    $additionalinfo["order1"],
 		    $additionalinfo["order2"],
 		    $shipping,
 		    $billing,
 		    $this->IP(),
 		    $additionalinfo["flags"],
 		    $additionalinfo["currency"],
 		    $additionalinfo["country"],
 		    $additionalinfo["language"],
 		    $this->eid,
 		    $this->hash($tobehashed),
 		    $this->encoding,
 		    $additionalinfo["pclass"],
 		    $articles,
 		    $additionalinfo["comment"],
 		    $additionalinfo["shipInfo"],
 		    $additionalinfo["travelInfo"],
 		    $additionalinfo["incomeInfo"],
 		    $additionalinfo["bankInfo"],
 		    $additionalinfo["sid"],
 		    $additionalinfo["extraInfo"]
 		);
 		$result = $this->call('add_invoice', $params);
 		return $result;
 	}
 	function AddOrder($pno,$billing,$shipping,$articles,$additionalinfo){
 		$tobehashed = array();
 		foreach ($articles as $article) $tobehashed[] = $article['goods']['title'];
 		$tobehashed[] = $this->key;
 		$params = array(
 		    $pno,
 		    strval($additionalinfo["gender"]),
 		    $additionalinfo["reference"],
 		    $additionalinfo["reference_code"],
 		    $additionalinfo["order1"],
 		    $additionalinfo["order2"],
 		    $shipping,
 		    $billing,
 		    $this->IP(),
 		    $additionalinfo["flags"],
 		    $additionalinfo["currency"],
 		    $additionalinfo["country"],
 		    $additionalinfo["language"],
 		    $this->eid,
 		    $this->hash($tobehashed),
 		    $this->encoding,
 		    $additionalinfo["pclass"],
 		    $articles,
 		    $additionalinfo["comment"],
 		    $additionalinfo["shipInfo"],
 		    $additionalinfo["travelInfo"],
 		    $additionalinfo["incomeInfo"],
 		    $additionalinfo["bankInfo"],
 		    $additionalinfo["sid"],
 		    $additionalinfo["extraInfo"]
 		);
 		$result = $this->call('add_order', $params);
 		return $result;
 	}
 	function GetAddress($pno) {
 		$type = 5;
 	    $params = array($pno,$this->eid,$this->hash(array($this->eid, $pno, $this->key)),$this->encoding,$type,$this->IP());
        $result = $this->call('get_addresses', $params);
        return $result;
    }
    function CreditCheck($id,$pno,$amount,$email,$phonenumber=""){
    	$params = array($pno,$this->eid,$this->hash(array($this->eid, $pno,$amount, $this->key)),$amount,$email,$phonenumber,$id,$this->IP());
    	$result = $this->call('credit_check', $params);
    	return $result;
    }
    function FetchCampaigns($additionalinfo = array()) {
    	$params = array($this->eid,$additionalinfo["currency"],$this->hash(array($this->eid, $additionalinfo["currency"], $this->key)),$additionalinfo["country"],$additionalinfo["language"]);
    	$result = $this->call('get_pclasses', $params);
    	return $result;
    }
    function CheckOrderStatus($invno,$addinfo=0){
    	$params = array($this->eid,$this->hash(array($this->eid, $invno, $this->key)),$invno,$addinfo);
    	$result = $this->call('check_order_status', $params);
    	return $result;
    }
    function CheckInvoiceStatus($invno,$addinfo=0){
    	$params = array($this->eid,$this->hash(array($this->eid, $invno, $this->key)),$invno,$addinfo);
    	$result = $this->call('check_invoice_status', $params);
    	return $result;
    }
    function hash($args) {
    	$data = implode(":", $args);
    	
    	$this->debug("HASHED DATA",$data);
    	
    	$preferred = array(
            'sha512',
            'sha384',
            'sha256',
            'sha224',
            'md5'
        );

        $hashes = array_intersect($preferred, hash_algos());

        
        $hash = array_shift($hashes);
    	return base64_encode(pack("H*", hash($hash, $data)));
    }
    function debug($name,$out) {
    	if (!$this->debugmode) return;
    	print "Name:$name Output:\n<br/>";
    	if(is_array($out) or  is_object($out)) print_r($out);
    	else print $out;
    	print "\n<br/>";
    }
    protected function call($method, $array) {
    	$this->debug($method,$array);
		$this->xmlrpc->verifypeer = false;
		$this->xmlrpc->verifyhost = 0;
        $timestart = microtime(true);

        $msg = new xmlrpcmsg($method);
        $params = array_merge(array($this->SERVER, $this->CLIENT), $array);

        $msg = new xmlrpcmsg($method);
        foreach ($params as $p) $msg->addParam(php_xmlrpc_encode($p, array('extension_api')));
            
        if ($this->debugmode) $this->xmlrpc->setDebug(2);
           
        $xmlrpcresp = $this->xmlrpc->send($msg);
        
        $timeend = microtime(true);
        
        $duration = (int) (($timeend - $timestart) * 1000);
        
        $status = $xmlrpcresp->faultCode();
        
        if ($status !== 0){
			$this->stat($method,$array, $xmlrpcresp->faultString(), $duration, $status);
        	return $xmlrpcresp->faultString();
        }
        $result = php_xmlrpc_decode($xmlrpcresp->value());
        
        $this->stat($method,$array, $result, $duration, $status);
        
        $this->debug($method, $result);
        return $result;
        
    }
    public function stat($type,$data, $response, $duration=0, $status=0) {
        $sock = @fsockopen('udp://'.$this->STAT, 51000, $errno, $errstr, 1500);

		if(!isset($_SESSION["uniqueId"])){
			$_SESSION["uniqueId"] = microtime(true)."-".rand(123456789, 987654321);
		}
		$uniqueId = $_SESSION["uniqueId"];
        if ($sock) {
        	$values = array(
        		"type"=>$type,
        		"timestamp"=>date("Y-m-d H:i:s"),
        		"data"=>utf8_encode( print_r( $data , 1)),
        		"response"=>utf8_encode( print_r($response, 1 ) ),
        		"duration"=>$duration,
        		"server"=>$_SERVER,
        		"eid"=>$this->eid,
        		"client"=>$this->CLIENT,
				"uniqueId"=>$uniqueId
        	);
			ob_start();
			$writeflag = @fwrite($sock,json_encode($values));
			ob_end_clean();
			if($writeflag==0 && $type == 'add_invoice' ){
				$this->stat_post($data, $type,$response, $duration, $status);
			}
			@fclose($sock);
        }
    }
    public function stat_post($data_rw,$type='', $response="", $duration=0, $status=0){
        $host = 'api.billmate.se/logs/index.php';
        $server = array('HTTP_USER_AGENT','SERVER_SOFTWARE','DOCUMENT_ROOT','SCRIPT_FILENAME','SERVER_PROTOCOL','REQUEST_METHOD','QUERY_STRING','REQUEST_TIME');
        $data['data'] = $data_rw;
        $data['server_info'] = array();
        foreach($server as $item ){
                $data['server_info'][$item] = $_SERVER[$item];
        }

        $data2 = array('cmd'=>$type, 'eid'=> $this->eid, 'client' => BILLMATE_VERSION,'host'=> $_SERVER['SERVER_NAME'],'data' => '<pre>Time:'.date('H:i:s')."\n".(var_export($data,1)).'</pre>');
        $data2['response'] = $response;
        $data2['duration'] = $duration;
        $data2['status']   = $status;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data2));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
    }
    function IP()
    {
    	global $REMOTE_ADDR, $HTTP_CLIENT_IP;
    	global $HTTP_X_FORWARDED_FOR, $HTTP_X_FORWARDED, $HTTP_FORWARDED_FOR, $HTTP_FORWARDED;
    	global $HTTP_VIA, $HTTP_X_COMING_FROM, $HTTP_COMING_FROM;
    
    	// Get some server/environment variables values
    	if (empty($REMOTE_ADDR)) {
    		if (!empty($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
    			$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['REMOTE_ADDR'])) {
    			$REMOTE_ADDR = $_ENV['REMOTE_ADDR'];
    		}
    		else if (@getenv('REMOTE_ADDR')) {
    			$REMOTE_ADDR = getenv('REMOTE_ADDR');
    		}
    	} // end if
    
    	if (empty($HTTP_CLIENT_IP)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_CLIENT_IP'])) {
    			$HTTP_CLIENT_IP = $_SERVER['HTTP_CLIENT_IP'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_CLIENT_IP'])) {
    			$HTTP_CLIENT_IP = $_ENV['HTTP_CLIENT_IP'];
    		}
    		else if (@getenv('HTTP_CLIENT_IP')) {
    			$HTTP_CLIENT_IP = getenv('HTTP_CLIENT_IP');
    		}
    	} // end if
    
    	if (empty($HTTP_X_FORWARDED_FOR)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    			$HTTP_X_FORWARDED_FOR = $_SERVER['HTTP_X_FORWARDED_FOR'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_X_FORWARDED_FOR'])) {
    			$HTTP_X_FORWARDED_FOR = $_ENV['HTTP_X_FORWARDED_FOR'];
    		}
    		else if (@getenv('HTTP_X_FORWARDED_FOR')) {
    			$HTTP_X_FORWARDED_FOR = getenv('HTTP_X_FORWARDED_FOR');
    		}
    	} // end if
    
    	if (empty($HTTP_X_FORWARDED)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED'])) {
    			$HTTP_X_FORWARDED = $_SERVER['HTTP_X_FORWARDED'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_X_FORWARDED'])) {
    			$HTTP_X_FORWARDED = $_ENV['HTTP_X_FORWARDED'];
    		}
    		else if (@getenv('HTTP_X_FORWARDED')) {
    			$HTTP_X_FORWARDED = getenv('HTTP_X_FORWARDED');
    		}
    	} // end if
    
    	if (empty($HTTP_FORWARDED_FOR)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_FORWARDED_FOR'])) {
    			$HTTP_FORWARDED_FOR = $_SERVER['HTTP_FORWARDED_FOR'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_FORWARDED_FOR'])) {
    			$HTTP_FORWARDED_FOR = $_ENV['HTTP_FORWARDED_FOR'];
    		}
    		else if (@getenv('HTTP_FORWARDED_FOR')) {
    			$HTTP_FORWARDED_FOR = getenv('HTTP_FORWARDED_FOR');
    		}
    	} // end if
    
    	if (empty($HTTP_FORWARDED)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_FORWARDED'])) {
    			$HTTP_FORWARDED = $_SERVER['HTTP_FORWARDED'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_FORWARDED'])) {
    			$HTTP_FORWARDED = $_ENV['HTTP_FORWARDED'];
    		}
    		else if (@getenv('HTTP_FORWARDED')) {
    			$HTTP_FORWARDED = getenv('HTTP_FORWARDED');
    		}
    	} // end if
    
    	if (empty($HTTP_VIA)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_VIA'])) {
    			$HTTP_VIA = $_SERVER['HTTP_VIA'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_VIA'])) {
    			$HTTP_VIA = $_ENV['HTTP_VIA'];
    		}
    		else if (@getenv('HTTP_VIA')) {
    			$HTTP_VIA = getenv('HTTP_VIA');
    		}
    	} // end if
    	if (empty($HTTP_X_COMING_FROM)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_X_COMING_FROM'])) {
    			$HTTP_X_COMING_FROM = $_SERVER['HTTP_X_COMING_FROM'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_X_COMING_FROM'])) {
    			$HTTP_X_COMING_FROM = $_ENV['HTTP_X_COMING_FROM'];
    		}
    		else if (@getenv('HTTP_X_COMING_FROM')) {
    			$HTTP_X_COMING_FROM = getenv('HTTP_X_COMING_FROM');
    		}
    	} // end if
    	if (empty($HTTP_COMING_FROM)) {
    		if (!empty($_SERVER) && isset($_SERVER['HTTP_COMING_FROM'])) {
    			$HTTP_COMING_FROM = $_SERVER['HTTP_COMING_FROM'];
    		}
    		else if (!empty($_ENV) && isset($_ENV['HTTP_COMING_FROM'])) {
    			$HTTP_COMING_FROM = $_ENV['HTTP_COMING_FROM'];
    		}
    		else if (@getenv('HTTP_COMING_FROM')) {
    			$HTTP_COMING_FROM = getenv('HTTP_COMING_FROM');
    		}
    	} // end if
    
    	// Gets the default ip sent by the user
    	if (!empty($REMOTE_ADDR)) {
    		$direct_ip = $REMOTE_ADDR;
    	}
    
    	// Gets the proxy ip sent by the user
    	$proxy_ip = '';
    	if (!empty($HTTP_X_FORWARDED_FOR)) {
    		$proxy_ip = $HTTP_X_FORWARDED_FOR;
    	} else if (!empty($HTTP_X_FORWARDED)) {
    		$proxy_ip = $HTTP_X_FORWARDED;
    	} else if (!empty($HTTP_FORWARDED_FOR)) {
    		$proxy_ip = $HTTP_FORWARDED_FOR;
    	} else if (!empty($HTTP_FORWARDED)) {
    		$proxy_ip = $HTTP_FORWARDED;
    	} else if (!empty($HTTP_VIA)) {
    		$proxy_ip = $HTTP_VIA;
    	} else if (!empty($HTTP_X_COMING_FROM)) {
    		$proxy_ip = $HTTP_X_COMING_FROM;
    	} else if (!empty($HTTP_COMING_FROM)) {
    		$proxy_ip = $HTTP_COMING_FROM;
    	} // end if... else if...
    
    	// Returns the true IP if it has been found, else ...
    	if (empty($proxy_ip)) {
    		// True IP without proxy
    		return $direct_ip;
    	} else {
    		$is_ip = preg_match('/([0-9]{1,3}.){3,3}[0-9]{1,3}/', $proxy_ip, $regs);
    
    		if ($is_ip && (count($regs) > 0)) {
    			// True IP behind a proxy
    			return $regs[0];
    		} else {
    
    			if (empty($HTTP_CLIENT_IP)) {
    				// Can't define IP: there is a proxy but we don't have
    				// information about the true IP
    				return "(unbekannt) " . $proxy_ip;
    			} else {
    				// better than nothing
    				return $HTTP_CLIENT_IP;
    			}
    		}
    	} // end if... else...
    }
    const ACCEPTED = 1;
    const PENDING = 2;
    const DENIED = 3;
 }
 
?>