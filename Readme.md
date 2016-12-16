# Billmate Payment Gateway for Prestashop
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")

## Documentation
[Installation manual in English](http://billmate.se/plugins/manual/Installation_Manual_Prestashop_Billmate.pdf)

[Installation manual in Swedish](http://billmate.se/plugins/manual/Installationsmanual_Prestashop_Billmate.pdf)

## Description

Billmate Gateway is a plugin that extends Prestashop, allowing your customers to get their products first and pay by invoice to Billmate later (http://www.billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in Prestashop, you need to log in to Billmate to approve/send the invoice.

Billmate is a great payment alternative for merchants and customers in Sweden.

## Important Note
* This plugin does not currently support campaigns.
* The automatic order activation on status change is supported from PrestaShop version 1.5 and above.
* This module doesnt support 1.4 anymore.
* Part credit seems to be an issue before prestashop 1.6
* Please let the decimals for currencies be 2 decimals, to prevent rounding issues.


## COMPATIBILITY PrestaShop versions
1.5.3.1 - 1.6.1.7

## Checkout Compatibility
* PrestaShop default checkout 1.5.3.1 - 1.6.1.7
* One page checkout for Prestashop Module 2.3.6 - 2.3.10
  http://addons.prestashop.com/en/6841-one-page-checkout-for-prestashop.html

## Installation

Read following information to install these plugins
* Uninstall and remove the old plugin directories.
* You will find five folders in the Zip-Archive. Upload to your modules folder. 
* Install our payment plugin.
* Fill in your Billmate ID and Secret, activate the payment methods thats suits you well. 
* Hit save button and it should be done.

## Testing
Our plugin is tested with [Browserstack](http://www.browserstack.com)

## FAQ
* What checkouts do Billmate supports?
We support Prestashops built-in checkout in 1-step and 5-step settings. We also supports One Page Checkout for Prestashop Module (http://addons.prestashop.com/en/6841-one-page-checkout-for-prestashop.html)

## Changelog

### 2.1.13(2016-12-16)
* fix - Multiple addresses on cart.
* enhancement - Default option selelected in Cardpayment for Authorization Method. 


### 2.1.11 (2016-10-28)
* Enhancement - Feature to add prestashop messages to invoice generated by Billmate

### 2.1.10 (2016-10-26)
* Fix - one payment method for Onepagecheckout.

### 2.1.9 (2016-10-21)
* Fix - Verify zipcode. 

### 2.1.8 (2016-10-17)
* Fix - Compatibility with PrestaShop 1.5.3.1
* Enhancement - Link to our manuals.
* Fix - Activate invoice statuses visible selection.

### 2.1.6(2016-08-12)
* Fix - Change classnames to play nicer with other payment modules

### 2.1.5(2016-07-19)
* Enhancement - Message when payment fails.
* Fix - Uninstall method.

### 2.1.4(2016-05-23)
* Fix - Check multiple firstnames.

### 2.1.3(2016-05-09)
* Fix - Discount name on invoice. 
* Enhancement - localized partpayment logo.

### 2.1.2(2016-04-25)
* Fix - Multiple payments.

### 2.1.1(2016-04-07)

* Fix - Pno visible logged in.
* Fix - Secure links.

### 2.1(2016-04-06)

* Enhancement - Credit invoice from store.
* Enhancement - Partcredit from store.
* Fix - Improved translations.
* Enhancement - Improved our logos.
* Enhancement - Changed address check flow.
* Enhancement - Improved currency support.
* Enhancement - Automatically updating paymentplans when expiring.

### 2.0.9(2016-01-26)

* Fix - Optimized Billmate.php

### 2.0.8(2016-01-18)

* Fix - Totals rounding.

### 2.0.7(2015-11-04)

* Enhancement - Billmate support plugins. 

### 2.0.6(2015-10-27)

* Fix - Product quantity calculation.

### 2.0.5(2015-10-26)

* Fix - mysql version 5.6.23 compatibility. 

### 2.0.4(2015-10-20)

* Fix - One page checkout with different amount of buttons.

### 2.0.3(2015-10-14)

* Fix - Company related stuff in checkout.

### 2.0.2(2015-10-07)
* Fix - Billmate Version
* Fix - Javascript issue with onepage checkout.

### 2.0.1(2015-09-29)
* Fix - Cancel callback 
* Fix - Logic for activate payment


### 2.0 (2015-09-28)
84 issues closed and 127 commits.

* Feature - validate credentials.
* Fix - Discount is not applied to invoice fee anymore.
* Enhancement - Invoice fee is not a product anymore.
* Enhancement - Get Address on checkout page.
* Enhancement - Choice for order id or reference id as Billmate order id.
* Enhancement - Localized Logos.
* Enhancement - Ajax in checkout to validate Address.
* Enhancement - Add variable product selection in product title on invoice.
* Improvement - Better Currency support.
* Improvement - Better Country support.
* Styling - Nicer Address validation popup.
* Tweak - One module instead of four.
* Tweak - Consequent Naming Conventions.
* Enhancement - Improved compatibility with Delayed delivery.
* Enhancement - Improved compatibility with multiple store locations.
* Enhancement - Improved checkout flow.
* Enhancement - Billmate ID and Secret only needs to be filled in once.

### 1.36 (2015-03-25)
25 issues closed and 58 commits.

* Feature - Activate the order in Billmate online automatically by setting a specific order status by enabling the setting for it in each specific payment module.
* Fix - Small translation fix for 3D Secure setting.
* Fix - No more double breadcrumbs in Billmate Bank redirect page.
* Fix - The module now works together with the discount type of free gift.
* Fix - Clarified that the invoice fee you enter in admin is excluding VAT.
* Fix - Improved support for other currencies.
* Fix - Invoice fee is sent in correct currency with auto converting.
* Fix - Some layout improvements.
* Fix - Some translation improvements.

### 1.35.2 (2015-02-02)
3 issues closed and 13 commits.

* Fix - If no order status was set, the module would stop working. Now it will default to the Prestashop standard order accepted status if no status is defined.
* Fix - Fixed a bug if minify was enabled the invoice & part payment module would not work.
* Fix - Increased the z-index of the billmatepopup to 9999, it should now always be on top.

### 1.35.1 (2015-01-30)
1 issue closed and 4 commits.

* Fix - Updated how auto activate card & bankpayments are processed to contain the correct order id.

### 1.35 (2015-01-28)
Total of 61 issues closed and 80 commits, the biggest release yet.

* Fix - Made the styling better overall through the plugin.
* Fix - Improved compatibility with Prestashop 1.4.
* Fix - Improved compatibility with Prestashop 1.6.
* Fix – Texts are now bettered formulated and standardized.
* Fix – Hover effects improved for better UI experience.
* Fix – Improved the rounding of totals.
* Fix – Checkbox for accept email invoices is now check as standard.
* Fix – Fixed various encoding issues on error messages.
* Fix – Improved translations.
* Fix – If callback is registered before the redirect, everything now works as it should.
* Fix – Sends in the cart id together with a timestamp when order is created on card/bank, then updates to correct order ID when the order is created inside Prestashop.
* Fix – Billmatepopup now has a z-index of 999 and should now always be displayed on top.
* Fix – Specific prices on articles is not deleted by the plugin (Sorry for that one).
* Feature – Invoice fee is now displayed on the checkout page.
* Tweak – Part payment only displays in front end of store if PClasses exist.
* Tweak – Changed company name from eFinance Nordic AB to Billmate AB.
