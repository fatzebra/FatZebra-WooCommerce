<?php

/*
Plugin Name: WooCommerce Fat Zebra Gateway
Plugin URI: https://www.fatzebra.com.au/support/supported-carts
Description: Extends WooCommerce with Fat Zebra payment gateway along with WooCommerce subscriptions support.
Version: 1.5.12
Author: Fat Zebra
Author URI: https://www.fatzebra.com.au
*/

/* Copyright (C) 2012 Fat Zebra Pty. Ltd.

  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
  to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
  of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
  IN THE SOFTWARE.
*/

add_action('plugins_loaded', 'fz_init', 0);

function fz_init() {
  if (!class_exists('WC_Payment_Gateway')) {
    ?>
    <div id="message" class="error">
      <p><?php printf(__('%sWooCommerce Fat Zebra Extension is inactive.%s The %sWooCommerce plugin%s must be active for the WooCommerce Fat Zebra Extension to work. Please %sinstall & activate WooCommerce%s', 'wc_fatzebra'), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url('plugins.php') . '">', '&nbsp;&raquo;</a>'); ?></p>
    </div>
    <?php
    return;
  }

  global $woocommerce;
  // Check the WooCommerce version...
  if (!version_compare($woocommerce->version, '2.1', ">=")) {
    ?>
    <div id="message" class="error">
      <p><?php printf(__('%sWooCommerce Fat Zebra Extension is inactive.%s The version of WooCommerce you are using is not compatible with this verion of the Fat Zebra Extension. Please update WooCommerce to version 2.1 or greater, or remove this version of the Fat Zebra Extension and install an older version.', 'wc_fatzebra'), '<strong>', '</strong>'); ?></p>
    </div>
    <?php
    return;
  }

  include("class-wc-fatzebra-masterpass.php");
  fz_masterpass_init();
  include("class-wc-fatzebra-visacheckout.php");
  fz_visacheckout_init();
  include("class-wc-fatzebra-amex-eco.php");
  fz_amex_eco_init();

  class WC_FatZebra extends WC_Payment_Gateway_CC {

    public function __construct() {
      $this->id = 'fatzebra';
      $this->icon = apply_filters('woocommerce_fatzebra_icon', '');
      $this->has_fields = true;
      $this->method_title = __('Fat Zebra', 'woocommerce');
      $this->version = "1.5.12";

      $this->api_version = "1.0";
      $this->live_url = "https://gateway.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->sandbox_url = "https://gateway.sandbox.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->supports = array('subscriptions', 'products', 'refunds', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_amount_changes', 'subscription_payment_method_change', 'subscription_date_changes');
      $this->params = array();
      $this->country_map = array("AD" => "AND", "AE" => "ARE", "AF" => "AFG", "AG" => "ATG", "AI" => "AIA", "AL" => "ALB", "AM" => "ARM", "AN" => "ANT", "AO" => "AGO", "AQ" => "ATA", "AR" => "ARG", "AS" => "ASM", "AT" => "AUT", "AU" => "AUS", "AW" => "ABW", "AX" => "ALA", "AZ" => "AZE", "BA" => "BIH", "BB" => "BRB", "BD" => "BGD", "BE" => "BEL", "BF" => "BFA", "BG" => "BGR", "BH" => "BHR", "BI" => "BDI", "BJ" => "BEN", "BL" => "BLM", "BM" => "BMU", "BN" => "BRN", "BO" => "BOL", "BQ" => "BES", "BR" => "BRA", "BS" => "BHS", "BT" => "BTN", "BV" => "BVT", "BW" => "BWA", "BY" => "BLR", "BZ" => "BLZ", "CA" => "CAN", "CC" => "CCK", "CD" => "COD", "CF" => "CAF", "CG" => "COG", "CH" => "CHE", "CI" => "CIV", "CK" => "COK", "CL" => "CHL", "CM" => "CMR", "CN" => "CHN", "CO" => "COL", "CR" => "CRI", "CU" => "CUB", "CV" => "CPV", "CW" => "CUW", "CX" => "CXR", "CY" => "CYP", "CZ" => "CZE", "DE" => "DEU", "DJ" => "DJI", "DK" => "DNK", "DM" => "DMA", "DO" => "DOM", "DZ" => "DZA", "EC" => "ECU", "EE" => "EST", "EG" => "EGY", "EH" => "ESH", "ER" => "ERI", "ES" => "ESP", "ET" => "ETH", "FI" => "FIN", "FJ" => "FJI", "FK" => "FLK", "FM" => "FSM", "FO" => "FRO", "FR" => "FRA", "GA" => "GAB", "GB" => "GBR", "GD" => "GRD", "GE" => "GEO", "GF" => "GUF", "GG" => "GGY", "GH" => "GHA", "GI" => "GIB", "GL" => "GRL", "GM" => "GMB", "GN" => "GIN", "GP" => "GLP", "GQ" => "GNQ", "GR" => "GRC", "GS" => "SGS", "GT" => "GTM", "GU" => "GUM", "GW" => "GNB", "GY" => "GUY", "HK" => "HKG", "HM" => "HMD", "HN" => "HND", "HR" => "HRV", "HT" => "HTI", "HU" => "HUN", "ID" => "IDN", "IE" => "IRL", "IL" => "ISR", "IM" => "IMN", "IN" => "IND", "IO" => "IOT", "IQ" => "IRQ", "IR" => "IRN", "IS" => "ISL", "IT" => "ITA", "JE" => "JEY", "JM" => "JAM", "JO" => "JOR", "JP" => "JPN", "KE" => "KEN", "KG" => "KGZ", "KH" => "KHM", "KI" => "KIR", "KM" => "COM", "KN" => "KNA", "KP" => "PRK", "KR" => "KOR", "KW" => "KWT", "KY" => "CYM", "KZ" => "KAZ", "LA" => "LAO", "LB" => "LBN", "LC" => "LCA", "LI" => "LIE", "LK" => "LKA", "LR" => "LBR", "LS" => "LSO", "LT" => "LTU", "LU" => "LUX", "LV" => "LVA", "LY" => "LBY", "MA" => "MAR", "MC" => "MCO", "MD" => "MDA", "ME" => "MNE", "MF" => "MAF", "MG" => "MDG", "MH" => "MHL", "MK" => "MKD", "ML" => "MLI", "MM" => "MMR", "MN" => "MNG", "MO" => "MAC", "MP" => "MNP", "MQ" => "MTQ", "MR" => "MRT", "MS" => "MSR", "MT" => "MLT", "MU" => "MUS", "MV" => "MDV", "MW" => "MWI", "MX" => "MEX", "MY" => "MYS", "MZ" => "MOZ", "NA" => "NAM", "NC" => "NCL", "NE" => "NER", "NF" => "NFK", "NG" => "NGA", "NI" => "NIC", "NL" => "NLD", "NO" => "NOR", "NP" => "NPL", "NR" => "NRU", "NU" => "NIU", "NZ" => "NZL", "OM" => "OMN", "PA" => "PAN", "PE" => "PER", "PF" => "PYF", "PG" => "PNG", "PH" => "PHL", "PK" => "PAK", "PL" => "POL", "PM" => "SPM", "PN" => "PCN", "PR" => "PRI", "PS" => "PSE", "PT" => "PRT", "PW" => "PLW", "PY" => "PRY", "QA" => "QAT", "RE" => "REU", "RO" => "ROU", "RS" => "SRB", "RU" => "RUS", "RW" => "RWA", "SA" => "SAU", "SB" => "SLB", "SC" => "SYC", "SD" => "SDN", "SE" => "SWE", "SG" => "SGP", "SH" => "SHN", "SI" => "SVN", "SJ" => "SJM", "SK" => "SVK", "SL" => "SLE", "SM" => "SMR", "SN" => "SEN", "SO" => "SOM", "SR" => "SUR", "SS" => "SSD", "ST" => "STP", "SV" => "SLV", "SX" => "SXM", "SY" => "SYR", "SZ" => "SWZ", "TC" => "TCA", "TD" => "TCD", "TF" => "ATF", "TG" => "TGO", "TH" => "THA", "TJ" => "TJK", "TK" => "TKL", "TL" => "TLS", "TM" => "TKM", "TN" => "TUN", "TO" => "TON", "TR" => "TUR", "TT" => "TTO", "TV" => "TUV", "TW" => "TWN", "TZ" => "TZA", "UA" => "UKR", "UG" => "UGA", "UM" => "UMI", "US" => "USA", "UY" => "URY", "UZ" => "UZB", "VA" => "VAT", "VC" => "VCT", "VE" => "VEN", "VG" => "VGB", "VI" => "VIR", "VN" => "VNM", "VU" => "VUT", "WF" => "WLF", "WS" => "WSM", "YE" => "YEM", "YT" => "MYT", "ZA" => "ZAF", "ZM" => "ZMB", "ZW" => "ZWE");

      // Define user set variables
      $this->title = "Credit Card";
      $this->description = in_array("description", $this->settings) ? $this->settings['description'] : "";

      // Load the form fields.
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();

      if ($this->direct_post_enabled()) {
        array_push($this->supports, 'tokenization');
      }

      // Actions
      add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); // < 2.0
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options')); //> 2.0
      add_action('scheduled_subscription_payment_fatzebra', array(&$this, 'scheduled_subscription_payment'), 10, 3);
      // add_action('woocommerce_order_actions', array(&$this, 'add_process_deferred_payment_button'), 99, 1);
    }

    /**
     * Indicates if direct post is enabled/configured or not
     */
    function direct_post_enabled() {
      return $this->settings['use_direct_post'] == 'yes' && !is_null($this->settings['shared_secret']);
    }

    /**
     * Indicates if we should send fraud data
     */
    function fraud_detection_enabled() {
      return $this->settings['fraud_data'] == 'yes';
    }

    /**
     * Returns the direct post URL
     */
    function get_direct_post_url() {
      $sandbox_mode = $this->settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'
      $url = $sandbox_mode ? $this->sandbox_url : $url = $this->live_url;
      // Replace the URL with the tokenize method and re-create the order text (json payload)
      $url = str_replace("purchases", "credit_cards", $url);
      $url = str_replace("v1.0", "v2", $url);
      $url = $url . "/direct/" . $this->settings["username"] . ".json";

      return $url;
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields()
    {

      $this->form_fields = array(
        'enabled' => array(
          'title' => __('Enable/Disable', 'woocommerce'),
          'type' => 'checkbox',
          'label' => __('Enable Fat Zebra', 'woocommerce'),
          'default' => 'yes'
        ),
        'test_mode' => array(
          'title' => __('Test Mode', 'woocommerce'),
          'type' => 'checkbox',
          'description' => __('Switches the gateway to live mode.', 'woocommerce'),
          'default' => "yes"
        ),
        'sandbox_mode' => array(
          'title' => __('Sandbox Mode', 'woocommerce'),
          'type' => "checkbox",
          'description' => __('Switches the gateway URL to the sandbox URL', "woocommerce"),
          'default' => "yes"
        ),
        'use_direct_post' => array(
          'title' => __('Use Direct Post', 'woocommerce'),
          'type' => 'checkbox',
          'description' => 'Uses the Direct Post method for payments which prevents card data from being sent to your web server.',
          'default' => 'no'
        ),
        'show_logo' => array(
          'title' => __("Show Fat Zebra Logo", 'woocommerce'),
          'type' => 'checkbox',
          'description' => __("Shows or hides the 'Fat Zebra Certified' logo on the payment form", "woocommerce"),
          'default' => "yes"
        ),
        'show_card_logos' => array(
          'title' => __("Show credit card logos", 'woocommerce'),
          'type' => 'multiselect',
          'description' => "Shows or hides the credit card icons (AMEX, Visa, Discover, JCB etc). <a href=\"http://www.iconshock.com/credit-card-icons/\">Credit Card Icons by iconshock</a>",
          'default' => array("visa", "mastercard"),
          "options" => array("visa" => "VISA", "mastercard" => "MasterCard", "american_express" => "AMEX", "jcb" => "JCB"), //, "diners_club" => "Diners", "discover" => "Discover")
        ),
        'username' => array(
          'title' => __("Gateway Username", "woocommerce"),
          'type' => 'text',
          'description' => __("The Gateway Authentication Username", "woocommerce"),
          'default' => "test"
        ),
        'token' => array(
          'title' => __("Gateway Token", "woocommerce"),
          'type' => "text",
          'description' => __("The Gateway Authentication Token", "woocommerce"),
          'default' => "test"
        ),
        'shared_secret' => array(
          'title' => __("Gateway Shared Secret", "woocommerce"),
          'type' => "text",
          'description' => __("The Gateway Shared Secret - Required for Direct Post", "woocommerce"),
          'default' => ""
        ),
        // 'deferred_payments' => array(
        //   'title' => __("Enable Deferred Payments", "woocommerce"),
        //   'type' => 'checkbox',
        //   'description' => __("Deferred payments enable you to capture the customers card details in Fat Zebra's system and process them at a later date (for example, once you have reviewed the order for high-risk products). Note: Deferred Payments cannot be used with WooCommerce Subscription - any subscriptions will be processed in Real Time.", "woocommerce"),
        //   'default' => 'no'
        // ),
        'fraud_data' => array(
          'title' => __("Send Fraud Data", "woocommerce"),
          'type' => 'checkbox',
          'description' => __("Send additional data for fraud detection. Note this must be enabled by Fat Zebra before the data will be used.", "woocommerce"),
          'default' => 'no'
        ),
        'fraud_device_id' => array(
          'title' => __("Enable Device ID", "woocommerce"),
          'type' => 'checkbox',
          'description' => __("For fraud detection. Enable this if instructed by Fat Zebra.", "woocommerce"),
          'default' => 'no'
        )
      );

    } // End init_form_fields()

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() {
      ?>
      <h3><?php _e('Fat Zebra', 'woocommerce'); ?></h3>
      <p><?php _e('Allows Fat Zebra Payments. ', 'woocommerce'); ?></p>
      <table class="form-table">
        <?php $this->generate_settings_html(); ?>
      </table>
    <?php
    } // End admin_options()

    function payment_fields() {
      if ($this->direct_post_enabled()) {
        // Register and enqueue direct post handling script
        $url = $this->get_direct_post_url();

        $return_path = uniqid('fatzebra-nonce-');
        $verification_value = hash_hmac('md5', $return_path, $this->settings["shared_secret"]);

        wp_register_script('fz-direct-post-handler', plugin_dir_url(__FILE__) . '/images/fatzebra.js', array('jquery'), WC_VERSION, true);
        wp_localize_script('fz-direct-post-handler', 'fatzebra', array(
          'url' => $url,
          'return_path' => $return_path,
          'verification_value' => $verification_value)
        );
        wp_enqueue_script('fz-direct-post-handler');
      }

      echo "<input type='hidden' name='fatzebra-token' id='fatzebra-token' /><span class='payment-errors required'></span>";

      if ($this->fraud_detection_enabled() && $this->settings["fraud_device_id"] === "yes") {
        $device_id_url = $this->settings['sandbox_mode'] == 'yes' ? 'https://ci-mpsnare.iovation.com/snare.js' : 'https://mpsnare.iesnare.com/snare.js';
        wp_register_script('fz-deviceid', plugin_dir_url(__FILE__) . '/images/fatzebra-deviceid.js', array(), WC_VERSION, false);
        wp_register_script('fz-io-bb', $device_id_url, array('fz-deviceid'), '1', true);
        wp_enqueue_script('fz-io-bb');
      }
      $this->form(array('fields_have_names' => !$this->direct_post_enabled()), $extra_fields);

    }

    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id) {
      global $woocommerce;

      if ($this->direct_post_enabled()) {
        $this->params["card_token"] = $_POST['fatzebra-token'];
      } else {
        $this->params["card_number"] = str_replace(' ', '', $_POST['fatzebra-card-number']);
        if (!isset($_POST["fatzebra-card-number"])) {
          $this->params["card_number"] = $_POST['cardnumber'];
        }

        $this->params["cvv"] = $_POST["fatzebra-card-cvc"];
        if (!isset($_POST['fatzebra-card-cvc'])) {
          $this->params["cvv"] = $_POST['card_cvv'];
        }

        if (isset($_POST['fatzebra-card-expiry']) && !empty($_POST['fatzebra-card-expiry'])) {
          list($exp_month, $exp_year) = explode('/', $_POST['fatzebra-card-expiry']);
        } else {
          $exp_month = $_POST['card_expiry_month'];
          $exp_year = $_POST['card_expiry_year'];
        }
        $this->params["card_expiry"] = trim($exp_month) . "/" . (2000 + intval($exp_year));

        $this->params["card_holder"] = $_POST['billing_first_name'] . " " . $_POST['billing_last_name'];
      }

      $this->params["customer_ip"] = $this->get_customer_real_ip();

      $defer_payment = false; //$this->settings["deferred_payments"] == "yes";

      $order = new WC_Order($order_id);
      $this->params["currency"] = $order->get_order_currency();

      if (class_exists("WC_Subscriptions_Order") && wcs_order_contains_subscription($order)) {
        // No deferred payments for subscriptions.
        $defer_payment = false;
        // Charge sign up fee + first period here..
        // Periodic charging should happen via scheduled_subscription_payment_fatzebra
        $this->params["amount"] = $this->convert_to_cents($order->get_total());
      } else {
        $this->params["amount"] = $this->convert_to_cents($order->order_total);
      }

      $this->params["reference"] = (string)$order_id;
      $test_mode = $this->settings['test_mode'] == 'yes';
      $this->params["test"] = $test_mode;
      $this->params["deferred"] = $defer_payment;

      if (trim($this->params["card_holder"]) == "") { // If the customer is updating their details the $_POST values for name will be missing, so fetch from the order
        $this->params["card_holder"] = $order->billing_first_name . " " . $order->billing_last_name;
      }

      if ($this->fraud_detection_enabled()) {
        // Add in the fraud data payload
        $fraud_data = $this->get_fraud_payload($order);

        $this->params['fraud'] = $fraud_data;
      }

      if ($this->params["amount"] === 0) {
        $result = $this->tokenize_card($this->params);
      } else {
        $result = $this->do_payment($this->params);
      }

      if (is_wp_error($result)) {
        switch ($result->get_error_code()) {
          case 1: // Non-200 response, so failed... (e.g. 401, 403, 500 etc).
            $order->add_order_note($result->get_error_message());
            wc_add_notice($result->get_error_message(), 'error');
            break;

          case 2: // Gateway error (data etc)
            $errors = $result->get_error_data();
            foreach ($errors as $error) {
              $order->add_order_note("Gateway Error: " . $error);
            }
            error_log("WooCommerce Fat Zebra - Gateway Error: " . print_r($errors, true));
            wc_add_notice("Payment Failed: " . implode(", ", $errors), 'error');
            break;

          case 3: // Declined - error data is array with keys: message, id
            wc_add_notice("Payment declined: " . $this->response_data->response->message, 'error');
            if (isset($this->response_data->response->fraud_result) && !empty($this->response_data->response->fraud_result)) {
              if ($this->response_data->response->fraud_result == 'Accept') {
                $order->add_order_note("Fraud Check Result: Accept");
              } else {
                $order->add_order_note("Fraud Check Result: " . $this->response_data->response->fraud_result . " - " . implode(", ", $this->response_data->response->fraud_messages));
              }
            }
            break;

          case 4: // Exception caught, something bad happened. Data is exception
          default:
            wc_add_notice("Unknown error.", 'error');
            $order->add_order_note(__("Unknown Error (exception): " . print_r($result->get_error_data(), true)));
            break;
        }
        return;

      } else { // Success! Returned is an array with the transaction ID etc
        // For a deferred payment we set the status to on-hold and then add a detailed note for review.
        if ($defer_payment) {
          $date = new DateTime($result["card_expiry"], new DateTimeZone("Australia/Sydney"));
          $note = "Deferred Payment:<ul><li>Card Token: " . $result["card_token"] . "</li><li>Card Holder: " . $result["card_holder"] . "</li><li>Card Number: " . $result["card_number"] . "</li><li>Expiry: " . $date->format("m/Y") . "</li></ul>";
          $order->update_status("on-hold", $note);
          update_post_meta($order_id, "_fatzebra_card_token", $result["card_token"]);
          update_post_meta($order_id, "fatzebra_card_token", $result["card_token"]);
        } else {
          if ($this->params["amount"] === 0) {
            $order->add_order_note(__("Fat Zebra payment complete - $0 initial amount, card tokenized. Card token: " . $result["card_token"]));
          } else {
            $order->add_order_note(__("Fat Zebra payment complete. Reference: " . $result["transaction_id"]));
          }

          if (isset($this->response_data->response->fraud_result) && !empty($this->response_data->response->fraud_result)) {
            if ($this->response_data->response->fraud_result == 'Accept') {
              $order->add_order_note("Fraud Check Result: Accept");
            } else {
              $order->add_order_note("Fraud Check Result: " . $this->response_data->response->fraud_result . " - " . implode(", ", $this->response_data->response->fraud_messages));
            }
          }

          $order->payment_complete($result['transaction_id']);

          // Store the card token as post meta
          update_post_meta($order_id, "_fatzebra_card_token", $result["card_token"]);
          update_post_meta($order_id, "fatzebra_card_token", $result["card_token"]);
          update_post_meta($order_id, 'Fat Zebra Transaction ID', $result['transaction_id']);
        }
        $woocommerce->cart->empty_cart();

        return array('result' => 'success', 'redirect' => $this->get_return_url($order));
      }
    }

    /**
     * Process refund
     *
     * If the gateway declares 'refunds' support, this will allow it to refund
     * a passed in amount.
     *
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  boolean True or false based on success, or a WP_Error object
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
      global $woocommerce;

      $order = new WC_Order($order_id);
      $this->params["amount"] = $this->convert_to_cents($amount);
      $this->params["reference"] = $order_id . "-" . time(); // It is not possible to simply refund against the order ID as multiple reunds are permitted...
      $this->params["transaction_id"] = $order->get_transaction_id();
      if (empty($this->params['transaction_id']) || $this->params["transaction_id"] == false) { // get_post_meta could be falsy if the value does not exist...
        $this->params["transaction_id"] = $this->fetch_fatzebra_transaction_id($order_id);
      }

      $this->params["customer_ip"] = $this->get_customer_real_ip();
      $this->params["currency"] = $order->get_order_currency();

      $test_mode = $this->settings['test_mode'] == 'yes';
      $this->params["test"] = $test_mode;
      $sandbox_mode = $this->settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'

      $order_text = json_encode($this->params);

      $url = $sandbox_mode ? $this->sandbox_url : $url = $this->live_url;
      // URL is for /refunds
      $url = str_replace('purchases', 'refunds', $url);

      $args = array('method' => 'POST',
                    'body' => $order_text,
                    'headers' => array('Authorization' => 'Basic ' . base64_encode($this->settings["username"] . ":" . $this->settings["token"]),
                      'X-Test-Mode' => $test_mode,
                      'User-Agent' => "WooCommerce Plugin " . $this->version),
                    'timeout' => 30);
      try {
        $this->response = (array)wp_remote_request($url, $args);

        if ((int)$this->response["response"]["code"] != 200 && (int)$this->response["response"]["code"] != 201) {
         wc_add_notice("Refund failed: " . $this->response["response"]["message"], 'error');
         return false;
        }

        $this->response_data = json_decode($this->response['body']);

        if (!$this->response_data->successful) {
          wc_add_notice('Refund Failed - Gateway Error: ' . implode(",", $this->response_data->errors), 'error');
          return false;
        }

        if (!$this->response_data->response->successful) {
          wc_add_notice('Refund Declined: ' . $this->response_data->response->message, 'error');
          return false;
        }

        if ($this->response_data->response->successful) {
          wc_add_notice('Refund Approved');
          $order->add_order_note('Refund for ' . $amount . ' successful. Refund ID: ' . $this->response_data->response->id);
          return true;
        }

      } catch (Exception $e) {
        wc_add_notice("Unknown Refund Error - please see error log", "error");
        error_log("Exception caught during refund: " . print_r($e, true));
        return false;
      }

      return false;
    }

    function fetch_fatzebra_transaction_id($order_id) {
      return false;
    }

    // For the thankyou page :)
    function thankyou_page() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }

    /**
     * Process the subscription payment (manually... well via wp_cron)
     *
     * @param $amount the amount for this payment
     * @param $order the order ID
     * @param $product_id the product ID
     */
    function scheduled_subscription_payment($amount_to_charge, $order, $product_id) {
      $this->params = array();
      $this->params["amount"] = $this->convert_to_cents($amount_to_charge);
      $this->params["test"] = $test_mode;
      $this->params["reference"] = $order->id . "-" . date("dmY"); // Reference for order ID 123 will become 123-01022012

      $token = get_post_meta($order->id, "_fatzebra_card_token", true);
      if (empty($token)) $token = get_post_meta($order->id, "fatzebra_card_token", true);

      $this->params["card_token"] = $token;
      $ip = get_post_meta($post_id, "Customer IP Address", true);
      if (empty($ip)) $ip = "127.0.0.1";
      $this->params["customer_ip"] = $ip;
      $this->params["deferred"] = false;
      $result = $this->do_payment($this->params);

      if (is_wp_error($result)) {
        $error = "";
        $txn_id = "None";
        switch ($result->get_error_code()) {
          case 1: // Non-200 response, so failed... (e.g. 401, 403, 500 etc).
            $error = $result->get_error_message();
            break;

          case 2: // Gateway error (data etc)
            $errors = $result->get_error_data();
            $error = implode(", ", $errors);
            error_log("WooCommerce Fat Zebra - Gateway Error: " . print_r($errors, true));
            break;

          case 3: // Declined - error data is array with keys: message, id
            $error = $this->response_data->response->message;
            $txn_id = $this->response_data->response->transaction_id;
            break;

          case 4: // Exception caught, something bad happened. Data is exception
          default:
            $error = "Unknown - Error - See error log";
            error_log("WC Fat Zebra (Subscriptions) - Unknown Error (exception): " . print_r($result->get_error_data(), true));
            break;
        }

        // Add the error details and return
        $order->add_order_note(__("Subscription Payment Failed: " . $error . ". Transaction ID: " . $txn_id));
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order, $product_id);

      } else { // Success! Returned is an array with the transaction ID etc
        // Update the subscription and return
        // Add a note to the order
        $order->add_order_note(__("Subscription Payment Successful. Transaction ID: " . $result["transaction_id"]));
        WC_Subscriptions_Manager::process_subscription_payments_on_order($order);
      }
    }

    /**
     *
     * @return mixed WP_Error or Array (result)
     */
    function do_payment($params) {
      $sandbox_mode = $this->settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'
      $test_mode = $this->settings["test_mode"] == "yes";

      $order_text = json_encode($params);

      $url = $sandbox_mode ? $this->sandbox_url : $url = $this->live_url;

      // Deferred payments need to post to the /credit_cards endpoint.
      if (isset($params["deferred"]) && $params["deferred"]) {
        // Replace the URL with the tokenize method and re-create the order text (json payload)
        $url = str_replace("purchases", "credit_cards", $url);
        $payload = array("card_holder" => $params["card_holder"], "card_number" => $params["card_number"], "card_expiry" => $params["card_expiry"], "cvv" => $params["cvv"]);

        $ip = get_post_meta($post_id, "Customer IP Address", true);
        if (empty($ip)) $ip = "127.0.0.1";
        if (!isset($payload["customer_ip"])) $payload["customer_ip"] = $ip;

        $order_text = json_encode($payload);
      }

      $args = array(
        'method' => 'POST',
        'body' => $order_text,
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($this->settings["username"] . ":" . $this->settings["token"]),
          'X-Test-Mode' => $test_mode,
          'User-Agent' => "WooCommerce Plugin " . $this->version
          ),
        'timeout' => 30
        );

      try {
        $this->response = (array)wp_remote_request($url, $args);

        if ((int)$this->response["response"]["code"] != 200 && (int)$this->response["response"]["code"] != 201) {
          $error = new WP_Error();
          $error->add(1, "Credit Card Payment failed: " . $this->response["response"]["message"]);
          $error->add_data($this->response);
          return $error;
        }

        $this->response_data = json_decode($this->response['body']);

        if (!$this->response_data->successful) {
          $error = new WP_Error();
          $error->add(2, "Gateway Error", $this->response_data->errors);

          return $error;
        }

        // If we are doing a deferred payments we override the data here.
        if (isset($params["deferred"]) && $params["deferred"]) {
          return array("card_token" => $this->response_data->response->token, "card_number" => $this->response_data->response->card_number, "card_expiry" => $this->response_data->response->card_expiry, "card_holder" => $this->response_data->response->card_holder);
        }

        if (!$this->response_data->response->successful) {
          $error = new WP_Error();
          $error->add(3, "Payment Declined", array("message" => $this->response_data->response->message, "id" => $this->response_data->response->id));
          return $error;
        }

        if ($this->response_data->response->successful) {
          return array("transaction_id" => $this->response_data->response->id, "card_token" => $this->response_data->response->card_token);
        }

      } catch (Exception $e) {
        $error = new WP_Error();
        $error->add(4, "Unknown Error", $e);
        return $error;
      }
    }

    /**
     *
     * @return mixed WP_Error or Array (result)
     */
    function tokenize_card($params) {
      $sandbox_mode = $this->settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'
      $test_mode = $this->settings["test_mode"] == "yes";

      $ip = get_post_meta($post_id, "Customer IP Address", true);
      if (empty($ip)) $ip = "127.0.0.1";
      if (!isset($payload["customer_ip"])) $payload["customer_ip"] = $ip;

      $order_text = json_encode($params);

      $url = $sandbox_mode ? $this->sandbox_url : $url = $this->live_url;
      // Replace the URL with the tokenize method and re-create the order text (json payload)
      $url = str_replace("purchases", "credit_cards", $url);
      $payload = array("card_holder" => $params["card_holder"], "card_number" => $params["card_number"], "card_expiry" => $params["card_expiry"], "cvv" => $params["cvv"]);
      $order_text = json_encode($payload);

      $args = array(
        'method' => 'POST',
        'body' => $order_text,
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($this->settings["username"] . ":" . $this->settings["token"]),
          'X-Test-Mode' => $test_mode,
          'User-Agent' => "WooCommerce Plugin " . $this->version
          ),
        'timeout' => 30
        );
      try {
        $this->response = (array)wp_remote_request($url, $args);

        if ((int)$this->response["response"]["code"] != 200 && (int)$this->response["response"]["code"] != 201) {
          $error = new WP_Error();
          $error->add(1, "Credit Card Payment failed: " . $this->response["response"]["message"]);
          $error->add_data($this->response);
          return $error;
        }

        $this->response_data = json_decode($this->response['body']);

        if (!$this->response_data->successful) {
          $error = new WP_Error();
          $error->add(2, "Gateway Error", $this->response_data->errors);

          return $error;
        }

        return array("card_token" => $this->response_data->response->token, "card_number" => $this->response_data->response->card_number, "card_expiry" => $this->response_data->response->card_expiry, "card_holder" => $this->response_data->response->card_holder, "transaction_id" => $this->response_data->response->token);
      } catch (Exception $e) {
        $error = new WP_Error();
        $error->add(4, "Unknown Error", $e);
        return $error;
      }
    }

    // Add the 'Charge Card' button if the order is on-hold
    function add_process_deferred_payment_button($order_id) {
      $order = new WC_Order($order_id);
      if ($order->status == "on-hold") {
        echo '<li><input type="submit" class="button tips" name="process" value="Charge Card" data-tip="Attempts to process a deferred payment" /></li>';
      }
    }

    /** Builds the fraud payload for the request
     * @param $order WC_Order the order to build the payload against
     */
    function get_fraud_payload($order) {
      $fraud_data = array("website" => get_site_url(), "customer" => $this->get_fraud_customer($order), "items" => $this->get_fraud_items($order), "shipping_address" => $this->get_fraud_shipping($order));

      if ($this->settings["fraud_device_id"] === "yes") {
        $fraud_data["device_id"] = $_POST['io_bb'];
      }

      return $fraud_data;
    }

    /**
     * Fetches the customer details for the fraud check request
     */
    function get_fraud_customer($order) {
      $data = array(
        'first_name' => $order->billing_first_name,
        'last_name' => $order->billing_last_name,
        'email' => $order->billing_email,
        'address_1' => $order->billing_address_1,
        'address_2' => $order->billing_address_2,
        'city' => $order->billing_city,
        'country' => $this->country_map[$order->billing_country],
        'post_code' => $order->billing_postcode,
        'home_phone' => $order->billing_phone
        );

      return $data;
    }

    /**
     * Fetches the item details from the order for the fraud check request
     */
    function get_fraud_items($order) {

      $data = array();
      $items = $order->get_items();

      foreach ($items as $item) {
        $product = new WC_Product($item['product_id']);
        if (isset($item['variation_id'])) {
          $name = $product->get_title();
          $product = new WC_Product($item['variation_id']);
        } else {
          $name = $product->get_title();
        }

        $data[] = array(
          'product_code' => (string)$product->id,
          'sku' => $product->sku,
          'description' => $name,
          'qty' => $item['qty'],
          'cost' => $product->get_price(),
          'line_total' => $order->get_line_subtotal($item)
          );
      }

      return $data;
    }

    /**
     * Fetches the shipping details from the order for the fraud check request
     */
    function get_fraud_shipping($order) {
      $data = array(
      'first_name' => $order->shipping_first_name,
      'last_name' => $order->shipping_last_name,
      'email' => $order->shipping_email,
      'address_1' => $order->shipping_address_1,
      'address_2' => $order->shipping_address_2,
      'city' => $order->shipping_city,
      'country' => $this->country_map[$order->shipping_country],
      'post_code' => $order->shipping_postcode,
      'shipping_method' => 'low_cost' // TODO: Shipping Method Map
      );

      if (empty($data['email'])) $data['email'] = $order->billing_email;

      return $data;
    }

    function get_customer_real_ip() {
      $customer_ip = $_SERVER['REMOTE_ADDR'];
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $customer_ip = $forwarded_ips[0];
      }

      return $customer_ip;
    }

    /**
     * Safely convert to cents, preventing rounding errors
     * Before: $134.95 = 13,494 cents
     * After: $134.95 = 13,495 cents
     * @param  float $amount Amount in dollars (eg 134.95)
     * @return int           Amount in Cents (eg 13495)
     */
    public function convert_to_cents($amount)
    {
      if(function_exists('bcmul')) {
        // BCMath is the most reliable method, if enabled
        return (int) bcmul($amount, 100);
      }
      // Hacky workaround
      return (int) round(($amount * 100), 0, PHP_ROUND_HALF_UP);
    }
  }

  /**
   * Add the gateway to WooCommerce
   **/
  function add_fz_gateway($methods) {
    $methods[] = 'WC_FatZebra';
    return $methods;
  }

  // Attempt to process the deferred payment. This is called when you press the 'Charge card' button on the order page.
  function attempt_deferred_payment($post_id, $post) {
    global $wpdb, $woocommerce, $woocommerce_errors;

    // Bail if we don't have anything to do.
    if (!isset($_POST['process'])) return;
    $order = new WC_Order($post_id);
    if ($order->status != "on-hold") return;

    $gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
    $gateway = $gateways['fatzebra'];

    // Build the params for the payment
    $token = get_post_meta($post_id, "_fatzebra_card_token", true);
    if (empty($token)) $token = get_post_meta($post_id, "fatzebra_card_token", true);

    $ip = get_post_meta($post_id, "Customer IP Address", true);
    if (empty($ip)) $ip = "127.0.0.1";
    $this->params["customer_ip"] = $ip;


    $params = array("card_token" => $token, "amount" => $this->convert_to_cents($order->order_total), "reference" => $order->id, "customer_ip" => $ip);

    // Do the payment and handle the result.
    $result = $gateway->do_payment($params);
    if (is_wp_error($result)) {
      switch ($result->get_error_code()) {
        case 1: // Non-200 response, so failed... (e.g. 401, 403, 500 etc).
          $order->add_order_note($result->get_error_message());
          $woocommerce->add_error($result->get_error_message());
          break;

        case 2: // Gateway error (data etc)
          $errors = $result->get_error_data();
          foreach ($errors as $error) {
            $order->add_order_note("Gateway Error: " . $error);
          }
          error_log("WooCommerce Fat Zebra - Unknown error: " . print_r($errors, true));
          $woocommerce->add_error("Payment Failed: " . implode(", ", $errors));
          break;

        case 3: // Declined - error data is array with keys: message, id
          $order->add_order_note(__("Payment Declined: " . $result->response->message . ". Reference: " . $result->response->transaction_id));
          $woocommerce->add_error("Payment declined: " . $result->response->message);
          return;
          break;

        case 4: // Exception caught, something bad happened. Data is exception
        default:
          $woocommerce->add_error("Unknown error.");
          $order->add_order_note(__("Unknown Error (exception): " . print_r($result->get_error_data(), true)));
          break;
      }
      return;
    } else {
      // We're all good - update the notes, change status to payment complete and send invoice.
      $order->add_order_note("Fat Zebra payment complete. Reference: " . $result["transaction_id"]);

      if (isset($_POST['order_status'])) unset($_POST['order_status']);

      $order->payment_complete();
    }
  }

  // Only update the recurring payment method if:
  //  - The method is not set
  //  - the fatzebra_card_token is set
  function set_recurring_payment_method($post_id) {
    $method = get_post_meta($post_id, "_recurring_payment_method", true);
    $token = get_post_meta($post_id, "fatzebra_card_token", true);
    if (empty($method) && !empty($token)) {
      update_post_meta($post_id, "_recurring_payment_method", "fatzebra");
      update_post_meta($post_id, "_recurring_payment_method_title", "Credit Card");
    }
  }

  function fz_customize_woocommerce_states() {
    global $states;

    $states['AU'] = array(
        'ACT' => __( 'ACT', 'woocommerce' ),
        'NSW' => __( 'NSW', 'woocommerce' ),
        'NT'  => __( 'NT', 'woocommerce' ),
        'QLD' => __( 'QLD', 'woocommerce' ),
        'SA'  => __( 'SA', 'woocommerce' ),
        'TAS' => __( 'TAS', 'woocommerce' ),
        'VIC' => __( 'VIC', 'woocommerce' ),
        'WA'  => __( 'WA', 'woocommerce' )
    );

    return $states;
  }

  add_filter('woocommerce_payment_gateways', 'add_fz_gateway');
  add_action('woocommerce_process_shop_order_meta', 'attempt_deferred_payment', 1, 2);
  add_action('save_post', 'set_recurring_payment_method');
  add_filter( 'woocommerce_states', 'fz_customize_woocommerce_states' );
}
