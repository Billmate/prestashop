<?php
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
			$db->Execute('DELETE FROM `'.DB_PREFIX_.'billmate_payment_pclasses` WHERE language = "'.$language.'"');

			if (!is_array($data))
				throw new Exception(strip_tags($data));
			else
			{
				array_walk($data, array($this, 'correct_lang_billmate'));
				foreach ($data as $row)
				{
					$row['eid'] = $eid;

					Db::getInstance()->insert('billmate_payment_pclasses', $row);

				}
			}
		}

		public function correct_lang_billmate(&$item, $index)
		{
			$item['description']  = utf8_encode($item['description']);
			$item['startfee']     = $item['startfee'] / 100;
			$item['handlingfee']  = $item['handlingfee'] / 100;
			$item['interestrate'] = $item['interestrate'] / 100;
			$item['minamount']    = $item['minamount'] / 100;
			$item['maxamount']    = $item['maxamount'] / 100;
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

		public function getPClasses($eid = '', $language, $prepare = false, $total)
		{
			if (!empty($eid))
				$this->_eid = $eid;

			$data = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.
			                                    'billmate_payment_pclasses` where eid="'.$this->_eid.
			                                    '" AND language="'.$language.'" AND expirydate > NOW()');
			if (!$prepare)
			{
				if (!is_array($data))
					$data = array();

				return $data;
			}
			$pclasses = array();
			$key      = 0;
			foreach ($data as $row)
			{
				$pclasses[$key]                = $row;
				$pclasses[$key]['monthlycost'] = BillmateCalc::calc_monthly_cost($total, $row, BillmateFlags::CHECKOUT_PAGE);
				$key++;
			}

			return $pclasses;


		}

		public static function hasPclasses($language)
		{
			$data = Db::getInstance()->ExecuteS('SELECT count(*) FROM '._DB_PREFIX_.
			                                    'billmate_payment_pclasses WHERE language = "'.$language.
			                                    '" AND expirydate > NOW()');

			if ($data == 0)
				return false;

			return true;
		}
	}