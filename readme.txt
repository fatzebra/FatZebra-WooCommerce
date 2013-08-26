=== WooCommerce Fat Zebra Gateway ===
Contributors: fatzebra
Tags: ecommerce, woocommerce, fatzebra, payment gateway, australia, subscriptions, wcsubscriptions
Requires at least: 3.3
Stable tag: trunk

The WooCommerce Fat Zebra Gateway plugin enabled integration with WooCommerce and the Fat Zebra Payment Gateway (for Australian Merchants).

Now supports WooCommerce Subscriptions.

== Description ==

This plugin provides integration with WooCommerce and the Fat Zebra Payment Gateway, an Australian online payment gateway.

Support has now been added for WooCommerce Subscriptions, allowing you to create recurring products in your WooCommerce store.

![WooCommerce Subscriptions](http://wcdocs.woothemes.com/wp-content/uploads/2012/06/supports-subscriptions-badge.png)

Tested with WooCommerce version 1.6 and 2.0.2.

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

== Support ==

If you have any issue with the Fat Zebra Gateway for WooCommerce please contact us at support@fatzebra.com.au and we will be more then happy to help out.
