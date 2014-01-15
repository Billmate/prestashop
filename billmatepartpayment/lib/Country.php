<?php

/**
 * BillmateCountry
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */

/**
 * Country Constants class
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
if( !class_exists('BillmateCountry', false ) ):
class BillmateCountry
{

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
    public static $countriesdata = array(209 =>'sweden', 73=> 'finland',59=> 'denmark', 164 => 'norway', 81 => 'germany', 15 => 'austria', 154 => 'netherlands' );
    
    public static function getContryByNumber($number){
       
        return isset(self::$countriesdata[$number]) ? self::$countriesdata[$number]: false;
    }

    /**
     * Converts a country code, e.g. 'de' or 'deu' to the BillmateCountry constant.
     *
     * @param string $val country code iso-alpha-2 or iso-alpha-3
     *
     * @return int|null
     */
    public static function fromCode($val)
    {
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
     * @param int  $val    BillmateCountry constant
     * @param bool $alpha3 Whether to return a ISO-3166-1 alpha-3 code
     *
     * @return string|null
     */
    public static function getCode($val, $alpha3 = false)
    {
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
     * Checks country against currency and returns true if they match.
     *
     * @param int $country  {@link BillmateCountry}
     * @param int $language {@link BillmateLanguage}
     *
     * @return bool
     */
    public static function checkLanguage($country, $language)
    {
        switch($country) {
        case BillmateCountry::DE:
            return ($language === BillmateLanguage::DE);
        case BillmateCountry::NL:
            return ($language === BillmateLanguage::NL);
        case BillmateCountry::FI:
            return ($language === BillmateLanguage::FI);
        case BillmateCountry::DK:
            return ($language === BillmateLanguage::DA);
        case BillmateCountry::NO:
            return ($language === BillmateLanguage::NB);
        case BillmateCountry::SE:
            return ($language === BillmateLanguage::SV);
        default:
            //Country not yet supported by Billmate.
            return false;
        }
    }

    /**
     * Checks country against language and returns true if they match.
     *
     * @param int $country  {@link BillmateCountry}
     * @param int $currency {@link BillmateCurrency}
     *
     * @return bool
     */
    public static function checkCurrency($country, $currency)
    {
        switch($country) {
        case BillmateCountry::DE:
        case BillmateCountry::NL:
        case BillmateCountry::FI:
            return ($currency === BillmateCurrency::EUR);
        case BillmateCountry::DK:
            return ($currency === BillmateCurrency::DKK);
        case BillmateCountry::NO:
            return ($currency === BillmateCurrency::NOK);
        case BillmateCountry::SE:
            return ($currency === BillmateCurrency::SEK);
        default:
            //Country not yet supported by Billmate.
            return false;
        }
    }

    /**
     * Get language for supplied country. Defaults to English.
     *
     * @param int $country BillmateCountry constant
     *
     * @return int
     */
    public static function getLanguage($country)
    {
        switch($country) {
        case BillmateCountry::DE:
            return BillmateLanguage::DE;
        case BillmateCountry::NL:
            return BillmateLanguage::NL;
        case BillmateCountry::FI:
            return BillmateLanguage::FI;
        case BillmateCountry::DK:
            return BillmateLanguage::DA;
        case BillmateCountry::NO:
            return BillmateLanguage::NB;
        case BillmateCountry::SE:
            return BillmateLanguage::SV;
        default:
            return BillmateLanguage::EN;
        }
    }

    /**
     * Get currency for supplied country
     *
     * @param int $country BillmateCountry constant
     *
     * @return int|false
     */
    public static function getCurrency($country)
    {
        switch($country) {
        case BillmateCountry::DE:
        case BillmateCountry::NL:
        case BillmateCountry::FI:
            return BillmateCurrency::EUR;
        case BillmateCountry::DK:
            return BillmateCurrency::DKK;
        case BillmateCountry::NO:
            return BillmateCurrency::NOK;
        case BillmateCountry::SE:
            return BillmateCurrency::SEK;
        default:
            return false;
        }
    }
}
endif;
