<?php
/**
 * BillMate Class
 *
 * LICENSE: This source file is part of BillMate, that is fully owned by Billmate AB
 * This is not open source. For licensing queries, please contact at info@billmate.se
 *
 * @category Billmate
 * @package Billmate
 * @author Yuksel Findik <yuksel@efinance.se>
 * @copyright 2013-2014 Billmate AB
 * @license Proprietary and fully owned by Billmate AB
 * @version 0.5.8
 * @link http://www.billmate.se
 *
 * History:
 * 0.0.1 20130318 Yuksel Findik: First Version
 * Dependencies:
 *
 *  xmlrpc-3.0.0.beta/lib/xmlrpc.inc
 *      from {@link http://phpxmlrpc.sourceforge.net/}
 *
 * xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc
 *      from {@link http://phpxmlrpc.sourceforge.net/}
 *
 */

class preOrder extends ObjectModelCore{
	/** @var integer Delivery address id */
	public $id_address_delivery;
	public $id;
	/** @var integer Invoice address id */
	public $id_address_invoice;

	public $id_shop_group;

	public $id_shop;

	/** @var integer Cart id */
	public $id_cart;

	/** @var integer Currency id */
	public $id_currency;

	/** @var integer Language id */
	public $id_lang;

	/** @var integer Customer id */
	public $id_customer;

	/** @var integer Carrier id */
	public $id_carrier;

	/** @var integer Order State id */
	public $current_state;

	/** @var string Secure key */
	public $secure_key;

	/** @var string Payment method */
	public $payment;

	/** @var string Payment module */
	public $module;

	/** @var float Currency exchange rate */
	public $conversion_rate;

	/** @var boolean Customer is ok for a recyclable package */
	public $recyclable = 1;

	/** @var boolean True if the customer wants a gift wrapping */
	public $gift = 0;

	/** @var string Gift message if specified */
	public $gift_message;

	/** @var boolean Mobile Theme */
	public $mobile_theme;

	/**
	 * @var string Shipping number
	 * @deprecated 1.5.0.4
	 * @see OrderCarrier->tracking_number
	 */
	public $shipping_number;

	/** @var float Discounts total */
	public $total_discounts;

	public $total_discounts_tax_incl;
	public $total_discounts_tax_excl;

	/** @var float Total to pay */
	public $total_paid;

	/** @var float Total to pay tax included */
	public $total_paid_tax_incl;

	/** @var float Total to pay tax excluded */
	public $total_paid_tax_excl;

	/** @var float Total really paid @deprecated 1.5.0.1 */
	public $total_paid_real;

	/** @var float Products total */
	public $total_products;

	/** @var float Products total tax included */
	public $total_products_wt;

	/** @var float Shipping total */
	public $total_shipping;

	/** @var float Shipping total tax included */
	public $total_shipping_tax_incl;

	/** @var float Shipping total tax excluded */
	public $total_shipping_tax_excl;

	/** @var float Shipping tax rate */
	public $carrier_tax_rate;

	/** @var float Wrapping total */
	public $total_wrapping;

	/** @var float Wrapping total tax included */
	public $total_wrapping_tax_incl;

	/** @var float Wrapping total tax excluded */
	public $total_wrapping_tax_excl;

	/** @var integer Invoice number */
	public $invoice_number;

	/** @var integer Delivery number */
	public $delivery_number;

	/** @var string Invoice creation date */
	public $invoice_date;

	/** @var string Delivery creation date */
	public $delivery_date;

	/** @var boolean Order validity (paid and not canceled) */
	public $valid;

	/** @var string Object creation date */
	public $date_add;

	/** @var string Object last modification date */
	public $date_upd;

