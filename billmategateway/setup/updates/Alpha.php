<?php

/**
 * Created by PhpStorm.
 * User: jesperjohansson
 * Date: 15-09-07
 * Time: 10:20
 */
require_once(_PS_MODULE_DIR_.'/billmategateway/interfaces/SetupFileInterface.php');

class Alpha implements SetupFileInterface
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function install()
    {
        try
        {
            $drop = $this->db->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'billmate_payment_fees`;CREATE TABLE `'._DB_PREFIX_.'billmate_payment_pclasses` (
                                        `id` int AUTO_INCREMENT,
                                        `order_id` int,
                                        `invoice_fee` decimal(11,2),
                                        `tax_rate` decimal(11,2)
                                        PRIMARY KEY(`id`));',false);


        }
        catch (Exception $e)
        {
            echo $e;
            die();
        }
    }
}