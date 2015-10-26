<?php
	/**
	 * Created by PhpStorm.* User: jesper* Date: 15-03-17 * Time: 15:09
	 *
	 * @author    Jesper Johansson jesper@boxedlogistics.se
	 * @copyright Billmate AB 2015
	 * @license   OpenSource
	 */

	require_once(_PS_MODULE_DIR_.'/billmategateway/interfaces/SetupFileInterface.php');

	class InitInstall implements SetupFileInterface {

		/**
		 * @var
		 */
		private $db;

		public function __construct($db)
		{
			$this->db = $db;
		}

		public function install()
		{
			try
			{
				$this->db->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'billmate_rno;',false);
				$this->db->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'billmate_payment_pclasses`;',false);
				$drop = $this->db->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'billmate_payment_pclasses`;CREATE TABLE `'._DB_PREFIX_.'billmate_payment_pclasses` (
                                        `id` int AUTO_INCREMENT,
                                        `eid` int,
                                        `paymentplanid` int,
                                        `description` varchar(255),
                                        `nbrofmonths` int,
                                        `startfee` decimal(11,2),
                                        `handlingfee` decimal(11,2),
                                        `minamount` decimal(11,2),
                                        `maxamount` decimal(11,2),
                                        `interestrate` decimal(11,2),
                                        `monthlycost` decimal(11,2),
                                        `type` tinyint,
                                        `expirydate` date,
                                        `country` varchar(255),
                                        `language` varchar(255),
                                        `currency` varchar(10),
                                        PRIMARY KEY(`id`));',false);
				$this->db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatebankpay";');
				$this->db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatepartpay";');
				$this->db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmatecardpay";');
				$this->db->execute('DELETE FROM '._DB_PREFIX_.'module WHERE name = "billmateinvoice";');
				$this->db->execute('INSERT INTO '._DB_PREFIX_.'module (name,active,version) VALUES("billmatebankpay",1,"2.0.0");');
				$this->db->execute('INSERT INTO '._DB_PREFIX_.'module (name,active,version) VALUES("billmateinvoice",1,"2.0.0");');
				$this->db->execute('INSERT INTO '._DB_PREFIX_.'module (name,active,version) VALUES("billmatecardpay",1,"2.0.0");');
				$this->db->execute('INSERT INTO '._DB_PREFIX_.'module (name,active,version) VALUES("billmatepartpay",1,"2.0.0");');

			}
			catch (Exception $e)
			{
				echo $e;
				die();
			}

		}
	}