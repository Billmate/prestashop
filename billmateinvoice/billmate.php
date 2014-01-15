<?php


/**
 * Language Constants class
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @link      http://integration.billmate.com/
 */
if(!class_exists('BillmateLanguage', false) ){
class BillmateLanguage
{

    /**
     * Language constant for Danish (DA).<br>
     * ISO639_DA
     *
     * @var int
     */
    const DA = 27;

    /**
     * Language constant for German (DE).<br>
     * ISO639_DE
     *
     * @var int
     */
    const DE = 28;

    /**
     * Language constant for English (EN).<br>
     * ISO639_EN
     *
     * @var int
     */
    const EN = 31;

    /**
     * Language constant for Finnish (FI).<br>
     * ISO639_FI
     *
     * @var int
     */
    const FI = 37;

    /**
     * Language constant for Norwegian (NB).<br>
     * ISO639_NB
     *
     * @var int
     */
    const NB = 97;

    /**
     * Language constant for Dutch (NL).<br>
     * ISO639_NL
     *
     * @var int
     */
    const NL = 101;

    /**
     * Language constant for Swedish (SV).<br>
     * ISO639_SV
     *
     * @var int
     */
    const SV = 138;

    /**
     * Converts a language code, e.g. 'de' to the BillmateLanguage constant.
     *
     * @param string $val language code
     *
     * @return int|null
     */
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

    /**
     * Converts a BillmateLanguage constant to the respective language code.
     *
     * @param int $val BillmateLanguage constant
     *
     * @return string|null
     */
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
/**
 * Provides country constants (ISO3166) for the supported countries.
 *
 * @package BillmateAPI
 */
 if( !class_exists('BillmateCountry' )){
class BillmateCountry {

    /**
     * Country constant for Denmark (DK).<br>
     * ISO3166_DK
     *
     * @var int
     */
    const DK = 59;

    /**
     * Country constant for Finland (FI).<br>
     * ISO3166_FI
     *
     * @var int
     */
    const FI = 73;

    /**
     * Country constant for Germany (DE).<br>
     * ISO3166_DE
     *
     * @var int
     */
    const DE = 81;

    /**
     * Country constant for Netherlands (NL).<br>
     * ISO3166_NL
     *
     * @var int
     */
    const NL = 154;

    /**
     * Country constant for Norway (NO).<br>
     * ISO3166_NO
     *
     * @var int
     */
    const NO = 164;

    /**
     * Country constant for Sweden (SE).<br>
     * ISO3166_SE
     *
     * @var int
     */
    const SE = 209;

    /**
     * Class constructor.
     * Disable instantiation.
     */
    public static $countriesdata = array(209 =>'sweden', 73=> 'finland',59=> 'denmark', 164 => 'norway', 81 => 'germany', 15 => 'austria', 154 => 'netherlands' );
    
    public static function getContryByNumber($number){
       
        return isset(self::$countriesdata[$number]) ? self::$countriesdata[$number]: false;
    }
    
    
    private function __construct() {
    }
    public static function getCountries(){
        return self::$countriesdata;
    }
    /**
     * Converts a country code, e.g. 'de' or 'deu' to the BillmateCountry constant.
     *
     * @param  string  $val
     * @return int|null
     */
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

    /**
     * Converts a BillmateCountry constant to the respective country code.
     *
     * @param  int  $val
     * @param  bool $alpha3  Whether to return a ISO-3166-1 alpha-3 code
     * @return string|null
     */
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
    /**
     * Converts a BillmateCountry constant to the respective country code.
     *
     * @param  int  $val
     * @param  bool $alpha3  Whether to return a ISO-3166-1 alpha-3 code
     * @return string|null
     */
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

} //End BillmateCountry
}

/**
 * Currency Constants class
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * 
 * @link      http://integration.billmate.com/
 */
 
if(!class_exists('BillmateCurrency', false)):
class BillmateCurrency
{

    /**
     * Currency constant for Swedish Crowns (SEK).
     *
     * @var int
     */
    const SEK = 0;

    /**
     * Currency constant for Norwegian Crowns (NOK).
     *
     * @var int
     */
    const NOK = 1;

    /**
     * Currency constant for Euro.
     *
     * @var int
     */
    const EUR = 2;

    /**
     * Currency constant for Danish Crowns (DKK).
     *
     * @var int
     */
    const DKK = 3;

    /**
     * Converts a currency code, e.g. 'eur' to the BillmateCurrency constant.
     *
     * @param string $val currency code
     *
     * @return int|null
     */
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

    /**
     * Converts a BillmateCurrency constant to the respective language code.
     *
     * @param int $val BillmateCurrency constant
     *
     * @return string|null
     */
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
