<?php
@session_start();
if(!function_exists('getCountryID')){

	require dirname(__FILE__).'/utf8.php';
	require dirname(__FILE__).'/xmlrpc-2.2.2/lib/xmlrpc.inc';
	require dirname(__FILE__).'/xmlrpc-2.2.2/lib/xmlrpcs.inc';

	define('BILLPLUGIN_VERSION', '1.31');
	
	define('BILLMATE_VERSION',  "PHP:Prestashop:".BILLPLUGIN_VERSION );

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
	}
	function bill_sqlRestrict($id_shop_group = null, $id_shop = null){
		if( empty($id_shop_group) && empty($id_shop)){
			return false;
		}
		if ($id_shop)
			return ' AND id_shop = '.(int)$id_shop;
		elseif ($id_shop_group)
			return ' AND id_shop_group = '.(int)$id_shop_group.' AND (id_shop IS NULL OR id_shop = 0)';
		else
			return ' AND (id_shop_group IS NULL OR id_shop_group = 0) AND (id_shop IS NULL OR id_shop = 0)';
	}
	function billmate_deleteConfig($key){
		$condition = '';
		
		if((version_compare(_PS_VERSION_,'1.5','>'))){
			$id_shop = Shop::getContextShopID(true);
			$id_shop_group = Shop::getContextShopGroupID(true);
			$condition = bill_sqlRestrict($id_shop_group, $id_shop);
		}
		
		$sql = 'DELETE FROM `'._DB_PREFIX_.'configuration_lang`	WHERE `id_configuration` IN ( SELECT `id_configuration`	FROM `'._DB_PREFIX_.'configuration`	WHERE `name` = "'.pSQL($key).'" '.$condition.')';

		$result = Db::getInstance()->execute($sql);
	 	$sql = 'DELETE FROM `'._DB_PREFIX_.'configuration` WHERE `name` = "'.pSQL($key).'" '.$condition;
	 
		$result2 = Db::getInstance()->execute($sql);
		Configuration::loadConfiguration();
	}
}

if( defined('BILLMATE_DEBUG')){
	@error_reporting(E_ALL);
	@ini_set('display_errors', 1);
}else{
	@error_reporting(NULL);
	@ini_set('display_errors', 0);
}

if(!class_exists('BillmateLanguage', false) ){
class BillmateLanguage
{
    const DA = 27;
    const DE = 28;
    const EN = 31;
    const FI = 37;
    const NB = 97;
    const NL = 101;
    const SV = 138;
    public static function fromCode($val)
    {
        switch(strtolower($val)) {
        case 'en':
            return self::EN;
        case 'da':
            return self::DA;
        case 'de':
            return self::DE;
        case 'fi':
            return self::FI;
        case 'nb':
            return self::NB;
        case 'nl':
            return self::NL;
        case 'sv':
            return self::SV;
        default:
            return null;
        }
    }
    public static function getCode($val)
    {
        switch($val) {
        case self::EN:
            return 'en';
        case self::DA:
            return 'da';
        case self::DE:
            return 'de';
        case self::FI:
            return 'fi';
        case self::NB:
            return 'nb';
        case self::NL:
            return 'nl';
        case self::SV:
            return 'sv';
        default:
            return null;
        }
    }
}
}
 if( !class_exists('BillmateCountry' )){
class BillmateCountry {
    const DK = 59;
    const FI = 73;
    const DE = 81;
    const NL = 154;
    const NO = 164;
    const SE = 209;
    public static $countriesdata = array(209 =>'sweden', 73=> 'finland',59=> 'denmark', 164 => 'norway', 81 => 'germany', 15 => 'austria', 154 => 'netherlands' );
    public static function getContryByNumber($number){
        return isset(self::$countriesdata[$number]) ? self::$countriesdata[$number]: false;
    }
    private function __construct() {
    }
    public static function getCountries(){
        return self::$countriesdata;
    }
    public static function fromCode($val) {
        switch(strtolower($val)) {
            case 'swe':
            case 'se':
                return self::SE;
            case 'nor':
            case 'no':
                return self::NO;
            case 'dnk':
            case 'dk':
                return self::DK;
            case 'fin':
            case 'fi':
                return self::FI;
            case 'deu':
            case 'de':
                return self::DE;
            case 'nld':
            case 'nl':
                return self::NL;
            default:
                return null;
        }
    }
    public static function getCode($val, $alpha3 = false) {
         switch($val) {
            case BillmateCountry::SE:
                return ($alpha3) ? 'swe' : 'se';
            case BillmateCountry::NO:
                return ($alpha3) ? 'nor' : 'no';
            case BillmateCountry::DK:
                return ($alpha3) ? 'dnk' : 'dk';
            case BillmateCountry::FI:
                return ($alpha3) ? 'fin' : 'fi';
            case BillmateCountry::DE:
                return ($alpha3) ? 'deu' : 'de';
            case self::NL:
                return ($alpha3) ? 'nld' : 'nl';
            default:
                return null;
        }
    }
    public static function getCodeByName($name, $alpha3 = false) {
         $name = strtolower( $name );
         $val = array_search($name, self::$countriesdata);
         switch($val) {
            case BillmateCountry::SE:
                return ($alpha3) ? 'swe' : 'se';
            case BillmateCountry::NO:
                return ($alpha3) ? 'nor' : 'no';
            case BillmateCountry::DK:
                return ($alpha3) ? 'dnk' : 'dk';
            case BillmateCountry::FI:
                return ($alpha3) ? 'fin' : 'fi';
            case BillmateCountry::DE:
                return ($alpha3) ? 'deu' : 'de';
            case self::NL:
                return ($alpha3) ? 'nld' : 'nl';
            default:
                return null;
        }
    }
} }
if(!class_exists('BillmateCurrency', false)):
class BillmateCurrency
{
    const SEK = 0;
    const NOK = 1;
    const EUR = 2;
    const DKK = 3;
    public static function fromCode($val)
    {
        switch(strtolower($val)) {
        case 'dkk':
            return self::DKK;
        case 'eur':
        case 'euro':
            return self::EUR;
        case 'nok':
            return self::NOK;
        case 'sek':
            return self::SEK;
        default:
            return null;
        }
    }
    public static function getCode($val)
    {
        switch($val) {
        case self::DKK:
            return 'dkk';
        case self::EUR:
            return 'eur';
        case self::NOK:
            return 'nok';
        case self::SEK:
            return 'sek';
        default:
            return null;
        }
    }
}
endif;

//set_error_handler('call_log_billmate');
//set_exception_handler('exception_billmate');

?>