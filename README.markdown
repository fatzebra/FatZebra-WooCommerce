Fat Zebra WooCommerce Plugin
============================

Version 1.0.1 for API Version 1.0

A WordPress plugin to add Fat Zebra support to [WooCommerce](http://www.woothemes.com/woocommerce/) for Australian Merchants.

Dependencies
------------

 * WordPress
 * WooCommerce ~> 1.5

This plugin uses wp_http_request to submit data to Fat Zebra - this means that you do not need to ensure cURL or similar is installed on your site, the function will determine the most suitable technique to use.


Install the plugin
---------------------
There are two methods to install the plugin:

**Copy the file to the WordPress plugins directory:**
 
 
 1. Make a new directory in [SITE_ROOT]/wp-content/plugins called fatzebra-woocommerce
 2. Copy the file class-wc-fatzebra.php to this new directory.
 3. Activate the newly installed plugin in the WordPress Plugin Manager.

**Install the plugin from WordPress (Coming Soon)**

 1. Search for the *WooCommerce Fat Zebra* plugin from the WordPress Plugin Manager.
 2. Install the plugin.
 3. Activate the newly installed plugin.


Configuration
-------------

1. Visit the WooCommerce settings page, and click on the **Payment Gateways** tab.
2. Click on **Fat Zebra** to edit the settings. If you do not see Fat Zebra in the list at the top of the screen make sure you have activated the plugin in the WordPress Plugin Manager.
3. Enable the Payment Method, name it Credit Card (this will show up on the payment page your customer sees) and add in your credentials. Click Save.

You should now be able to test the purchases via Fat Zebra.

Support
-------
If you have any issue with the Fat Zebra Gateway for WooCommerce please contact us at support@fatzebra.com.au and we will be more then happy to help out.

Pull Requests
-------------
If you would like to contribute to the plugin please fork the project, make your changes within a feature branch and then submit a pull request. All pull requests will be reviewed as soon as possible and integrated into the main branch if deemed suitable.