<?php
	/*
     * Created by PhpStorm.
     * User: jesper
     * Date: 15-03-20
     * Time: 17:02
     * @author Jesper Johansson jesper@boxedlogistics.se
     * @copyright Billmate AB 2015
     */

	class BillmateFlags {

		const NO_FLAG = 0;
		const FEMALE = 0;
		const MALE = 1;
		const ACCEPTED = 1;
		const PENDING = 2;
		const DENIED = 3;
		const GA_ALL = 1;
		const GA_LAST = 2;
		const GA_GIVEN = 5;
		const PRINT_1000 = 1;
		const PRINT_100 = 2;
		const PRINT_10 = 4;
		const IS_SHIPMENT = 8;
		const IS_HANDLING = 16;
		const INC_VAT = 32;
		const CHECKOUT_PAGE = 0;
		const PRODUCT_PAGE = 1;
		const IS_BILLING = 100;
		const IS_SHIPPING = 101;
		const TEST_MODE = 2;
		const PCLASS_INVOICE = -1;
		const AUTO_ACTIVATE = 1;
		const PRE_PAY = 8;
		const SENSITIVE_ORDER = 1024;
		const RETURN_OCR = 8192;
		const NORMAL_SHIPMENT = 1;
		const EXPRESS_SHIPMENT = 2;
		const M_PHONE_TRANSACTION = 262144;
		const M_SEND_PHONE_PIN = 524288;
		const NEW_AMOUNT = 0;
		const ADD_AMOUNT = 1;
		const RSRV_SEND_BY_MAIL = 4;
		const RSRV_SEND_BY_EMAIL = 8;
		const RSRV_PRESERVE_RESERVATION = 16;
		const RSRV_SENSITIVE_ORDER = 32;
		const RSRV_PHONE_TRANSACTION = 512;
		const RSRV_SEND_PHONE_PIN = 1024;
	}