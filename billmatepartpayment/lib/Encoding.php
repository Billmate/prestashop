<?php
/**
 * BillmateEncoding
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

require_once 'Exceptions.php';

/**
 * Encoding class
 *
 * @category  Payment
 * @package   BillmateAPI
 * @author    MS Dev <ms.modules@billmate.com>
 * @copyright 2012 Billmate AB (http://billmate.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.billmate.com/
 */
class BillmateEncoding
{
    /**
     * PNO/SSN encoding for Sweden.
     *
     * @var int
     */
    const PNO_SE = 2;

    /**
     * PNO/SSN encoding for Norway.
     *
     * @var int
     */
    const PNO_NO = 3;

    /**
     * PNO/SSN encoding for Finland.
     *
     * @var int
     */
    const PNO_FI = 4;

    /**
     * PNO/SSN encoding for Denmark.
     *
     * @var int
     */
    const PNO_DK = 5;

    /**
     * PNO/SSN encoding for Germany.
     *
     * @var int
     */
    const PNO_DE = 6;

    /**
     * PNO/SSN encoding for Netherlands.
     *
     * @var int
     */
    const PNO_NL = 7;

    /**
     * Encoding constant for customer numbers.
     *
     * @see Billmate::setCustomerNo()
     * @var int
     */
    const CUSTNO = 1000;

    /**
     * Encoding constant for email address.
     *
     * @var int
     */
    const EMAIL = 1001;

    /**
     * Encoding constant for cell numbers.
     *
     * @var int
     */
    const CELLNO = 1002;

    /**
     * Encoding constant for bank bic + account number.
     *
     * @var int
     */
    const BANK_BIC_ACC_NO = 1003;

    /**
     * Returns a regexp string for the specified encoding constant.
     *
     * @param int $enc PNO/SSN encoding constant.
     *
     * @return string The regular expression.
     * @throws Billmate_UnknownEncodingException
     */
    public static function getRegexp($enc)
    {
        switch($enc) {
        case self::PNO_SE:
            /*
             * All positions except C contain numbers 0-9.
             *
             * PNO:
             * YYYYMMDDCNNNN, C = -|+  length 13
             * YYYYMMDDNNNN                   12
             * YYMMDDCNNNN                    11
             * YYMMDDNNNN                     10
             *
             * ORGNO:
             * XXXXXXNNNN
             * XXXXXX-NNNN
             * 16XXXXXXNNNN
             * 16XXXXXX-NNNN
             *
             */
            return '/^[0-9]{6,6}(([0-9]{2,2}[-\+]{1,1}[0-9]{4,4})|([-\+]'.
                '{1,1}[0-9]{4,4})|([0-9]{4,6}))$/';
        case self::PNO_NO:
            /*
             * All positions contain numbers 0-9.
             *
             * Pno
             * DDMMYYIIIKK    ("fodelsenummer" or "D-nummer") length = 11
             * DDMMYY-IIIKK   ("fodelsenummer" or "D-nummer") length = 12
             * DDMMYYYYIIIKK  ("fodelsenummer" or "D-nummer") length = 13
             * DDMMYYYY-IIIKK ("fodelsenummer" or "D-nummer") length = 14
             *
             * Orgno
             * Starts with 8 or 9.
             *
             * NNNNNNNNK      (orgno)                         length = 9
             */
            return '/^[0-9]{6,6}((-[0-9]{5,5})|([0-9]{2,2}((-[0-9]'.
                '{5,5})|([0-9]{1,1})|([0-9]{3,3})|([0-9]{5,5))))$/';
        case self::PNO_FI:
            /*
             * Pno
             * DDMMYYCIIIT
             * DDMMYYIIIT
             * C = century, '+' = 1800, '-' = 1900 och 'A' = 2000.
             * I = 0-9
             * T = 0-9, A-F, H, J, K-N, P, R-Y
             *
             * Orgno
             * NNNNNNN-T
             * NNNNNNNT
             * T = 0-9, A-F, H, J, K-N, P, R-Y
             */
            return '/^[0-9]{6,6}(([A\+-]{1,1}[0-9]{3,3}[0-9A-FHJK-NPR-Y]'.
                '{1,1})|([0-9]{3,3}[0-9A-FHJK-NPR-Y]{1,1})|([0-9]{1,1}-{0,1}'.
                '[0-9A-FHJK-NPR-Y]{1,1}))$/i';
        case self::PNO_DK:
            /*
             * Pno
             * DDMMYYNNNG       length 10
             * G = gender, odd/even for men/women.
             *
             * Orgno
             * XXXXXXXX         length 8
             */
            return '/^[0-9]{8,8}([0-9]{2,2})?$/';
        case self::PNO_NL:
        case self::PNO_DE:
            /**
             * Pno
             * DDMMYYYYG         length 9
             * DDMMYYYY                 8
             *
             * Orgno
             * XXXXXXX                  7  company org nr
             */
            return '/^[0-9]{7,9}$/';
        case self::EMAIL:
            /**
             * Validates an email.
             */
            return '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]'.
                '+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z0-9-][a-zA-Z0-9-]+)+$/';
        case self::CELLNO:
            /**
             * Validates a cellno.
             *
             */
            return '/^07[\ \-0-9]{8,13}$/';
        default:
            throw new Billmate_UnknownEncodingException($enc);
        }
    }

    /**
     * Checks if the specified PNO is correct according to specified encoding constant.
     *
     * @param string $pno PNO/SSN string.
     * @param int    $enc {@link BillmateEncoding PNO/SSN encoding} constant.
     *
     * @return bool   True if correct.
     */
    public static function checkPNO($pno, $enc = null)
    {
        return strlen($pno) > 0;
    }
}
