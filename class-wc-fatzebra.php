<?php

/*
Plugin Name: WooCommerce Fat Zebra Gateway
Plugin URI: https://www.fatzebra.com.au/support/supported-carts
Description: Extends WooCommerce with Fat Zebra payment gateway along with WooCommerce subscriptions support.
Version: 1.4.3
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
  if ( !class_exists( 'WC_Payment_Gateway' ) ) { ?>
    <div id="message" class="error">
      <p><?php printf( __( '%sWooCommerce Fat Zebra Extension is inactive.%s The %sWooCommerce plugin%s must be active for the WooCommerce Subscriptions to work. Please %sinstall & activate WooCommerce%s',  'wc_fatzebra'), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url( 'plugins.php' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
    </div>
    <?php
    return;
  }

  include("class-wc-fatzebra-masterpass.php");
  fz_masterpass_init();

  class WC_FatZebra extends WC_Payment_Gateway {

    public function __construct() {
      $this->id           = 'fatzebra';
      $this->icon         = apply_filters('woocommerce_fatzebra_icon', '');
      $this->has_fields   = true;
      $this->method_title = __( 'Fat Zebra', 'woocommerce' );
      $this->version      = "1.4.1";

      $this->api_version  = "1.0";
      $this->live_url     = "https://gateway.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->sandbox_url  = "https://gateway.sandbox.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->supports     = array( 'subscriptions', 'products', 'products', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_amount_changes', 'subscription_payment_method_change', 'subscription_date_changes', 'default_credit_card_form' );
      $this->params       = array();

      // Define user set variables
      $this->title        = "Credit Card";
      $this->description  = in_array("description", $this->settings) ? $this->settings['description'] : "";

      // Load the form fields.
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();

      // Actions
      add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); // < 2.0
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); //> 2.0
      add_action('scheduled_subscription_payment_fatzebra', array(&$this, 'scheduled_subscription_payment'), 10, 3);
      add_action('woocommerce_order_actions', array(&$this, 'add_process_deferred_payment_button'), 99, 1);
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

      $this->form_fields = array(
      'enabled' => array(
              'title' => __( 'Enable/Disable', 'woocommerce' ),
              'type' => 'checkbox',
              'label' => __( 'Enable Fat Zebra', 'woocommerce' ),
              'default' => 'yes'
            ),
      'test_mode' => array(
              'title' => __( 'Test Mode', 'woocommerce' ),
              'type' => 'checkbox',
              'description' => __( 'Switches the gateway to live mode.', 'woocommerce' ),
              'default' => "yes"
            ),
      'sandbox_mode' => array(
              'title' => __( 'Sandbox Mode', 'woocommerce'),
              'type' => "checkbox",
              'description' => __('Switches the gateway URL to the sandbox URL', "woocommerce"),
              'default' => "yes"
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
      'deferred_payments' => array(
        'title' => __("Enable Deferred Payments", "woocommerce"),
        'type' => 'checkbox',
        'description' => __("Deferred payments enable you to capture the customers card details in Fat Zebra's system and process them at a later date (for example, once you have reviewed the order for high-risk products). Note: Deferred Payments cannot be used with WooCommerce Subscription - any subscriptions will be processed in Real Time.", "woocommerce"),
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


    /**
     * Process the payment and return the result
     **/
    function process_payment( $order_id ) {
      global $woocommerce;
  
      $this->params["card_number"] = str_replace(' ', '', $_POST['fatzebra-card-number']);
      $this->params["cvv"] = $_POST["fatzebra-card-cvc"];
      list($exp_month, $exp_year) = split('/', $_POST['fatzebra-card-expiry']);
      $this->params["card_expiry"] = trim($exp_month) . "/" . (2000 + intval($exp_year));  
      $this->params["card_holder"] = $_POST['billing_first_name'] . " " . $_POST['billing_last_name'];
      $this->params["customer_ip"] = $_SERVER['REMOTE_ADDR'];

      $defer_payment = $this->settings["deferred_payments"] == "yes";

      $order = new WC_Order( $order_id );

      if (class_exists("WC_Subscriptions_Order") && WC_Subscriptions_Order::order_contains_subscription($order)) {
        // No deferred payments for subscriptions.
        $defer_payment = false;
        // Charge sign up fee + first period here..
        // Periodic charging should happen via scheduled_subscription_payment_fatzebra
        $this->params["amount"] = (int)(WC_Subscriptions_Order::get_total_initial_payment($order) * 100);
      } else {
        $this->params["amount"] = (int)($order->order_total * 100);
      }

      $this->params["reference"] = (string)$order_id;
      $this->params["test"] = $test_mode;
      $this->params["deferred"] = $defer_payment;

      if (trim($this->params["card_holder"]) == "") { // If the customer is updating their details the $_POST values for name will be missing, so fetch from the order
        $this->params["card_holder"] = $order->billing_first_name ." ". $order->billing_last_name;
      }

      if ($this->params["amount"] === 0) {
        $result = $this->tokenize_card($this->params);
      } else {
        $result = $this->do_payment($this->params);
      }

      if (is_wp_error($result)) {
        switch($result->get_error_code()) {
          case 1: // Non-200 response, so failed... (e.g. 401, 403, 500 etc).
            $order->add_order_note($result->get_error_message());
            $woocommerce->add_error($result->get_error_message());
          break;

          case 2: // Gateway error (data etc)
            $errors = $result->get_error_data();
            foreach($errors as $error) {
              $order->add_order_note("Gateway Error: " . $error);
            }
            error_log("WooCommerce Fat Zebra - Gateway Error: " . print_r($errors, true));
            $woocommerce->add_error("Payment Failed: Unspecific Gateway Error.");
            break;

          case 3: // Declined - error data is array with keys: message, id
            $order->add_order_note(__("Payment Declined: " . $this->response_data->response->message . ". Reference: " . $this->response_data->response->transaction_id));
            $woocommerce->add_error("Payment declined: " . $this->response_data->response->message);
            return;
          break;

          case 4: // Exception caught, something bad happened. Data is exception
          default:
            $woocommerce->add_error("Unknown error.");
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

          $order->payment_complete();

          // Store the card token as post meta
          update_post_meta($order_id, "_fatzebra_card_token", $result["card_token"]);
          update_post_meta($order_id, "fatzebra_card_token", $result["card_token"]);
        }
        $woocommerce->cart->empty_cart();
        unset($_SESSION['order_awaiting_payment']);

        return array(
          'result'  => 'success',
          'redirect'  => $this->get_return_url( $order )
        );
      }
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
      $this->params["amount"] = (int)($amount_to_charge * 100);
      $this->params["test"] = $test_mode;
      $this->params["reference"] = $order->id . "-" . date("dmY"); // Reference for order ID 123 will become 123-01022012

      $token = get_post_meta($order->id, "_fatzebra_card_token", true);
      if (empty($token)) $token = get_post_meta($order->id, "fatzebra_card_token", true);

      $this->params["card_token"] = $token;
      $ip = get_post_meta($post_id, "Customer IP Address", true);
      if(empty($ip)) $ip = "127.0.0.1";
      $this->params["customer_ip"] = $ip;
      $this->params["deferred"] = false;
      $result = $this->do_payment($this->params);



      if (is_wp_error($result)) {
        $error = "";
        $txn_id = "None";
        switch($result->get_error_code()) {
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
            return;
          break;

          case 4: // Exception caught, something bad happened. Data is exception
          default:
            $error = "Unknown - Error - See error log";
            error_log("WC Fat Zebra (Subscriptions) - Unknown Error (exception): " . print_r($result->get_error_data(), true));
            break;
        }

        // Add the error details and return
        $order->add_order_note(__("Subscription Payment Failed: " . $error . ". Transaction ID: " . $txn_id, WC_Subscriptions::$text_domain));
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );

      } else { // Success! Returned is an array with the transaction ID etc
        // Update the subscription and return
        // Add a note to the order
        $order->add_order_note(__("Subscription Payment Successful. Transaction ID: " . $result["transaction_id"], WC_Subscriptions::$text_domain));
        WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
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
        if(empty($ip)) $ip = "127.0.0.1";
        if(!isset($payload["customer_ip"])) $payload["customer_ip"] = $ip;

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
      if(empty($ip)) $ip = "127.0.0.1";
      if(!isset($payload["customer_ip"])) $payload["customer_ip"] = $ip;

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
        echo '<li><input type="submit" class="button tips" name="process" value="Charge Card" data-tip="Attemptes to process a deferred payment" /></li>';
      }
    }
  }

  /**
   * Add the gateway to WooCommerce
   **/
  function add_fz_gateway( $methods ) {
    $methods[] = 'WC_FatZebra'; return $methods;
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
    if(empty($ip)) $ip = "127.0.0.1";
    $this->params["customer_ip"] = $ip;


    $params = array("card_token" => $token, "amount" => (int)($order->order_total * 100), "reference" => $order->id, "customer_ip" => $ip);

    // Do the payment and handle the result.
    $result = $gateway->do_payment($params);
    if (is_wp_error($result)) {
      switch($result->get_error_code()) {
        case 1: // Non-200 response, so failed... (e.g. 401, 403, 500 etc).
          $order->add_order_note($result->get_error_message());
          $woocommerce->add_error($result->get_error_message());
        break;

        case 2: // Gateway error (data etc)
          $errors = $result->get_error_data();
          foreach($errors as $error) {
            $order->add_order_note("Gateway Error: " . $error);
          }
          error_log("WooCommerce Fat Zebra - Unknown error: " . print_r($errors, true));
          $woocommerce->add_error("Payment Failed: Unspecific Gateway Error.");
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

      if(isset($_POST['order_status'])) unset($_POST['order_status']);

      $order->payment_complete();
    }
  }

  // Only update the recurring payment method if:
  //  - The method is not set
  //  - the fatzebra_card_token is set
  function set_recurring_payment_method($post_id) {
    $method = get_post_meta($post_id, "_recurring_payment_method", true);
    $token  = get_post_meta($post_id, "fatzebra_card_token", true);
    if(empty($method) && !empty($token)) {
      update_post_meta($post_id, "_recurring_payment_method", "fatzebra");
      update_post_meta($post_id, "_recurring_payment_method_title", "Credit Card");
    }
  }

  add_filter('woocommerce_payment_gateways', 'add_fz_gateway' );
  add_action('woocommerce_process_shop_order_meta', 'attempt_deferred_payment', 1, 2);
  add_action('save_post', 'set_recurring_payment_method');
}
