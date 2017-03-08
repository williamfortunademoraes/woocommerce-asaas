<?php
/**
 * WooCommerce Asaas Gateway class
 *
 * @package WooCommerce_Asaas/Classes/Gateway
 * @version 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Asaas gateway.
 */
class WC_Asaas_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'asaas';
		$this->icon               = apply_filters( 'woocommerce_asaas_icon', plugins_url( 'assets/images/asaas.png', plugin_dir_path( __FILE__ ) ) );
		$this->method_title       = __( 'Asaas', 'woocommerce-asaas' );
		$this->method_description = __( 'Accept payments by banking ticket using the Asaas.', 'woocommerce-asaas' );
		$this->order_button_text  = __( 'Proceed to payment', 'woocommerce-asaas' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->token             = $this->get_option( 'token' );
		$this->sandbox_token     = $this->get_option( 'sandbox_token' );
		$this->send_only_total   = $this->get_option( 'send_only_total', 'no' );
		$this->invoice_prefix    = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->sandbox           = $this->get_option( 'sandbox', 'no' );
		$this->debug             = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Set the API.
		$this->api = new WC_Asaas_API( $this );

		// Main actions.
		add_action( 'woocommerce_api_wc_pagseguro_gateway', array( $this, 'ipn_handler' ) );
		add_action( 'valid_asaas_api_request', array( $this, 'update_order_status' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' === get_woocommerce_currency();
	}

	/**
	 * Get token.
	 *
	 * @return string
	 */
	public function get_token() {
		return 'yes' === $this->sandbox ? $this->sandbox_token : $this->token;
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = 'yes' === $this->get_option( 'enabled' ) && '' !== $this->get_token() && $this->using_supported_currency();

		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			$available = false;
		}

		return $available;
	}

	// /**
	//  * Has fields.
	//  *
	//  * @return bool
	//  */
	// public function has_fields() {
	// 	return 'transparent' === $this->method;
	// }

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ( is_checkout() && $this->is_available() ) {
			if ( ! get_query_var( 'order-received' ) ) {
				$session_id = $this->api->get_session_id();
				$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

				wp_enqueue_style( 'Asaas-checkout', plugins_url( 'assets/css/transparent-checkout' . $suffix . '.css', plugin_dir_path( __FILE__ ) ), array(), WC_PagSeguro::VERSION );
				wp_enqueue_script( 'Asaas-library', $this->api->get_direct_payment_url(), array(), null, true );
				wp_enqueue_script( 'Asaas-checkout', plugins_url( 'assets/js/transparent-checkout' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'Asaas-library', 'woocommerce-extra-checkout-fields-for-brazil-front' ), WC_PagSeguro::VERSION, true );

				wp_localize_script(
					'Asaas-checkout',
					'wc_pagseguro_params',
					array(
						'session_id'         => $session_id,
						'interest_free'      => __( 'interest free', 'woocommerce-asaas' ),
						'invalid_card'       => __( 'Invalid credit card number.', 'woocommerce-asaas' ),
						'invalid_expiry'     => __( 'Invalid expiry date, please use the MM / YYYY date format.', 'woocommerce-asaas' ),
						'expired_date'       => __( 'Please check the expiry date and use a valid format as MM / YYYY.', 'woocommerce-asaas' ),
						'general_error'      => __( 'Unable to process the data from your credit card on the Asaas, please try again or contact us for assistance.', 'woocommerce-asaas' ),
						'empty_installments' => __( 'Select a number of installments.', 'woocommerce-asaas' ),
					)
				);
			}
		}
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	protected function get_log_view() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'woocommerce-asaas' ) . '</a>';
		}

		return '<code>woocommerce/logs/' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>';
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-asaas' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Asaas', 'woocommerce-asaas' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-asaas' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-asaas' ),
				'desc_tip'    => true,
				'default'     => __( 'Asaas', 'woocommerce-asaas' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-asaas' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-asaas' ),
				'default'     => __( 'Pay via Asaas', 'woocommerce-asaas' ),
			),
			'integration' => array(
				'title'       => __( 'Integration', 'woocommerce-asaas' ),
				'type'        => 'title',
				'description' => '',
			),
			// 'method' => array(
			// 	'title'       => __( 'Integration method', 'woocommerce-asaas' ),
			// 	'type'        => 'select',
			// 	'description' => __( 'Choose how the customer will interact with the Asaas. Redirect (Client goes to Asaas page) or Lightbox (Inside your store)', 'woocommerce-asaas' ),
			// 	'desc_tip'    => true,
			// 	'default'     => 'direct',
			// 	'class'       => 'wc-enhanced-select',
			// 	'options'     => array(
			// 		'redirect'    => __( 'Redirect (default)', 'woocommerce-asaas' ),
			// 		'lightbox'    => __( 'Lightbox', 'woocommerce-asaas' ),
			// 		'transparent' => __( 'Transparent Checkout', 'woocommerce-asaas' ),
			// 	),
			// ),
			'sandbox' => array(
				'title'       => __( 'Asaas Sandbox', 'woocommerce-asaas' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Asaas Sandbox', 'woocommerce-asaas' ),
				'desc_tip'    => true,
				'default'     => 'no',
				'description' => __( 'Asaas Sandbox can be used to test the payments.', 'woocommerce-asaas' ),
			),
			// 'email' => array(
			// 	'title'       => __( 'Asaas Email', 'woocommerce-asaas' ),
			// 	'type'        => 'text',
			// 	'description' => __( 'Please enter your Asaas email address. This is needed in order to take payment.', 'woocommerce-asaas' ),
			// 	'desc_tip'    => true,
			// 	'default'     => '',
			// ),
			'token' => array(
				'title'       => __( 'Asaas Token', 'woocommerce-asaas' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Please enter your Asaas token. This is needed to process the payment and notifications. Is possible generate a new token %s.', 'woocommerce-asaas' ), '<a href="https://asaas.com/documentacao/documentacao/">' . __( 'here', 'woocommerce-asaas' ) . '</a>' ),
				'default'     => '',
			),
			// 'sandbox_email' => array(
			// 	'title'       => __( 'Asaas Sandbox Email', 'woocommerce-asaas' ),
			// 	'type'        => 'text',
			// 	'description' => sprintf( __( 'Please enter your Asaas sandbox email address. You can get your sandbox email %s.', 'woocommerce-asaas' ), '<a href="https://sandbox.Asaas.uol.com.br/comprador-de-testes.html">' . __( 'here', 'woocommerce-asaas' ) . '</a>' ),
			// 	'default'     => '',
			// ),
			'sandbox_token' => array(
				'title'       => __( 'Asaas Sandbox Token', 'woocommerce-asaas' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Please enter your Asaas sandbox token. You can get your sandbox token %s.', 'woocommerce-asaas' ), '<a href="https://asaas.com/documentacao/documentacao/">' . __( 'here', 'woocommerce-asaas' ) . '</a>' ),
				'default'     => '',
			),
			'behavior' => array(
				'title'       => __( 'Integration Behavior', 'woocommerce-asaas' ),
				'type'        => 'title',
				'description' => '',
			),
			'send_only_total' => array(
				'title'   => __( 'Send only the order total', 'woocommerce-asaas' ),
				'type'    => 'checkbox',
				'label'   => __( 'If this option is enabled will only send the order total, not the list of items.', 'woocommerce-asaas' ),
				'default' => 'no',
			),
			'switch' => array(
				'title'   => __( 'Send only the order total', 'woocommerce-asaas' ),
				'type'    => 'select',
			    'options' => array(
			        'standard' => __( 'Option One', 'cmb2' ),
			        'custom'   => __( 'Option Two', 'cmb2' ),
			        'none'     => __( 'Option Three', 'cmb2' )),
				'label'   => __( 'If this option is enabled will only send the order total, not the list of items.', 'woocommerce-asaas' ),
				'default' => 'no',
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', 'woocommerce-asaas' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your Asaas account for multiple stores ensure this prefix is unqiue as Asaas will not allow orders with the same invoice number.', 'woocommerce-asaas' ),
				'desc_tip'    => true,
				'default'     => 'WC-',
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-asaas' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-asaas' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-asaas' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Asaas events, such as API requests, inside %s', 'woocommerce-asaas' ), $this->get_log_view() ),
			),
		);
	}

	/**
	 * Admin page.
	 */
	public function admin_options() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'Asaas-admin', plugins_url( 'assets/js/admin' . $suffix . '.js', base_dir_path( __FILE__ ) ), array( 'jquery' ), WC_Asaas::VERSION, true );

		var_dump(dirname( __FILE__ ) );
		var_dump(plugin_dir_path( __FILE__ ));

		include dirname( __FILE__ ) . '/views/html-admin-page.php';
	}

	/**
	 * Send email notification.
	 *
	 * @param string $subject Email subject.
	 * @param string $title   Email title.
	 * @param string $message Email message.
	 */
	protected function send_email( $subject, $title, $message ) {
		$mailer = WC()->mailer();

		$mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
	}

	/**
	 * Payment fields.
	 */
	public function payment_fields() {
		wp_enqueue_script( 'wc-credit-card-form' );

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$cart_total = $this->get_order_total();

		// if ( 'transparent' == $this->method ) {
		// 	wc_get_template( 'transparent-checkout-form.php', array(
		// 		'cart_total'        => $cart_total,
		// 		'tc_credit'         => $this->tc_credit,
		// 		'tc_transfer'       => $this->tc_transfer,
		// 		'tc_ticket'         => $this->tc_ticket,
		// 		'tc_ticket_message' => $this->tc_ticket_message,
		// 	), 'woocommerce/Asaas/', WC_PagSeguro::get_templates_path() );
		// }
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( 'lightbox' != $this->method ) {
			if ( isset( $_POST['pagseguro_sender_hash'] ) && 'transparent' == $this->method ) {
				$response = $this->api->do_payment_request( $order, $_POST );

				if ( $response['data'] ) {
					$this->update_order_status( $response['data'] );
				}
			} else {
				$response = $this->api->do_checkout_request( $order, $_POST );
			}

			if ( $response['url'] ) {
				// Remove cart.
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $response['url'],
				);
			} else {
				foreach ( $response['error'] as $error ) {
					wc_add_notice( $error, 'error' );
				}

				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		} else {
			$use_shipping = isset( $_POST['ship_to_different_address'] ) ? true : false;

			return array(
				'result'   => 'success',
				'redirect' => add_query_arg( array( 'use_shipping' => $use_shipping ), $order->get_checkout_payment_url( true ) ),
			);
		}
	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function receipt_page( $order_id ) {
		$order        = new WC_Order( $order_id );
		$request_data = $_POST;
		if ( isset( $_GET['use_shipping'] ) && true == $_GET['use_shipping'] ) {
			$request_data['ship_to_different_address'] = true;
		}

		$response = $this->api->do_checkout_request( $order, $request_data );

		// if ( $response['url'] ) {
		// 	// Lightbox script.
		// 	wc_enqueue_js( '
		// 		$( "#browser-has-javascript" ).show();
		// 		$( "#browser-no-has-javascript, #cancel-payment, #submit-payment" ).hide();
		// 		var isOpenLightbox = PagSeguroLightbox({
		// 				code: "' . esc_js( $response['token'] ) . '"
		// 			}, {
		// 				success: function ( transactionCode ) {
		// 					window.location.href = "' . str_replace( '&amp;', '&', esc_js( $this->get_return_url( $order ) ) ) . '";
		// 				},
		// 				abort: function () {
		// 					window.location.href = "' . str_replace( '&amp;', '&', esc_js( $order->get_cancel_order_url() ) ) . '";
		// 				}
		// 		});
		// 		if ( ! isOpenLightbox ) {
		// 			window.location.href = "' . esc_js( $response['url'] ) . '";
		// 		}
		// 	' );

		// 	wc_get_template( 'lightbox-checkout.php', array(
		// 		'cancel_order_url'    => $order->get_cancel_order_url(),
		// 		'payment_url'         => $response['url'],
		// 		'lightbox_script_url' => $this->api->get_lightbox_url(),
		// 	), 'woocommerce/Asaas/', WC_PagSeguro::get_templates_path() );
		// } else {
		// 	include 'views/html-receipt-page-error.php';
		// }
	}

	/**
	 * IPN handler.
	 */
	public function ipn_handler() {
		@ob_clean();

		$ipn = $this->api->process_ipn_request( $_POST );

		if ( $ipn ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'valid_pagseguro_ipn_request', $ipn );
			exit();
		} else {
			wp_die( esc_html__( 'Asaas Request Unauthorized', 'woocommerce-asaas' ), esc_html__( 'Asaas Request Unauthorized', 'woocommerce-asaas' ), array( 'response' => 401 ) );
		}
	}

	/**
	 * Update order status.
	 *
	 * @param array $posted Asaas post data.
	 */
	public function update_order_status( $posted ) {

		if ( isset( $posted->reference ) ) {
			$order_id = (int) str_replace( $this->invoice_prefix, '', $posted->reference );
			$order    = new WC_Order( $order_id );

			// Checks whether the invoice number matches the order.
			// If true processes the payment.
			if ( $order->id === $order_id ) {

				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Asaas payment status for order ' . $order->get_order_number() . ' is: ' . intval( $posted->status ) );
				}

				// Order details.
				$order_details = array(
					'type'         => '',
					'method'       => '',
					'installments' => '',
					'link'         => '',
				);

				if ( isset( $posted->code ) ) {
					update_post_meta(
						$order->id,
						__( 'Asaas Transaction ID', 'woocommerce-asaas' ),
						(string) $posted->code
					);
				}
				if ( isset( $posted->sender->email ) ) {
					update_post_meta(
						$order->id,
						__( 'Payer email', 'woocommerce-asaas' ),
						(string) $posted->sender->email
					);
				}
				if ( isset( $posted->sender->name ) ) {
					update_post_meta(
						$order->id,
						__( 'Payer name', 'woocommerce-asaas' ),
						(string) $posted->sender->name
					);
				}
				if ( isset( $posted->paymentMethod->type ) ) {
					$order_details['type'] = intval( $posted->paymentMethod->type );
					update_post_meta(
						$order->id,
						__( 'Payment type', 'woocommerce-asaas' ),
						$this->api->get_payment_name_by_type( $order_details['type'] )
					);
				}
				if ( isset( $posted->paymentMethod->code ) ) {
					$order_details['method'] = $this->api->get_payment_method_name( intval( $posted->paymentMethod->code ) );
					update_post_meta(
						$order->id,
						__( 'Payment method', 'woocommerce-asaas' ),
						$order_details['method']
					);
				}
				if ( isset( $posted->installmentCount ) ) {
					$order_details['installments'] = (string) $posted->installmentCount;
					update_post_meta(
						$order->id,
						__( 'Installments', 'woocommerce-asaas' ),
						$order_details['installments']
					);
				}
				if ( isset( $posted->paymentLink ) ) {
					$order_details['link'] = (string) $posted->paymentLink;
					update_post_meta(
						$order->id,
						__( 'Payment url', 'woocommerce-asaas' ),
						$order_details['link']
					);
				}

				// Save/update payment information for transparente checkout.
				// if ( 'transparent' == $this->method ) {
				// 	update_post_meta( $order->id, '_wc_pagseguro_payment_data', $order_details );
				// }

				switch ( intval( $posted->status ) ) {
					case 1 :
						$order->update_status( 'on-hold', __( 'Asaas: The buyer initiated the transaction, but so far the Asaas not received any payment information.', 'woocommerce-asaas' ) );

						break;
					case 2 :
						$order->update_status( 'on-hold', __( 'Asaas: Payment under review.', 'woocommerce-asaas' ) );

						break;
					case 3 :
						$order->add_order_note( __( 'Asaas: Payment approved.', 'woocommerce-asaas' ) );

						// For WooCommerce 2.2 or later.
						add_post_meta( $order->id, '_transaction_id', (string) $posted->code, true );

						// Changing the order for processing and reduces the stock.
						$order->payment_complete();

						break;
					case 4 :
						$order->add_order_note( __( 'Asaas: Payment completed and credited to your account.', 'woocommerce-asaas' ) );

						break;
					case 5 :
						$order->update_status( 'on-hold', __( 'Asaas: Payment came into dispute.', 'woocommerce-asaas' ) );
						$this->send_email(
							sprintf( __( 'Payment for order %s came into dispute', 'woocommerce-asaas' ), $order->get_order_number() ),
							__( 'Payment in dispute', 'woocommerce-asaas' ),
							sprintf( __( 'Order %s has been marked as on-hold, because the payment came into dispute in Asaas.', 'woocommerce-asaas' ), $order->get_order_number() )
						);

						break;
					case 6 :
						$order->update_status( 'refunded', __( 'Asaas: Payment refunded.', 'woocommerce-asaas' ) );
						$this->send_email(
							sprintf( __( 'Payment for order %s refunded', 'woocommerce-asaas' ), $order->get_order_number() ),
							__( 'Payment refunded', 'woocommerce-asaas' ),
							sprintf( __( 'Order %s has been marked as refunded by Asaas.', 'woocommerce-asaas' ), $order->get_order_number() )
						);

						break;
					case 7 :
						$order->update_status( 'cancelled', __( 'Asaas: Payment canceled.', 'woocommerce-asaas' ) );

						break;

					default :
						// No action xD.
						break;
				}
			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error: Order Key does not match with Asaas reference.' );
				}
			}
		}
	}


}
