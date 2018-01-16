<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	/*
	 * The class that handles the paymentplans
	 */

	require_once 'Flags.php';
	require_once 'billmatecalc.php';

	class pClasses {

		private $data = null;
		private $config = array();
		private $_eid = null;

		public function __construct($eid = '', $secret = '', $country = '', $language = '', $currency = '', $mode = 'live')
		{
			$this->config['eid']      = $eid;
			$this->_eid               = $eid;
			$this->config['secret']   = $secret;
			$this->config['country']  = $country;
			$this->config['language'] = $language;
			$this->config['currency'] = $currency;
			$this->config['mode']     = $mode;
			//$this->getPClasses($eid);
		}

		public function clear()
		{
			Db::getInstance()->Execute('truncate `'._DB_PREFIX_.'billmate_payment_pclasses`');
		}

		public function Save($eid, $secret, $country, $language, $currency, $mode = 'live')
		{
			$testmode = $mode == 'beta';
			$ssl      = true;
			$debug    = false;

			$billmate = new BillMate($eid, $secret, $ssl, $testmode, $debug);

			$values                = array();
			$values['PaymentData'] = array(
				'currency' => $currency,//SEK
				'country'  => $country,//Sweden
				'language' => $language,//Swedish
			);
			$data                  = $billmate->getPaymentplans($values);
			
			$db = Db::getInstance();
			//$db->Execute('TRUNCATE TABLE `'._DB_PREFIX_.'billmate_payment_pclasses`');
			if(self::hasPclasses($language,true))
				$db->Execute('DELETE FROM `'._DB_PREFIX_.'billmate_payment_pclasses` WHERE `language` = "'.$language.'"');

			if (!is_array($data))
				throw new Exception(strip_tags($data));
			else
			{
				if(!isset($data['code'])) {
					//array_walk($data, 'pClasses::correct_lang_billmate');
					foreach ($data as $row) {
						$row['eid'] = $eid;
						$row['description'] = mb_convert_encoding($row['description'], 'UTF-8', 'auto');
						$row['startfee'] = $row['startfee'] / 100;
						$row['handlingfee'] = $row['handlingfee'] / 100;
						$row['interestrate'] = $row['interestrate'] / 100;
						$row['minamount'] = $row['minamount'] / 100;
						$row['maxamount'] = $row['maxamount'] / 100;
						Db::getInstance()->insert('billmate_payment_pclasses', $row);

					}
				}
			}
		}

		public function correct_lang_billmate(&$item, $index)
		{
			$item['description']  = mb_convert_encoding($item['description'],'UTF-8','auto');
			$item['startfee']     = $item['startfee'] / 100;
			$item['handlingfee']  = $item['handlingfee'] / 100;
			$item['interestrate'] = $item['interestrate'] / 100;
			$item['minamount']    = $item['minamount'] / 100;
			$item['maxamount']    = $item['maxamount'] / 100;

		}

        public static function getLowestMinAmount() {
            $minamount = 0;
            $minamounts = array();
            $p = new pClasses(Configuration::get('BILLMATE_ID'));
            $pclasses = $p->getPClasses();

            if (is_array($pclasses) {
                foreach ($pclasses as $pclass) {
                    if (isset($pclass['minamount']) AND $pclass['minamount'] > 0) {
                        $minamounts[] = $pclass['minamount'];
                    }
                }
            }

            if (count($minamounts) > 0) {
                $minamount = min($minamounts);
            }

            return $minamount;
        }

		public function getCheapestPClass($sum, $flags, $language)
		{
			$lowest_pp = $lowest = false;

			$pclasses = $this->getPClasses('', $language);

			foreach ($pclasses as $pclass)
			{
				if ($pclass !== false)
				{
					$lowest_payment = BillmateCalc::get_lowest_payment_for_account($pclass['country']);

					if ($pclass['type'] < 2 && $sum >= $pclass['minamount'] && ($sum <= $pclass['maxamount'] || $pclass['maxamount'] == 0))
					{
						$minpay = BillmateCalc::calc_monthly_cost($sum, $pclass, $flags);

						if ($minpay < $lowest_pp || $lowest_pp === false)
						{
							if ($minpay >= $lowest_payment)
							{
								$lowest_pp = $minpay;
								$lowest                = $pclass;
								$lowest['monthlycost'] = $minpay;
							}
						}
					}
				}
			}

			return $lowest;
		}

		public function getPClasses($eid = '', $language = false, $prepare = false, $total = false)
		{
			if (!empty($eid))
				$this->_eid = $eid;
			$langQuery = ($language) ? ' AND language="'.$language.'" ' : '';
			$data = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.
												'billmate_payment_pclasses` where eid="'.$this->_eid.
			                                    '"'.$langQuery.' AND expirydate > NOW()');
			if (!$prepare)
			{
				if (!is_array($data))
					$data = array();

				return $data;
			}
			$pclasses = array();
			$key      = 0;
			if ($total)
			{
				foreach ($data as $row)
				{
					if ($row['type'] < 2 && $total >= $row['minamount'] && ($total <= $row['maxamount'] || $row['maxamount'] == 0))
					{
						$pclasses[$key] = $row;
						$pclasses[$key]['monthlycost'] = BillmateCalc::calc_monthly_cost($total, $row, BillmateFlags::CHECKOUT_PAGE);
						$key++;
					}
				}
			}

			return $pclasses;
		}

		public static function hasPclasses($language,$justcount = false)
		{
			$date = '';
			if	($justcount)
				$date = ' AND expirydate > NOW()';
			$data = Db::getInstance()->ExecuteS('SELECT count(*) AS total FROM '._DB_PREFIX_.
												'billmate_payment_pclasses WHERE language = "'.$language.'"'.
												$date);

			if ($data[0]['total'] == 0)
				return false;

			return true;
		}

		public static function checkPclasses($eid, $secret, $country, $language, $currency, $mode = 'live'){

			$data = Db::getInstance()->ExecuteS('SELECT count(*) AS total FROM '._DB_PREFIX_.
				'billmate_payment_pclasses WHERE language = "'.$language.
				'" AND expirydate > NOW()');

			if ($data[0]['total'] == 0)
			{
				self::Save($eid, $secret, $country, $language, $currency, $mode);
			}
		}
	}