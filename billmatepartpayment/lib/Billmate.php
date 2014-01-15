<?php
/**
 * Billmate API
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
 * This API provides a way to integrate with Billmate's services over the
 * XMLRPC protocol.
 *
 * All strings inputted need to be encoded with ISO-8859-1.<br>
 * In addition you need to decode HTML entities, if they exist.<br>
 *
 * For more information see our
 * {@link http://integration.billmate.com/en/api/step-by-step step by step} guide.
 *
 * Dependencies:
 *
 *  xmlrpc-3.0.0.beta/lib/xmlrpc.inc
 *      from {@link http://phpxmlrpc.sourceforge.net/}
 *
 * xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc
 *      from {@link http://phpxmlrpc.sourceforge.net/}
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
 
require dirname(__FILE__).'/lib/xmlrpc.inc';
require dirname(__FILE__).'/lib/xmlrpc_wrappers.inc';
require_once dirname(dirname(dirname(__FILE__))).'/billmateinvoice/commonfunctions.php'; 

class Billmate
{
    /**
     * Billmate PHP API version identifier.
     *
     * @var string
     */
    protected $VERSION = '';
 	var $SERVER = "1.0";
 	var $CLIENT = "";
 	var $STAT  = "stat.billmate.se";

    /**
     * Billmate protocol identifier.
     *
     * @var string
     */
    protected $PROTO = '4.1';

    /**
     * Flag to indicate use of the report server Candice.
     *
     * @var bool
     */
    private static $_candice = true;

    /**
     * URL/Address to the Candice server.
     * Port used is 80.
     *
     * @var string
     */
    private static $_c_addr = "api.billmate.se";

    /**
     * Constants used with LIVE mode for the communications with Billmate.
     *
     * @var int
     */
    const LIVE = 0;

    /**
     * URL/Address to the live Billmate Online server.
     * Port used is 443 for SSL and 80 without.
     *
     * @var string
     */
    private static $_live_addr = 'api.billmate.se';

    /**
     * Constants used with BETA mode for the communications with Billmate.
     *
     * @var int
     */
    const BETA = 1;

    /**
     * URL/Address to the beta test Billmate Online server.
     * Port used is 443 for SSL and 80 without.
     *
     * @var string
     */
    private static $_beta_addr = 'apitest.billmate.se';

    /**
     * Indicates whether the communications is over SSL or not.
     *
     * @var bool
     */
    protected $ssl = false;

    /**
     * An object of xmlrpc_client, used to communicate with Billmate.
     *
     * @link http://phpxmlrpc.sourceforge.net/
     *
     * @var xmlrpc_client
     */
    protected $xmlrpc;

    /**
     * Which server the Billmate API is using, LIVE or BETA (TESTING).
     *
     * @see Billmate::LIVE
     * @see Billmate::BETA
     *
     * @var int
     */
    protected $mode;

    /**
     * The URL/Address used to communicate with Billmate.
     *
     * @var string
     */
    protected $addr;

    /**
     * The port number used to communicate with Billmate.
     *
     * @var int
     */
    protected $port;

    /**
     * The estore's identifier received from Billmate.
     *
     * @var int
     */
    private $_eid;

    /**
     * The estore's shared secret received from Billmate.
     *
     * <b>Note</b>:<br>
     * DO NOT SHARE THIS WITH ANYONE!
     *
     * @var string
     */
    private $_secret;

    /**
     * BillmateCountry constant.
     *
     * @see BillmateCountry
     *
     * @var int
     */
    private $_country;

    /**
     * BillmateCurrency constant.
     *
     * @see BillmateCurrency
     *
     * @var int
     */
    private $_currency;

    /**
     * BillmateLanguage constant.
     *
     * @see BillmateLanguage
     *
     * @var int
     */
    private $_language;

    /**
     * An array of articles for the current order.
     *
     * @var array
     */
    protected $goodsList;

    /**
     * An array of article numbers and quantity.
     *
     * @var array
     */
    protected $artNos;

    /**
     * An BillmateAddr object containing the billing address.
     *
     * @var BillmateAddr
     */
    protected $billing;

    /**
     * An BillmateAddr object containing the shipping address.
     *
     * @var BillmateAddr
     */
    protected $shipping;

    /**
     * Estore's user(name) or identifier.
     * Only used in {@link Billmate::addTransaction()}.
     *
     * @var string
     */
    protected $estoreUser = "";

    /**
     * External order numbers from other systems.
     *
     * @var string
     */
    protected $orderid = array("", "");

    /**
     * Reference (person) parameter.
     *
     * @var string
     */
    protected $reference = "";

    /**
     * Reference code parameter.
     *
     * @var string
     */
    protected $reference_code = "";

    /**
     * An array of named extra info.
     *
     * @var array
     */
    protected $extraInfo = array();

    /**
     * An array of named bank info.
     *
     * @var array
     */
    protected $bankInfo = array();

    /**
     * An array of named income expense info.
     *
     * @var array
     */
    protected $incomeInfo = array();

    /**
     * An array of named shipment info.
     *
     * @var array
     */
    protected $shipInfo = array();

    /**
     * An array of named travel info.
     *
     * @ignore Do not show this in PHPDoc.
     * @var array
     */
    protected $travelInfo = array();

    /**
     * An array of named session id's.<br>
     * E.g. "dev_id_1" => ...<br>
     *
     * @var array
     */
    protected $sid = array();

    /**
     * A comment sent in the XMLRPC communications.
     * This is resetted using clear().
     *
     * @var string
     */
    protected $comment = "";

    /**
     * An array with all the checkoutHTML objects.
     *
     * @var array
     */
    protected $coObjects = array();

    /**
     * Flag to indicate if the API should output verbose
     * debugging information.
     *
     * @var bool
     */
    public static $debug = false;

    /**
     * Turns on the internal XMLRPC debugging.
     *
     * @var bool
     */
    public static $xmlrpcDebug = false;

    /**
     * If this is set to true, XMLRPC invocation is disabled.
     *
     * @var bool
     */
    public static $disableXMLRPC = false;

    /**
     * If the estore is using a proxy which populates the clients IP to
     * x_forwarded_for
     * then and only then should this be set to true.
     *
     * <b>Note</b>:<br>
     * USE WITH CARE!
     *
     * @var bool
     */
    public static $x_forwarded_for = false;

    /**
     * Array of HTML entities, used to create numeric htmlentities.
     *
     * @ignore Do not show this in PHPDoc.
     * @var array
     */
    protected static $htmlentities = false;

    /**
     * Populated with possible proxy information.
     * A comma separated list of IP addresses.
     *
     * @var string
     */
    private $_x_fwd;

    /**
     * The storage class for PClasses.
     *
     * Use 'xml' for xmlstorage.class.php.<br>
     * Use 'mysql' for mysqlstorage.class.php.<br>
     * Use 'json' for jsonstorage.class.php.<br>
     *
     * @var string
     */
    protected $pcStorage;

    /**
     * The storage URI for PClasses.
     *
     * Use the absolute or relative URI to a file if
     * {@link Billmate::$pcStorage} is set as 'xml' or 'json'.<br>
     * Use a HTTP-auth similar URL if {@link Billmate::$pcStorage} is set
     * as 'mysql', <br>
     * e.g. user:passwd@addr:port/dbName.dbTable.<br>
     * Or an associative array (recommended) {@see MySQLStorage}
     *
     * @var mixed
     */
    protected $pcURI;

    /**
     * PCStorage instance.
     *
     * @ignore Do not show this in PHPDoc.
     * @var PCStorage
     */
    protected $pclasses;

    /**
     * ArrayAccess instance.
     *
     * @ignore Do not show this in PHPDoc.
     * @var ArrayAccess
     */
    protected $config;

    /**
     * Empty constructor, because sometimes it's needed.
     */
    public function __construct()
    {
		$this->CLIENT = $this->VERSION = BILLMATE_VERSION;
    }

    /**
     * Checks if the config has fields described in argument.<br>
     * Missing field(s) is in the exception message.
     *
     * To check that the config has eid and secret:<br>
     * <code>
     * try {
     *     $this->hasFields('eid', 'secret');
     * }
     * catch(Exception $e) {
     *     echo "Missing fields: " . $e->getMessage();
     * }
     * </code>
     *
     * @throws Exception
     * @return void
     */
    protected function hasFields(/*variable arguments*/)
    {
        $missingFields = array();
        $args = func_get_args();
        foreach ($args as $field) {
            if (!isset($this->config[$field])) {
                $missingFields[] = $field;
            }
        }
        if (count($missingFields) > 0) {
            throw new Billmate_ConfigFieldMissingException(
                implode(', ', $missingFields)
            );
        }
    }

    /**
     * Initializes the Billmate object accordingly to the set config object.
     *
     * @throws BillmateException
     * @return void
     */
    protected function init()
    {
        $this->hasFields('eid', 'secret', 'mode', 'pcStorage', 'pcURI');

        if (!is_int($this->config['eid'])) {
            $this->config['eid'] = intval($this->config['eid']);
        }

        if ($this->config['eid'] <= 0) {
            throw new Billmate_ConfigFieldMissingException('eid');
        }

        if (!is_string($this->config['secret'])) {
            $this->config['secret'] = strval($this->config['secret']);
        }

        if (strlen($this->config['secret']) == 0) {
            throw new Billmate_ConfigFieldMissingException('secret');
        }

        //Set the shop id and secret.
        $this->_eid = $this->config['eid'];
        $this->_secret = $this->config['secret'];

        if (!is_numeric($this->config['country'])
            && strlen($this->config['country']) == 2
        ) {
            $this->setCountry($this->config['country']);
        } else {
            //Set the country specific attributes.
            try {
                $this->hasFields('country', 'language', 'currency');

                //If hasFields doesn't throw exception we can set them all.
                $this->setCountry($this->config['country']);
                $this->setLanguage($this->config['language']);
                $this->setCurrency($this->config['currency']);
            } catch(Exception $e) {
                //fields missing for country, language or currency
                $this->_country = $this->_language = $this->_currency = null;
            }
        }

        //Set addr and port according to mode.
        $this->mode = (int)$this->config['mode'];

        if ($this->mode === self::LIVE) {
            $this->addr = self::$_live_addr;
            $this->ssl = true;
        } else {
            $this->addr = self::$_beta_addr;
            $this->ssl = true;
        }

        try {
            $this->hasFields('ssl');
            $this->ssl = (bool)$this->config['ssl'];
        } catch(Exception $e) {
            //No 'ssl' field ignore it...
        }

        if ($this->ssl) {
             $this->port = 443;
        } else {
            $this->port = 80;
        }

        try {
            $this->hasFields('candice');
            self::$_candice = (bool)$this->config['candice'];
        } catch(Exception $e) {
            //No 'candice' field ignore it...
        }

        try {
            $this->hasFields('xmlrpcDebug');
            Billmate::$xmlrpcDebug = $this->config['xmlrpcDebug'];
        } catch(Exception $e) {
            //No 'xmlrpcDebug' field ignore it...
        }

        try {
            $this->hasFields('debug');
            Billmate::$debug = $this->config['debug'];
        } catch(Exception $e) {
            //No 'debug' field ignore it...
        }

        $this->pcStorage = $this->config['pcStorage'];
        $this->pcURI = $this->config['pcURI'];
        $this->xmlrpc = new xmlrpc_client(
            '/',
            $this->addr,
            $this->port,
            ($this->ssl) ? 'https' : 'http'
        );

        $this->xmlrpc->request_charset_encoding = 'ISO-8859-1';
    }

    /**
     * Method of ease for setting common config fields.
     *
     * The storage module for PClasses:<br>
     * Use 'xml' for xmlstorage.class.php.<br>
     * Use 'mysql' for mysqlstorage.class.php.<br>
     * Use 'json' for jsonstorage.class.php.<br>
     *
     * The storage URI for PClasses:<br>
     * Use the absolute or relative URI to a file if {@link Billmate::$pcStorage}
     * is set as 'xml' or 'json'.<br>
     * Use a HTTP-auth similar URL if {@link Billmate::$pcStorage} is set as
     * mysql', e.g. user:passwd@addr:port/dbName.dbTable.
     * Or an associative array (recommended) {@see MySQLStorage}
     *
     * <b>Note</b>:<br>
     * This disables the config file storage.<br>
     *
     * @param int    $eid       Merchant ID/EID
     * @param string $secret    Secret key/Shared key
     * @param int    $country   {@link BillmateCountry}
     * @param int    $language  {@link BillmateLanguage}
     * @param int    $currency  {@link BillmateCurrency}
     * @param int    $mode      {@link Billmate::LIVE} or {@link Billmate::BETA}
     * @param string $pcStorage PClass storage module.
     * @param string $pcURI     PClass URI.
     * @param bool   $ssl       Whether HTTPS (HTTP over SSL) or HTTP is used.
     * @param bool   $candice   Error reporting to Billmate.
     *
     * @see Billmate::setConfig()
     * @see BillmateConfig
     *
     * @throws BillmateException
     * @return void
     */
    public function config(
        $eid, $secret, $country, $language, $currency,
        $mode = Billmate::LIVE, $pcStorage = 'json', $pcURI = 'pclasses.json',
        $ssl = true, $candice = true
    ) {

        try {
            BillmateConfig::$store = false;
            $this->config = new BillmateConfig(null);

            $this->config['eid'] = $eid;
            $this->config['secret'] = $secret;
            $this->config['country']  = $country;
            $this->config['language'] = $language;
            $this->config['currency'] = $currency;
            $this->config['mode'] = $mode;
            $this->config['ssl'] = $ssl;
            $this->config['candice'] = $candice;
            $this->config['pcStorage'] = $pcStorage;
            $this->config['pcURI'] = $pcURI;

            $this->init();
        } catch(Exception $e) {
					p($e);
            $this->config = null;
            throw new BillmateException(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Sets and initializes this Billmate object using the supplied config object.
     *
     * @param BillmateConfig &$config Config object.
     *
     * @see BillmateConfig
     * @throws  BillmateException
     * @return  void
     */
    public function setConfig(&$config)
    {
        if (!$config instanceof ArrayAccess) {
            throw new Billmate_InvalidConfigurationException;
        }
        $this->config = $config;
        $this->init();
    }

    /**
     * Get the complete locale (country, language, currency) to use for the
     * values passed, or the configured value if passing null.
     *
     * @param mixed $country  country  constant or code
     * @param mixed $language language constant or code
     * @param mixed $currency currency constant or code
     *
     * @throws BillmateException
     * @return array
     */
    public function getLocale(
        $country = null, $language = null, $currency = null
    ) {
        $locale = array(
            'country' => null,
            'language' => null,
            'currency' => null
        );

        if ($country === null) {
            // Use the configured country / language / currency
            $locale['country'] = $this->_country;
            if ($this->_language !== null) {
                $locale['language'] = $this->_language;
            }

            if ($this->_currency !== null) {
                $locale['currency'] = $this->_currency;
            }
        } else {
            // Use the given country / language / currency
            if (!is_numeric($country)) {
                $country = BillmateCountry::fromCode($country);
            }
            $locale['country'] = intval($country);

            if ($language !== null) {
                if (!is_numeric($language)) {
                    $language = BillmateLanguage::fromCode($language);
                }
                $locale['language'] = intval($language);
            }

            if ($currency !== null) {
                if (!is_numeric($currency)) {
                    $currency = BillmateCurrency::fromCode($currency);
                }
                $locale['currency'] = intval($currency);
            }
        }

        // Complete partial structure with defaults
        if ($locale['currency'] === null) {
            $locale['currency'] = $this->getCurrencyForCountry(
                $locale['country']
            );
        }

        if ($locale['language'] === null) {
            $locale['language'] = $this->getLanguageForCountry(
                $locale['country']
            );
        }

        $this->_checkCountry($locale['country']);
        $this->_checkCurrency($locale['currency']);
        $this->_checkLanguage($locale['language']);

        if (!BillmateCountry::checkCurrency(
            $locale['country'], $locale['currency']
        )
        ) {
            throw new Billmate_CountryCurrencyMismatchException($country, $currency);
        }
        if (!BillmateCountry::checkLanguage(
            $locale['country'], $locale['language']
        )
        ) {
            throw new Billmate_CountryLanguageMismatchException($country, $language);
        }

        return $locale;
    }

    /**
     * Sets the country used.
     *
     * <b>Note</b>:<br>
     * If you input 'dk', 'fi', 'de', 'nl', 'no' or 'se', <br>
     * then currency and language will be set to mirror that country.<br>
     *
     * @param string|int $country {@link BillmateCountry}
     *
     * @see BillmateCountry
     *
     * @throws BillmateException
     * @return void
     */
    public function setCountry($country)
    {
        if (!is_numeric($country)
            && (strlen($country) == 2 || strlen($country) == 3)
        ) {
            $this->setCountry(self::getCountryForCode($country));
            $this->setCurrency($this->getCurrencyForCountry());
            $this->setLanguage($this->getLanguageForCountry());
        } else {
            $this->_checkCountry($country);
            $this->_country = $country;
        }
    }

    /**
     * Returns the country code for the set country constant.
     *
     * @param int $country {@link BillmateCountry Country} constant.
     *
     * @return string  Two letter code, e.g. "se", "no", etc.
     */
    public function getCountryCode($country = null)
    {
        if ($country === null) {
            $country = $this->_country;
        }

        $code = BillmateCountry::getCode($country);
        return (string) $code;
    }

    /**
     * Returns the {@link BillmateCountry country} constant from the country code.
     *
     * @param string $code Two letter code, e.g. "se", "no", etc.
     *
     * @throws BillmateException
     * @return int {@link BillmateCountry Country} constant.
     */
    public static function getCountryForCode($code)
    {
        $country = BillmateCountry::fromCode($code);
        if ($country === null) {
            throw new Billmate_UnknownCountryException($code);
        }
        return $country;
    }

    /**
     * Returns the country constant.
     *
     * @return int  {@link BillmateCountry}
     */
    public function getCountry()
    {
        return $this->_country;
    }

    /**
     * Sets the language used.
     *
     * <b>Note</b>:<br>
     * You can use the two letter language code instead of the constant.<br>
     * E.g. 'da' instead of using {@link BillmateLanguage::DA}.<br>
     *
     * @param string|int $language {@link BillmateLanguage}
     *
     * @see BillmateLanguage
     *
     * @throws BillmateException
     * @return void
     */
    public function setLanguage($language)
    {
        if (!is_numeric($language) && strlen($language) == 2) {
            $this->setLanguage(self::getLanguageForCode($language));
        } else {
            $this->_checkLanguage($language);
            $this->_language = $language;
        }
    }

    /**
     * Returns the language code for the set language constant.
     *
     * @param int $language {@link BillmateLanguage Language} constant.
     *
     * @return string Two letter code, e.g. "da", "de", etc.
     */
    public function getLanguageCode($language = null)
    {
        if ($language === null) {
            $language = $this->_language;
        }
        $code = BillmateLanguage::getCode($language);

        return (string) $code;
    }

    /**
     * Returns the {@link BillmateLanguage language} constant from the language code.
     *
     * @param string $code Two letter code, e.g. "da", "de", etc.
     *
     * @throws BillmateException
     * @return int  {@link BillmateLanguage Language} constant.
     */
    public static function getLanguageForCode($code)
    {
        $language = BillmateLanguage::fromCode($code);

        if ($language === null) {
            throw new Billmate_UnknownLanguageException($code);
        }
        return $language;
    }

    /**
     * Returns the language constant.
     *
     * @return int  {@link BillmateLanguage}
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * Sets the currency used.
     *
     * <b>Note</b>:<br>
     * You can use the three letter shortening of the currency.<br>
     * E.g. "dkk", "eur", "nok" or "sek" instead of the constant.<br>
     *
     * @param string|int $currency {@link BillmateCurrency}
     *
     * @see BillmateCurrency
     *
     * @throws BillmateException
     * @return void
     */
    public function setCurrency($currency)
    {
        if (!is_numeric($currency) && strlen($currency) == 3) {
            $this->setCurrency(self::getCurrencyForCode($currency));
        } else {
            $this->_checkCurrency($currency);
            $this->_currency = $currency;
        }
    }

    /**
     * Returns the {@link BillmateCurrency currency} constant from the currency
     * code.
     *
     * @param string $code Two letter code, e.g. "dkk", "eur", etc.
     *
     * @throws BillmateException
     * @return int  {@link BillmateCurrency Currency} constant.
     */
    public static function getCurrencyForCode($code)
    {
        $currency = BillmateCurrency::fromCode($code);
        if ($currency === null) {
            throw new Billmate_UnknownCurrencyException($code);
        }
        return $currency;
    }

    /**
     * Returns the the currency code for the set currency constant.
     *
     * @param int $currency {@link BillmateCurrency Currency} constant.
     *
     * @return string  Three letter currency code.
     */
    public function getCurrencyCode($currency = null)
    {
        if ($currency === null) {
            $currency = $this->_currency;
        }

        $code = BillmateCurrency::getCode($currency);
        return (string) $code;
    }

    /**
     * Returns the set currency constant.
     *
     * @return int  {@link BillmateCurrency}
     */
    public function getCurrency()
    {
        return $this->_currency;
    }

    /**
     * Checks set country against set currency and returns true if they match.
     * {@link BillmateCountry} or {@link BillmateCurrency} constants can be used,
     * or letter codes. Uses set values if parameter is null.
     *
     * E.g. Billmate allows Euro with Germany, Netherlands and Finland, thus
     * true will be returned.
     *
     * @param string|int $country  {@link BillmateCountry}
     * @param string|int $currency {@link BillmateCurrency}
     *
     * @throws BillmateException
     * @return bool
     */
    public function checkCountryCurrency($country = null, $currency = null)
    {
        if ($country === null) {
            $country = $this->_country;
        } else if (!is_numeric($country)
            && (strlen($country) == 2 || strlen($country) == 3)
        ) {
            $country = self::getCountryForCode($country);
        }

        if ($currency === null) {
            $currency = $this->_currency;
        } else if (!is_numeric($currency) && strlen($currency) == 3) {
            $currency = self::getCurrencyForCode($currency);
        }

        return BillmateCountry::checkCurrency($country, $currency);
    }

    /**
     * Returns the {@link BillmateLanguage language} constant for the specified
     * or set country.
     *
     * @param int $country {@link BillmateCountry Country} constant.
     *
     * @return int|false if no match otherwise BillmateLanguage constant.
     */
    public function getLanguageForCountry($country = null)
    {
        if ($country === null) {
            $country = $this->_country;
        }
        // Since getLanguage defaults to EN, check so we actually have a match
        $language = BillmateCountry::getLanguage($country);
        if (BillmateCountry::checkLanguage($country, $language)) {
            return $language;
        }
        return false;
    }

    /**
     * Returns the {@link BillmateCurrency currency} constant for the specified
     * or set country.
     *
     * @param int $country {@link BillmateCountry country} constant.
     *
     * @return int|false {@link BillmateCurrency currency} constant.
     */
    public function getCurrencyForCountry($country = null)
    {
        if ($country === null) {
            $country = $this->_country;
        }
        return BillmateCountry::getCurrency($country);
    }

    /**
     * <b>STILL UNDER DEVELOPMENT</b><br>
     * Sets the session id's for various device identification,
     * behaviour identification software.
     *
     * <b>Available named session id's</b>:<br>
     * string - dev_id_1<br>
     * string - dev_id_2<br>
     * string - dev_id_3<br>
     * string - beh_id_1<br>
     * string - beh_id_2<br>
     * string - beh_id_3<br>
     *
     * @param string $name Session ID identifier, e.g. 'dev_id_1'.
     * @param string $sid  Session ID.
     *
     * @throws BillmateException
     * @return void
     */
    public function setSessionID($name, $sid)
    {
        $this->_checkArgument($name, "name");
        $this->_checkArgument($sid, "sid");

        $this->sid[$name] = $sid;
    }

    /**
     * <b>STILL UNDER DEVELOPMENT</b><br>
     * Sets the shipment information for the upcoming transaction.<br>
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * int    - delay_adjust<br>
     * string - shipping_company<br>
     * string - shipping_product<br>
     * string - tracking_no<br>
     * array  - warehouse_addr<br>
     *
     * "warehouse_addr" is sent using {@link BillmateAddr::toArray()}.
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws BillmateException
     * @return void
     */
    public function setShipmentInfo($name, $value)
    {
        $this->_checkArgument($name, "name");

        $this->shipInfo[$name] = $value;
    }

    /**
     * <b>STILL UNDER DEVELOPMENT</b><br>
     * Sets the extra information for the upcoming transaction.<br>
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * string - cust_no<br>
     * string - estore_user<br>
     * string - maiden_name<br>
     * string - place_of_birth<br>
     * string - password<br>
     * string - new_password<br>
     * string - captcha<br>
     * int    - poa_group<br>
     * string - poa_pno<br>
     * string - ready_date<br>
     * string - rand_string<br>
     * int    - bclass<br>
     * string - pin<br>
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws BillmateException
     * @return void
     */
    public function setExtraInfo($name, $value)
    {
        $this->_checkArgument($name, "name");

        $this->extraInfo[$name] = $value;
    }

    /**
     * <b>STILL UNDER DEVELOPMENT</b><br>
     * Sets the income expense information for the upcoming transaction.<br>
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * int - yearly_salary<br>
     * int - no_people_in_household<br>
     * int - no_children_below_18<br>
     * int - net_monthly_household_income<br>
     * int - monthly_cost_accommodation<br>
     * int - monthly_cost_other_loans<br>
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws BillmateException
     * @return void
     */
    public function setIncomeInfo($name, $value)
    {
        $this->_checkArgument($name, "name");

        $this->incomeInfo[$name] = $value;
    }

    /**
     * <b>STILL UNDER DEVELOPMENT</b><br>
     * Sets the bank information for the upcoming transaction.<br>
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * int    - bank_acc_bic<br>
     * int    - bank_acc_no<br>
     * int    - bank_acc_pin<br>
     * int    - bank_acc_tan<br>
     * string - bank_name<br>
     * string - bank_city<br>
     * string - iban<br>
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws BillmateException
     * @return void
     */
    public function setBankInfo($name, $value)
    {
        $this->_checkArgument($name, "name");

        $this->bankInfo[$name] = $value;
    }

    /**
     * <b>STILL UNDER DEVELOPMENT</b><br>
     * Sets the travel information for the upcoming transaction.<br>
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * string - travel_company<br>
     * string - reseller_company<br>
     * string - departure_date<br>
     * string - return_date<br>
     * array  - destinations<br>
     * array  - passenger_list<br>
     * array  - passport_no<br>
     * array  - driver_license_no<br>
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws BillmateException
     * @return void
     */
    public function setTravelInfo($name, $value)
    {
        $this->_checkArgument($name, "name");

        $this->travelInfo[$name] = $value;
    }

    /**
     * Returns the clients IP address.
     *
     * @return string
     */
    public function getClientIP()
    {
        //Proxy handling.
        $tmp_ip = $_SERVER['REMOTE_ADDR'];
        $x_fwd = null;

        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $x_fwd = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }

        if (self::$x_forwarded_for && ($x_fwd !== null)) {
            //Cut out the first IP address
            $cpos = strpos($x_fwd, ',');
            if ($cpos !== false) {
                $tmp_ip = substr($x_fwd, 0, $cpos);
                $x_fwd = substr($x_fwd, $cpos+2);
            } else { //Only one IP address
                $tmp_ip = $x_fwd;
                $x_fwd = null;
            }
        }
        $this->_x_fwd = $x_fwd;

        return $tmp_ip;
    }

    /**
     * Sets the specified address for the current order.
     *
     * <b>Address type can be</b>:<br>
     * {@link BillmateFlags::IS_SHIPPING}<br>
     * {@link BillmateFlags::IS_BILLING}<br>
     *
     * @param int        $type Address type.
     * @param BillmateAddr $addr Specified address.
     *
     * @throws BillmateException
     * @return void
     */
    public function setAddress($type, $addr)
    {
        if (!($addr instanceof BillmateAddr)) {
            throw new Billmate_InvalidBillmateAddrException;
        }

        if ($addr->isCompany === null) {
            $addr->isCompany = false;
        }

        if ($type === BillmateFlags::IS_SHIPPING) {
            $this->shipping = $addr;
            self::printDebug("shipping address array", $this->shipping);
            return;
        }

        if ($type === BillmateFlags::IS_BILLING) {
            $this->billing = $addr;
            self::printDebug("billing address array", $this->billing);
            return;
        }
        throw new Billmate_UnknownAddressTypeException($type);
    }

    /**
     * Sets order id's from other systems for the upcoming transaction.<br>
     * User is only sent with {@link Billmate::addTransaction()}.<br>
     *
     * @param string $orderid1 order id 1
     * @param string $orderid2 order id 2
     * @param string $user     username
     *
     * @see Billmate::setExtraInfo()
     *
     * @throws BillmateException
     * @return void
     */
    public function setEstoreInfo($orderid1 = "", $orderid2 = "", $user = "")
    {
        if (!is_string($orderid1)) {
            $orderid1 = strval($orderid1);
        }

        if (!is_string($orderid2)) {
            $orderid2 = strval($orderid2);
        }

        if (!is_string($user)) {
            $user = strval($user);
        }

        if (strlen($user) > 0 ) {
            $this->setExtraInfo('estore_user', $user);
        }

        $this->orderid[0] = $orderid1;
        $this->orderid[1] = $orderid2;
    }

    /**
     * Sets the reference (person) and reference code, for the upcoming
     * transaction.
     *
     * If this is omitted, it can grab first name, last name from the address
     * and use that as a reference person.
     *
     * @param string $ref  Reference person / message to customer on invoice.
     * @param string $code Reference code / message to customer on invoice.
     *
     * @return void
     */
    public function setReference($ref, $code)
    {
        $this->_checkRef($ref, $code);
        $this->reference = $ref;
        $this->reference_code = $code;
    }

    /**
     * Returns the reference (person).
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Returns an associative array used to send the address to Billmate.
     *
     *
     * @param BillmateAddr $addr Address object to assemble.
     *
     * @throws BillmateException
     * @return array The address for the specified method.
     */
    protected function assembleAddr($addr)
    {
        if (!($addr instanceof BillmateAddr)) {
            throw new Billmate_InvalidBillmateAddrException;
        }

        return $addr->toArray();
    }

    /**
     * Sets the comment field, which can be shown in the invoice.
     *
     * @param string $data comment to set
     *
     * @return void
     */
    public function setComment($data)
    {
        $this->comment = $data;
    }

    /**
     * Adds an additional comment to the comment field. Appends with a newline.
     *
     * @param string $data comment to add
     *
     * @see Billmate::setComment()
     *
     * @return void
     */
    public function addComment($data)
    {
        $this->comment .= "\n".$data;
    }

    /**
     * Returns the PNO/SSN encoding constant for currently set country.
     *
     * <b>Note</b>:<br>
     * Country, language and currency needs to match!
     *
     * @throws BillmateException
     * @return int  {@link BillmateEncoding} constant.
     */
    public function getPNOEncoding()
    {
        $this->_checkLocale();


        switch ($this->_country) {
        case BillmateCountry::DE:
            if ($this->_currency !== BillmateCurrency::EUR
                || $this->_language !== BillmateLanguage::DE
            ) {
                throw new Billmate_CountryLanguageMismatchException(
                    $this->_currency, $this->_language
                );
            }
            return BillmateEncoding::PNO_DE;
        case BillmateCountry::DK:
            if ($this->_currency !== BillmateCurrency::DKK
                || $this->_language !== BillmateLanguage::DA
            ) {
                throw new Billmate_CountryLanguageMismatchException(
                    $this->_currency, $this->_language
                );
            }
            return BillmateEncoding::PNO_DK;
        case BillmateCountry::FI:
            if ($this->_currency !== BillmateCurrency::EUR
                || ($this->_language !== BillmateLanguage::FI
                && $this->_language !== BillmateLanguage::SV)
            ) {
                throw new Billmate_CountryLanguageMismatchException(
                    $this->_currency, $this->_language
                );
            }
            return BillmateEncoding::PNO_FI;
        case BillmateCountry::NL:
            if ($this->_currency !== BillmateCurrency::EUR
                || $this->_language !== BillmateLanguage::NL
            ) {
                throw new Billmate_CountryLanguageMismatchException(
                    $this->_currency, $this->_language
                );
            }
            return BillmateEncoding::PNO_NL;
        case BillmateCountry::NO:
            if ($this->_currency !== BillmateCurrency::NOK
                || $this->_language !== BillmateLanguage::NB
            ) {
                throw new Billmate_CountryLanguageMismatchException(
                    $this->_currency, $this->_language
                );
            }
            return BillmateEncoding::PNO_NO;
        case BillmateCountry::SE:
            if ($this->_currency !== BillmateCurrency::SEK
                || $this->_language !== BillmateLanguage::SV
            ) {
                throw new Billmate_CountryLanguageMismatchException(
                    $this->_currency, $this->_language
                );
            }
            return BillmateEncoding::PNO_SE;
        default:
            throw new Billmate_UnsupportedCountryException(
                $this->_country
            );
        }
    }

    /**
     * The purpose of this method is to check if the customer has answered
     * the ILT questions. If the questions need to be answered, an array
     * will be returned with a list of question ids in 'ilt_question_ids'
     * and a url where question data can be retrieved in 'ilt_question_url'.
     * The answers should be set using {@link Billmate::setIncomeInfo()}
     * using the same identifiers
     *
     * Note:
     * You need to call {@link Billmate::setAddress()} with
     * {@link BillmateFlags::IS_SHIPPING} before calling this method.
     *
     * An example could be:<br>
     * <code>
     * array(
     *     'ilt_question_url' => 'http://static.billmate.com/external/ilt/data',
     *     'ilt_question_ids' => array('ilt_1', 'ilt_2', 'ilt_3')
     * )
     * </code>
     *
     * You need to render this question and then send the identifier<br>
     * and the user supplied answer in {@link Billmate::setIncomeInfo()}.
     *
     * @param string    $pno      Personal number, SSN, date of birth, etc.
     * @param int       $gender   {@link BillmateFlags::FEMALE} or
     *                            {@link BillmateFlags::MALE},
     *                            null or "" for unspecified.
     * @param int|float $amount   Amount including VAT.
     * @param int       $pclass   The PClass
     * @param int       $encoding {@link BillmateEncoding Encoding} constant for
     *                            the PNO parameter.
     *
     * @throws BillmateException
     * @return array
     */
    public function checkILT(
        $pno, $gender, $amount, $pclass = -1, $encoding = null
    ) {
        $this->_checkLocale();

        $this->_checkAmount($amount);

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }
        $this->_checkPNO($pno, $encoding);

        if ($gender === 'm') {
            $gender = BillmateFlags::MALE;
        } else if ($gender === 'f') {
            $gender = BillmateFlags::FEMALE;
        }

        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        if (!($this->shipping instanceof BillmateAddr)) {
            throw new Billmate_MissingAddressException;
        }

        $shipping = $this->assembleAddr($this->shipping);

        //Shipping country must match specified country!
        if (strlen($shipping['country']) > 0
            && ($shipping['country'] !== $this->_country)
        ) {
            throw new Billmate_ShippingCountryException;
        }

        $digestSecret = self::digest(
            $this->colon(
                $this->_eid,
                $pno . $gender,
                $pclass,
                $amount,
                $this->_secret
            )
        );

        $paramList = array(
            $pno,
            $gender,
            $shipping,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding,
            $pclass,
            $amount
        );
		
		//billmate_log_data(array($paramList), $this->_eid, $method);
		
        self::printDebug("check_ilt array", $paramList);
		
        $result = $this->xmlrpc_call('check_ilt', $paramList);
		
        self::printDebug("check_ilt result array", $result);

        return $result;
    }
 	function GetAddress($pno) {
 		$type = 5;

		if(!isset($_SESSION['partpayment_person_nummber']) || $pno != $_SESSION['partpayment_person_nummber']){
			$hash = self::digest(
				$this->colon($this->_eid,  $pno, $this->_secret)
			);
			$params = array($pno,$this->_eid,$hash,'UTF-8',$type,$this->IP());
			$result = $this->xmlrpc_call('get_addresses', $params);
			
		}else{
			$result = $_SESSION['partpayment_person_data'];
		}


		$_SESSION['partpayment_person_nummber'] = $pno;
		$_SESSION['partpayment_person_data'] = $result;		
		
        return $result;
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
    		$is_ip = ereg('^([0-9]{1,3}.){3,3}[0-9]{1,3}', $proxy_ip, $regs);
    
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
    

    /**
     * Purpose: The get_addresses function is used to retrieve a customer's
     * address(es). Using this, the customer is not required to enter any
     * information, only confirm the one presented to him/her.<br>
     *
     * The get_addresses function can also be used for companies.<br>
     * If the customer enters a company number, it will return all the
     * addresses where the company is registered at.<br>
     *
     * The get_addresses function is ONLY allowed to be used for Swedish
     * persons with the following conditions:
     * <ul>
     *     <li>
     *          It can be only used if invoice or part payment is
     *          the default payment method
     *     </li>
     *     <li>
     *          It has to disappear if the customer chooses another
     *          payment method
     *     </li>
     *     <li>
     *          The button is not allowed to be called "get address", but
     *          "continue" or<br>
     *          it can be picked up automatically when all the numbers have
     *          been typed.
     *     </li>
     * </ul>
     *
     * <b>Type can be one of these</b>:<br>
     * {@link BillmateFlags::GA_ALL},<br>
     * {@link BillmateFlags::GA_LAST},<br>
     * {@link BillmateFlags::GA_GIVEN}.<br>
     *
     * @param string $pno      Social security number, personal number, ...
     * @param int    $encoding {@link BillmateEncoding PNO Encoding} constant.
     * @param int    $type     Specifies returned information.
     *
     * @link http://integration.billmate.com/en/api/standard-integration/functions
     *       /getaddresses
     * @throws BillmateException
     * @return array   An array of {@link BillmateAddr} objects.
     */
    public function getAddresses(
        $pno, $encoding = null, $type = BillmateFlags::GA_GIVEN
    ) {
        if ($this->_country !== BillmateCountry::SE) {
            throw new Billmate_UnsupportedMarketException("Sweden");
        }

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);

        $digestSecret = self::digest(
            $this->colon(
                $this->_eid, $pno, $this->_secret
            )
        );

        $paramList = array(
            $pno,
            $this->_eid,
            $digestSecret,
            $encoding,
            $type,
            $this->getClientIP()
        );

        self::printDebug("get_addresses array", $paramList);

        $result = $this->xmlrpc_call('get_addresses', $paramList);

        self::printDebug("get_addresses result array", $result);

        $addrs = array();
        foreach ($result as $tmpAddr) {
            try {
                $addr = new BillmateAddr();
                if ($type === BillmateFlags::GA_GIVEN) {
                    $addr->isCompany = empty($tmpAddr[0]);
                    if ($addr->isCompany) {
                        $addr->setCompanyName($tmpAddr[1]);
                        $addr->setStreet($tmpAddr[2]);
                        $addr->setZipCode($tmpAddr[3]);
                        $addr->setCity($tmpAddr[4]);
                        $addr->setCountry($tmpAddr[5]);
                    } else {
                        $addr->setFirstName($tmpAddr[0]);
                        $addr->setLastName($tmpAddr[1]);
                        $addr->setStreet($tmpAddr[2]);
                        $addr->setZipCode($tmpAddr[3]);
                        $addr->setCity($tmpAddr[4]);
                        $addr->setCountry($tmpAddr[5]);
                    }
                } else if ($type === BillmateFlags::GA_LAST) {
                    // Here we cannot decide if it is a company or not?
                    // Assume private person.
                    $addr->setLastName($tmpAddr[1]);
                    $addr->setStreet($tmpAddr[2]);
                    $addr->setZipCode($tmpAddr[3]);
                    $addr->setCity($tmpAddr[4]);
                    $addr->setCountry($tmpAddr[5]);
                } else if ($type === BillmateFlags::GA_ALL) {
                    if (strlen($tmpAddr[0]) > 0) {
                        $addr->setFirstName($tmpAddr[0]);
                        $addr->setLastName($tmpAddr[1]);
                    } else {
                        $addr->isCompany = true;
                        $addr->setCompanyName($tmpAddr[1]);
                    }
                    $addr->setStreet($tmpAddr[2]);
                    $addr->setZipCode($tmpAddr[3]);
                    $addr->setCity($tmpAddr[4]);
                    $addr->setCountry($tmpAddr[5]);
                } else {
                    continue;
                }
                $addrs[] = $addr;
            } catch(Exception $e) {
                //Silently fail
            }
        }

        return $addrs;
    }

    /**
     * Adds an article to the current goods list for the current order.
     *
     * <b>Note</b>:<br>
     * It is recommended that you use {@link BillmateFlags::INC_VAT}.<br>
     *
     * <b>Flags can be</b>:<br>
     * {@link BillmateFlags::INC_VAT}<br>
     * {@link BillmateFlags::IS_SHIPMENT}<br>
     * {@link BillmateFlags::IS_HANDLING}<br>
     * {@link BillmateFlags::PRINT_1000}<br>
     * {@link BillmateFlags::PRINT_100}<br>
     * {@link BillmateFlags::PRINT_10}<br>
     * {@link BillmateFlags::NO_FLAG}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * @param int    $qty      Quantity.
     * @param string $artNo    Article number.
     * @param string $title    Article title.
     * @param int    $price    Article price.
     * @param float  $vat      VAT in percent, e.g. 25% is inputted as 25.
     * @param float  $discount Possible discount on article.
     * @param int    $flags    Options which specify the article
     *                         ({@link BillmateFlags::IS_HANDLING}) and it's price
     *                         ({@link BillmateFlags::INC_VAT})
     *
     * @see Billmate::addTransaction()
     * @see Billmate::reserveAmount()
     * @see Billmate::activateReservation()
     *
     * @throws BillmateException
     * @return void
     */
    public function addArticle(
        $qty, $artNo, $title, $price, $vat, $discount = 0,
        $flags = BillmateFlags::INC_VAT
    ) {
        $this->_checkQty($qty);

        // Either artno or title has to be set
        if ((($artNo === null ) || ($artNo == ""))
            && (($title === null ) || ($title == ""))
        ) {
            throw new Billmate_ArgumentNotSetException('Title and ArtNo', 50026);
        }

        $this->_checkPrice($price);
        $this->_checkVAT($vat);
        $this->_checkDiscount($discount);
        $this->_checkInt($flags, 'flags');

        //Create goodsList array if not set.
        if (!$this->goodsList || !is_array($this->goodsList)) {
            $this->goodsList = array();
        }

        //Populate a temp array with the article details.
        $tmpArr = array(
            "artno" => $artNo,
            "title" => $title,
            "price" => $price,
            "vat" => $vat,
            "discount" => $discount,
            "flags" => $flags
        );

        //Add the temp array and quantity field to the internal goods list.
        $this->goodsList[] = array(
                "goods" => $tmpArr,
                "qty"   => $qty
        );

        if (count($this->goodsList) > 0) {
            self::printDebug(
                "article added",
                $this->goodsList[count($this->goodsList)-1]
            );
        }
    }

    /**
     * Assembles and sends the current order to Billmate.<br>
     * This clears all relevant data if $clear is set to true.<br>
     *
     * <b>This method returns an array with</b>:<br>
     * Invoice number<br>
     * Order status flag<br>
     *
     * If the flag {@link BillmateFlags::RETURN_OCR} is used:<br>
     * Invoice number<br>
     * OCR number <br>
     * Order status flag<br>
     *
     * <b>Order status can be</b>:<br>
     * {@link BillmateFlags::ACCEPTED}<br>
     * {@link BillmateFlags::PENDING}<br>
     * {@link BillmateFlags::DENIED}<br>
     *
     * Gender is only required for Germany and Netherlands.<br>
     *
     * <b>Flags can be</b>:<br>
     * {@link BillmateFlags::NO_FLAG}<br>
     * {@link BillmateFlags::TEST_MODE}<br>
     * {@link BillmateFlags::AUTO_ACTIVATE}<br>
     * {@link BillmateFlags::PRE_PAY}<br>
     * {@link BillmateFlags::SENSITIVE_ORDER}<br>
     * {@link BillmateFlags::RETURN_OCR}<br>
     * {@link BillmateFlags::M_PHONE_TRANSACTION}<br>
     * {@link BillmateFlags::M_SEND_PHONE_PIN}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * <b>Note</b>:<br>
     * Normal shipment type is assumed unless otherwise specified,
     * ou can do this by calling:<br>
     * {@link Billmate::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either:<br>
     * {@link BillmateFlags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link BillmateFlags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}<br>
     *
     * @param string $pno      Personal number, SSN, date of birth, etc.
     * @param int    $gender   {@link BillmateFlags::FEMALE} or
     *                         {@link BillmateFlags::MALE},
     *                         null or "" for unspecified.
     * @param int    $flags    Options which affect the behaviour.
     * @param int    $pclass   PClass id used for this invoice.
     * @param int    $encoding {@link BillmateEncoding Encoding} constant for the
     *                         PNO parameter.
     * @param bool   $clear    Whether customer info should be cleared after
     *                         this call or not.
     *
     * @link http://integration.billmate.com/en/api/standard-integration/functions/
     *       addtransaction
     *
     * @throws BillmateException
     * @return array An array with invoice number and order status. [string, int]
     */
    public function addTransaction(
        $pno, $gender, $flags = BillmateFlags::NO_FLAG,
        $pclass = BillmatePClass::INVOICE, $encoding = null, $clear = truex
    ) {
        $this->_checkLocale(50023);

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        if (!($flags & BillmateFlags::PRE_PAY)) {
            $this->_checkPNO($pno, $encoding);
        }

        if ($gender === 'm') {
            $gender = BillmateFlags::MALE;
        } else if ($gender === 'f') {
            $gender = BillmateFlags::FEMALE;
        }

        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        $this->_checkInt($flags,  'flags');
        $this->_checkInt($pclass, 'pclass');

        //Check so required information is set.
        $this->_checkGoodslist();

        //We need at least one address set
        if (!($this->billing instanceof BillmateAddr)
            && !($this->shipping instanceof BillmateAddr)
        ) {
            throw new Billmate_MissingAddressException;
        }

        //If only one address is set, copy to the other address.
        if (!($this->shipping instanceof BillmateAddr)
            && ($this->billing instanceof BillmateAddr)
        ) {
            $this->shipping = $this->billing;
        } else if (!($this->billing instanceof BillmateAddr)
            && ($this->shipping instanceof BillmateAddr)
        ) {
            $this->billing = $this->shipping;
        }

        //Assume normal shipment unless otherwise specified.
        if (!isset($this->shipInfo['delay_adjust'])) {
            $this->setShipmentInfo('delay_adjust', BillmateFlags::NORMAL_SHIPMENT);
        }

        //Make sure we get any session ID's or similar
        $this->initCheckout();

        //function add_transaction_digest
        $string = "";
        foreach ($this->goodsList as $goods) {
            $string .= $goods['goods']['title'] .':';
        }
        $digestSecret = self::digest($string . $this->_secret);
        //end function add_transaction_digest

        $billing = $this->assembleAddr($this->billing);
        $shipping = $this->assembleAddr($this->shipping);

        //Shipping country must match specified country!
        if (strlen($shipping['country']) > 0
            && ($shipping['country'] !== $this->_country)
        ) {
            throw new Billmate_ShippingCountryException;
        }

        $paramList = array(
            $pno,
            $gender,
            $this->reference,
            $this->reference_code,
            $this->orderid[0],
            $this->orderid[1],
            $shipping,
            $billing,
            $this->getClientIP(),
            $flags,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding,
            $pclass,
            $this->goodsList,
            $this->comment,
            $this->shipInfo,
            $this->travelInfo,
            $this->incomeInfo,
            $this->bankInfo,
            $this->sid,
            $this->extraInfo
        );

        self::printDebug('add_invoice', $paramList);

        $result = $this->xmlrpc_call('add_invoice', $paramList);

        if ($clear === true) {
            //Make sure any stored values that need to be unique between
            //purchases are cleared.
            foreach ($this->coObjects as $co) {
                $co->clear();
            }
            $this->clear();
        }

        self::printDebug('add_invoice result', $result);

        return $result;
    }


    /**
     * Activates previously created invoice
     * (from {@link Billmate::addTransaction()}).
     *
     * <b>Note</b>:<br>
     * If you want to change the shipment type, you can specify it using:
     * {@link Billmate::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link BillmateFlags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link BillmateFlags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}
     *
     * @param string $invNo  Invoice number.
     * @param int    $pclass PClass id used for this invoice.
     * @param bool   $clear  Whether customer info should be cleared after this
     *                       call.
     *
     * @see Billmate::setShipmentInfo()
     * @link http://integration.billmate.com/en/api/standard-integration/functions
     *       /activateinvoice
     *
     * @throws BillmateException
     * @return string  An URL to the PDF invoice.
     */
    public function activateInvoice(
        $invNo, $pclass = BillmatePClass::INVOICE, $clear = true
    ) {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
            $pclass,
            $this->shipInfo
        );

        self::printDebug('activate_invoice', $paramList);

        $result = $this->xmlrpc_call('activate_invoice', $paramList);

        if ($clear === true) {
            $this->clear();
        }

        self::printDebug('activate_invoice result', $result);

        return $result;
    }

    /**
     * Removes a passive invoices which has previously been created with
     * {@link Billmate::addTransaction()}.
     * True is returned if the invoice was successfully removed, otherwise an
     * exception is thrown.<br>
     *
     * @param string $invNo Invoice number.
     *
     * @throws BillmateException
     * @return bool
     */
    public function deleteInvoice($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret
        );

        self::printDebug('delete_invoice', $paramList);

        $result = $this->xmlrpc_call('delete_invoice', $paramList);

        return ($result == 'ok') ? true : false;
    }

    /**
     * Summarizes the prices of the held goods list
     *
     * @return int total amount
     */
    public function summarizeGoodsList()
    {
        $amount = 0;
        if (!is_array($this->goodsList)) {
            return $amount;
        }
        foreach ($this->goodsList as $goods) {
            $price = $goods['goods']['price'];

            // Add VAT if price is Excluding VAT
            if (($goods['goods']['flags'] & BillmateFlags::INC_VAT) === 0) {
                $vat = $goods['goods']['vat'] / 100.0;
                $price *= (1.0 + $vat);
            }

            // Reduce discounts
            if ($goods['goods']['discount'] > 0) {
                $discount = $goods['goods']['discount'] / 100.0;
                $price *= (1.0 - $discount);
            }

            $amount += $price * (int)$goods['qty'];
        }
        return $amount;
    }

    /**
     * Reserves a purchase amount for a specific customer. <br>
     * The reservation is valid, by default, for 7 days.<br>
     *
     * <b>This method returns an array with</b>:<br>
     * A reservation number (rno)<br>
     * Order status flag<br>
     *
     * <b>Order status can be</b>:<br>
     * {@link BillmateFlags::ACCEPTED}<br>
     * {@link BillmateFlags::PENDING}<br>
     * {@link BillmateFlags::DENIED}<br>
     *
     * <b>Please note</b>:<br>
     * Activation must be done with activate_reservation, i.e. you cannot
     * activate through Billmate Online.
     *
     * Gender is only required for Germany and Netherlands.<br>
     *
     * <b>Flags can be set to</b>:<br>
     * {@link BillmateFlags::NO_FLAG}<br>
     * {@link BillmateFlags::TEST_MODE}<br>
     * {@link BillmateFlags::RSRV_SENSITIVE_ORDER}<br>
     * {@link BillmateFlags::RSRV_PHONE_TRANSACTION}<br>
     * {@link BillmateFlags::RSRV_SEND_PHONE_PIN}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * <b>Note</b>:<br>
     * Normal shipment type is assumed unless otherwise specified, you can do
     * this by calling:<br>
     * {@link Billmate::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link BillmateFlags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link BillmateFlags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}<br>
     *
     * @param string $pno      Personal number, SSN, date of birth, etc.
     * @param int    $gender   {@link BillmateFlags::FEMALE} or
     *                         {@link BillmateFlags::MALE}, null for unspecified.
     * @param int    $amount   Amount to be reserved, including VAT.
     * @param int    $flags    Options which affect the behaviour.
     * @param int    $pclass   {@link BillmatePClass::getId() PClass ID}.
     * @param int    $encoding {@link BillmateEncoding PNO Encoding} constant.
     * @param bool   $clear    Whether customer info should be cleared after
     *                         this call.
     *
     * @link http://integration.billmate.com/en/api/advanced-integration
     *       /functions/reserveamount
     *
     * @throws BillmateException
     * @return array An array with reservation number and order
     *               status. [string, int]
     */
    public function reserveAmount(
        $pno, $gender, $amount, $flags = 0, $pclass = BillmatePClass::INVOICE,
        $encoding = null, $clear = true
    ) {
        $this->_checkLocale();

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);

        if ($gender === 'm') {
            $gender = BillmateFlags::MALE;
        } else if ($gender === 'f') {
            $gender = BillmateFlags::FEMALE;
        }
        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        $this->_checkInt($flags,  'flags');
        $this->_checkInt($pclass, 'pclass');

        //Check so required information is set.
        $this->_checkGoodslist();


        //Calculate automatically the amount from goodsList.
        if ($amount === -1) {
            $amount = (int)round($this->summarizeGoodsList());
        } else {
            $this->_checkAmount($amount);
        }

        if ($amount <= 0) {
            throw new Billmate_InvalidPriceException($amount);
        }

        //No addresses used for phone transactions
        if ($flags & BillmateFlags::RSRV_PHONE_TRANSACTION) {
            $billing = $shipping = '';
        } else {
            $billing = $this->assembleAddr($this->billing);
            $shipping = $this->assembleAddr($this->shipping);

            if (strlen($shipping['country']) > 0
                && ($shipping['country'] !== $this->_country)
            ) {
                throw new Billmate_ShippingCountryException;
            }
        }

        //Assume normal shipment unless otherwise specified.
        if (!isset($this->shipInfo['delay_adjust'])) {
            $this->setShipmentInfo('delay_adjust', BillmateFlags::NORMAL_SHIPMENT);
        }

        //Make sure we get any session ID's or similar
        $this->initCheckout($this, $this->_eid);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $pno, $amount, $this->_secret)
        );

        $paramList = array(
            $pno,
            $gender,
            $amount,
            $this->reference,
            $this->reference_code,
            $this->orderid[0],
            $this->orderid[1],
            $shipping,
            $billing,
            $this->getClientIP(),
            $flags,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding, $pclass,
            $this->goodsList,
            $this->comment,
            $this->shipInfo,
            $this->travelInfo,
            $this->incomeInfo,
            $this->bankInfo,
            $this->sid,
            $this->extraInfo
        );

        self::printDebug('reserve_amount', $paramList);

        $result = $this->xmlrpc_call('reserve_amount', $paramList);

        if ($clear === true) {
            //Make sure any stored values that need to be unique between
            //purchases are cleared.
            foreach ($this->coObjects as $co) {
                $co->clear();
            }
            $this->clear();
        }

        self::printDebug('reserve_amount result', $result);

        return $result;
    }

    /**
     * Cancels a reservation.
     *
     * @param string $rno Reservation number.
     *
     * @link http://integration.billmate.com/en/api/advanced-integration/functions
     *       /cancelreservation
     *
     * @throws BillmateException
     * @return bool True, if the cancellation was successful.
     */
    public function cancelReservation($rno)
    {
        $this->_checkRNO($rno);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $rno, $this->_secret)
        );
        $paramList = array(
            $rno,
            $this->_eid,
            $digestSecret
        );

        self::printDebug('cancel_reservation', $paramList);

        $result = $this->xmlrpc_call('cancel_reservation', $paramList);

        return ($result == 'ok');
    }

    /**
     * Changes specified reservation to a new amount.
     *
     * <b>Flags can be either of these</b>:<br>
     * {@link BillmateFlags::NEW_AMOUNT}<br>
     * {@link BillmateFlags::ADD_AMOUNT}<br>
     *
     * @param string $rno    Reservation number.
     * @param int    $amount Amount including VAT.
     * @param int    $flags  Options which affect the behaviour.
     *
     * @link http://integration.billmate.com/en/api/advanced-integration/functions
     *       /changereservation
     *
     * @throws BillmateException
     * @return bool    True, if the change was successful.
     */
    public function changeReservation(
        $rno, $amount, $flags = BillmateFlags::NEW_AMOUNT
    ) {
        $this->_checkRNO($rno);
        $this->_checkAmount($amount);
        $this->_checkInt($flags, 'flags');

        $digestSecret = self::digest(
            $this->colon($this->_eid, $rno, $amount, $this->_secret)
        );
        $paramList = array(
            $rno,
            $amount,
            $this->_eid,
            $digestSecret,
            $flags
        );

        self::printDebug('change_reservation', $paramList);

        $result = $this->xmlrpc_call('change_reservation', $paramList);

        return ($result  == 'ok') ? true : false;
    }


    /**
     * Activates a previously created reservation.
     *
     * <b>This method returns an array with</b>:<br>
     * Risk status ("no_risk", "ok")<br>
     * Invoice number<br>
     *
     * Gender is only required for Germany and Netherlands.<br>
     *
     * Use of the OCR parameter is optional.
     * An OCR number can be retrieved by using:
     * {@link Billmate::reserveOCR()} or {@link Billmate::reserveOCRemail()}.
     *
     * <b>Flags can be set to</b>:<br>
     * {@link BillmateFlags::NO_FLAG}<br>
     * {@link BillmateFlags::TEST_MODE}<br>
     * {@link BillmateFlags::RSRV_SEND_BY_MAIL}<br>
     * {@link BillmateFlags::RSRV_SEND_BY_EMAIL}<br>
     * {@link BillmateFlags::RSRV_PRESERVE_RESERVATION}<br>
     * {@link BillmateFlags::RSRV_SENSITIVE_ORDER}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * <b>Note</b>:<br>
     * Normal shipment type is assumed unless otherwise specified, you can
     * do this by calling:
     * {@link Billmate::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link BillmateFlags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link BillmateFlags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}<br>
     *
     * @param string $pno      Personal number, SSN, date of birth, etc.
     * @param string $rno      Reservation number.
     * @param int    $gender   {@link BillmateFlags::FEMALE} or
     *                         {@link BillmateFlags::MALE}, null for unspecified.
     * @param string $ocr      A OCR number.
     * @param int    $flags    Options which affect the behaviour.
     * @param int    $pclass   {@link BillmatePClass::getId() PClass ID}.
     * @param int    $encoding {@link BillmateEncoding PNO Encoding} constant.
     * @param bool   $clear    Whether customer info should be cleared after
     *                         this call.
     *
     * @link http://integration.billmate.com/en/api/advanced-integration/functions
     *       /activatereservation
     * @see Billmate::reserveAmount()
     *
     * @throws BillmateException
     * @return array An array with risk status and invoice number [string, string].
     */
    public function activateReservation(
        $pno, $rno, $gender, $ocr = "", $flags = BillmateFlags::NO_FLAG,
        $pclass = BillmatePClass::INVOICE, $encoding = null, $clear = true
    ) {
        $this->_checkLocale();

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);
        $this->_checkRNO($rno);

        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        $this->_checkOCR($ocr);
        $this->_checkRef($this->reference, $this->reference_code);

        $this->_checkGoodslist();

        //No addresses used for phone transactions
        $billing = $shipping = '';
        if ( !($flags & BillmateFlags::RSRV_PHONE_TRANSACTION) ) {
            $billing = $this->assembleAddr($this->billing);
            $shipping = $this->assembleAddr($this->shipping);

            if (strlen($shipping['country']) > 0
                && ($shipping['country'] !== $this->_country)
            ) {
                throw new Billmate_ShippingCountryException;
            }
        }

        //activate digest
        $string = $this->_eid . ":" . $pno . ":";
        foreach ($this->goodsList as $goods) {
            $string .= $goods["goods"]["artno"] . ":" . $goods["qty"] . ":";
        }
        $digestSecret = self::digest($string . $this->_secret);
        //end digest

        //Assume normal shipment unless otherwise specified.
        if (!isset($this->shipInfo['delay_adjust'])) {
            $this->setShipmentInfo('delay_adjust', BillmateFlags::NORMAL_SHIPMENT);
        }

        $paramList = array(
            $rno,
            $ocr,
            $pno,
            $gender,
            $this->reference,
            $this->reference_code,
            $this->orderid[0],
            $this->orderid[1],
            $shipping,
            $billing,
            "0.0.0.0",
            $flags,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding,
            $pclass,
            $this->goodsList,
            $this->comment,
            $this->shipInfo,
            $this->travelInfo,
            $this->incomeInfo,
            $this->bankInfo,
            $this->extraInfo
        );

        self::printDebug('activate_reservation', $paramList);

        $result = $this->xmlrpc_call('activate_reservation', $paramList);

        if ($clear === true) {
            $this->clear();
        }

        self::printDebug('activate_reservation result', $result);

        return $result;
    }


    /**
     * Splits a reservation due to for example outstanding articles.
     *
     * <b>For flags usage see</b>:<br>
     * {@link Billmate::reserveAmount()}<br>
     *
     * @param string $rno    Reservation number.
     * @param int    $amount The amount to be subtracted from the reservation.
     * @param int    $flags  Options which affect the behaviour.
     *
     * @link http://integration.billmate.com/en/api/advanced-integration/functions
     *       /splitreservation
     *
     * @throws BillmateException
     * @return string A new reservation number.
     */
    public function splitReservation(
        $rno, $amount, $flags = BillmateFlags::NO_FLAG
    ) {
        //Check so required information is set.
        $this->_checkRNO($rno);
        $this->_checkAmount($amount);

        if ($amount <= 0) {
            throw new Billmate_InvalidPriceException($amount);
        }

        $digestSecret = self::digest(
            $this->colon($this->_eid, $rno, $amount, $this->_secret)
        );
        $paramList = array(
            $rno,
            $amount,
            $this->orderid[0],
            $this->orderid[1],
            $flags,
            $this->_eid,
            $digestSecret
        );

        self::printDebug('split_reservation array', $paramList);

        $result = $this->xmlrpc_call('split_reservation', $paramList);

        self::printDebug('split_reservation result', $result);

        return $result;
    }

    /**
     * Reserves a specified number of OCR numbers.<br>
     * For the specified country or the {@link Billmate::setCountry() set country}.<br>
     *
     * @param int $no      The number of OCR numbers to reserve.
     * @param int $country {@link BillmateCountry} constant.
     *
     * @link http://integration.billmate.com/en/api/advanced-integration/functions
     *       /reserveocrnums
     *
     * @throws BillmateException
     * @return array An array of OCR numbers.
     */
    public function reserveOCR($no, $country = null)
    {
        $this->_checkNo($no);
        if ($country === null) {
            if (!$this->_country) {
                throw new Billmate_MissingCountryException;
            }
            $country = $this->_country;
        } else {
            $this->_checkCountry($country);
        }

        $digestSecret = self::digest(
            $this->colon($this->_eid, $no, $this->_secret)
        );
        $paramList = array(
            $no,
            $this->_eid,
            $digestSecret,
            $country
        );

        self::printDebug('reserve_ocr_nums array', $paramList);

        return $this->xmlrpc_call('reserve_ocr_nums', $paramList);
    }

    /**
     * Reserves the number of OCRs specified and sends them to the given email.
     *
     * @param int    $no      Number of OCR numbers to reserve.
     * @param string $email   address.
     * @param int    $country {@link BillmateCountry} constant.
     *
     * @return bool True, if the OCRs were reserved and sent.
     */
    public function reserveOCRemail($no, $email, $country = null)
    {
        $this->_checkNo($no);
        $this->_checkPNO($email, BillmateEncoding::EMAIL);

        if ($country === null) {
            if (!$this->_country) {
                throw new Billmate_MissingCountryException;
            }
            $country = $this->_country;
        } else {
            $this->_checkCountry($country);
        }

        $digestSecret = self::digest(
            $this->colon($this->_eid, $no, $this->_secret)
        );
        $paramList = array(
            $no,
            $email,
            $this->_eid,
            $digestSecret,
            $country
        );

        self::printDebug('reserve_ocr_nums_email array', $paramList);

        $result = $this->xmlrpc_call('reserve_ocr_nums_email', $paramList);

        return ($result == 'ok');
    }

    /**
     * Checks if the specified SSN/PNO has an part payment account with Billmate.
     *
     * @param string $pno      Social security number, Personal number, ...
     * @param int    $encoding {@link BillmateEncoding PNO Encoding} constant.
     *
     * @link http://integration.billmate.com/en/api/standard-integration/functions
     *       /hasaccount
     *
     * @throws BillmateException
     * @return bool    True, if customer has an account.
     */
    public function hasAccount($pno, $encoding = null)
    {
        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);

        $digest = self::digest(
            $this->colon($this->_eid, $pno, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $pno,
            $digest,
            $encoding
        );

        self::printDebug('has_account', $paramList);

        $result = $this->xmlrpc_call('has_account', $paramList);

        return ($result === 'true');
    }

    /**
     * Adds an article number and quantity to be used in
     * {@link Billmate::activatePart()}, {@link Billmate::creditPart()}
     * and {@link Billmate::invoicePartAmount()}.
     *
     * @param int    $qty   Quantity of specified article.
     * @param string $artNo Article number.
     *
     * @link http://integration.billmate.com/en/api/invoice-handling-functions/
     *       functions/mkartno
     *
     * @throws BillmateException
     * @return void
     */
    public function addArtNo($qty, $artNo)
    {
        $this->_checkQty($qty);
        $this->_checkArtNo($artNo);

        if (!is_array($this->artNos)) {
            $this->artNos = array();
        }

        $this->artNos[] = array('artno' => $artNo, 'qty' => $qty);
    }

    /**
     * Partially activates a passive invoice.
     *
     * Returned array contains index "url" and "invno".<br>
     * The value of "url" is a URL pointing to a temporary PDF-version of the
     * activated invoice.<br>
     * The value of "invno" is either 0 if the entire invoice was activated or
     * the number on the new passive invoice.<br>
     *
     * <b>Note</b>:<br>
     * You need to call {@link Billmate::addArtNo()} first, to specify which
     * articles and how many you want to partially activate.<br>
     * If you want to change the shipment type, you can specify it using:
     * {@link Billmate::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link BillmateFlags::NORMAL_SHIPMENT NORMAL_SHIPMENT}
     * or {@link BillmateFlags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}
     *
     * @param string $invNo  Invoice numbers.
     * @param int    $pclass PClass id used for this invoice.
     * @param bool   $clear  Whether customer info should be cleared after
     *                       this call.
     *
     * @see Billmate::addArtNo()
     * @see Billmate::activateInvoice()
     * @link http://integration.billmate.com/en/api/standard-integration/functions
     *       /activatepart
     *
     * @throws BillmateException
     * @return array An array with invoice URL and invoice number.
     *         ['url' => val, 'invno' => val]
     */
    public function activatePart(
        $invNo, $pclass = BillmatePClass::INVOICE, $clear = true
    ) {
        $this->_checkInvNo($invNo);
        $this->_checkArtNos($this->artNos);

        self::printDebug('activate_part artNos array', $this->artNos);

        //function activate_part_digest
        $string = $this->_eid . ":" . $invNo . ":";
        foreach ($this->artNos as $artNo) {
            $string .= $artNo["artno"] . ":". $artNo["qty"] . ":";
        }
        $digestSecret = self::digest($string . $this->_secret);
        //end activate_part_digest

        $paramList = array(
            $this->_eid,
            $invNo,
            $this->artNos,
            $digestSecret,
            $pclass,
            $this->shipInfo
        );

        self::printDebug('activate_part array', $paramList);

        $result = $this->xmlrpc_call('activate_part', $paramList);

        if ($clear === true) {
            $this->clear();
        }

        self::printDebug('activate_part result', $result);

        return $result;
    }

    /**
     * Retrieves the total amount for an active invoice.
     *
     * @param string $invNo Invoice number.
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /invoiceamount
     *
     * @throws BillmateException
     * @return float The total amount.
     */
    public function invoiceAmount($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret
        );

        self::printDebug('invoice_amount array', $paramList);

        $result = $this->xmlrpc_call('invoice_amount', $paramList);

        //Result is in cents, fix it.
        return ($result / 100);
    }

    /**
     * Changes the order number of a purchase that was set when the order was
     * made online.
     *
     * @param string $invNo   Invoice number.
     * @param string $orderid Estores order number.
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /updateorderno
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function updateOrderNo($invNo, $orderid)
    {
        $this->_checkInvNo($invNo);
        $this->_checkEstoreOrderNo($orderid);

        $digestSecret = self::digest(
            $this->colon($invNo, $orderid, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $orderid
        );

        self::printDebug('update_orderno array', $paramList);

        $result = $this->xmlrpc_call('update_orderno', $paramList);

        return $result;
    }

    /**
     * Sends an activated invoice to the customer via e-mail. <br>
     * The email is sent in plain text format and contains a link to a
     * PDF-invoice.<br>
     *
     * <b>Please note!</b><br>
     * Regular postal service is used if the customer has not entered his/her
     * e-mail address when making the purchase (charges may apply).<br>
     *
     * @param string $invNo Invoice number.
     *
     * @link http://integration.billmate.com/en/api/invoice-handling-functions
     *       /functions/emailinvoice
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function emailInvoice($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret
        );

        self::printDebug('email_invoice array', $paramList);

        return $this->xmlrpc_call('email_invoice', $paramList);
    }

    /**
     * Requests a postal send-out of an activated invoice to a customer by
     * Billmate (charges may apply).
     *
     * @param string $invNo Invoice number.
     *
     * @link http://integration.billmate.com/en/api/invoice-handling-functions
     *       /functions/sendinvoice
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function sendInvoice($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret
        );

        self::printDebug('send_invoice array', $paramList);

        return $this->xmlrpc_call('send_invoice', $paramList);
    }

    /**
     * Gives discounts on invoices.<br>
     * If you are using standard integration and the purchase is not yet
     * activated (you have not yet delivered the goods), <br>
     * just change the article list in our online interface Billmate Online.<br>
     *
     * <b>Flags can be</b>:<br>
     * {@link BillmateFlags::INC_VAT}<br>
     * {@link BillmateFlags::NO_FLAG}, <b>NOT RECOMMENDED!</b><br>
     *
     * @param string $invNo  Invoice number.
     * @param int    $amount The amount given as a discount.
     * @param float  $vat    VAT in percent, e.g. 22.2 for 22.2%.
     * @param int    $flags  If amount is {@link BillmateFlags::INC_VAT including}
     *                       or {@link BillmateFlags::NO_FLAG excluding} VAT.
     *
     * @link http://integration.billmate.com/en/api/invoice-handling-functions
     *       /functions/returnamount
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function returnAmount(
        $invNo, $amount, $vat, $flags = BillmateFlags::INC_VAT
    ) {
        $this->_checkInvNo($invNo);
        $this->_checkAmount($amount);
        $this->_checkVAT($vat);
        $this->_checkInt($flags, 'flags');

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $amount,
            $vat,
            $digestSecret,
            $flags
        );

        self::printDebug('return_amount', $paramList);

        return $this->xmlrpc_call('return_amount', $paramList);
    }

    /**
     * Performs a complete refund on an invoice, part payment and mobile
     * purchase.
     *
     * @param string $invNo  Invoice number.
     * @param string $credNo Credit number.
     *
     * @link http://integration.billmate.com/en/api/invoice-handling-functions
     *       /functions/creditinvoice
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function creditInvoice($invNo, $credNo = "")
    {
        $this->_checkInvNo($invNo);
        $this->_checkCredNo($credNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $credNo,
            $digestSecret
        );

        self::printDebug('credit_invoice', $paramList);

        return $this->xmlrpc_call('credit_invoice', $paramList);
    }

    /**
     * Performs a partial refund on an invoice, part payment or mobile purchase.
     *
     * <b>Note</b>:<br>
     * You need to call {@link Billmate::addArtNo()} first.<br>
     *
     * @param string $invNo  Invoice number.
     * @param string $credNo Credit number.
     *
     * @see  Billmate::addArtNo()
     * @link http://integration.billmate.com/en/api/invoice-handling-functions
     *       /functions/creditpart
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function creditPart($invNo, $credNo = "")
    {
        $this->_checkInvNo($invNo);
        $this->_checkCredNo($credNo);
        $this->_checkArtNos($this->artNos);

        //function activate_part_digest
        $string = $this->_eid . ":" . $invNo . ":";
        foreach ($this->artNos as $artNo) {
            $string .= $artNo["artno"] . ":". $artNo["qty"] . ":";
        }
        $digestSecret = self::digest($string . $this->_secret);
        //end activate_part_digest

        $paramList = array(
            $this->_eid,
            $invNo,
            $this->artNos,
            $credNo,
            $digestSecret
        );

        $this->artNos = array();

        self::printDebug('credit_part', $paramList);

        return $this->xmlrpc_call('credit_part', $paramList);
    }

    /**
     * Changes the quantity of a specific item in a passive invoice.
     *
     * @param string $invNo Invoice number.
     * @param string $artNo Article number.
     * @param int    $qty   Quantity of specified article.
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /updategoodsqty
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function updateGoodsQty($invNo, $artNo, $qty)
    {
        $this->_checkInvNo($invNo);
        $this->_checkQty($qty);
        $this->_checkArtNo($artNo);

        $digestSecret = self::digest(
            $this->colon($invNo, $artNo, $qty, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $artNo,
            $qty
        );

        self::printDebug('update_goods_qty', $paramList);

        return $this->xmlrpc_call('update_goods_qty', $paramList);
    }

    /**
     * Changes the amount of a fee (e.g. the invoice fee) in a passive invoice.
     *
     * <b>Type can be</b>:<br>
     * {@link BillmateFlags::IS_SHIPMENT}<br>
     * {@link BillmateFlags::IS_HANDLING}<br>
     *
     * @param string $invNo     Invoice number.
     * @param int    $type      Charge type.
     * @param int    $newAmount The new amount for the charge.
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /updatechargeamount
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function updateChargeAmount($invNo, $type, $newAmount)
    {
        $this->_checkInvNo($invNo);
        $this->_checkInt($type, 'type');
        $this->_checkAmount($newAmount);

        if ($type === BillmateFlags::IS_SHIPMENT) {
            $type = 1;
        } else if ($type === BillmateFlags::IS_HANDLING) {
            $type = 2;
        }

        $digestSecret = self::digest(
            $this->colon($invNo, $type, $newAmount, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $type,
            $newAmount
        );

        self::printDebug('update_charge_amount', $paramList);

        return $this->xmlrpc_call('update_charge_amount', $paramList);
    }

    /**
     * The invoice_address function is used to retrieve the address of a
     * purchase.
     *
     * @param string $invNo Invoice number.
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /invoiceaddress
     *
     * @throws BillmateException
     * @return BillmateAddr
     */
    public function invoiceAddress($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret
        );

        self::printDebug('invoice_address', $paramList);

        $result = $this->xmlrpc_call('invoice_address', $paramList);

        $addr = new BillmateAddr();
        if (strlen($result[0]) > 0) {
            $addr->isCompany = false;
            $addr->setFirstName($result[0]);
            $addr->setLastName($result[1]);
        } else {
            $addr->isCompany = true;
            $addr->setCompanyName($result[1]);
        }
        $addr->setStreet($result[2]);
        $addr->setZipCode($result[3]);
        $addr->setCity($result[4]);
        $addr->setCountry($result[5]);

        return $addr;
    }

    /**
     * Retrieves the amount of a specific goods from a purchase.
     *
     * <b>Note</b>:<br>
     * You need to call {@link Billmate::addArtNo()} first.<br>
     *
     * @param string $invNo Invoice number.
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /invoicepartamount
     * @see  Billmate::addArtNo()
     *
     * @throws BillmateException
     * @return float The amount of the goods.
     */
    public function invoicePartAmount($invNo)
    {
        $this->_checkInvNo($invNo);
        $this->_checkArtNos($this->artNos);

        //function activate_part_digest
        $string = $this->_eid . ":" . $invNo . ":";
        foreach ($this->artNos as $artNo) {
            $string .= $artNo["artno"] . ":". $artNo["qty"] . ":";
        }
        $digestSecret = self::digest($string . $this->_secret);
        //end activate_part_digest

        $paramList = array(
            $this->_eid,
            $invNo,
            $this->artNos,
            $digestSecret
        );
        $this->artNos = array();

        self::printDebug('invoice_part_amount', $paramList);

        $result = $this->xmlrpc_call('invoice_part_amount', $paramList);

        return ($result / 100);
    }

    /**
     * Returns the current order status for a specific reservation or invoice.
     * Use this when {@link Billmate::addTransaction()} or
     * {@link Billmate::reserveAmount()} returns a {@link BillmateFlags::PENDING}
     * status.
     *
     * <b>Order status can be</b>:<br>
     * {@link BillmateFlags::ACCEPTED}<br>
     * {@link BillmateFlags::PENDING}<br>
     * {@link BillmateFlags::DENIED}<br>
     *
     * @param string $id   Reservation number or invoice number.
     * @param int    $type 0 if $id is an invoice or reservation, 1 for order id
     *
     * @link http://integration.billmate.com/en/api/other-functions/functions
     *       /checkorderstatus
     *
     * @throws BillmateException
     * @return string  The order status.
     */
    public function checkOrderStatus($id, $type = 0)
    {
        $this->_checkArgument($id, "id");

        $this->_checkInt($type, 'type');
        if ($type !== 0 && $type !== 1) {
            throw new Billmate_InvalidTypeException(
                'type', "0 or 1"
            );
        }

        $digestSecret = self::digest(
            $this->colon($this->_eid, $id, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $digestSecret,
            $id,
            $type
        );

        self::printDebug('check_order_status', $paramList);

        return $this->xmlrpc_call('check_order_status', $paramList);
    }

    /**
     * Retrieves a list of all the customer numbers associated with the
     * specified pno.
     *
     * @param string $pno      Social security number, Personal number, ...
     * @param int    $encoding {@link BillmateEncoding PNO Encoding} constant.
     *
     * @throws BillmateException
     * @return array An array containing all customer numbers associated
     *               with that pno.
     */
    public function getCustomerNo($pno, $encoding = null)
    {
        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }
        $this->_checkPNO($pno, $encoding);

        $digestSecret = self::digest(
            $this->colon($this->_eid, $pno, $this->_secret)
        );
        $paramList = array(
            $pno,
            $this->_eid,
            $digestSecret,
            $encoding
        );

        self::printDebug('get_customer_no', $paramList);

        return $this->xmlrpc_call('get_customer_no', $paramList);
    }

    /**
     * Associates a pno with a customer number when you want to make future
     * purchases without a pno.
     *
     * @param string $pno      Social security number, Personal number, ...
     * @param string $custNo   The customer number.
     * @param int    $encoding {@link BillmateEncoding PNO Encoding} constant.
     *
     * @throws BillmateException
     * @return bool  True, if the customer number was associated with the pno.
     */
    public function setCustomerNo($pno, $custNo, $encoding = null)
    {
        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }
        $this->_checkPNO($pno, $encoding);

        $this->_checkArgument($custNo, 'custNo');

        $digestSecret = self::digest(
            $this->colon($this->_eid, $pno, $custNo, $this->_secret)
        );
        $paramList = array(
            $pno,
            $custNo,
            $this->_eid,
            $digestSecret,
            $encoding
        );

        self::printDebug('set_customer_no', $paramList);

        $result = $this->xmlrpc_call('set_customer_no', $paramList);

        return ($result == 'ok');
    }

    /**
     * Removes a customer number from association with a pno.
     *
     * @param string $custNo The customer number.
     *
     * @throws BillmateException
     * @return bool    True, if the customer number association was removed.
     */
    public function removeCustomerNo($custNo)
    {
        $this->_checkArgument($custNo, 'custNo');

        $digestSecret = self::digest(
            $this->colon($this->_eid, $custNo, $this->_secret)
        );

        $paramList = array(
            $custNo,
            $this->_eid,
            $digestSecret
        );

        self::printDebug('remove_customer_no', $paramList);

        $result = $this->xmlrpc_call('remove_customer_no', $paramList);

        return ($result == 'ok');
    }

    /**
     * Sets notes/log information for the specified invoice  number.
     *
     * @param string $invNo Invoice number.
     * @param string $notes Note(s) to be associated with the invoice.
     *
     * @throws BillmateException
     * @return string  Invoice number.
     */
    public function updateNotes($invNo, $notes)
    {
        $this->_checkInvNo($invNo);

        if (!is_string($notes)) {
            $notes = strval($notes);
        }

        $digestSecret = self::digest(
            $this->colon($invNo, $notes, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $notes
        );

        self::printDebug('update_notes', $paramList);

        return $this->xmlrpc_call('update_notes', $paramList);
    }

    /**
     * Returns the configured PCStorage object.
     *
     * @throws Exception|BillmateException
     * @return PCStorage
     */
    public function getPCStorage()
    {
        if (isset($this->pclasses)) {
            return $this->pclasses;
        }

        include_once 'pclasses/storage.intf.php';
        $className = $this->pcStorage.'storage';
        $pclassStorage = dirname(__FILE__) . "/pclasses/{$className}.class.php";

        include_once $pclassStorage;
        $storage = new $className;

        if (!($storage instanceof PCStorage)) {
            throw new Billmate_PCStorageInvalidException(
                $className, $pclassStorage
            );
        }
        return $storage;
    }

    /**
     * Fetch pclasses
     *
     * @param PCStorage $storage  PClass Storage
     * @param int       $country  BillmateCountry constant
     * @param int       $language BillmateLanguage constant
     * @param int       $currency BillmateCurrency constant
     *
     * @return void
     */
    private function _fetchPClasses($storage, $country, $language, $currency)
    {
        $digestSecret = self::digest(
            $this->colon($this->_eid, $currency, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $currency,
            $digestSecret,
            $country,
            $language
        );

        self::printDebug('get_pclasses array', $paramList);

        $result = $this->xmlrpc_call('get_pclasses', $paramList);

        self::printDebug('get_pclasses result', $result);


        foreach ($result as &$pclass) {
            //numeric htmlentities
            $pclass[1] = $pclass[1];

            //Below values are in "cents", fix them.
            $pclass[3] /= 100; //divide start fee with 100
            $pclass[4] /= 100; //divide invoice fee with 100
            $pclass[5] /= 100; //divide interest rate with 100
            $pclass[6] /= 100; //divide min amount with 100

            if ($pclass[9] != '-') {
                //unix timestamp instead of yyyy-mm-dd
                $pclass[9] = strtotime($pclass[9]);
            }

            //Associate the PClass with this estore.
            array_unshift($pclass, $this->_eid);

            $storage->addPClass(new BillmatePClass($pclass));
        }
    }

    /**
     * Fetches the PClasses from Billmate Online.<br>
     * Removes the cached/stored pclasses and updates.<br>
     * You are only allowed to call this once, or once per update of PClasses
     * in KO.<br>
     *
     * <b>Note</b>:<br>
     * If language and/or currency is null, then they will be set to mirror
     * the specified country.<br/>
     * Short codes like DE, SV or EUR can also be used instead of the constants.
     *
     * @param string|int $country  {@link BillmateCountry Country} constant,
     *                             or two letter code.
     * @param mixed      $language {@link BillmateLanguage Language} constant,
     *                             or two letter code.
     * @param mixed      $currency {@link BillmateCurrency Currency} constant,
     *                             or three letter code.
     *
     * @throws BillmateException
     * @return void
     */
    public function fetchPClasses(
        $country = null, $language = null, $currency = null
    ) {
        extract(
            $this->getLocale($country, $language, $currency),
            EXTR_OVERWRITE
        );

        if (!($this->config instanceof ArrayAccess)) {
            throw new Billmate_IncompleteConfigurationException;
        }

        $pclasses = $this->getPCStorage();
        try {
            //Attempt to load previously stored pclasses, so they aren't
            // accidentially removed.
            $pclasses->load($this->pcURI);
        }
        catch(Exception $e) {
            self::printDebug('load pclasses', $e->getMessage());
        }

        $this->_fetchPClasses($pclasses, $country, $language, $currency);

        $pclasses->save($this->pcURI);
        $this->pclasses = $pclasses;
    }

    /**
     * Removes the stored PClasses, if you need to update them.
     *
     * @throws BillmateException
     * @return void
     */
    public function clearPClasses()
    {
        if ($this->config instanceof ArrayAccess) {
            $pclasses = $this->getPCStorage();
            $pclasses->clear($this->pcURI);
        } else {
            throw new Billmate_IncompleteConfigurationException;
        }
    }

    /**
     * Retrieves the specified PClasses.
     *
     * <b>Type can be</b>:<br>
     * {@link BillmatePClass::CAMPAIGN}<br>
     * {@link BillmatePClass::ACCOUNT}<br>
     * {@link BillmatePClass::SPECIAL}<br>
     * {@link BillmatePClass::FIXED}<br>
     * {@link BillmatePClass::DELAY}<br>
     * {@link BillmatePClass::MOBILE}<br>
     *
     * @param int $type PClass type identifier.
     *
     * @throws BillmateException
     * @return array An array of PClasses. [BillmatePClass]
     */
    public function getPClasses($type = null)
    {
        if (!($this->config instanceof ArrayAccess)) {
            throw new Billmate_IncompleteConfigurationException;
        }
        if (!$this->pclasses) {
            $this->pclasses = $this->getPCStorage();
            $this->pclasses->load($this->pcURI);
        }
        $tmp = $this->pclasses->getPClasses(
            $this->_eid, $this->_country, $type
        );
        $this->sortPClasses($tmp[$this->_eid]);
        return $tmp[$this->_eid];
    }

    /**
     * Retrieve a flattened array of all pclasses stored in the configured
     * pclass storage.
     *
     * @return array
     */
    public function getAllPClasses()
    {
        if (!$this->pclasses) {
            $this->pclasses = $this->getPCStorage();
            $this->pclasses->load($this->pcURI);
        }
        return $this->pclasses->getAllPClasses();
    }

    /**
     * Returns the specified PClass.
     *
     * @param int $id The PClass ID.
     *
     * @return BillmatePClass
     */
    public function getPClass($id)
    {
        if (!is_numeric($id)) {
            throw new Billmate_InvalidTypeException('id', 'integer');
        }

        if (!($this->config instanceof ArrayAccess)) {
            throw new Billmate_IncompleteConfigurationException;
        }

        if (!$this->pclasses || !($this->pclasses instanceof PCStorage)) {
            $this->pclasses = $this->getPCStorage();
            $this->pclasses->load($this->pcURI);
        }
        return $this->pclasses->getPClass(
            intval($id), $this->_eid, $this->_country
        );
    }

    /**
     * Sorts the specified array of BillmatePClasses.
     *
     * @param array &$array An array of {@link BillmatePClass PClasses}.
     *
     * @return void
     */
    public function sortPClasses(&$array)
    {
        if (!is_array($array)) {
            //Input is not an array!
            $array = array();
            return;
        }
        //Sort pclasses array after natural sort (natcmp)
        if (!function_exists('pcCmp')) {
            /**
             * Comparison function
             *
             * @param BillmatePClass $a object 1
             * @param BillmatePClass $b object 2
             *
             * @return int
             */
            function pcCmp($a, $b)
            {
                if ($a->getDescription() == null
                    && $b->getDescription() == null
                ) {
                    return 0;
                } else if ($a->getDescription() == null) {
                    return 1;
                } else if ($b->getDescription() == null) {
                    return -1;
                } else if ($b->getType() === 2 && $a->getType() !== 2) {
                    return 1;
                } else if ($b->getType() !== 2 && $a->getType() === 2) {
                    return -1;
                }

                return strnatcmp($a->getDescription(), $b->getDescription())*-1;
            }
        }
        usort($array, "pcCmp");
    }

    /**
     * Returns the cheapest, per month, PClass related to the specified sum.
     *
     * <b>Note</b>: This choose the cheapest PClass for the current country.<br>
     * {@link Billmate::setCountry()}
     *
     * <b>Flags can be</b>:<br>
     * {@link BillmateFlags::CHECKOUT_PAGE}<br>
     * {@link BillmateFlags::PRODUCT_PAGE}<br>
     *
     * @param float $sum   The product cost, or total sum of the cart.
     * @param int   $flags Which type of page the info will be displayed on.
     *
     * @throws BillmateException
     * @return BillmatePClass or false if none was found.
     */
    public function getCheapestPClass($sum, $flags)
    {
        if (!is_numeric($sum)) {
            throw new Billmate_InvalidPriceException($sum);
        }

        if (!is_numeric($flags)
            || !in_array(
                $flags, array(
                    BillmateFlags::CHECKOUT_PAGE, BillmateFlags::PRODUCT_PAGE)
            )
        ) {
            throw new Billmate_InvalidTypeException(
                'flags',
                BillmateFlags::CHECKOUT_PAGE . ' or ' . BillmateFlags::PRODUCT_PAGE
            );
        }

        $lowest_pp = $lowest = false;

        foreach ($this->getPClasses() as $pclass) {
            $lowest_payment = BillmateCalc::get_lowest_payment_for_account(
                $pclass->getCountry()
            );
            if ($pclass->getType() < 2 && $sum >= $pclass->getMinAmount()) {
                $minpay = BillmateCalc::calc_monthly_cost(
                    $sum, $pclass, $flags
                );

                if ($minpay < $lowest_pp || $lowest_pp === false) {
                    if ($pclass->getType() == BillmatePClass::ACCOUNT
                        || $minpay >= $lowest_payment
                    ) {
                        $lowest_pp = $minpay;
                        $lowest = $pclass;
                    }
                }
            }
        }

        return $lowest;
    }

    /**
     * Initializes the checkoutHTML objects.
     *
     * @see Billmate::checkoutHTML()
     * @return void
     */
    protected function initCheckout()
    {
        $dir = dirname(__FILE__);

        //Require the CheckoutHTML interface/abstract class
        include_once $dir.'/checkout/checkouthtml.intf.php';

        //Iterate over all .class.php files in checkout/
        foreach (glob($dir.'/checkout/*.class.php') as $checkout) {
            if (!self::$debug) {
                ob_start();
            }
            include_once $checkout;

            $className = basename($checkout, '.class.php');
            $cObj = new $className;

            if ($cObj instanceof CheckoutHTML) {
                $cObj->init($this, $this->_eid);
                $this->coObjects[$className] = $cObj;
            }

            if (!self::$debug) {
                ob_end_clean();
            }
        }
    }

    /**
     * Returns the checkout page HTML from the checkout classes.
     *
     * <b>Note</b>:<br>
     * This method uses output buffering to silence unwanted echoes.<br>
     *
     * @see CheckoutHTML
     *
     * @return string  A HTML string.
     */
    public function checkoutHTML()
    {
        if (empty($this->coObjects)) {
            $this->initCheckout();
        }
        $dir = dirname(__FILE__);

        //Require the CheckoutHTML interface/abstract class
        include_once $dir.'/checkout/checkouthtml.intf.php';

        //Iterate over all .class.php files in
        $html = "\n";
        foreach ($this->coObjects as $cObj) {
            if (!self::$debug) {
                ob_start();
            }
            if ($cObj instanceof CheckoutHTML) {
                $html .= $cObj->toHTML() . "\n";
            }
            if (!self::$debug) {
                ob_end_clean();
            }
        }

        return $html;
    }

    /**
     * Creates a XMLRPC call with specified XMLRPC method and parameters from array.
     *
     * @param string $method XMLRPC method.
     * @param array  $array  XMLRPC parameters.
     *
     * @throws BillmateException
     * @return mixed
     */
    protected function xmlrpc_call($method, $array)
    {
    	//billmate_log_data(array($method, $array), $this->_eid);
    	
        if (!($this->xmlrpc instanceof xmlrpc_client)) {
            throw new Billmate_IncompleteConfigurationException;
        }
        if (!isset($method) || !is_string($method)) {
            throw new Billmate_InvalidTypeException('method', 'string');
        }
        if ($array === null || count($array) === 0) {
            throw new BillmateException("Parameterlist is empty or null!", 50067);
        }
        if (self::$disableXMLRPC) {
            return true;
        }
        try {
           
            $this->xmlrpc->verifypeer = false;

            $timestart = microtime(true);

            //Create the XMLRPC message.
            $msg = new xmlrpcmsg($method);
            $params = array_merge(
                array(
                    $this->PROTO, $this->VERSION
                ), $array
            );

            $msg = new xmlrpcmsg($method);
            foreach ($params as $p) {
                if (!$msg->addParam(
                    php_xmlrpc_encode($p, array('extension_api'))
                )
                ) {
                    throw new BillmateException(
                        "Failed to add parameters to XMLRPC message.",
                        50068
                    );
                }
            }

            //Send the message.
            $selectDateTime = microtime(true);
            if (self::$xmlrpcDebug) {
                $this->xmlrpc->setDebug(2);
            }
            $xmlrpcresp = $this->xmlrpc->send($msg);

            //Calculate time and selectTime.
            $timeend = microtime(true);
            $time = (int) (($selectDateTime - $timestart) * 1000);
            $selectTime = (int) (($timeend - $timestart) * 1000);

            $status = $xmlrpcresp->faultCode();

            if ($status !== 0) {
				$this->stat($method,$array, utf8_encode($xmlrpcresp->faultString()), $selectTime, $status);
                throw new BillmateException($xmlrpcresp->faultString(), $status);
            }
			$result = php_xmlrpc_decode($xmlrpcresp->value());
			
			$this->stat($method,$array, $result, $selectTime, $status);

            return $result;
        }
        catch(BillmateException $e) {
            //Otherwise it is caught below, and rethrown.
            throw $e;
        }
        catch(Exception $e) {
            throw new BillmateException($e->getMessage(), $e->getCode());
        }
    }
	
    function stat($type,$data, $response="", $duration=0, $status=0) {
        $sock = @fsockopen('udp://'.$this->STAT, 51000, $errno, $errstr, 1500);
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
				"eid"=>$this->_eid,
				"client"=>$this->CLIENT,
				"uniqueId"=>$uniqueId
			);
			@fwrite($sock,json_encode($values));
			@fclose($sock);
        }
    }

    /**
     * Removes all relevant order/customer data from the internal structure.
     *
     * @return void
     */
    public function clear()
    {
        $this->goodsList = null;
        $this->comment = "";

        $this->billing = null;
        $this->shipping = null;

        $this->shipInfo = array();
        $this->extraInfo = array();
        $this->bankInfo = array();
        $this->incomeInfo = array();

        $this->reference = "";
        $this->reference_code = "";

        $this->orderid[0] = "";
        $this->orderid[1] = "";

        $this->artNos = array();
        $this->coObjects = array();
    }

    /**
     * Sends a report to Candice.
     *
     * @param string $method     XMLRPC method.
     * @param int    $time       Elapsed time of entire XMLRPC call.
     * @param int    $selectTime Time to create the XMLRPC parameters.
     * @param int    $status     XMLRPC error code.
     *
     * @return void
     */
    protected function sendStat($method, $time, $selectTime, $status)
    {
        $fp = @fsockopen('udp://'.self::$_c_addr, 80, $errno, $errstr, 1500);
        if ($fp) {
            $url = (($this->ssl) ? 'https://' : 'http://').$this->addr;
            $data = $this->pipe(
                $this->_eid,
                $method,
                $time,
                $selectTime,
                $status,
                $url.':'.$this->port
            );
            $digest = self::digest($this->pipe($data, $this->_secret));

            self::printDebug("candice report", $data);

            @fwrite($fp, $this->pipe($data, $digest));
            @fclose($fp);
        }
    }

    /**
     * Implodes parameters with delimiter ':'.
     *
     * @return string Colon separated string.
     */
    public static function colon(/* variable parameters */)
    {
        $args = func_get_args();
        return implode(':', $args);
    }

    /**
     * Implodes parameters with delimiter '|'.
     *
     * @return string Pipe separated string.
     */
    public static function pipe(/* variable parameters */)
    {
        $args = func_get_args();
        return implode('|', $args);
    }

    /**
     * Creates a digest hash from the inputted string,
     * and the specified or the preferred hash algorithm.
     *
     * @param string $data Data to be hashed.
     * @param string $hash hash algoritm to use
     *
     * @throws BillmateException
     * @return string  Base64 encoded hash.
     */
    public static function digest($data, $hash = null)
    {
        if ($hash===null) {
            $preferred = array(
                'sha512',
                'sha384',
                'sha256',
                'sha224',
                'md5'
            );

            $hashes = array_intersect($preferred, hash_algos());

            if (count($hashes) == 0) {
                throw new BillmateException(
                    "No available hash algorithm supported!"
                );
            }
            $hash = array_shift($hashes);
        }
        self::printDebug('digest() using hash', $hash);

        return base64_encode(pack("H*", hash($hash, $data)));
    }

    /**
     * Converts special characters to numeric htmlentities.
     *
     * <b>Note</b>:<br>
     * If supplied string is encoded with UTF-8, o umlaut ("ö") will become two
     * HTML entities instead of one.
     *
     * @param string $str String to be converted.
     *
     * @return string String converted to numeric HTML entities.
     */
    public static function num_htmlentities($str)
    {
        if (!self::$htmlentities) {
            self::$htmlentities = array();
            $table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
            foreach ($table as $char => $entity) {
                self::$htmlentities[$entity] = '&#' . ord($char) . ';';
            }
        }

        return str_replace(
            array_keys(
                self::$htmlentities
            ), self::$htmlentities, htmlentities($str)
        );
    }

    /**
     * Prints debug information if debug is set to true.
     * $msg is used as header/footer in the output.
     *
     * if FirePHP is available it will be used instead of
     * dumping the debug info into the document.
     *
     * It uses print_r and encapsulates it in HTML/XML comments.
     * (<!-- -->)
     *
     * @param string $msg   Debug identifier, e.g. "my array".
     * @param mixed  $mixed Object, type, etc, to be debugged.
     *
     * @return void
     */
    public static function printDebug($msg, $mixed)
    {
        if (self::$debug) {
            if (class_exists('FB', false)) {
                FB::send($mixed, $msg);
            } else {
                echo "\n<!-- ".$msg.": \n";
                print_r($mixed);
                echo "\n end ".$msg." -->\n";
            }
        }
    }

    /**
     * Checks/fixes so the invNo input is valid.
     *
     * @param string &$invNo Invoice number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkInvNo(&$invNo)
    {
        if (!isset($invNo)) {
            throw new Billmate_ArgumentNotSetException("Invoice number");
        }
        if (!is_string($invNo)) {
            $invNo = strval($invNo);
        }
        if (strlen($invNo) == 0) {
            throw new Billmate_ArgumentNotSetException("Invoice number");
        }
    }

    /**
     * Checks/fixes so the quantity input is valid.
     *
     * @param int &$qty Quantity.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkQty(&$qty)
    {
        if (!isset($qty)) {
            throw new Billmate_ArgumentNotSetException("Quantity");
        }
        if (is_numeric($qty) && !is_int($qty)) {
            $qty = intval($qty);
        }
        if (!is_int($qty)) {
            throw new Billmate_InvalidTypeException("Quantity", "integer");
        }
    }

    /**
     * Checks/fixes so the artTitle input is valid.
     *
     * @param string &$artTitle Article title.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkArtTitle(&$artTitle)
    {
        if (!is_string($artTitle)) {
            $artTitle = strval($artTitle);
        }
        if (!isset($artTitle) || strlen($artTitle) == 0) {
            throw new Billmate_ArgumentNotSetException("artTitle", 50059);
        }
    }

    /**
     * Checks/fixes so the artNo input is valid.
     *
     * @param int|string &$artNo Article number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkArtNo(&$artNo)
    {
        if (is_numeric($artNo) && !is_string($artNo)) {
            //Convert artNo to string if integer.
            $artNo = strval($artNo);
        }
        if (!isset($artNo) || strlen($artNo) == 0 || (!is_string($artNo))) {
            throw new Billmate_ArgumentNotSetException("artNo");
        }
    }

    /**
     * Checks/fixes so the credNo input is valid.
     *
     * @param string &$credNo Credit number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkCredNo(&$credNo)
    {
        if (!isset($credNo)) {
            throw new Billmate_ArgumentNotSetException("Credit number");
        }

        if ($credNo === false || $credNo === null) {
            $credNo = "";
        }
        if (!is_string($credNo)) {
            $credNo = strval($credNo);
            if (!is_string($credNo)) {
                throw new Billmate_InvalidTypeException("Credit number", "string");
            }
        }
    }

    /**
     * Checks so that artNos is an array and is not empty.
     *
     * @param array &$artNos Array from {@link Billmate::addArtNo()}.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkArtNos(&$artNos)
    {
        if (!is_array($artNos)) {
            throw new Billmate_InvalidTypeException("artNos", "array");
        }
        if (empty($artNos)) {
            throw new BillmateException('ArtNo array is empty!', 50064);
        }
    }

    /**
     * Checks/fixes so the integer input is valid.
     *
     * @param int    &$int  {@link BillmateFlags flags} constant.
     * @param string $field Name of the field.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkInt(&$int, $field)
    {
        if (!isset($int)) {
            throw new Billmate_ArgumentNotSetException($field);
        }
        if (is_numeric($int) && !is_int($int)) {
            $int = intval($int);
        }
        if (!is_numeric($int) || !is_int($int)) {
            throw new Billmate_InvalidTypeException($field, "integer");
        }
    }

    /**
     * Checks/fixes so the VAT input is valid.
     *
     * @param float &$vat VAT.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkVAT(&$vat)
    {
        if (!isset($vat)) {
            throw new Billmate_ArgumentNotSetException("VAT");
        }
        if (is_numeric($vat) && (!is_int($vat) || !is_float($vat))) {
            $vat = floatval($vat);
        }
        if (!is_numeric($vat) || (!is_int($vat) && !is_float($vat))) {
            throw new Billmate_InvalidTypeException("VAT", "integer or float");
        }
    }

    /**
     * Checks/fixes so the amount input is valid.
     *
     * @param int &$amount Amount.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkAmount(&$amount)
    {
        if (!isset($amount)) {
            throw new Billmate_ArgumentNotSetException("Amount");
        }
        if (is_numeric($amount)) {
            $this->_fixValue($amount);
        }
        if (is_numeric($amount) && !is_int($amount)) {
            $amount = intval($amount);
        }
        if (!is_numeric($amount) || !is_int($amount)) {
            throw new Billmate_InvalidTypeException("amount", "integer");
        }
    }

    /**
     * Checks/fixes so the price input is valid.
     *
     * @param int &$price Price.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkPrice(&$price)
    {return;
        if (!isset($price)) {
            throw new Billmate_ArgumentNotSetException("Price");
        }
        if (is_numeric($price)) {
            $this->_fixValue($price);
        }
        if (is_numeric($price) && !is_int($price)) {
            $price = intval($price);
        }
        if (!is_numeric($price) || !is_int($price)) {
            throw new Billmate_InvalidTypeException("Price", "integer");
        }
    }

    /**
     * Multiplies value with 100 and rounds it.
     * This fixes value/price/amount inputs so that KO can handle them.
     *
     * @param float &$value value
     *
     * @return void
     */
    private function _fixValue(&$value)
    {
        $value = round($value * 100);
    }

    /**
     * Checks/fixes so the discount input is valid.
     *
     * @param float &$discount Discount amount.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkDiscount(&$discount)
    {
        if (!isset($discount)) {
            throw new Billmate_ArgumentNotSetException("Discount");
        }
        if (is_numeric($discount)
            && (!is_int($discount) || !is_float($discount))
        ) {
            $discount = floatval($discount);
        }

        if (!is_numeric($discount)
            || (!is_int($discount) && !is_float($discount))
        ) {
            throw new Billmate_InvalidTypeException("Discount", "integer or float");
        }
    }

    /**
     * Checks/fixes so that the estoreOrderNo input is valid.
     *
     * @param string &$estoreOrderNo Estores order number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkEstoreOrderNo(&$estoreOrderNo)
    {
        if (!isset($estoreOrderNo)) {
            throw new Billmate_ArgumentNotSetException("Order number");
        }

        if (!is_string($estoreOrderNo)) {
            $estoreOrderNo = strval($estoreOrderNo);
            if (!is_string($estoreOrderNo)) {
                throw new Billmate_InvalidTypeException("Order number", "string");
            }
        }
    }

    /**
     * Checks/fixes to the PNO/SSN input is valid.
     *
     * @param string &$pno Personal number, social security  number, ...
     * @param int    $enc  {@link BillmateEncoding PNO Encoding} constant.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkPNO(&$pno, $enc)
    {
        if (!$pno) {
            throw new Billmate_ArgumentNotSetException("PNO/SSN");
        }

        if (!BillmateEncoding::checkPNO($pno)) {
            throw new Billmate_InvalidPNOException;
        }
    }

    /**
     * Checks/fixes to the country input is valid.
     *
     * @param int &$country {@link BillmateCountry Country} constant.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkCountry(&$country)
    {
        if (!isset($country)) {
            throw new Billmate_ArgumentNotSetException("Country");
        }
        if (is_numeric($country) && !is_int($country)) {
            $country = intval($country);
        }
        if (!is_numeric($country) || !is_int($country)) {
            throw new Billmate_InvalidTypeException("Country", "integer");
        }
    }

    /**
     * Checks/fixes to the language input is valid.
     *
     * @param int &$language {@link BillmateLanguage Language} constant.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkLanguage(&$language)
    {
        if (!isset($language)) {
            throw new Billmate_ArgumentNotSetException("Language");
        }
        if (is_numeric($language) && !is_int($language)) {
            $language = intval($language);
        }
        if (!is_numeric($language) || !is_int($language)) {
            throw new Billmate_InvalidTypeException("Language", "integer");
        }
    }

    /**
     * Checks/fixes to the currency input is valid.
     *
     * @param int &$currency {@link BillmateCurrency Currency} constant.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkCurrency(&$currency)
    {
        if (!isset($currency)) {
            throw new Billmate_ArgumentNotSetException("Currency");
        }
        if (is_numeric($currency) && !is_int($currency)) {
            $currency = intval($currency);
        }
        if (!is_numeric($currency) || !is_int($currency)) {
            throw new Billmate_InvalidTypeException("Currency", "integer");
        }
    }

    /**
     * Checks/fixes so no/number is a valid input.
     *
     * @param int &$no Number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkNo(&$no)
    {
        if (!isset($no)) {
            throw new Billmate_ArgumentNotSetException("no");
        }
        if (is_numeric($no) && !is_int($no)) {
            $no = intval($no);
        }
        if (!is_numeric($no) || !is_int($no) || $no <= 0) {
            throw new Billmate_InvalidTypeException('no', 'integer > 0');
        }
    }

    /**
     * Checks/fixes so reservation number is a valid input.
     *
     * @param string &$rno Reservation number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkRNO(&$rno)
    {
        if (!is_string($rno)) {
            $rno = strval($rno);
        }
        if (strlen($rno) == 0) {
            throw new Billmate_ArgumentNotSetException("RNO");
        }
    }

    /**
     * Checks/fixes so that reference/refCode are valid.
     *
     * @param string &$reference Reference string.
     * @param string &$refCode   Reference code.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkRef(&$reference, &$refCode)
    {
        if (!is_string($reference)) {
            $reference = strval($reference);
            if (!is_string($reference)) {
                throw new Billmate_InvalidTypeException("Reference", "string");
            }
        }

        if (!is_string($refCode)) {
            $refCode = strval($refCode);
            if (!is_string($refCode)) {
                throw new Billmate_InvalidTypeException("Reference code", "string");
            }
        }
    }

    /**
     * Checks/fixes so that the OCR input is valid.
     *
     * @param string &$ocr OCR number.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkOCR(&$ocr)
    {
        if (!is_string($ocr)) {
            $ocr = strval($ocr);
            if (!is_string($ocr)) {
                throw new Billmate_InvalidTypeException("OCR", "string");
            }
        }
    }

     /**
     * Check so required argument is supplied.
     *
     * @param string $argument argument to check
     * @param string $name     name of argument
     *
     * @throws Billmate_ArgumentNotSetException
     * @return void
     */
    private function _checkArgument($argument, $name)
    {
        if (!is_string($argument)) {
            $argument = strval($argument);
        }

        if (strlen($argument) == 0) {
            throw new Billmate_ArgumentNotSetException($name);
        }
    }

    /**
     * Check so Locale settings (country, currency, language) are set.
     *
     * @throws BillmateException
     * @return void
     */
    private function _checkLocale()
    {
        if (!is_int($this->_country)
            || !is_int($this->_language)
            || !is_int($this->_currency)
        ) {
            throw new Billmate_InvalidLocaleException;
        }
    }

    /**
     * Checks wether a goodslist is set.
     *
     * @throws Billmate_MissingGoodslistException
     * @return void
     */
    private function _checkGoodslist()
    {
        if (!is_array($this->goodsList) || empty($this->goodsList)) {
            throw new Billmate_MissingGoodslistException;
        }
    }

    /**
     * Set the pcStorage method used for this instance
     *
     * @param PCStorage $pcStorage PCStorage implementation
     *
     * @return void
     */
    public function setPCStorage($pcStorage)
    {
        if (!($pcStorage instanceof PCStorage)) {
            throw new Billmate_InvalidTypeException('pcStorage', 'PCStorage');
        }
        $this->pcStorage = $pcStorage->getName();
        $this->pclasses = $pcStorage;
    }

} //End Billmate

/**
 * Include the {@link BillmateConfig} class.
 */
require_once 'billmateconfig.php';

/**
 * Include the {@link BillmatePClass} class.
 */
require_once 'billmatepclass.php';

/**
 * Include the {@link BillmateCalc} class.
 */
require_once 'billmatecalc.php';

/**
 * Include the {@link BillmateAddr} class.
 */
require_once 'billmateaddr.php';

/**
 * Include the Exception classes.
 */
require_once 'Exceptions.php';

/**
 * Include the BillmateEncoding class.
 */
require_once 'Encoding.php';


/**
 * Include the BillmateFlags class.
 */
require_once 'Flags.php';

/**
 * Include BillmateCountry, BillmateCurrency, BillmateLanguage classes
 */
require_once 'Country.php';
require_once 'Currency.php';
require_once 'Language.php';
