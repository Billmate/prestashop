<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * Class for Common Billmate stuff
	 */

	require_once 'Billmate.php';
	require_once 'Encoding.php';
	require_once 'Utf8.php';
        define('BILLMATE_PLUGIN_VERSION','3.4.4');
	class Common {

		public static function getBillmate($eid, $secret, $testmode, $ssl = true, $debug = false)
		{
			if (!defined('BILLMATE_CLIENT')) {
				$version = defined('_PS_VERSION_') ? _PS_VERSION_ : 'toOld';
				define('BILLMATE_CLIENT', 'PrestaShop:' .$version. ' PLUGIN:' . BILLMATE_PLUGIN_VERSION);
			}
			if(!defined('BILLMATE_SERVER'))
				define('BILLMATE_SERVER','2.1.7');
			return new BillMate($eid, $secret, $ssl, $testmode, $debug);
		}

		public static function match_usernamevp($str1, $str2)
		{
			$name1     = explode(' ', utf8_strtolower(Encoding::fixUTF8($str1)));
			$name2     = explode(' ', utf8_strtolower(Encoding::fixUTF8($str2)));
			$foundName = array_intersect($name1, $name2);

			return count($foundName) > 0;
		}

		public static function matchstr($string1, $string2)
		{
			$string1 = explode(' ', utf8_strtolower(Encoding::fixUTF8($string1)));
			$string2 = explode(' ', utf8_strtolower(Encoding::fixUTF8($string2)));

			$filterStr1 = array();
			foreach ($string1 as $str1)
			{
				if (trim($str1, '.') == $str1)
					$filterStr1[] = $str1;

			}
			$filterStr2 = array();
			foreach ($string2 as $str2)
			{
				if (trim($str2, '.') == $str2)
					$filterStr2[] = $str2;

			}
			$foundName = array_intersect($filterStr1, $filterStr2);

			return (count($foundName) == count($filterStr1));
		}

        public static function getCartCheckoutHash() {
            if (Configuration::get('BILLMATE_CHECKOUT_ACTIVATE') != 1) {
                Common::unsetCartCheckoutHash();
            }
            $context = Context::getContext();
            return $context->cookie->{'BillmateHash'.$context->cart->id};
        }

        public static function setCartCheckoutHash($hash = '') {
            $context = Context::getContext();
            $context->cookie->{'BillmateHash'.$context->cart->id} = $hash;
        }

        public static function unsetCartCheckoutHash() {
            $context = Context::getContext();
            if (isset($context->cookie->{'BillmateHash'.$context->cart->id})) {
                unset($context->cookie->{'BillmateHash'.$context->cart->id});
            }
        }
	}
