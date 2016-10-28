<?php

function fz_visacheckout_init() {
  class WC_FatZebra_VisaCheckout extends WC_Payment_Gateway {

    public function __construct() {
      $this->id           = 'fatzebra_visacheckout';
      $this->icon         = "https://assets.secure.checkout.visa.com/VmeCardArts/partner/POS_horizontal_99x34.png";
      $this->has_fields   = true;
      $this->method_title = __( 'Fat Zebra (VISA Checkout)', 'woocommerce' );
      $this->version      = "1.5.12";

      $this->api_version  = "1.0";
      $this->live_url     = "https://gateway.fatzebra.com.au/v{$this->api_version}/purchases";
      $this->sandbox_url  = "https://gateway.sandbox.fatzebra.com.au/v{$this->api_version}/purchases";

      $this->supports     = array( 'subscriptions', 'products', 'refunds', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_amount_changes', 'subscription_payment_method_change', 'subscription_date_changes' );
      $this->params       = array();

      // Define user set variables
      $this->title        = 'Visa Checkout';
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
              'label' => __( 'Enable Visa Checkout', 'woocommerce' ),
              'default' => 'no'
            ),
      'api_key' => array(
              'title' => __("API Key", 'woocommerce'),
              'type' => 'text',
              'description' => __("The Visa Checkout API Key (provided by Fat Zebra).", "woocommerce"),
              'default' => ""
            ),
      'logo_url' => array(
              'title' => "Logo URL",
              'type' => 'text',
              'description' => "The HTTPS Logo URL to be displayed in the Visa Checkout Dialog. Maximum 174px wide and 34px high.",
              'default' => ''
            ),
      'collect_shipping' => array(
              'title' => "Collect Shipping Details",
              'type' => 'checkbox',
              'default' => 'yes'
            ),
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
        global $woocommerce;
        $callid = WC()->session->get('visa_wallet_callid');
      ?>
      <input type='hidden' name='encKey' id='encKey' />
      <input type='hidden' name='encPaymentData' id='encPaymentData' />
      <input type='hidden' name='callid' id='callid' value='<?php echo $callid; ?>' />
      <input type='hidden' name='token' id='token' value='<?php echo WC()->session->get('visa_wallet_token'); ?>' />

      <p>
        With Visa Checkout, you now have an easier way to pay with your card online, from the company you know and trust. Create an account once and speed through your purchase without re-entering payment or shipping information wherever you see Visa Checkout.
      </p>

      <?php if (!empty($callid)) { ?>
        <p style='font-weight: bold'>The following card has been selected via Visa Checkout:</p>
        <p style='font-size: 20px; padding-left: 40px; margin-top: 20px; font-family: monospace;'>
          <?php echo WC()->session->get('visa_wallet_masked_number'); ?>
        </p>

      <?php } ?>

      <style type='text/css'>
        #custom-visa-checkout-outer-container{
         float: right;
        }
      </style>
      <?php

      $visa_base_url = $this->parent_settings['sandbox_mode'] == "yes" ? 'https://sandbox.secure.checkout.visa.com' : 'https://secure.checkout.visa.com';
      $accepted_cards = array();
      foreach($this->parent_settings['show_card_logos'] as $card) {
          if ($card == 'american_express') $card = "AMEX";
          $accepted_cards[] = strtoupper($card);
      }

      wp_register_script( 'fz-visacheckout', plugin_dir_url(__FILE__) . '/images/fatzebra-visacheckout.js', array(), WC_VERSION, false );
      wp_register_script( 'visa-checkout', "{$visa_base_url}/checkout-widget/resources/js/integration/v1/sdk.js", array(), WC_VERSION, true );
      wp_localize_script( 'fz-visacheckout', 'fzvisa', array(
            'api_key' => $this->settings['api_key'],
            'website' => get_site_url(),
            'website_name' => get_bloginfo('title'),
            'logo_url' => $this->settings['logo_url'],
            'regions' => array('AU'),
            'card_brands' => $accepted_cards,
            'currency' => get_woocommerce_currency(),
            'order_total' => $woocommerce->cart->total,
            'visa_checkout_button' => $this->get_visa_checkout_button($accepted_cards),
            'button_text' => 'Pay',
            'review_message' => 'Please select your card and click Pay to complete your order',
            'shipping' => $this->settings['collect_shipping'] == "yes",
            'checkout_captured' => !is_null($callid)
            )
          );
      wp_enqueue_script( 'fz-visacheckout' );
      wp_enqueue_script( 'visa-checkout' );

      if ($this->parent_settings['fraud_data'] == 'yes' && $this->parent_settings["fraud_device_id"] === "yes") {
        $device_id_url = $this->parent_settings['sandbox_mode'] == 'yes' ? 'https://ci-mpsnare.iovation.com/snare.js' : 'https://mpsnare.iesnare.com/snare.js';
        wp_register_script('fz-deviceid', plugin_dir_url(__FILE__) . '/images/fatzebra-deviceid.js', array(), WC_VERSION, false);
        wp_register_script('fz-io-bb', $device_id_url, array('fz-deviceid'), '1', true);
        wp_enqueue_script('fz-io-bb');
      }

      if (!is_null($callid)) {
        //pre-select Visa Checkout as the payment method
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $available_gateways['fatzebra_visacheckout']->set_current();
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

      $this->params["wallet"] = array(
                                  "type" => "VISA",
                                  "callid" => $_POST['callid']
                                );

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
          $this->clear_visa_checkout_session_values();

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
      if (isset($params['callid'])) {
        $payload = array("wallet" => array("type" => "VISA", "callid" => $params['callid']));
      } else {
        $payload = array("card_holder" => $params["card_holder"], "card_number" => $params["card_number"], "card_expiry" => $params["card_expiry"], "cvv" => $params["cvv"]);
      }
      $order_text = json_encode($payload);

      $args = array('method' => 'POST', 'body' => $order_text, 'headers' => array('Authorization' => 'Basic ' . base64_encode($this->parent_settings["username"] . ":" . $this->parent_settings["token"]), 'X-Test-Mode' => $test_mode, 'User-Agent' => "WooCommerce Plugin " . $this->version), 'timeout' => 30);
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

    /**
    *
    * @return mixed WP_Error or Array (result)
    */
    function do_payment($params) {
      $sandbox_mode = $this->parent_settings["sandbox_mode"] == "yes"; // Yup, the checkbox settings return as 'yes' or 'no'
      $test_mode = $this->parent_settings["test_mode"] == "yes";

      $order_text = json_encode($params);

      $url = $sandbox_mode ? $this->sandbox_url : $url = $this->live_url;

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
          $error->add(3,
            "Payment Declined",
            array(
              "message" => $this->response_data->response->message,
              "id" => $this->response_data->response->id,
              "fraud_result" => $this->response_data->response->fraud_result,
              "fraud_messages" => $this->response_data->response->fraud_messages
            )
          );
          return $error;
        }

        if ($this->response_data->response->successful) {
          return array(
            "transaction_id" => $this->response_data->response->id,
            "card_token" => $this->response_data->response->card_token,
            "fraud_result" => $this->response_data->response->fraud_result,
            "fraud_messages" => $this->response_data->response->fraud_messages
          );
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

    function clear_visa_checkout_session_values() {
      $keys = array('visa_wallet_first_name', 'visa_wallet_last_name', 'visa_wallet_address_1', 'visa_wallet_address_2', 'visa_wallet_city', 'visa_wallet_state', 'visa_wallet_postcode', 'visa_wallet_phone', 'visa_wallet_email', 'visa_wallet_callid', 'visa_wallet_token');

      foreach($keys as $key):
        WC()->session->set($key, null);
      endforeach;
    }

    function get_visa_checkout_button($accepted_cards) {
      return "<style type='text/css'>
                #custom-visa-checkout-outer-container{
                  position:relative;
                  width: 213px;
                  display: inline-block;
                  margin-top: 5px;
                }
                #custom-visa-checkout-learn-more{
                  width:210px;
                  text-align:right;
                  margin-top: -10px;
                }
                #custom-visa-checkout-learn-more-text{
                  font:bold 8.5pt arial;
                  text-decoration: none;
                  color: #0044AA;
                }
                img#custom-visa-checkout {
                  max-width: inherit;
                }

                .v-button {
                  cursor: pointer;
                  width: 213px !important;
                  box-shadow: none !important;
                  border: none !important;
                  backgound: none !important;
                }
              </style>
              <div id='custom-visa-checkout-outer-container'>
                <img alt='Button' class='v-button' data-2x-image='https://secure.checkout.visa.com/wallet-services-web/xo/button.png?locale=en_AU&amp;card_brands=VISA,MasterCard&amp;style=color&amp;size=425' height='47' id='v-checkout-button' role='button' src='https://secure.checkout.visa.com/wallet-services-web/xo/button.png?locale=en_AU&amp;card_brands=VISA,MasterCard&amp;style=color&amp;size=213' width='213'>
                <div id='custom-visa-checkout-learn-more'>
                  <a href='javascript:void(0)' id='custom-visa-checkout-learn-more-text' onclick=\"document.getElementById('custom-visa-checkout').style.display='block'\">Learn More</a>
                </div>
                <img alt='Cart_learnmore_pop-up_left' id='custom-visa-checkout' onclick=\"document.getElementById('custom-visa-checkout').style.display='none';\" src='https://sandbox-assets.secure.checkout.visa.com/VmeCardArts/partner/Cart_LearnMore_Pop-Up_left.png' style='display: none; z-index: 9999; position: absolute; top: -170px; left: 220px; box-shadow: none; width: auto;'>
              </div>";
    }
  }

  /**
   * Add the gateway to WooCommerce
   **/
  function add_fz_visacheckout_gateway( $methods ) {
    $methods[] = 'WC_FatZebra_VisaCheckout'; return $methods;
  }

  function inject_visacheckout_button() {
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if (!isset($payment_gateways['fatzebra_visacheckout'])) {
      return;
    }

    $gw = $payment_gateways['fatzebra_visacheckout'];

    $amount = WC()->cart->total;
    $visa_base_url = $gw->parent_settings['sandbox_mode'] == "yes" ? 'https://sandbox.secure.checkout.visa.com' : 'https://secure.checkout.visa.com';
      $accepted_cards = array();
      foreach($gw->parent_settings['show_card_logos'] as $card) {
          if ($card == 'american_express') $card = "AMEX";
          $accepted_cards[] = strtoupper($card);
      }

      wp_register_script( 'fz-visacheckout', plugin_dir_url(__FILE__) . '/images/fatzebra-visacheckout.js', array(), WC_VERSION, false );
      wp_register_script( 'visa-checkout', "{$visa_base_url}/checkout-widget/resources/js/integration/v1/sdk.js", array(), WC_VERSION, true );
      wp_localize_script( 'fz-visacheckout', 'fzvisa', array(
            'api_key' => $gw->settings['api_key'],
            'website' => get_site_url(),
            'website_name' => get_bloginfo('title'),
            'logo_url' => $gw->settings['logo_url'],
            'regions' => array('AU'),
            'card_brands' => $accepted_cards,
            'currency' => get_woocommerce_currency(),
            'order_total' => $amount,
            'visa_checkout_button' => $gw->get_visa_checkout_button($accepted_cards),
            'inline' => true,
            'shipping' => $gw->settings['collect_shipping'] == "yes",
            'button_text' => 'Continue',
            'review_message' => 'Please select your card and click Continue to finish your order',
            )
          );
      wp_enqueue_script( 'fz-visacheckout' );
      wp_enqueue_script( 'visa-checkout' );
  }

  function fz_visacheckout_set_callid_cookie() {
    if (isset($_POST['callid']) && !empty($_POST['callid'])) {
      wc_setcookie( 'fatzebra_visacheckout_callid', $_POST['callid'] );
    }
  }

  function fz_visacheckout_load_from_callid($fields) {
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if (!isset($payment_gateways['fatzebra_visacheckout'])) {
      return $fields;
    }

    $gw = $payment_gateways['fatzebra_visacheckout'];

    $callid = isset($_COOKIE['fatzebra_visacheckout_callid']) ? $_COOKIE['fatzebra_visacheckout_callid'] : null;
    if (empty($callid)) {
      return $fields;
    }

    $token_result = $gw->tokenize_card(array("callid" => $callid));

    if (is_wp_error($token_result)) {
      // Tokenization error - for now lets return the fields with no manipulation..

      return $fields;
    } // Arghhh!

    $wallet = $token_result['wallet'];

    // Store the wallet data in a session. It is important to remove this upon successful checkout....
    WC()->session->set('visa_wallet_first_name', $wallet->name->first);
    WC()->session->set('visa_wallet_last_name', $wallet->name->last);
    WC()->session->set('visa_wallet_address_1', $wallet->address->line_1);
    WC()->session->set('visa_wallet_address_2', $wallet->address->line_2);
    WC()->session->set('visa_wallet_city', $wallet->address->city);
    WC()->session->set('visa_wallet_state', $wallet->address->state);
    WC()->session->set('visa_wallet_postcode', $wallet->address->postcode);
    WC()->session->set('visa_wallet_phone', $wallet->address->phone);
    WC()->session->set('visa_wallet_email', $wallet->email);
    WC()->session->set('visa_wallet_callid', $callid);
    WC()->session->set('visa_wallet_token', $token_result['card_token']);
    WC()->session->set('visa_wallet_masked_number', $token_result['card_number']);

    wc_setcookie('fatzebra_visacheckout_callid', null, time() - 3600);
    return $fields;
  }

  function fz_visacheckout_populate_default_field_value($_, $fieldname) {
    switch($fieldname) {
      case 'billing_email':
        return WC()->session->get('visa_wallet_email');
        break;

      case 'billing_first_name':
      case 'shipping_first_name':
        return WC()->session->get('visa_wallet_first_name');
        break;

      case 'billing_last_name':
      case 'shipping_last_name':
        return WC()->session->get('visa_wallet_last_name');
        break;

      case 'billing_address_1':
      case 'shipping_address_1':
        return WC()->session->get('visa_wallet_address_1');
        break;

      case 'billing_address_2':
      case 'shipping_address_2':
        return WC()->session->get('visa_wallet_address_2');
        break;


      case 'billing_city':
      case 'shipping_city':
        return WC()->session->get('visa_wallet_city');
        break;

      case 'billing_state':
      case 'shipping_state':
        return WC()->session->get('visa_wallet_state');
        break;

      case 'billing_postcode':
      case 'shipping_postcode':
        return WC()->session->get('visa_wallet_postcode');
        break;

      case 'billing_phone':
      case 'shipping_phone':
        return WC()->session->get('visa_wallet_phone');
        break;

      case 'ship_to_different_address':
        return false; // Using wallet we prefill the shipping address. The customer can change this if they want in the form.
      break;
    }
  }

  function fz_visacheckout_ship_to_different_address($value) {
    return false;
  }

  function fz_visacheckout_force_gateway($gateways) {
    if (is_null(WC()->session && WC()->session->get('visa_wallet_callid')))  {
      return $gateways;
    } else {

      if(!isset($gateways['fatzebra_visacheckout'])) return $gateways;

      $forced_gateways = array();
      $forced_gateways['fatzebra_visacheckout'] = $gateways['fatzebra_visacheckout'];

      return $forced_gateways;
    }
  }

  function fz_visacheckout_anticlickjack() {
    $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    // Only show if Visa Checkout enabled
    if (!isset($payment_gateways['fatzebra_visacheckout'])) {
      return;
    }

    // Only show on Cart and Checkout
    if(wc_get_page_id( 'checkout' ) != get_the_ID() && wc_get_page_id('cart') != get_the_ID()){
      return;
    }
    ?>
      <style id="antiClickjack">body{display:none;}</style>
      <script type="text/javascript">
        if (self === top) {
          var antiClickjack = document.getElementById("antiClickjack");
          antiClickjack.parentNode.removeChild(antiClickjack);
        } else {
          top.location = self.location;
        }
      </script>
    <?php
  }

  add_filter('woocommerce_payment_gateways', 'add_fz_visacheckout_gateway' );
  add_action('woocommerce_proceed_to_checkout', 'inject_visacheckout_button');

  add_action( 'woocommerce_set_cart_cookies',  'fz_visacheckout_set_callid_cookie' );
  add_filter( 'woocommerce_checkout_fields' , 'fz_visacheckout_load_from_callid' );
  add_filter( 'woocommerce_checkout_get_value', 'fz_visacheckout_populate_default_field_value', -10, 2);
  add_filter( 'woocommerce_ship_to_different_address_checked', 'fz_visacheckout_ship_to_different_address');
  add_filter( 'woocommerce_available_payment_gateways', 'fz_visacheckout_force_gateway', -10, 1);
  add_action( 'wp_head', 'fz_visacheckout_anticlickjack', 1);
}
