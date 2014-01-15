<?php

/**
 * Replace var_export()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.var_export
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.2 $
 * @since       PHP 4.2.0
 * @require     PHP 4.0.0 (user_error)
 */
if (!function_exists('var_export')) {
    function var_export($array, $return = false, $lvl=0)
    {
        // Common output variables
        $indent      = '  ';
        $doublearrow = ' => ';
        $lineend     = ",\n";
        $stringdelim = '\'';

        // Check the export isn't a simple string / int
        if (is_string($array)) {
            $out = $stringdelim . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $array)) . $stringdelim;
        } elseif (is_int($array) || is_float($array)) {
            $out = (string)$array;
        } elseif (is_bool($array)) {
            $out = $array ? 'true' : 'false';
        } elseif (is_null($array)) {
            $out = 'NULL';
        } elseif (is_resource($array)) {
            $out = 'resource';
        } else {
            // Begin the array export
            // Start the string
            $out = "array (\n";

            // Loop through each value in array
            foreach ($array as $key => $value) {
                // If the key is a string, delimit it
                if (is_string($key)) {
                    $key = str_replace('\'', '\\\'', str_replace('\\', '\\\\', $key));
                    $key = $stringdelim . $key . $stringdelim;
                }

                $val = var_export($value, true, $lvl+1);
                // Delimit value
                /*if (is_array($value)) {
                    // We have an array, so do some recursion
                    // Do some basic recursion while increasing the indent
                    $recur_array = explode($newline, var_export($value, true));
                    $temp_array = array();
                    foreach ($recur_array as $recur_line) {
                        $temp_array[] = $indent . $recur_line;
                    }
                    $recur_array = implode($newline, $temp_array);
                    $value = $newline . $recur_array;
                } elseif (is_null($value)) {
                    $value = 'NULL';
                } else {
                    $value = str_replace($find, $replace, $value);
                    $value = $stringdelim . $value . $stringdelim;
                }*/

                // Piece together the line
                for ($i = 0; $i < $lvl; $i++)
                    $out .= $indent;
                $out .= $key . $doublearrow . $val . $lineend;
            }

            // End our string
            for ($i = 0; $i < $lvl; $i++)
                $out .= $indent;
            $out .= ")";
        }

        // Decide method of output
        if ($return === true) {
            return $out;
        } else {
            echo $out;
            return;
        }
    }
}
?>