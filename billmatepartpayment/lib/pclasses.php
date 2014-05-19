<?php
require 'Flags.php';
require 'billmatecalc.php';

class pClasses{
	private $data = null;
	private $config = array();
	private $_eid = null;
	public function __construct($eid='', $secret='', $country='', $language='', $currency='', $mode = 'live' ){

		$this->config['eid'] = $eid;
		$this->config['secret'] = $secret;
		$this->config['country']  = $country;
		$this->config['language'] = $language;
		$this->config['currency'] = $currency;
		$this->config['mode'] = $mode;
		$this->getPClasses($eid);
	}
	public function clear(){
		Db::getInstance()->Execute('truncate `'._DB_PREFIX_.'billmate_payment_pclasses`');
	}
	public function Save($eid, $secret, $country, $language, $currency, $mode = 'live' ){
		$testmode = $mode == 'beta';
        $ssl=true;
        $debug = false;
		
        $billmate = new Billmate($eid, $secret, $ssl, $debug, $testmode);
		switch ($country) {
			// Sweden
			case 'SE':
				$country = 209;
				$language = 138;
				$encoding = 2;
				$currency = 0;
				break;
			// Finland
			case 'FI':
				$country = 73;
				$language = 37;
				$encoding = 4;
				$currency = 2;
				break;
			// Denmark
			case 'DK':
				$country = 59;
				$language = 27;
				$encoding = 5;
				$currency = 3;
				break;
			// Norway	
			case 'NO':
				$country = 164;
				$language = 97;
				$encoding = 3;
				$currency = 1;

				break;
			// Germany	
			case 'DE':
				$country = 81;
				$language = 28;
				$encoding = 6;
				$currency = 2;
				break;
			// Netherlands															
			case 'NL':
				$country = 154;
				$language = 101;
				$encoding = 7;
				$currency = 2;
				break;
		}
        
        $additionalinfo = array(
	        "currency"=>$currency,//SEK
	        "country"=>$country,//Sweden
	        "language"=>$language,//Swedish
        );
        $data = $billmate->FetchCampaigns($additionalinfo);
		
		$db = Db::getInstance();
		$db->Execute('TRUNCATE TABLE `'._DB_PREFIX_.'billmate_payment_pclasses`');
		if( !is_array($data)){
			throw new Exception(strip_tags($data));
		} else {
			array_walk($data, array($this,'correct_lang_billmate'));
			foreach($data as $_row){
				$_row['eid'] = $eid;
				$_row['country'] = $country;
				
				if((version_compare(_PS_VERSION_,'1.5','>='))){
					Db::getInstance()->insert('billmate_payment_pclasses',$_row);
				} else {
					$data = $_row;
					array_walk($data,create_function('&$value, $idx','$value = "`".$idx."`=\'$value\'";'));
				
					$result &= $db->Execute('insert into `'._DB_PREFIX_.'billmate_payment_pclasses` SET '.implode(',',$data));
				}
			}
		}
	}
	
    function correct_lang_billmate(&$item, $index){
        $keys = array('id', 'description','months', 'startfee','invoicefee','interestrate', 'minamount', 'country', 'type', 'expire', 'maxamount' );
        $item[1] = utf8_encode($item[1]);
        $item = array_combine( $keys, $item );
        $item['startfee'] = $item['startfee'] / 100;
        $item['invoicefee'] = $item['invoicefee'] / 100;
        $item['interestrate'] = $item['interestrate'] / 100;
        $item['minamount'] = $item['minamount'] / 100;
        $item['maxamount'] = $item['maxamount'] / 100;
    }
	
	public function getCheapestPClass($sum, $flags){
        $lowest_pp = $lowest = false;
		
		$pclasses = $this->getPClasses();
        foreach ( $pclasses as $pclass) {
			if( $pclass !== false ){
				$lowest_payment = BillmateCalc::get_lowest_payment_for_account( $pclass['country'] );
				if ($pclass['type'] < 2 && $sum >= $pclass['minamount'] && ($sum <= $pclass['maxamount'] || $pclass['maxamount'] == 0) ) {
					$minpay = BillmateCalc::calc_monthly_cost( $sum, $pclass, $flags );

					if ($minpay < $lowest_pp || $lowest_pp === false) {
						if ($minpay >= $lowest_payment ) {
							$lowest_pp = $minpay;
							$lowest = $pclass;
						}
					}
				}
			}
        }

        return $lowest;	
	}
	public function getPClasses($eid = ''){
		if(!empty($eid) && $eid != $this->_eid || !is_array($this->data)){
			$this->_eid = $eid;
			if($_SERVER['REMOTE_ADDR'] == '122.173.227.3'){
			//echo 'SELECT * FROM `'._DB_PREFIX_.'billmate_payment_pclasses` where eid="'.$this->_eid.'"';
			}
			$this->data = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'billmate_payment_pclasses` where eid="'.$this->_eid.'"');
		}
		
		if( !is_array($this->data) ) $this->data = array();
		return $this->data;
	}
	public function __set($key, $val){
	}
	public function __sleep(){
	}
	public function __wake(){
	}
}