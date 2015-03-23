<?php
require_once('BillmateCountry.php');
class BillmateCalc
{
    protected static $accuracy = 0.01;
    private static function _midpoint($a, $b)
    {
        return (($a+$b)/2);
    }
    private static function _npv($pval, $payarray, $rate, $fromdayone)
    {
        $month = $fromdayone;
        foreach ($payarray as $payment) {
            $pval -= $payment / pow(1 + $rate/(12*100.0), $month++);
        }
        return ($pval);
    }
    private static function  _irr($pval, $payarray, $fromdayone)
    {
        $low = 0.0;
        $high = 100.0;
        $lowval = self::_npv($pval, $payarray, $low, $fromdayone);
        $highval = self::_npv($pval, $payarray, $high, $fromdayone);
                if ($lowval > 0.0) {
            return (-1);
        }
                do {
            $mid = self::_midpoint($low, $high);
            $midval  = self::_npv($pval, $payarray, $mid, $fromdayone);
            if (abs($midval) < self::$accuracy) {
                                return ($mid);
            }
            if ($highval < 0.0) {
                                $low = $high;
                $lowval = $highval;
                $high *= 2;
                $highval = self::_npv($pval, $payarray, $high, $fromdayone);
            } else if ($midval >= 0.0) {
                                $high = $mid;
                $highval = $midval;
            } else {
                                $low = $mid;
                $lowval = $midval;
            }
        } while ($high < 1000000);
                return (-2);
    }
    private static function  _irr2apr($irr)
    {
        return (100 * (pow(1 + $irr / (12 * 100.0), 12) - 1));
    }
    private static function _fulpacc(
        $pval, $rate, $fee, $minpay, $payment, $months, $base
    ) {
        $bal = $pval;
        $payarray = array();
        while (($months != 0) && ($bal > self::$accuracy)) {
            $interest = $bal * $rate / (100.0 * 12);
            $newbal = $bal + $interest + $fee;
            if ($minpay >= $newbal || $payment >= $newbal) {
                $payarray[] = $newbal;
                return $payarray;
            }
            $newpay = max($payment, $minpay);
            if ($base) {
                $newpay = max($newpay, $bal/24.0 + $fee + $interest);
            }
            $bal = $newbal - $newpay;
            $payarray[] = $newpay;
            $months -= 1;
        }
        return $payarray;
    }
    private static function _annuity($pval, $months, $rate)
    {
        if ($months == 0) {
            return $pval;
        }
        if ($rate == 0) {
            return $pval/$months;
        }
        $p = $rate / (100.0*12);
        return $pval * $p / (1 - pow((1+$p), -$months));
    }
    private static function _aprAnnuity($pval, $months, $rate, $fee, $minpay)
    {
        $payment = self::_annuity($pval, $months, $rate) + $fee;
        if ($payment < 0) {
            return $payment;
        }
        $payarray = self::_fulpacc(
            $pval, $rate, $fee, $minpay, $payment, $months, false
        );
        $apr = self::_irr2apr(self::_irr($pval, $payarray, 1));
        return $apr;
    }
    private static function _getPayArray($sum, $pclass, $flags)
    {
        $monthsfee = 0;
        if ($flags === BillmateFlags::CHECKOUT_PAGE) {
            $monthsfee = $pclass['handlingfee'];
        }
        $startfee = 0;
        if ($flags === BillmateFlags::CHECKOUT_PAGE) {
            $startfee = $pclass['startfee'];
        }
                $sum += $startfee;
        $base = true;//($pclass['type'] === BillmatePClass::ACCOUNT);
        $lowest = self::get_lowest_payment_for_account($pclass['country']);
        if ($flags == BillmateFlags::CHECKOUT_PAGE) {
            $minpay = $lowest;
        } else {
            $minpay = 0;
        }
        $payment = self::_annuity(
            $sum,
            $pclass['nbrofmonths'],
            $pclass['interestrate']
        );
                $payment += $monthsfee;
        return  self::_fulpacc(
            $sum,
            $pclass['interestrate'],
            $monthsfee,
            $minpay,
            $payment,
            $pclass['nbrofmonths'],
            $base
        );
    }
    public static function calc_apr($sum, $pclass, $flags, $free = 0)
    {
        if (!is_numeric($sum)) {
            throw new Exception('sum', 'numeric');
        }
        if (is_numeric($sum) && (!is_int($sum) || !is_float($sum))) {
            $sum = (float) $sum;
        }

        if (!is_numeric($free)) {
            throw new Exception('free',  'integer');
        }
        if (is_numeric($free) && !is_int($free)) {
            $free = (int) $free;
        }
        if ($free < 0) {
            throw new Exception(
                'Error in ' . __METHOD__ .
                ': Number of free months must be positive or zero!'
            );
        }
        if (is_numeric($flags) && !is_int($flags))
            $flags = (int) $flags;

        if (!is_numeric($flags)
            || !in_array(
                $flags, array(
                    BillmateFlags::CHECKOUT_PAGE, BillmateFlags::PRODUCT_PAGE
                )
            )
        )
        {
            throw new Exception(
                'flags',
                BillmateFlags::CHECKOUT_PAGE . ' or ' . BillmateFlags::PRODUCT_PAGE
            );
        }
        $monthsfee = 0;
        if ($flags === BillmateFlags::CHECKOUT_PAGE) {
            $monthsfee = $pclass['handlingfee'];
        }
        $startfee = 0;
        if ($flags === BillmateFlags::CHECKOUT_PAGE) {
            $startfee = $pclass['startfee'];
        }
                $sum += $startfee;
        $lowest = self::get_lowest_payment_for_account($pclass['country']);
        if ($flags == BillmateFlags::CHECKOUT_PAGE) {
            $minpay = $lowest ;
        } else {
            $minpay = 0;
        }
                $payment = self::_annuity(
            $sum,
            $pclass['nbrofmonths'],
            $pclass['interestrate']
        ) + $monthsfee;
        $type = $pclass['type'];
		
		return round(
			self::_aprAnnuity(
				$sum, $pclass['nbrofmonths'],
				$pclass['interestrate'],
				$pclass['handlingfee'],
				$minpay
			),
			2
		);
    }
    public static function total_credit_purchase_cost($sum, $pclass, $flags)
    {
        if (!is_numeric($sum)) {
            throw new Exception('sum', 'numeric');
        }
        if (is_numeric($sum) && (!is_int($sum) || !is_float($sum)))
            $sum = (float) $sum;

        if (is_numeric($flags) && !is_int($flags))
            $flags = (int) $flags;

        if (!is_numeric($flags)
            || !in_array(
                $flags,
                array(
                    BillmateFlags::CHECKOUT_PAGE, BillmateFlags::PRODUCT_PAGE
                )
            )
        ) {
            throw new Exception(
                'flags',
                BillmateFlags::CHECKOUT_PAGE . ' or ' . BillmateFlags::PRODUCT_PAGE
            );
        }
        $payarr = self::_getPayArray($sum, $pclass, $flags);
        $credit_cost = 0;
        foreach ($payarr as $pay) {
            $credit_cost += $pay;
        }
        return self::pRound($credit_cost, $pclass['country']);
    }
    public static function calc_monthly_cost($sum, $pclass, $flags)
    {
        if (!is_numeric($sum))
            throw new Exception('sum', 'numeric');

        if (is_numeric($sum) && (!is_int($sum) || !is_float($sum)))
            $sum = (float) $sum;

        if (is_numeric($flags) && !is_int($flags))
            $flags = (int) $flags;

        if (!is_numeric($flags)
            || !in_array(
                $flags,
                array(
                    BillmateFlags::CHECKOUT_PAGE, BillmateFlags::PRODUCT_PAGE
                )
            )
        )
            throw new Exception(
                'flags',
                BillmateFlags::CHECKOUT_PAGE . ' or ' . BillmateFlags::PRODUCT_PAGE
            );

        $payarr = self::_getPayArray($sum, $pclass, $flags);
        $value = 0;
        if (isset($payarr[0])) {
            $value = $payarr[0];
        }
        if (BillmateFlags::CHECKOUT_PAGE == $flags) {
            return round($value, 0);
        }
        return self::pRound($value, $pclass['country']);
    }
    public static function get_lowest_payment_for_account($country)
    {
	
        switch ($country) {
	        case 'se':
                return 50.0;
	        case 'no':
	            return 95.0;
	        case 'fi':
	            return 8.95;
	        case 'dk':
	            return 89.0;
	        case 'de':
	        case 'nl':
	            return 6.95;
	        default:
	            throw new Exception($country);
        }
    }
    public static function pRound($value, $country)
    {
        $multiply = 1; 
        switch($country) {
        case BillmateCountry::FI:
        case BillmateCountry::DE:
        case BillmateCountry::NL:
            $multiply = 10;             break;
        }
        return floor(($value*$multiply)+0.5)/$multiply;
    }
}