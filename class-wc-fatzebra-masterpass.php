<?php

function fz_masterpass_init() {
  class WC_FatZebra_MasterPass extends WC_Payment_Gateway {

    public function __construct() {
      $this->id           = 'fatzebra_masterpass';
      $this->icon         = "https://www.mastercard.com/mc_us/wallet/img/en/AU/mcpp_wllt_btn_chk_147x034px.png";
      $this->has_fields   = true;
      $this->method_title = __( 'Fat Zebra (MasterPass)', 'woocommerce' );
      $this->version      = "1.5.12";

      $this->api_version  = "1.0";
      $this->live_url     = "https://gateway.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->sandbox_url  = "https://gateway.sandbox.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->sandbox_paynow_url = "https://paynow.sandbox.fatzebra.com.au/v2";
      $this->live_paynow_url = "https://paynow.fatzebra.com.au/v2";

      $this->supports     = array( 'subscriptions', 'products', 'refunds', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_amount_changes', 'subscription_payment_method_change', 'subscription_date_changes' );
      $this->params       = array();

      // Define user set variables
      $this->title        = 'MasterPass';
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

      add_action( 'woocommerce_api_wc_fatzebra_masterpass' , array( $this, 'check_masterpass_response' ) );
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
      //  $this->parent_settings = array_map( array( $this, 'format_settings' ), $this->parent_settings );
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
              'label' => __( 'Enable MasterPass', 'woocommerce' ),
              'default' => 'no'
            ),
      'shared_secret' => array(
              'title' => __("Shared Secret", 'woocommerce'),
              'type' => 'text',
              'description' => __("The shared secret for direct post request and response authentication.", "woocommerce"),
              'default' => ""
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
      $logo_url = plugins_url("images/Fat-Zebra-Certified-small.png", __FILE__);

      ?>
       <p>Check out with MasterCard MasterPass.</p>
      <?php
    }

    /**
     * Process the payment and return the result
     **/
    function process_payment( $order_id ) {
      global $woocommerce;

      $order = new WC_Order( $order_id );

      if (class_exists("WC_Subscriptions_Order") && WC_Subscriptions_Order::order_contains_subscription($order)) {
        // Charge sign up fee + first period here..
        // Periodic charging should happen via scheduled_subscription_payment_fatzebra
        $amount = (int)(WC_Subscriptions_Order::get_total_initial_payment($order) * 100);
      } else {
        $amount = (int)($order->order_total * 100);
      }

      $page_url = $this->parent_settings["sandbox_mode"] == "yes" ? $this->sandbox_paynow_url : $this->live_paynow_url;

      $_SESSION['masterpass_order_id'] = $order_id;

      $username    = $this->parent_settings["username"];
      $currency    = $order->get_order_currency();
      $reference   = (string)$order_id;

      $return_args = array('wc-api' => 'WC_FatZebra_MasterPass', "echo[order_id]" => $order_id);
      if ($amount === 0) $return_args["echo[tokenize]"] = true;
      $return_path = str_replace( 'https:', 'http:', add_query_arg($return_args, home_url( '/' ) ) );

      $verification_string = implode(":", array($reference, $amount, $currency, $return_path));
      $verification_value  = hash_hmac("md5", $verification_string, $this->settings["shared_secret"]);

      $redirect = "$page_url/{$username}/{$reference}/{$currency}/{$amount}/{$verification_value}?masterpass=true&iframe=true&return_path=" . urlencode($return_path);
      if ($amount === 0) $redirect .= "&tokenize_only=true";
      $result = array('result' => 'success', 'redirect' => $redirect);
      return $result;
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

      $token = get_post_meta($order->id, "fatzebra_card_token", true);

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
            break;

          case 4: // Exception caught, something bad happened. Data is exception
          default:
            $error = "Unknown - Error - See error log";
            error_log("WC Fat Zebra (Subscriptions) - Unknown Error (exception): " . print_r($result->get_error_data(), true));
            break;
        }

        // Add the error details and return
        $order->add_order_note(__("Subscription Payment Failed: " . $error . ". Transaction ID: " . $txn_id));
        WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );

      } else { // Success! Returned is an array with the transaction ID etc
        // Update the subscription and return
        // Add a note to the order
        $order->add_order_note(__("Subscription Payment Successful. Transaction ID: " . $result["transaction_id"]));
        WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
      }
    }

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
    * Handle the callback from MasterPass for processing
    *
    */
    function check_masterpass_response() {
      global $woocommerce;
      $message = "";
      $response_class = "success";
      $successful = false;
      $order_id = isset($_GET['reference']) ? $_GET['reference'] : $_GET['echo']['order_id'];
      $order = new WC_Order($order_id);

      $tokenize_only = isset($_GET['echo']['tokenize']);
      // Verify the response
      $verification_str   = $tokenize_only ? implode(":", array($_GET['r'], $_GET['token'])) : implode(":", array($_GET['r'], $_GET['successful'], $_GET['amount'], $_GET['currency'], $_GET['id'], $_GET['token']));
      $verification_value = hash_hmac("md5", $verification_str, $this->settings['shared_secret']);

      if ($verification_value !== $_GET['v']) {
        $woocommerce->add_error(__("Error verifying response from Hosted Payment Page", 'woocommerce'));
      }

      $order = new WC_Order($order_id);

      switch((int)$_GET['r']) {
        case 1:
          $order->payment_complete($_GET['id']);
          $order->add_order_note("Payment via Fat Zebra (MasterPass) successful. Transaction ID: " . $_GET['id'] .   ". Message: " . $_GET['message']);
          $woocommerce->cart->empty_cart();

          update_post_meta($order_id, "fatzebra_card_token", $_GET["token"]);

          $successful = true;

          $message = "Approved";
          break;
        case 2:
          $order->add_order_note("Payment failed: " . $_GET['message']);
          $order->update_status('failed');
          $successful = false;
          $message = $_GET['message'];
          $response_class = "error";
          break;

        case 94:
          $order->add_order_note("Payment failed: MasterPass Cancelled by Customer");
          $successful = false;
          $message = "MasterPass Checkout Cancelled";
          $order->update_status("failed");
          break;

        default:
          if(isset($order)) {
            $order->add_order_note("Payment failed. Response code: " . $_GET['r']);
            $order->update_status("failed");
          }

          $successful = false;
          $message = "Unknown Error";
          break;
      }

      if (!$successful) {
        $woocommerce->add_error( __( 'Payment error: ', 'woocommerce' ) . $message );
      }
      $redirect_url =  add_query_arg('order',
                                      $order->id,
                                      add_query_arg('key', $order->order_key,
                                      get_permalink(get_option('woocommerce_thanks_page_id'))));

      wp_redirect($redirect_url);
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
        if(empty($ip)) $ip = "127.0.0.1";
        if(!isset($payload["customer_ip"])) $payload["customer_ip"] = $ip;

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
  }

  /**
   * Add the gateway to WooCommerce
   **/
  function add_fz_masterpass_gateway( $methods ) {
    $methods[] = 'WC_FatZebra_MasterPass'; return $methods;
  }

  add_filter('woocommerce_payment_gateways', 'add_fz_masterpass_gateway' );
}
