<?php
/**
 * Plugin Name: WooCommerce Asaas
 * Plugin URI: http://github.com/taianunes/woocommerce-asaas
 * Description: Gateway de pagamento Asaas para WooCommerce.
 * Author: Taian Nunes
 * Author URI: http://taianunes.com
 * Version: 0.0.1
 * License: GPLv2 or later
 * Text Domain: woocommerce-asaas
 * Domain Path: languages/
 *
 *
 */

// Don't load directly
defined( 'WPINC' ) or die;

/**
 * WooCommerce Assas main class.
 */
class WC_Asaas {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '0.0.1';

	/**
	 * Static Singleton Holder
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Static Singleton Factory Method
	 *
	 * @return self
	 */
	public static function instance(){
		return self::$instance ? self::$instance : self::$instance = new self;
	}

	/**
	 * Initialize the plugin public actions.
	 */
	private function __construct(){
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );


		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			$this->includes();

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hides_when_is_outside_brazil' ) );
			add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'stop_cancel_unpaid_orders' ), 10, 2 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this, 'ecfb_missing_notice' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}

		// // admin actions
		// add_action( 'current_screen',    array( $this, 'redirect_admin_group_order' ) );
		// add_action( 'current_screen',    array( $this, 'redirect_admin_single_order' ) );
		// add_action( 'admin_menu',        array( $this, 'add_menu_page' ) );
		// add_action( 'admin_notices',     array( $this, 'action_single_order_notice' ) );
		// add_action( 'admin_notices',     array( $this, 'action_group_order_notice' ) );

		// // admin filters
		// add_filter( 'manage_shop_order_posts_columns', 	array( $this, 'filter_manage_shop_order_posts_columns' ), 11, 1 );
		// add_filter( 'post_date_column_time', 			array( $this, 'filter_format_products_time_column' ) );
	}

	/**
	 * Includes.
	 */
	private function includes() {

		include_once plugin_dir_path( __FILE__ ) . 'src/class-wc-asaas-api.php';
		include_once plugin_dir_path( __FILE__ ) . 'src/class-wc-asaas-gateway.php';
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with Asaas.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Asaas_Gateway';

		return $methods;
	}

	/**
	 * Hides the Asaas with payment method with the customer lives outside Brazil.
	 *
	 * @param   array $available_gateways Default Available Gateways.
	 *
	 * @return  array                     New Available Gateways.
	 */
	public function hides_when_is_outside_brazil( $available_gateways ) {

		// Remove PagSeguro gateway.
		if ( isset( $_REQUEST['country'] ) && 'BR' != $_REQUEST['country'] ) {
			unset( $available_gateways['asaas'] );
		}

		return $available_gateways;
	}

	/**
	 * Stop cancel unpaid Asaas orders.
	 *
	 * @param  bool     $cancel Check if need cancel the order.
	 * @param  WC_Order $order  Order object.
	 *
	 * @return bool
	 */
	public function stop_cancel_unpaid_orders( $cancel, $order ) {
		if ( 'asaas' === $order->payment_method ) {
			return false;
		}

		return $cancel;
	}

	/**
	 * Action links.
	 *
	 * @param array $links Action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=asaas' ) ) . '">' . __( 'Settings', 'woocommerce-asaas' ) . '</a>';
		} else {
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_asaas_gateway' ) ) . '">' . __( 'Settings', 'woocommerce-asaas' ) . '</a>';
		}

		return array_merge( $plugin_links, $links );
	}

	/**
	 * WooCommerce Extra Checkout Fields for Brazil notice.
	 */
	public function ecfb_missing_notice() {
		$settings = get_option( 'woocommerce_asaas_settings', array( 'method' => '' ) );

		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-ecfb.php';
		}
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-woocommerce.php';
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-asaas', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}

add_action( 'plugins_loaded', array( 'WC_Asaas', 'instance' ), 15 );