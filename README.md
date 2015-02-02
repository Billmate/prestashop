# Billmate Payment Gateway for Prestashop
By Billmate AB - [https://billmate.se](https://billmate.se/ "billmate.se")

Documentation with instructions on how to setup the plugin can be found [here](http://billmate.se/plugins/manual/Installation_Manual_Prestashop_Billmate.pdf) (Swedish).

## Description

Billmate Gateway is a plugin that extends Prestashop, allowing your customers to get their products first and pay by invoice to Billmate later (https://www.billmate.se/). This plugin utilizes Billmate Invoice, Billmate Card, Billmate Bank and Billmate Part Payment (Standard Integration type).

When the order is passed to Billmate a credit record of the customer is made. If the check turns out all right, Billmate creates an invoice in their system. After you (as the merchant) completes the order in Prestashop, you need to log in to Billmate to approve/send the invoice.

Billmate is a great payment alternative for merchants and customers in Sweden.

## Important Note

This plugin does not currently support campaigns.

### Invoice Fee Handling
Since of version 1.31 the Invoice Fee for Billmate Invoice are added as a simple (hidden) product. This is to match order total in Prestashop and your billmate part payment (in earlier versions the invoice fee only were added to Billmate).

To create a Invoice fee product: 
* Add a simple (hidden) product. Mark it as a taxable product.
* Go to the Billmate Gateway settings page and add the ID of the Invoice Fee product. The ID can be found by hovering the Invoice Fee product on the Products page in Prestashop.



## Installation

Read following information to install these plugins

You will find three plugins billmateInvoice, BillmateCardpay, BillmatePartpayment

extract zip file under prestashop_root/modules

## Changelog

### 1.35.2 (2015-02-02)
2 issues closed and 6 commits
* Fix - If no order status was set, the module will default to the Prestashop default.
* Fix - Fixed a bug if minify was enabled the invoice & part payment module would not work.

### 1.35.1 (2015-01-30)
1 issue closed and 4 commits
* Fix - Updated how auto activate card & bankpayments are processed to contain the correct order id.

### 1.35 (2015-01-28)
Total of 61 issues closed and 80 commits the biggest release yet.

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
