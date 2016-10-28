<?php

function fz_amex_eco_init() {
  class WC_FatZebra_Amex_Eco extends WC_Payment_Gateway {

    public function __construct() {
      $this->id           = 'fatzebra_amex_eco';
      $this->icon         = "https://web.aexp-static.com/au/content/text/express-checkout/hub/images/ExpressCheckout_HeaderGraphic.png";
      $this->has_fields   = true;
      $this->method_title = __( 'Fat Zebra (AMEX ECO)', 'woocommerce' );
      $this->version      = "1.5.12";

      $this->api_version  = "1.0";
      $this->live_url     = "https://gateway.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->sandbox_url  = "https://gateway.sandbox.fatzebra.com.au/v{$this->api_version}/purchases";

      $this->supports     = array( 'subscriptions', 'products', 'refunds', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_amount_changes', 'subscription_payment_method_change', 'subscription_date_changes' );
      $this->params       = array();

      // Define user set variables
      $this->title        = 'AMEX ECO';
      $this->description  = in_array("description", $this->settings) ? $this->settings['description'] : "";

      // Load the form fields.
      $this->init_form_fields();
      $this->init_parent_settings(); // Allows us to access $this->parent_settings[] for username, token etc

      // Load the settings.
      $this->init_settings();

      // Actions
      add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); // < 2.0
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); //> 2.0
      add_action('scheduled_subscription_payment_' . $this->id, array(&$this, 'scheduled_subscription_payment'), 10, 3);
    }

    /**
     * Initializes the parent (WC_FatZebra) settings
     */
    function init_parent_settings() {
      $this->parent_settings = get_option("woocommerce_fatzebra_settings", null);
      if ( ! $this->parent_settings || ! is_array( $this->parent_settings ) ) {
        $this->parent_settings = array();
      }

      // if ( $this->parent_settings && is_array( $this->parent_settings ) ) {
      //  $this->parent_settings = array_map( $this->parent_settings );
      // }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

      $this->form_fields = array(
      'enabled' => array(
              'title' => __( 'Enable/Disable', 'woocommerce' ),
              'type' => 'checkbox',
              'label' => __( 'Enable AMEX ECO', 'woocommerce' ),
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
      <h3>Fat Zebra</h3>
      <p>Allows Fat Zebra Payments.</p>
      <table class="form-table">
        <?php $this->generate_settings_html(); ?>
      </table>
      <?php
    } // End admin_options()

    function payment_fields() {
        global $woocommerce;
        $auth_code = WC()->session->get('amex_wallet_auth_code');
      ?>
       <input type='hidden' name='token' id='token' value='<?php echo WC()->session->get('amex_wallet_token'); ?>' />

       <?php if (!empty($auth_code)) { ?>
         <p style='font-weight: bold'>The following card has been selected via Amex Express Checkout:</p>
         <p style='font-size: 20px; padding-left: 40px; margin-top: 20px; font-family: monospace;'>
           <?php echo WC()->session->get('amex_wallet_masked_number'); ?>
         </p>

       <?php } ?>

       <?php
      if (!is_null($auth_code)) {
        //pre-select Visa Checkout as the payment method
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $available_gateways['fatzebra_amex_eco']->set_current();
      }

    }

    /**
     * Process the payment and return the result
     **/
    function process_payment( $order_id ) {
      global $woocommerce;
      $this->params["customer_ip"] = $this->get_customer_real_ip();

      $defer_payment = $this->parent_settings["deferred_payments"] == "yes";
      $test_mode = $this->parent_settings["test_mode"] == "yes";

      $order = new WC_Order( $order_id );
      $this->params["currency"] = $order->get_order_currency();

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

      if (isset($_POST['token'])) {
        $this->params['card_token'] = $_POST['token'];
      }

      if ($this->parent_settings['fraud_data'] == 'yes') {
        $fz_base = new WC_FatZebra();
        $fraud_data = $fz_base->get_fraud_payload($order);
        $this->params['fraud'] = $fraud_data;
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
            wc_add_notice($result->get_error_message(), 'error');
            break;

          case 2: // Gateway error (data etc)
            $errors = $result->get_error_data();
            foreach($errors as $error) {
              $order->add_order_note("Gateway Error: " . $error);
            }
            error_log("WooCommerce Fat Zebra - Gateway Error: " . print_r($errors, true));
            wc_add_notice("Payment Failed: " . implode(", ", $errors), 'error');
            break;

          case 3: // Declined - error data is array with keys: message, id
            $order->add_order_note(__("Payment Declined: " . $this->response_data->response->message . ". Reference: " . $this->response_data->response->transaction_id));
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

          if (isset($this->response_data->response->fraud_result ) && !empty($this->response_data->response->fraud_result )) {
            if ($this->response_data->response->fraud_result  == 'Accept') {
              $order->add_order_note("Fraud Check Result: Accept");
            } else {
              $order->add_order_note("Fraud Check Result: " . $this->response_data->response->fraud_result  . " - " . implode(", ", $this->response_data->response->fraud_messages));
            }
          }
          $order->payment_complete($result['transaction_id']);

          // Clear the session values
          clear_amex_eco_session_values();

          // Store the card token as post meta
          update_post_meta($order_id, "_fatzebra_card_token", $result["card_token"]);
          update_post_meta($order_id, "fatzebra_card_token", $result["card_token"]);
        }
        $woocommerce->cart->empty_cart();

        return array(
          'result'  => 'success',
          'redirect'  => $this->get_return_url( $order )
        );
      }
    }

    /**
     * Process a refund of an order
     * This is delegated to the WC_FatZebra parent class
     * @return mixed WP_Error or Array (result)
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
      $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
      if (!isset($payment_gateways['fatzebra'])) {
        return false;
      }

      $gw = $payment_gateways['fatzebra'];
      return $gw->process_refund($order_id, $amount, $reason);
    }

    /**
     *
     * @return mixed WP_Error or Array (result)
     */
    function tokenize_card($params) {
      $sandbox_mode = $this->parent_settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'
      $test_mode = $this->parent_settings["test_mode"] == "yes";

      $ip = null; //get_post_meta($post_id, "Customer IP Address", true);
      if (empty($ip)) $ip = "127.0.0.1";
      if (!isset($payload["customer_ip"])) $payload["customer_ip"] = $ip;

      $order_text = json_encode($params);

      $url = $sandbox_mode ? $this->sandbox_url : $url = $this->live_url;
      // Replace the URL with the tokenize method and re-create the order text (json payload)
      $url = str_replace("purchases", "credit_cards", $url);
      $payload = array("wallet" => array("type" => "AMEX", "auth_code" => $params['auth_code'], "transaction_id" => $params['transaction_id'], "wallet_id" => $params['wallet_id'], "card_type" => $params['card_type']));
      $order_text = json_encode($payload);

      $args = array('method' => 'POST', 'body' => $order_text, 'headers' => array('Authorization' => 'Basic ' . base64_encode($this->parent_settings["username"] . ":" . $this->parent_settings["token"]), 'X-Test-Mode' => $test_mode, 'User-Agent' => "WooCommerce Plugin " . $this->version), 'timeout' => 30);
      try {
        $this->response = (array)wp_remote_request($url, $args);
        if ((int)$this->response["response"]["code"] != 200 && (int)$this->response["response"]["code"] != 201) {
          $error = new WP_Error();
          $error->add(1, "Credit Card Capture failed: " . $this->response["response"]["message"]);
          $error->add_data($this->response);
          return $error;
        }

        $this->response_data = json_decode($this->response['body']);

        if (!$this->response_data->successful) {
          $error = new WP_Error();
          $error->add(2, "Gateway Error", $this->response_data->errors);

          return $error;
        }

        return array(
          "card_token" => $this->response_data->response->token,
          "card_number" => $this->response_data->response->card_number,
          "card_expiry" => $this->response_data->response->card_expiry,
          "card_holder" => $this->response_data->response->card_holder,
          "transaction_id" => $this->response_data->response->token,
          "wallet" => $this->response_data->response->wallet);
      } catch (Exception $e) {
        $error = new WP_Error();
        $error->add(4, "Unknown Error", $e);
        return $error;
      }
    }

    // For the thankyou page :)
    function thankyou_page() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }

    /**
    * Process the subscription payment (manually... well via wp_cron)
    * Delegated to WC_FatZebra
    *
    * @param $amount the amount for this payment
    * @param $order the order ID
    * @param $product_id the product ID
    */
    function scheduled_subscription_payment($amount_to_charge, $order, $product_id) {
      $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
      if (!isset($payment_gateways['fatzebra'])) {
        return false;
      }

      $gw = $payment_gateways['fatzebra'];
      return $gw->scheduled_subscription_payment($amount_to_charge, $order, $product_id);
    }

    /**
    *
    * @return mixed WP_Error or Array (result)
    */
    function do_payment($params) {
      $sandbox_mode = $this->parent_settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'
      $test_mode = $this->parent_settings["test_mode"] == "yes";

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
          'Authorization' => 'Basic ' . base64_encode($this->parent_settings["username"] . ":" . $this->parent_settings["token"]),
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

    function get_customer_real_ip() {
      $customer_ip = $_SERVER['REMOTE_ADDR'];
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $customer_ip = $forwarded_ips[0];
      }

      return $customer_ip;
    }
}
    function clear_amex_eco_session_values() {
      $keys = array('amex_wallet_first_name', 'amex_wallet_last_name', 'amex_wallet_address_1', 'amex_wallet_address_2', 'amex_wallet_city', 'amex_wallet_state', 'amex_wallet_postcode', 'amex_wallet_phone', 'amex_wallet_email', 'amex_wallet_callid', 'amex_wallet_token');

      foreach($keys as $key):
        WC()->session->set($key, null);
      endforeach;
    }

  /**
   * Add the gateway to WooCommerce
   **/
  function add_fz_amex_eco_gateway( $methods ) {
    $methods[] = 'WC_FatZebra_Amex_Eco'; return $methods;
  }

  function inject_amex_eco_button() {
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if (!isset($payment_gateways['fatzebra_amex_eco'])) {
      return;
    }

    $gw = $payment_gateways['fatzebra_amex_eco'];

    $amount = WC()->cart->total;
    $accepted_cards = array();
    foreach($gw->parent_settings['show_card_logos'] as $card) {
        if ($card == 'american_express') $card = "AMEX";
        $accepted_cards[] = strtoupper($card);
    }

    wp_register_script( 'fz-amex_eco', plugin_dir_url(__FILE__) . '/images/fatzebra-amexeco.js', array(), WC_VERSION, false );
    wp_localize_script( 'fz-amex_eco', 'amexeco', array(
          'inline' => true
          )
    );
    wp_enqueue_script( 'fz-amex_eco' );
    $username = $gw->parent_settings['username'];

    $sandbox_mode = $gw->parent_settings["sandbox_mode"] == "yes";
    $base_url = $sandbox_mode ? 'https://paynow-sandbox.pmnts.io' : 'https://paynow.pmnts.io';
    echo "<iframe src='$base_url/v2/amexeco/$username/ABC123/AUD/$amount/abcd1234?tokenize_only=true&postmessage=true' frameborder='0' height='38' width='155' seamless='seamless' allowtransparency='true' scrolling='no' style='position:relative; margin-top: -20px; top: 15px;'></iframe>";
  }

  function fz_amex_eco_load_from_auth_code() {
    clear_amex_eco_session_values();
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if (!isset($payment_gateways['fatzebra_amex_eco'])) {
      return $fields;
    }

    $gw = $payment_gateways['fatzebra_amex_eco'];

    $auth_code = isset($_POST['auth_code']) ? $_POST['auth_code'] : null;
    $transaction_id = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : null;
    $wallet_id = isset($_POST['wallet_id']) ? $_POST['wallet_id'] : null;
    $card_type = isset($_POST['card_type']) ? $_POST['card_type'] : null;
    if (empty($auth_code)) {
      return $fields;
    }

    $token_result = $gw->tokenize_card(array("auth_code" => $auth_code, 'transaction_id' => $transaction_id, 'wallet_id' => $wallet_id, 'card_type' => $card_type));
    if (is_wp_error($token_result)) {
      // Tokenization error - for now lets return the fields with no manipulation..
      clear_amex_eco_session_values();
      return $fields;
    } // Arghhh!

    $wallet = $token_result['wallet'];

    // Store the wallet data in a session. It is important to remove this upon successful checkout....
    WC()->session->set('amex_wallet_first_name', $wallet->name->first);
    WC()->session->set('amex_wallet_last_name', $wallet->name->last);
    WC()->session->set('amex_wallet_address_1', $wallet->address->line_1);
    WC()->session->set('amex_wallet_address_2', $wallet->address->line_2);
    WC()->session->set('amex_wallet_city', $wallet->address->city);
    WC()->session->set('amex_wallet_state', $wallet->address->state);
    WC()->session->set('amex_wallet_postcode', $wallet->address->postcode);
    WC()->session->set('amex_wallet_country', $wallet->address->country); // 2 letter code
    WC()->session->set('amex_wallet_phone', $wallet->address->phone);
    WC()->session->set('amex_wallet_email', $wallet->email);
    WC()->session->set('amex_wallet_auth_code', $auth_code);
    WC()->session->set('amex_wallet_token', $token_result['card_token']);
    WC()->session->set('amex_wallet_masked_number', $token_result['card_number']);

    wc_setcookie('fatzebra_amex_eco_auth_code', null, time() - 3600);
  }

  function fz_amex_eco_populate_default_field_value($_, $fieldname) {
    switch($fieldname) {
      case 'billing_email':
        return WC()->session->get('amex_wallet_email');
        break;

      case 'billing_first_name':
      case 'shipping_first_name':
        return WC()->session->get('amex_wallet_first_name');
        break;

      case 'billing_last_name':
      case 'shipping_last_name':
        return WC()->session->get('amex_wallet_last_name');
        break;

      case 'billing_address_1':
      case 'shipping_address_1':
        return WC()->session->get('amex_wallet_address_1');
        break;

      case 'billing_address_2':
      case 'shipping_address_2':
        return WC()->session->get('amex_wallet_address_2');
        break;


      case 'billing_city':
      case 'shipping_city':
        return WC()->session->get('amex_wallet_city');
        break;

      case 'billing_country':
      case 'shipping_country':
        return WC()->session->get('amex_wallet_country');
        break;

      case 'billing_state':
      case 'shipping_state':
        return WC()->session->get('amex_wallet_state');
        break;

      case 'billing_postcode':
      case 'shipping_postcode':
        return WC()->session->get('amex_wallet_postcode');
        break;

      case 'billing_phone':
      case 'shipping_phone':
        return WC()->session->get('amex_wallet_phone');
        break;

      case 'ship_to_different_address':
        return false; // Using wallet we prefill the shipping address. The customer can change this if they want in the form.
      break;
    }
  }

  function fz_amex_eco_ship_to_different_address($value) {
    return false;
  }

  function fz_amex_eco_force_gateway($gateways) {
    if (is_checkout()) {
        // If ECO params are set, permit, else, remove
        if (!is_null(WC()->session->get('amex_wallet_token'))) {
          $forced_gateways = array();
          $forced_gateways['fatzebra_amex_eco'] = $gateways['fatzebra_amex_eco'];
          return $forced_gateways;
        }

        unset($gateways['fatzebra_amex_eco']);
    }

    return $gateways;
  }

  add_filter('woocommerce_payment_gateways', 'add_fz_amex_eco_gateway' );
  add_action('woocommerce_cart_actions', 'inject_amex_eco_button');
  add_action( 'woocommerce_api_wc_fatzebra_amex_eco', 'fz_amex_eco_load_from_auth_code' );
  add_filter( 'woocommerce_checkout_get_value', 'fz_amex_eco_populate_default_field_value', -10, 2);
  add_filter( 'woocommerce_ship_to_different_address_checked', 'fz_amex_eco_ship_to_different_address');
  add_filter( 'woocommerce_available_payment_gateways', 'fz_amex_eco_force_gateway', -10, 1);

}
