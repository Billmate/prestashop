<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-19
 * Time: 13:03
 */

	require_once "Billmate.php";
	require_once "Encoding.php";
	require_once "Utf8.php";

class Common {

	public static function getBillmate( $eid, $secret, $testmode, $ssl = true, $debug = false)
	{
		return new BillMate($eid,$secret,$ssl,$testmode,$debug);
	}

	public static function match_usernamevp( $str1, $str2 ){
		$name1 = explode(' ', utf8_strtolower( Encoding::fixUTF8( $str1 ) ) );
		$name2 = explode(' ', utf8_strtolower( Encoding::fixUTF8( $str2 ) ) );
		$foundName = array_intersect($name1, $name2);
		return count($foundName ) > 0;
	}

	public static function matchstr($string1, $string2 ){
		$string1 = explode(" ", utf8_strtolower(Encoding::fixUTF8($string1)) );
		$string2 = explode(" ", utf8_strtolower(Encoding::fixUTF8($string2)) );

		$filterStr1 = array();
		foreach( $string1 as $str1 ){
			if( trim($str1,'.') == $str1 ){
				$filterStr1[] = $str1;
			}
		}
		$filterStr2 = array();
		foreach( $string2 as $str2 ){
			if( trim($str2,'.') == $str2 ){
				$filterStr2[] = $str2;
			}
		}
		$foundName = array_intersect( $filterStr1, $filterStr2 );
		return (count($foundName)==count($filterStr1));
	}
}
