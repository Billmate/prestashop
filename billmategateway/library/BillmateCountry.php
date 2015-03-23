<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */
	/*
	 * Class that handles Countries
	 */

	class BillmateCountry {

		const DK = 59;
		const FI = 73;
		const DE = 81;
		const NL = 154;
		const NO = 164;
		const SE = 209;
		public static $countriesdata = array(
			209 => 'sweden',
			73  => 'finland',
			59  => 'denmark',
			164 => 'norway',
			81  => 'germany',
			15  => 'austria',
			154 => 'netherlands'
		);

		public static function getContryByNumber($number)
		{
			return isset(self::$countriesdata[$number]) ? self::$countriesdata[$number] : false;
		}

		private function __construct()
		{
		}

		public static function getCountries()
		{
			return self::$countriesdata;
		}

		public static function fromCode($val)
		{
			switch (strtolower($val))
			{
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

		public static function getCode($val, $alpha3 = false)
		{
			switch ($val)
			{
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

		public static function getCodeByName($name, $alpha3 = false)
		{
			$name = strtolower($name);
			$val  = array_search($name, self::$countriesdata);
			switch ($val)
			{
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
	}