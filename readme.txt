=== WooCommerce Fat Zebra Gateway ===
Contributors: fatzebra
Tags: ecommerce, woocommerce, fatzebra, payment gateway, australia, subscriptions, wcsubscriptions
Requires at least: 3.8
Tested up to: 4.5
Stable tag: trunk

The WooCommerce Fat Zebra Gateway plugin enabled integration with WooCommerce and the Fat Zebra Payment Gateway (for Australian Merchants).

Now supports WooCommerce Subscriptions.

== Description ==

This plugin provides integration with WooCommerce and the Fat Zebra Payment Gateway, an Australian online payment gateway.

Support has now been added for WooCommerce Subscriptions, allowing you to create recurring products in your WooCommerce store.

![WooCommerce Subscriptions](http://wcdocs.woothemes.com/wp-content/uploads/2012/06/supports-subscriptions-badge.png)

Tested with WooCommerce version 2.5.

Visit [https://www.fatzebra.com.au](https://www.fatzebra.com.au "Fat Zebra Online Payment Gateway") for more details on using Fat Zebra.


== Installation ==

There are two methods to install the plugin:

**Copy the file to the WordPress plugins directory:**

1. Make a new directory in [SITE_ROOT]/wp-content/plugins called woocommerce-fat-zebra-gateway
2. Copy the file class-wc-fatzebra.php to this new directory.
3. Activate the newly installed plugin in the WordPress Plugin Manager.


**Install the plugin from WordPress:**

1. Search for the WooCommerce Fat Zebra plugin from the WordPress Plugin Manager.
2. Install the plugin.
3. Activate the newly installed plugin.

== Configuration ==

1. Visit the WooCommerce settings page, and click on the Payment Gateways tab.
2. Click on Fat Zebra to edit the settings. If you do not see Fat Zebra in the list at the top of the screen make sure you have activated the plug in in the WordPress Plug in Manager.
3. Enable the Payment Method, name it Credit Card (this will show up on the payment page your customer sees) and add in your credentials. Click Save.
4. Optional: Display the Fat Zebra logo on your checkout page - we will love you forever if you choose this option.

You should now be able to test the purchases via Fat Zebra.

== Changelog ==

= 1.0 =
* Initial release

= 1.0.2 =
* Added support for Fat Zebra logo and credit card icons
* Removed error tracing accidentally left in

= 1.3.0 =
* Added support for 'deferred' payments where card details can be authorized but processed after manual review.
* Fixed bug with reference to WooCommerce Subscriptions when the extension is not installed.

= 1.3.1 =
* Disabled emails when charging card - emails should be triggered manually by the store owner

= 1.3.2 =
* Fixed issue with subscription transactions not sending valid IP.
* Fixed general typo.

= 1.3.3 =
* Added help icon for security code
* Removed style for logo and added CSS in style block to allow for override.

= 1.3.4 =
* Fixed lazy PHP tag (<?) causing issues in PHP 5.3.3

= 1.3.5 =
* Fixed support for 2.0 and tested on both 2.0 and 1.6

= 1.3.6 =
* Updated supports attributes to allow for amount changes, removing the notification when viewing/editing an order.

= 1.4.0 =
* Fixed warning regarding changing subscription amount
* Added support for updating the subscription payment details
* Fixed up the rendering of the credit card form
* Updated image rendering to use the plugins_url method call in order to work properly with HTTPS etc
* Added support for tokenizing only if a subscription has a $0 initial payment amount
* Added the ability to create a new subscription and copy over the Fat Zebra card token
* The Fat Zebra card token is now stored as fatzebra_card_token, instead of _fatzebra_card_token, allowing it to be set by the store owner if necessary
* Added script referencecs instead of inlining JS etc.
* Default IP to 127.0.0.1 if not set in the order meta.

= 1.4.1 =
* Added support for MasterPass checkout
* Added script to convert 2-digit date to 4-digit date on blur automatically

= 1.4.2 =
* Bug fix for WooCommerce Subscriptions where the subscription processing hook was being added in twice, resulting in a successful order, and then a failed order due to reference collision.

= 1.4.3 =
* Bug fix to handle missing card holder name when updating payment details for subscriptions

= 1.4.4 =
* Updated to use the default card form provided by WooCommerce
* Fixed issue with redirect upon successful payment

= 1.5.0 =
* Added support for Direct Post tokenization, sending the card details directly to Fat Zebra instead of via the merchants web server
* Added support for Visa Checkout as payment option
* Added support for currencies other then AUD (based on order currency)

= 1.5.1 =
* Fixed issue where visa checkout express option wasn't working properly

= 1.5.2 =
* Fixed deprecations and errors introduced with version mismatch against WC Subscriptions
* Updated configuration to disable Visa Checkout and MasterPass by default
* Fixed Visa Checkout anti-clickjack from running on pages which are not checkout and cart

= 1.5.4 =
* Updated reference prefix used during testing

= 1.5.5 =
* Fix incorrect field breaking Direct Post (As per https://wordpress.org/support/topic/bug-152-breaks-direct-post)
* Empty Credit Card Token when Credit Card details are modified, fixes problem where subsequent payment attempts with a new credit card continue to use the original tokenized card, even if original card has been declined


= 1.5.7 =
* Fixed issue with WC()->sesison being nil causing orders and subscriptions to not show up

= 1.5.9 =
* Fixed warning generated by visacheckout forced gateway

= 1.5.10 =
* Fixed issue with Direct Post in WooCommerce 2.6
* Setup read for Amex Express Checkout

= 1.5.12 =
* Fixed refund not processing due to change in storage of transaction ID

== Support ==

If you have any issue with the Fat Zebra Gateway for WooCommerce please contact us at support@fatzebra.com.au and we will be more then happy to help out.
