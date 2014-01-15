<?php


/**
 * Replace array_key_exists()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.array_key_exists
 * @version     $Revision: 1.1 $
 * @since       PHP 4.1.0
 * @require     PHP 4.0.0 (user_error)
 */
if (!function_exists('array_key_exists')) {
    function array_key_exists($key, $search)
    {
        if (!is_scalar($key)) {
            user_error('array_key_exists() The first argument should be either a string or an integer',
                E_USER_WARNING);
            return false;
        }

        if (is_object($search)) {
            $search = get_object_vars($search);
        }

        if (!is_array($search)) {
            user_error('array_key_exists() The second argument should be either an array or an object',
                E_USER_WARNING);
            return false;
        }

        return in_array($key, array_keys($search));
    }
}

?>