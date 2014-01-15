<?php
/**
 * Replace is_scalar()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.is_scalar
 * @author      Gaetano Giunta
 * @version     $Revision: 1.2 $
 * @since       PHP 4.0.5
 * @require     PHP 4 (is_bool)
 */
if (!function_exists('is_scalar')) {
    function is_scalar($val)
    {
        // Check input
        return (is_bool($val) || is_int($val) || is_float($val) || is_string($val));
    }
}

?>