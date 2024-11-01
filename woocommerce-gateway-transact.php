<?php
/**
 * Plugin Name: WooCommerce Transact.io Gateway
 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-transact/
 * Description: Take payments on your store using transact.io
 * Author: Transact.io
 * Author URI: https://transact.io/
 * Version: 0.1.0
 * Requires at least: 4.4
 * Tested up to: 5.0.2
 * WC requires at least: 3.5
 * WC tested up to: 3.5
 * Text Domain: woocommerce-gateway-transact
 * Domain Path: /languages
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce fallback notice.
 */
function woocommerce_transact_missing_wc_notice() {

	echo '<div class="error"><p><strong> Transact WooCommerce gateway requires WooCommerce to be installed and active. </strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_gateway_transact_init' );

function woocommerce_gateway_transact_init() {
	load_plugin_textdomain( 'woocommerce-gateway-transact', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_transact_missing_wc_notice' );
		return;
	}

	if ( ! class_exists( 'WC_Transact' ) ) :
		/**
		 * Required minimums and constants
		 */
		define( 'WC_TRANSACT_VERSION', '0.1.0');
		define( 'WC_TRANSACT_MIN_PHP_VER', '5.6.0' );
		define( 'WC_TRANSACT_MIN_WC_VER', '3.5.0' );
		define( 'WC_TRANSACT_MAIN_FILE', __FILE__ );
		define( 'WC_TRANSACT_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_TRANSACT_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

		class WC_Transact {

			/**
			 * @var Singleton The reference the *Singleton* instance of this class
			 */
			private static $instance;

			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			/**
			 * Prevent cloning of the instance of the Singleton instance.
			 *
			 * @return void
			 */
			private function __clone() {}

			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			private function __wakeup() {}

			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			private function __construct() {
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();
			}

			/**
			 * Init the plugin after plugins_loaded so environment variables are set.
			 *
			 */
			public function init() {

				require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-transact.php';

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			}

			/**
			 * Updates the plugin version in db
			 *
			 */
			public function update_plugin_version() {
				delete_option( 'wc_transact_version' );
				update_option( 'wc_transact_version', WC_TRANSACT_VERSION );
			}

			/**
			 * Handles upgrade routines.
			 *
			 */
			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}

				if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_TRANSACT_VERSION !== get_option( 'wc_transact_version' ) ) ) {
					do_action( 'woocommerce_transact_updated' );

					if ( ! defined( 'WC_TRANSACT_INSTALLING' ) ) {
						define( 'WC_TRANSACT_INSTALLING', true );
					}

					$this->update_plugin_version();
				}
			}

			/**
			 * Adds plugin action links.
			 *
			 */
			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="admin.php?page=wc-settings&tab=checkout&section=transact_gateway">' . esc_html__( 'Settings', 'woocommerce-gateway-transact' ) . '</a>',
					'<a href="https://transact.io/about/integration">' . esc_html__( 'Docs', 'woocommerce-gateway-transact' ) . '</a>',
					'<a href="https://transact.io/about/contact">' . esc_html__( 'Support', 'woocommerce-gateway-transact' ) . '</a>',
				);
				return array_merge( $plugin_links, $links );
			}

			/**
			 * Add the gateways to WooCommerce.
			 *
			 */
			public function add_gateways( $methods ) {

				$methods[] = 'WC_Gateway_Transact';

				return $methods;
			}


		}

		WC_Transact::get_instance();
	endif;
}