	/**
	 * @var string Order reference, this reference is not unique, but unique for a payment
	 */
	public $reference;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'orders',
		'primary' => 'id_order',
		'fields' => array(
			'id_address_delivery' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_address_invoice' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_cart' => 					array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),
			'id_currency' => 				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_shop_group' => 				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'id_shop' => 					array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'id_lang' => 					array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_customer' => 				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_carrier' => 				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'current_state' => 				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'secure_key' => 				array('type' => self::TYPE_STRING, 'validate' => 'isMd5'),
			'payment' => 					array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
			'module' => 					array('type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true),
			'recyclable' => 				array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'gift' => 						array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'gift_message' => 				array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
			'mobile_theme' => 				array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
			'total_discounts' =>			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_discounts_tax_incl' =>	array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_discounts_tax_excl' =>	array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_paid' => 				array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
			'total_paid_tax_incl' => 		array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_paid_tax_excl' => 		array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_paid_real' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
			'total_products' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
			'total_products_wt' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
			'total_shipping' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_shipping_tax_incl' =>	array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_shipping_tax_excl' =>	array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'carrier_tax_rate' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
			'total_wrapping' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_wrapping_tax_incl' =>	array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'total_wrapping_tax_excl' =>	array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
			'shipping_number' => 			array('type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'),
			'conversion_rate' => 			array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
			'invoice_number' => 			array('type' => self::TYPE_INT),
			'delivery_number' => 			array('type' => self::TYPE_INT),
			'invoice_date' => 				array('type' => self::TYPE_DATE),
			'delivery_date' => 				array('type' => self::TYPE_DATE),
			'valid' => 						array('type' => self::TYPE_BOOL),
			'reference' => 					array('type' => self::TYPE_STRING),
			'date_add' => 					array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'date_upd' => 					array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
		),
	);

	protected $webserviceParameters = array(
		'objectMethods' => array('add' => 'addWs'),
		'objectNodeName' => 'order',
		'objectsNodeName' => 'orders',
		'fields' => array(
			'id_address_delivery' => array('xlink_resource'=> 'addresses'),
			'id_address_invoice' => array('xlink_resource'=> 'addresses'),
			'id_cart' => array('xlink_resource'=> 'carts'),
			'id_currency' => array('xlink_resource'=> 'currencies'),
			'id_lang' => array('xlink_resource'=> 'languages'),
			'id_customer' => array('xlink_resource'=> 'customers'),
			'id_carrier' => array('xlink_resource'=> 'carriers'),
			'current_state' => array('xlink_resource'=> 'order_states'),
			'module' => array('required' => true),
			'invoice_number' => array(),
			'invoice_date' => array(),
			'delivery_number' => array(),
			'delivery_date' => array(),
			'valid' => array(),
			'date_add' => array(),
			'date_upd' => array(),
		),
		'associations' => array(
			'order_rows' => array('resource' => 'order_row', 'setter' => false, 'virtual_entity' => true,
				'fields' => array(
					'id' =>  array(),
					'product_id' => array('required' => true),
					'product_attribute_id' => array('required' => true),
					'product_quantity' => array('required' => true),
					'product_name' => array('setter' => false),
					'product_price' => array('setter' => false),
					'unit_price_tax_incl' => array('setter' => false),
					'unit_price_tax_excl' => array('setter' => false),
				)),
		),

	);

	protected $_taxCalculationMethod = PS_TAX_EXC;

	protected static $_historyCache = array();

	public function __construct($id = null, $id_lang = null)
	{
		parent::__construct($id, $id_lang);

		$is_admin = (is_object(Context::getContext()->controller) && Context::getContext()->controller->controller_type == 'admin');
		if ($this->id_customer && !$is_admin)
		{
			$customer = new Customer((int)($this->id_customer));
			$this->_taxCalculationMethod = Group::getPriceDisplayMethod((int)$customer->id_default_group);
		}
		else
			$this->_taxCalculationMethod = Group::getDefaultPriceDisplayMethod();
	}
	
	public function add($autodate = true, $null_values = true){
		return parent::add($autodate, $null_values);
	}
	public function addCartRule($id_cart_rule, $name, $values, $id_order_invoice = 0, $free_shipping = null)
	{
		$order_cart_rule = new OrderCartRule();
		$order_cart_rule->id_order = $this->id;
		$order_cart_rule->id_cart_rule = $id_cart_rule;
		$order_cart_rule->id_order_invoice = $id_order_invoice;
		$order_cart_rule->name = $name;
		$order_cart_rule->value = $values['tax_incl'];
		$order_cart_rule->value_tax_excl = $values['tax_excl'];
		if ($free_shipping === null)
		{
			$cart_rule = new CartRule($id_cart_rule);
			$free_shipping = $cart_rule->free_shipping;
		}
		$order_cart_rule->free_shipping = (int)$free_shipping;
		$order_cart_rule->add();
	}
}