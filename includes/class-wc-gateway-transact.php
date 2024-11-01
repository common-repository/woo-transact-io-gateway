<?php

if (!class_exists('TransactIoMsg')) {
	require_once dirname(__FILE__) . '/../vendors/transact-io-php/transact-io.php';
}

use Firebase\JWT\SignatureInvalidException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Gateway_Transact extends WC_Payment_Gateway {

		/**
		 * Notices (array)
		 * @var array
		 */
		public $notices = array();

		/**
		 * Alternate credit card statement name
		 *
		 * @var bool
		 */
		public $statement_descriptor;

		/**
		 * secret key
		 *
		 * @var string
		 */
		private $secret_key;


		/**
		 * Constructor
		 */
		public function __construct() {
			$this->retry_interval = 1;
			$this->id             = 'transact_gateway';
			$this->method_title   = 'Transact.io';
			$this->title = '<a href="https://transact.io" target="_blank">Transact.io</a> - Your digital debit card';


			$this->method_description = 'TRANSACT enables small payments on the web '
				.' without bank fees that consume or exceed the value of the '
				.' transaction, enabling an entirely new economy.'
				.' The settings here must match your publisher account settings on transact.io';

			$this->logo = 'https://transact.io/assets/images/favicon.png';
			$this->has_fields         = false;
			$this->supports           = array(
				'products',
			);

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();
			$this->description          = $this->get_option( 'description' );
			$this->enabled              = $this->get_option( 'enabled' );



			$this->secret_key           = ! empty( $this->settings['secret_key'] ) ? $this->settings['secret_key'] : '';
			$this->account_id           = ! empty( $this->settings['account_id'] ) ? $this->settings['account_id'] : '';


			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
//			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );


			add_action( 'woocommerce_api_transact', array( $this, 'transact_api_callback' ) );

		}
		public function transact_api_callback() {

			error_log('transact_api_callback');

			if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				error_log('non post request received by transact API');
				status_header( 400 );
				return;
			}
			error_log('transact_api_callback: post: '.  print_r($_POST, TRUE));

//			error_log('transact_api_callback posted'. print_r($posted, TRUE));
			if (empty($_POST) || empty($_POST['transact_token'])) {
				status_header( 400 );
				return;
			}


			try {
				$transact = new TransactIoMsg(); // get new instance
				$transact->setSecret($this->secret_key);
				$decoded = $transact->decodeToken($_POST['transact_token']);
				error_log('transact API decoded'. print_r($decoded, TRUE));

				$order_id = $decoded->item;
				$order = wc_get_order( $order_id );

				$order->update_meta_data( '_transact_id', $decoded->tid );
				$order->update_meta_data( '_transact_token', $_POST['transact_token']);
				$order->payment_complete();
				$order->set_payment_method_title( 'Transact.io' );
				$order->save();


				error_log('order complete' . print_r($order, TRUE));

				WC()->cart->empty_cart();

				$url = $this->get_return_url($order);
				error_log('return url: '. $url);
				wp_redirect($url);


			} catch ( SignatureInvalidException $e) {
				error_log('Invalid signature ' . print_r($e, TRUE));


				status_header( 400 );
				echo "error validating signature, check store signing keys";
				exit;
//				wp_redirect( wc_get_page_permalink( 'cart' ) );


			} catch ( Exception $e ) {
				error_log('Error processing. ' . print_r($e, TRUE));
				status_header( 400 );
				echo "error processing please, report this. ";
				exit;
			}

		}

		/**
		 * Checks to see if all criteria is met before showing payment method.

		 */
		public function is_available() {

			return get_woocommerce_currency() == 'USD';

		}

		/**
		 * Get_icon function.
		 * @return string
		 */

		public function get_icon() {

			$icon = '<img src="https://transact.io/assets/images/transact_logo-tm-horiz.png">';
			return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );

		}

		/**
		 * payment_scripts function.
		 *
		 * Outputs scripts used
		 *
		 * @access public
		 */

		 /*
		public function payment_scripts() {

			error_log('payment scripts here');

			if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() ) {
				return;
			}

			//wp_enqueue_style( 'transact_styles' );
			//wp_enqueue_script( 'woocommerce_transact' );
		}
		*/


		/**
		 * Initialize Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = require( WC_TRANSACT_PLUGIN_PATH . '/includes/admin/transact-settings.php' );
		}

/*
		public function get_transaction_url( $order ) {
			error_log('get_transaction_url: '. print_r($order, TRUE));
		}
*/

		/**
		 * Renders the payment form to customers
		 *
		 */

		public function form() {

		}

		/**
		 * Payment form on checkout page
		 */

		 /*
		public function payment_fields() {
			$total                = WC()->cart->total;
			$display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards;
			$description          = $this->get_description();
			$description          = ! empty( $description ) ? $description : '';

			// If paying from order, we need to get total from order not cart.
			if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
				$order = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) );
				$total = $order->get_total();
			}

			if ( is_add_payment_method_page() ) {
				$total = '';
			}

			echo '<div
				id="stripe-sepa_debit-payment-data"
				data-amount="' . esc_attr( WC_Stripe_Helper::get_stripe_amount( $total ) ) . '"
				data-currency="' . esc_attr( strtolower( get_woocommerce_currency() ) ) . '">';


			$description = trim( $description );

			echo apply_filters( 'wc_stripe_description', wpautop( wp_kses_post( $description ) ), $this->id );

			if ( $display_tokenization ) {
				$this->tokenization_script();
				$this->saved_payment_methods();
			}

			$this->form();

			if ( apply_filters( 'wc_stripe_display_save_payment_method_checkbox', $display_tokenization ) && ! is_add_payment_method_page() && ! isset( $_GET['change_payment_method'] ) ) {
				$this->save_payment_method_checkbox();
			}

			do_action( 'wc_stripe_sepa_payment_fields', $this->id );

			echo '</div>';
		}
		*/

		/**
		 * Process the payment
		 *
		 * @param int  $order_id Reference.
		 * @param bool $retry Should we retry on fail.
		 * @param bool $force_save_source Force save the payment source.
		 *
		 * @throws Exception If payment will not be accepted.
		 *
		 * @return array|void
		 */
		public function process_payment( $order_id, $retry = true, $force_save_source = false ) {

			try {
				error_log('process_payment order ID: '. $order_id);

				$order = wc_get_order( $order_id );

				error_log('process_payment order: '. print_r($order, true));

				$cart_contents = WC()->cart->get_cart_contents();
				error_log('process_payment cart_contents: '. print_r($cart_contents, true));

				$item_names = [];
				foreach ( $cart_contents as $cart_item_key => $values ) {
					$product = $values['data'];
					$item_names[] = $product->get_name();
				}
				$item_names = array_unique($item_names);



				error_log('process_payment item_names: '. print_r($item_names, true));

				error_log('process_payment this: '. print_r($this, true));

				$cart_url = wc_get_cart_url();
				error_log('process_payment cart_url: '. print_r($cart_url, true));


				$transact = new TransactIoMsg(); // get new instance

				$transact->setSecret($this->secret_key);

				// Required: set ID of who gets paid
  			$transact->setRecipient($this->account_id);

				// Price of sale in cents
				$total = $order->get_total() *100;

				$transact->setPrice(round($total));
				$transact->setMethod('POST');
				$transact->setURL(get_site_url() . '/wc-api/transact/');
				$transact->setItem($order_id);
				$transact->setUid($order->get_order_key());

				$site_name = get_bloginfo( 'name' );
				$title = $site_name .' : ' . implode(', ', $item_names);;
				$transact->setTitle($title);
				$token = $transact->getToken();

				error_log('process_payment transact: '. print_r($transact, true));


				return array(
					'result'   => 'success',
//					'redirect' => 'http://localhost:5555/purchase/?t='. $token,
//					'redirect' => 'https://test.xsact.com/purchase/?t='. $token,
					'redirect' => 'https://transact.io/purchase/?t='. $token,
				);

			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
				error_log('transact error:'. print_r($e, TRUE));

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}

		}
	}


