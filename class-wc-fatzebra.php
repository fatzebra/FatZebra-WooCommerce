<?php

/*
Plugin Name: WooCommerce Fat Zebra Gateway
Plugin URI: https://www.fatzebra.com.au/help
Description: Extends WooCommerce with Fat Zebra payment gateway.
Version: 0.0.1
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
	if ( ! class_exists( 'woocommerce_payment_gateway' ) ) { return; }

	class WC_FatZebra extends WC_Payment_Gateway {
			
		public function __construct() { 
	        $this->id			= 'fatzebra';
	        $this->icon 		= apply_filters('woocommerce_fatzebra_icon', '');
	        $this->has_fields 	= true;
	        $this->method_title = __( 'Fat Zebra', 'woocommerce' );
		
			$this->live_url 	= "https://gateway.fatzebra.com.au/purchases";
			$this->sandbox_url	= "https://sandbox.gateway.fatzebra.com.au/purchases";
			$this->params 		= array();
			
			// Load the form fields.
			$this->init_form_fields();
				
			// Load the settings.
			$this->init_settings();
				
			// Define user set variables
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
				
			// Actions
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
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
				'title' => array(
								'title' => __( 'Title', 'woocommerce' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ), 
								'default' => __( 'Fat Zebra', 'woocommerce' )
							),
				'test_mode' => array(
								'title' => __( 'Test Mode', 'woocommerce' ), 
								'type' => 'checkbox', 
								'description' => __( 'Switches the gateway to live mode.', 'woocommerce' ), 
								'default' => true
							),
				'sandbox_mode' => array(
								'title' => __( 'Sandbox Mode', 'woocommerce'),
								'type' => "checkbox",
								'description' => 'Switches the gateway URL to the sandbox URL',
								'default' => true
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
	    	<p><?php _e('Allows Fat Zebra Pyaments. ', 'woocommerce'); ?></p>
	    	<table class="form-table">
	    	<?php
	    		// Generate the HTML For the settings form.
	    		$this->generate_settings_html();
	    	?>
			</table><!--/.form-table-->
	    	<?php
	    } // End admin_options()

		function payment_fields() {
			?>
			<p class="form-row">
				<label for="cardholder">
					<?php _e("Full Name", "woocommerce"); ?>
					<abbr class="required" title="required">*</abbr>
				</label>
				<input type="text" name="cardholder" id="cardholder" placeholder="<?php _e("Card Holder", "woocommerce"); ?>" style="width: 48%;"/>
			</p>
			<div class="clear"></div>
			<p class="form-row form-row-first">
				<label for="cardnumber">
					<?php _e("Card Number", "woocommerce"); ?>
					<abbr class="required" title="required">*</abbr>
				</label>
				<input type="text" name="cardnumber" id="cardnumber" style="width: 100%;" />
			</p>

			<p class="form-row form-row-last">
				<label for="card_expiry_month">
					<?php _e("CVV", "woocommerce"); ?>
					<abbr class="required" title="required">*</abbr>
				</label>
				<input type="text" id="card_cvv" name="card_cvv" placeholder="123" />
			</p>
			<div class="clear"></div>
			<p class="form-row">
				<label for="card_expiry_month">
					<?php _e("Expiry", "woocommerce"); ?>
					<abbr class="required" title="required">*</abbr>
				</label>
				<input type="text" id="card_expiry_month" name="card_expiry_month" placeholder="<?php echo date("m"); ?>" style="width: 50px; margin-right: 0;" /> / 
				<input type="text" id="card_expiry_year" name="card_expiry_year" placeholder="<?php echo date("Y"); ?>" style="width: 70px;"/>
			</p>
			<?	
		}
		
		function validate_fields() {
			global $woocommerce;

			if(empty($_POST['cardholder']))
				$woocommerce->add_error(__("Cardholder required", "woocommerce"));
			if(empty($_POST['cardnumber']))
				$woocommerce->add_error(__("Card Number required", "woocommerce"));
			if(empty($_POST['card_cvv']))
				$woocommerce->add_error(__("CVV required", "woocommerce"));
			if(empty($_POST['card_expiry_month']))
				$woocommerce->add_error(__("Expiry Month required", "woocommerce"));
			if(empty($_POST['card_expiry_year']))
				$woocommerce->add_error(__("Expiry Year required", "woocommerce"));

			if(!empty($_POST['card_expiry_year']) && ((int)$_POST['card_expiry_year'] < date("Y")))
				$woocommerce->add_error(__("Expiry date is invalid (year)", "woocommerce"));
			elseif(!empty($_POST['card_expiry_month']) && !((int)$_POST['card_expiry_month'] >= date("m")))
				$woocommerce->add_error(__("Expiry date is invalid (month)", "woocommerce"));

			if(!$woocommerce->error_count()) {
				// OK Good to go!
				$this->params["card_number"] = $_POST['cardnumber'];
				$this->params["card_holder"] = $_POST['cardholder'];
				$this->params["cvv"] = $_POST['card_cvv'];
				$this->params["card_expiry"] = $_POST['card_expiry_month'] . "/" . $_POST['card_expiry_year'];
				$this->params["customer_ip"] = $_SERVER['REMOTE_ADDR'];
				$this->valid = true;
			} else {
				$this->valid = false;
			}
		} // end validate fields

		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );
			$sandbox_mode = $this->settings["sandbox_mode"];
			$test_mode = $this->settings["test_mode"];
			$this->params["reference"] = (string)$order_id;
			$this->params["amount"] = (int)($order->order_total * 100);
			// $this->params["test"] = $test_mode; // This will be available in the next public release of the API

			$order_text = json_encode($this->params);

			if ($sandbox_mode == 1) {
				$url = $this->sandbox_url;
			} else {
				$url = $this->live_url;
			}
			
			$args = array(
				'method' => 'POST',
				'body' => $order_text,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode($this->settings["username"] . ":" . $this->settings["token"]),
					'X-Test-Mode' => $test_mode
				),
				'timeout' => 30
			);
			try {
				$this->response = (array)wp_remote_request($url, $args);

				if ((int)$this->response["response"]["code"] != 200 && (int)$this->response["response"]["code"] != 201) {
					$order->add_order_note("Response error: " . print_r($this->response, true));
					$woocommerce->add_error("Credit Card Payment failed: " . $this->response['response']['message']);
					return;
				}

				$this->response_data = json_decode($this->response['body']);
				error_log(print_r($this->response_data->response, true));


				if ($this->response_data->successful != 1) {
					foreach($this->response_data->errors as $error) {
						$order->add_order_note("Gateway Error: " . $error);	
					}
					
					$woocommerce->add_error("Payment Failed: Unspecific Gateway Error.");
					return;
				}

				if ($this->response_data->response->successful == 1) {
					$order->add_order_note(__("Fat Zebra payment complete. Reference: " . $this->response_data->response->transaction_id));
					$order->payment_complete();

					$woocommerce->cart->empty_cart();
					unset($_SESSION['order_awaiting_payment']);

					return array(
						'result' 	=> 'success',
						'redirect'  => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
					);
				} 

				if ($this->response_data->result->successful != 1) {
					$order->add_order_note(__("Payment Declined: " . $this->response_data->response->message . ". Reference: " . $this->response_data->response->transaction_id));
					$woocommerce->add_error("Payment declined: " . $this->response_data->response->message);
					return;

				}
			} catch (Exception $e) {
				$woocommerce->add_error("Unknown error.");
				return;
			}
		}

		// For the thankyou page :)
		function thankyou_page() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}
	}

	/**
	 * Add the gateway to WooCommerce
	 **/
	function add_fz_gateway( $methods ) {
		$methods[] = 'WC_FatZebra'; return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_fz_gateway' );
}
?>
