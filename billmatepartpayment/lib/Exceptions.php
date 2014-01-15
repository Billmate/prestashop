<?php
/**
 * Billmate Exceptions
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

require_once 'Country.php';

/**
 * BillmateException class, only used so it says "BillmateException" instead of
 * Exception.
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class BillmateException extends Exception
{
    /**
     * Returns an error message readable by end customers.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage() . " (#".$this->code.")";
    }
}

/**
 * Exception for invalid Configuration object
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidConfigurationException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            "Supplied config is not a BillmateConfig/ArrayAccess object!",
            50001
        );
    }
}
/**
 * Exception for incomplete Configuration object
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_IncompleteConfigurationException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('Billmate instance not fully configured!', 50002);
    }
}

/**
 * Exception for invalid BillmateAddr object
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidBillmateAddrException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            "Supplied address is not a BillmateAddr object!",
            50011
        );
    }
}

/**
 * Exception for no BillmateAddr set
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_MissingAddressException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("No address set!", 50035);
    }
}

/**
 * Exception for missing Configuration field
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_ConfigFieldMissingException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $field config field
     */
    public function __construct($field)
    {
        parent::__construct("Config field '{$field}' is not valid!", 50003);

    }
}

/**
 * Exception for Unknown Encoding
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnknownEncodingException extends BillmateException
{
    /**
     * Constructor
     *
     * @param int $encoding encoding
     */
    public function __construct($encoding)
    {
        parent::__construct(
            "Unknown PNO/SSN encoding constant! ({$encoding})", 50091
        );
    }
}

/**
 * Exception for Unknown Address Type
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnknownAddressTypeException extends BillmateException
{
    /**
     * Constructor
     *
     * @param int $type type
     */
    public function __construct($type)
    {
        parent::__construct("Unknown address type: {$type}", 50012);
    }
}

/**
 * Exception for Missing Country
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_MissingCountryException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('You must set country first!', 50046);
    }
}

/**
 * Exception for Unknown Country
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnknownCountryException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $country country
     */
    public function __construct($country)
    {
        parent::__construct("Unknown country! ({$country})", 50006);
    }
}

/**
 * Exception for Unknown Language
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnknownLanguageException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $language language
     */
    public function __construct($language)
    {
        parent::__construct("Unknown language! ({$language})", 50007);
    }
}

/**
 * Exception for Unknown Currency
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnknownCurrencyException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $currency currency
     */
    public function __construct($currency)
    {
        parent::__construct("Unknown currency! ({$currency})", 50008);
    }
}

/**
 * Exception for Missing Arguments
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_ArgumentNotSetException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $argument argument
     */
    public function __construct($argument)
    {
        parent::__construct("Argument '{$argument}' not set!", 50005);
    }
}

/**
 * Exception for Country and Currency mismatch
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_CountryCurrencyMismatchException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $country  country
     * @param mixed $currency currency
     */
    public function __construct($country, $currency)
    {
        $countryCode = BillmateCountry::getCode($country);
        parent::__construct(
            "Mismatching country/currency for '{$countryCode}'! ".
            "[country: $country currency: $currency]",
            50011
        );
    }
}

/**
 * Exception for Country and Currency mismatch
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_CountryLanguageMismatchException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $country  country
     * @param mixed $language language
     */
    public function __construct($country, $language)
    {
        $countryCode = BillmateCountry::getCode($country);
        parent::__construct(
            "Mismatching country/language for '{$countryCode}'! ".
            "[country: $country language: $language]",
            50024
        );
    }
}

/**
 * Exception for Unsupported Country
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnsupportedCountryException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $country country
     */
    public function __construct($country)
    {
        $countryCode = BillmateCountry::getCode($country);
        if ($countryCode === null) {
            $countryCode = $country;
        }
        parent::__construct(
            "Unknown/unsupported country! ({$countryCode})! ",
            50025
        );
    }
}
/**
 * Exception for Shipping country being different from set country
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_ShippingCountryException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'Shipping address country must match the country set!', 50041
        );
    }
}

/**
 * Exception for Missing Goodslist
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_MissingGoodslistException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("No articles in goodslist!", 50034);
    }
}

/**
 * Exception for invalid price
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidPriceException extends BillmateException
{
    /**
     * Constructor
     *
     * @param mixed $price price
     */
    public function __construct($price)
    {
        parent::__construct(
            "price/amount must be an integer and greater than 0! ($price)",
            50039
        );
    }
}


/**
 * Exception for invalid pcstorage class
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_PCStorageInvalidException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $className     classname
     * @param string $pclassStorage pcstorage class file
     */
    public function __construct($className, $pclassStorage)
    {
        parent::__construct(
            "$className located in $pclassStorage is not a PCStorage instance.",
            50052
        );
    }
}

/**
 * Exception for invalid type
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidTypeException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $param parameter
     * @param string $type  type
     */
    public function __construct($param, $type)
    {
        parent::__construct(
            "$param is not of the expected type. Expected: $type.",
            50062
        );
    }
}

/**
 * Exception for invalid PNO
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidPNOException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("PNO/SSN is not valid!", 50078);
    }
}


/**
 * Exception for invalid Email
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidEmailException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("Email is not valid!", 50017);
    }
}

/**
 * Exception for invalid Email
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_UnsupportedMarketException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string|array $countries allowed countries
     */
    public function __construct($countries)
    {
        if (is_array($countries)) {
            $countries = implode(", ", $countries);
        }
        parent::__construct(
            "This method is only available for customers from: {$countries}",
            50025
        );
    }
}
/**
 * Exception for invalid Locale
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_InvalidLocaleException extends BillmateException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            "You must set country, language and currency!",
            50023
        );
    }
}

/**
 * Exception for Missing Address Fields
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_AddressFieldMissingException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $argument argument
     */
    public function __construct($argument)
    {
        parent::__construct("'{$argument}' not set!", 50015);
    }
}

/**
 * Exception for File Not Writable
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_FileNotWritableException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $file filename
     */
    public function __construct($file)
    {
        parent::__construct("Unable to write to {$file}!");
    }
}

/**
 * Exception for File Not Readable
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_FileNotReadableException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $file filename
     */
    public function __construct($file)
    {
        parent::__construct("Unable to read from {$file}!");
    }
}

/**
 * Exception for File Not Readable
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_FileNotFoundException extends BillmateException
{
    /**
     * Constructor
     *
     * @param string $file filename
     */
    public function __construct($file)
    {
        parent::__construct("Unable to find file: {$file}!");
    }
}

/**
 * Exception for Database Errors
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */

class Billmate_DatabaseException extends BillmateException
{
}

/**
 * Exception for PClass Errors
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_PClassException extends BillmateException
{
}

/**
 * Exception for XML Parse errors
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class Billmate_XMLParseException extends BillmateException
{
        /**
     * Constructor
     *
     * @param string $file filename
     */
    public function __construct($file)
    {
        parent::__construct("Unable to parse XML file: {$file}!");
    }
}
