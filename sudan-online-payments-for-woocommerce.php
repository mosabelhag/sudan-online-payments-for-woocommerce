<?php
/**
 * Plugin Name: Sudan Online Payments for WooCommerce
 * Plugin URI:  https://github.com/mosabelhag/sudan-online-payments-for-woocommerce
 * Description: Allow customers to pay via Sudanese Bank Transfer and upload a receipt.
 * Version:     1.0.0
 * Author:      MosabElhag
 * Author URI:  https://mousab.com
 * Text Domain: sudan-online-payments-for-woocommerce
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

// Define Plugin Constants
define( 'SUDAN_ONLINE_PAYMENTS_VERSION', '1.0.0' );
define( 'SUDAN_ONLINE_PAYMENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SUDAN_ONLINE_PAYMENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'sudan_online_payments_active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

/**
 * Main Plugin Class
 */
class Sudan_Online_Payments_WooCommerce {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Load the gateway class. Use priority 11 to ensure WooCommerce is loaded.
		add_action( 'plugins_loaded', array( $this, 'init_gateway' ), 11 );

		// Register the gateway
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		
		// Enqueue Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Plugin Action Links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

		// Add Custom Tab to WooCommerce Settings
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_sudan_online_payments', array( $this, 'settings_tab_content' ) );
		add_action( 'woocommerce_update_options_sudan_online_payments', array( $this, 'update_settings' ) );
	}

	/**
	 * Initialize the Gateway Class
	 */
	public function init_gateway() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once SUDAN_ONLINE_PAYMENTS_PLUGIN_DIR . 'includes/class-wc-gateway-sudan-online-payments.php';
			
			// Hooks for AJAX Upload (Must be outside the class construction to ensure they are registered)
			add_action( 'wp_ajax_sudan_online_payments_upload_receipt', array( 'WC_Gateway_Sudan_Online_Payments', 'handle_ajax_upload' ) );
			add_action( 'wp_ajax_nopriv_sudan_online_payments_upload_receipt', array( 'WC_Gateway_Sudan_Online_Payments', 'handle_ajax_upload' ) );
		}
	}

	/**
	 * Add the gateway to WooCommerce
	 * 
	 * @param array $gateways Existing gateways.
	 * @return array Modified gateways.
	 */
	public function add_gateway( $gateways ) {
		if ( ! class_exists( 'WC_Gateway_Sudan_Online_Payments' ) ) {
			require_once SUDAN_ONLINE_PAYMENTS_PLUGIN_DIR . 'includes/class-wc-gateway-sudan-online-payments.php';
		}
		$gateways[] = 'WC_Gateway_Sudan_Online_Payments';
		return $gateways;
	}

	/**
	 * Enqueue Frontend Scripts
	 */
	public function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_style( 'sudan-online-payments-style', SUDAN_ONLINE_PAYMENTS_PLUGIN_URL . 'assets/css/style.css', array(), SUDAN_ONLINE_PAYMENTS_VERSION );
			wp_enqueue_script( 'sudan-online-payments-script', SUDAN_ONLINE_PAYMENTS_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), SUDAN_ONLINE_PAYMENTS_VERSION, true );
			
			wp_localize_script( 'sudan-online-payments-script', 'sudan_online_payments_params', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			));
		}
	}

	/**
	 * Enqueue Admin Scripts
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Only load on WooCommerce Settings page
		if ( 'woocommerce_page_wc-settings' === $hook ) {
			wp_enqueue_script( 'sudan-online-payments-admin-script', SUDAN_ONLINE_PAYMENTS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SUDAN_ONLINE_PAYMENTS_VERSION, true );
			wp_localize_script( 'sudan-online-payments-admin-script', 'sudan_online_payments_admin_params', array(
				'plugin_url' => SUDAN_ONLINE_PAYMENTS_PLUGIN_URL,
			));
		}
	}

	/**
	 * Add Settings Link to Plugins Page
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="admin.php?page=wc-settings&tab=sudan_online_payments">' . __( 'Settings', 'sudan-online-payments-for-woocommerce' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add custom tab to WooCommerce Settings
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['sudan_online_payments'] = __( 'Sudan Online Payments', 'sudan-online-payments-for-woocommerce' );
		return $settings_tabs;
	}

	/**
	 * Output Settings Tab Content
	 * We will just display the Gateway settings here for convenience.
	 */
	public function settings_tab_content() {
		// Ensure the gateway class is loaded
		$this->init_gateway();
		
		// Ideally we would get the instance from WC()->payment_gateways() but for now:
		$gateway = new WC_Gateway_Sudan_Online_Payments();
		$gateway->admin_options();
	}

	/**
	 * Save Options
	 */
	public function update_settings() {
		// Ensure the gateway class is loaded
		$this->init_gateway();

		$gateway = new WC_Gateway_Sudan_Online_Payments();
		$gateway->process_admin_options();
	}
}

// Initialize the plugin
add_action( 'plugins_loaded', array( 'Sudan_Online_Payments_WooCommerce', 'get_instance' ) );


