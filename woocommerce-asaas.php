<?php
/**
 * Plugin Name: WooCommerce Asaas
 * Plugin URI: https://taianunes.com
 * Description: Gateway de pagamento Asaas para WooCommerce.
 * Author: Taian Nunes
 * Author URI: https://taianunes.com
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

		// Checks with WooCommerce and WC Boleto are installed.
		if ( !class_exists( 'WC_Payment_Gateway' ) || !class_exists( 'WC_Boleto' ) ) {
			add_action( 'admin_notices', array( $this, 'plugins_missing_notice' ) );
			return false;
		}


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

		// frontend actions
		if (! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'redirect_single_order' ) );
			add_action( 'template_redirect', array( $this, 'update_order_meta_exp_date' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'bc_add_frontend_sripts' ) );
			return false;
		}

		// admin actions
		add_action( 'current_screen',    array( $this, 'redirect_admin_group_order' ) );
		add_action( 'current_screen',    array( $this, 'redirect_admin_single_order' ) );
		add_action( 'admin_menu',        array( $this, 'add_menu_page' ) );
		add_action( 'admin_notices',     array( $this, 'action_single_order_notice' ) );
		add_action( 'admin_notices',     array( $this, 'action_group_order_notice' ) );

		// admin filters
		add_filter( 'manage_shop_order_posts_columns', 	array( $this, 'filter_manage_shop_order_posts_columns' ), 11, 1 );
		add_filter( 'post_date_column_time', 			array( $this, 'filter_format_products_time_column' ) );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once 'src/class-wc-asaas-api.php';
	}

	public function add_menu_page() {
		$this->home_page_id = add_menu_page( 'Boleto Control', 'Boleto Control', 'edit_posts', 'bc-home', array( $this, 'render_home_page' ), 'dashicons-media-default' );
		// $this->payments_page_id = add_submenu_page( 'bc-home','Controle Pagamentos', 'Controle Pagamentos', 'manage_options', 'bc-payments', array( $this, 'render_page' ) );
		// $this->admin_page_id = add_submenu_page( 'bc-home','BC Admin', 'Admin', 'manage_options', 'bc-admin', array( $this, 'render_admin_page' ) );
		$this->settings_id = add_submenu_page( 'bc-home','Settings', 'Settings', 'manage_options', 'bc-settings', array( $this, 'render_page' ) );

	}

	/**
	 * Missing plugins fallback notice
	 *
	 * @return string
	 */
	public function plugins_missing_notice() {
		include_once 'inc/views/html-notice-plugins-missing.php';
	}

	/**
	 * Home Page View
	 *
	 * @return string
	 */
	public function render_home_page() {
		include_once 'inc/views/html-render-home-page.php';
	}

	/**
	 * Admin Page View
	 *
	 * @return string
	 */
	public function render_admin_page() {
		include_once 'inc/views/html-render-admin-page.php';
	}

	public function bc_add_frontend_sripts()
	{

	    if ( ! is_page( 'assinatura' ) ) {
			return false;
		}

	    // Register the style like this for a plugin:
	    wp_register_style( 'bc-default-style', plugins_url( '/assets/verticaltimeline/css/default.css', __FILE__ ), array(), false, 'all' );
	    wp_register_style( 'bc-component-style', plugins_url( '/assets/verticaltimeline/css/component.css', __FILE__ ), array(), false, 'all'  );

		// Register custom js for plugin:
		wp_register_script( 'bc-vertical-js', plugins_url( '/assets/verticaltimeline/js/modernizr.custom.js', __FILE__ ), array( 'jquery' ) );

	    // enqueue custom style and scripts
	    wp_enqueue_style( 'bc-default-style' );
	    wp_enqueue_style( 'bc-component-style' );
	    wp_enqueue_script( 'bc-vertical-js' );
	}


	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-boleto' );

		load_textdomain( 'woocommerce-boleto', trailingslashit( WP_LANG_DIR ) . 'woocommerce-boleto/woocommerce-boleto-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-boleto', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


}

add_action( 'plugins_loaded', array( 'WC_Boleto_Control', 'instance' ), 15 );