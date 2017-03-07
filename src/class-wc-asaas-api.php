<?php
/**
 * WooCommerce Asaas API class
 *
 * @package WooCommerce_Asaas/Classes/API
 * @version 0.0.1
 */

defined( 'WPINC' ) or die;

/**
 * WooCommerce Asaas API.
 */
class WC_Asaas_API {

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
	public static function instance() {
		return self::$instance ? self::$instance : self::$instance = new self;
	}

	/**
	 * API varibles stored in a single Object
	 *
	 * @var array $api {
	 *     @type string     $key         License key for the API (PUE)
	 *     @type string     $version     Which version of we are dealing with
	 *     @type string     $domain      Domain in which the API lies
	 *     @type string     $path        Path of the API on the domain above
	 * }
	 */
	public $api = array(
		'key' 		=> '6f1beb274b8b85e613d66a88109d7b6d72dee679d50d62c46c67c6a356ed4445',
		'version' 	=> 'v2',
		'domain' 	=> 'https://homolog.asaas.com/',
		'path'		=> 'api/',
	);

	/**
	 * Constructor
	 *
	 * @param WC_PagSeguro_Gateway $gateway Payment Gateway instance.
	 */
	private function __construct( $gateway = null ) {
		$this->gateway = $gateway;

		// Turns api array into object
		$this->api = (object) $this->api;
	}

	/**
	 * Get the API environment.
	 *
	 * @return string
	 */
	protected function get_environment() {
		return ( 'yes' == $this->gateway->sandbox ) ? 'homolog.' : '';
	}

	/**
	 * Get the Customers URL.
	 *
	 * @return string.
	 */
	protected function get_api_url() {
		return 'https://' . $this->get_environment() . 'asaas.com/api/v2/';
	}

	/**
	 * Get TOKEN for current environment.
	 *
	 * @return string.
	 */
	protected function get_token() {
		return ( 'yes' == $this->gateway->sandbox ) ? $this->gateway->sandbox_token : $this->gateway->token;
	}

	/**
	 * Check if is localhost.
	 *
	 * @return bool
	 */
	protected function is_localhost() {
		$url  = home_url( '/' );
		$home = untrailingslashit( str_replace( array( 'https://', 'http://' ), '', $url ) );

		return in_array( $home, array( 'localhost', '127.0.0.1' ) );
	}

	/**
	 * Money format.
	 *
	 * @param  int/float $value Value to fix.
	 *
	 * @return float            Fixed value.
	 */
	protected function money_format( $value ) {
		return number_format( $value, 2, '.', '' );
	}

	/**
	 * Sanitize the item description.
	 *
	 * @param  string $description Description to be sanitized.
	 *
	 * @return string
	 */
	protected function sanitize_description( $description ) {
		return sanitize_text_field( substr( $description, 0, 95 ) );
	}

	/**
	 * Get payment name by type.
	 *
	 * @param  int $value Payment Type number.
	 *
	 * @return string
	 */
	public function get_payment_name_by_type( $value ) {
		$types = array(
			1 => __( 'Credit Card', 'woocommerce-asaas' ),
			2 => __( 'Billet', 'woocommerce-asaas' ),
			3 => __( 'Bank Transfer', 'woocommerce-asaas' ),
			4 => __( 'Account deposit', 'woocommerce-asaas' ),
		);

		return isset( $types[ $value ] ) ? $types[ $value ] : __( 'Unknown', 'woocommerce-asaas' );
	}

	/**
	 * Get payment method name.
	 *
	 * @param  int $value Payment method number.
	 *
	 * @return string
	 */
	public function get_payment_method_name( $value ) {
		$credit = __( 'Credit Card %s', 'woocommerce-asaas' );
		$ticket = __( 'Billet %s', 'woocommerce-asaas' );
		$debit  = __( 'Bank Transfer %s', 'woocommerce-asaas' );

		$methods = array(
			101 => sprintf( $credit, 'Visa' ),
			102 => sprintf( $credit, 'MasterCard' ),
			103 => sprintf( $credit, 'American Express' ),
			104 => sprintf( $credit, 'Diners' ),
			105 => sprintf( $credit, 'Hipercard' ),
			106 => sprintf( $credit, 'Aura' ),
			107 => sprintf( $credit, 'Elo' ),
			108 => sprintf( $credit, 'PLENOCard' ),
			109 => sprintf( $credit, 'PersonalCard' ),
			110 => sprintf( $credit, 'JCB' ),
			111 => sprintf( $credit, 'Discover' ),
			112 => sprintf( $credit, 'BrasilCard' ),
			113 => sprintf( $credit, 'FORTBRASIL' ),
			114 => sprintf( $credit, 'CARDBAN' ),
			115 => sprintf( $credit, 'VALECARD' ),
			116 => sprintf( $credit, 'Cabal' ),
			117 => sprintf( $credit, 'Mais!' ),
			118 => sprintf( $credit, 'Avista' ),
			119 => sprintf( $credit, 'GRANDCARD' ),
			201 => sprintf( $ticket, 'Bradesco' ),
			202 => sprintf( $ticket, 'Santander' ),
			301 => sprintf( $debit, 'Bradesco' ),
			302 => sprintf( $debit, 'Itaú' ),
			303 => sprintf( $debit, 'Unibanco' ),
			304 => sprintf( $debit, 'Banco do Brasil' ),
			305 => sprintf( $debit, 'Real' ),
			306 => sprintf( $debit, 'Banrisul' ),
			307 => sprintf( $debit, 'HSBC' ),
			401 => __( 'PagSeguro credit', 'woocommerce-asaas' ),
			501 => __( 'Oi Paggo', 'woocommerce-asaas' ),
			701 => __( 'Account deposit', 'woocommerce-asaas' ),
		);

		return isset( $methods[ $value ] ) ? $methods[ $value ] : __( 'Unknown', 'woocommerce-asaas' );
	}

	/**
	 * Get the paymet method.
	 *
	 * @param  string $method Payment method.
	 *
	 * @return string
	 */
	public function get_payment_method( $method ) {
		$methods = array(
			'credit-card'    => 'CREDIT_CARD',
			'banking-ticket' => 'BOLETO',
			'bank-transfer'  => 'TRANSFER',
		);

		return isset( $methods[ $method ] ) ? $methods[ $method ] : '';
	}

	/**
	 * Get error message.
	 *
	 * @param  int $code Error code.
	 *
	 * @return string
	 */
	public function get_error_message( $code ) {
		$code = (string) $code;

		$messages = array(
			'11013' => __( 'Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-asaas' ),
			'11014' => __( 'Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-asaas' ),
			'53018' => __( 'Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-asaas' ),
			'53019' => __( 'Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-asaas' ),
			'53020' => __( 'Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-asaas' ),
			'53021' => __( 'Please enter with a valid phone number with DDD. Example: (11) 5555-5555.', 'woocommerce-asaas' ),
			'11017' => __( 'Please enter with a valid zip code number.', 'woocommerce-asaas' ),
			'53022' => __( 'Please enter with a valid zip code number.', 'woocommerce-asaas' ),
			'53023' => __( 'Please enter with a valid zip code number.', 'woocommerce-asaas' ),
			'53053' => __( 'Please enter with a valid zip code number.', 'woocommerce-asaas' ),
			'53054' => __( 'Please enter with a valid zip code number.', 'woocommerce-asaas' ),
			'11164' => __( 'Please enter with a valid CPF number.', 'woocommerce-asaas' ),
			'53110' => '',
			'53111' => __( 'Please select a bank to make payment by bank transfer.', 'woocommerce-asaas' ),
			'53045' => __( 'Credit card holder CPF is required.', 'woocommerce-asaas' ),
			'53047' => __( 'Credit card holder birthdate is required.', 'woocommerce-asaas' ),
			'53042' => __( 'Credit card holder name is required.', 'woocommerce-asaas' ),
			'53049' => __( 'Credit card holder phone is required.', 'woocommerce-asaas' ),
			'53051' => __( 'Credit card holder phone is required.', 'woocommerce-asaas' ),
			'11020' => __( 'The address complement is too long, it cannot be more than 40 characters.', 'woocommerce-asaas' ),
			'53028' => __( 'The address complement is too long, it cannot be more than 40 characters.', 'woocommerce-asaas' ),
			'53029' => __( '<strong>Neighborhood</strong> is a required field.', 'woocommerce-asaas' ),
			'53046' => __( 'Credit card holder CPF invalid.', 'woocommerce-asaas' ),
			'53122' => __( 'Invalid email domain. You must use an email @sandbox.pagseguro.com.br while you are using the PagSeguro Sandbox.', 'woocommerce-asaas' ),
		);

		if ( isset( $messages[ $code ] ) ) {
			return $messages[ $code ];
		}

		return __( 'An error has occurred while processing your payment, please review your data and try again. Or contact us for assistance.', 'woocommerce-asaas' );
	}

	/**
	 * Get the available payment methods.
	 *
	 * @return array
	 */
	protected function get_available_payment_methods() {
		$methods = array();

		if ( 'yes' == $this->gateway->tc_credit ) {
			$methods[] = 'credit-card';
		}

		if ( 'yes' == $this->gateway->tc_transfer ) {
			$methods[] = 'bank-transfer';
		}

		if ( 'yes' == $this->gateway->tc_ticket ) {
			$methods[] = 'banking-ticket';
		}

		return $methods;
	}




///////////////////////////////////??////////////////////////////////////////
///////////////////////////////////??////////////////////////////////////////

	/**
	 * Builds an endpoint URL
	 *
	 * @param string $endpoint  Endpoint for the Event Aggregator service
	 * @param array  $data      Parameters to add to the URL
	 *
	 * @return string|WP_Error
	 */
	public function build_url( $endpoint, $data = array() ) {

		// Constructs url address
		// $url = "$this->get_environment().{$this->api->domain}{$this->api->path}{$this->api->version}/{$endpoint}";
		$url = $this->get_environment . $this->get_api_url() . $endpoint;

		// If we have data we add it
		if ( ! empty( $data ) ) {
			$url = add_query_arg( $data, $url );
		}

		return $url;
	}
	/**
	 * Performs a GET request against the Asaas API service
	 *
	 * @param string $endpoint   Endpoint for the Asaas API service
	 * @param array  $data       Parameters to send to the endpoint
	 *
	 * @return array|stdClass|WP_Error
	 */
	public function get( $endpoint, $data = array() ) {
		$url = $this->build_url( $endpoint, $data );

		// If we have an WP_Error we return it here
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$headers = array(
			'access_token'  => $this->get_token(),
		);

		$args = array(
			'timeout' 	=> 60,
			'headers' 	=> $headers
		);

		// Get api first response
		$response = wp_remote_get( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			if ( isset( $response->errors['http_request_failed'] ) ) {
				$response->errors['http_request_failed'][0] = __( 'Connection timed out while transferring the feed.', 'boleto-control' );
			}
			return $response;
		}

		// Get first response
		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$body_var = get_object_vars( $response );

		// Return if we have only this data
		if ( ! empty( $response->data ) ) {

			$offset = sizeof( $response->data );

			// If has more values do next requests
			while ( $body_var['hasMore'] ) {

				// Builds url sending offset var
				$url = $this->build_url( $endpoint, array( 'offset' => $offset ) );

				// Gets next page
				$page = wp_remote_get( esc_url_raw( $url ), $args );

				if ( is_wp_error( $page ) ) {
					return $page;
				}

				$page = json_decode( wp_remote_retrieve_body( $page ) );

				// Merge page data
				$response->data = array_merge( $response->data ,$page->data );

				$body_var = get_object_vars( $page );

				// Increment offset
				$offset += sizeof( $page->data );

			};

			return $response->data;
		}

		return $response;
	}

	/**
	 * Performs a POST request against Asaas API service
	 *
	 * @param string $endpoint   Endpoint for the Asaas API service
	 * @param array  $data       Parameters to send to the endpoint
	 *
	 * @return array|stdClass|WP_Error
	 */
	public function post( $endpoint, $data = array() ) {
		$url = $this->build_url( $endpoint );

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Start POST Request for Asaas API for url: ' . $url );
		}

		// If we have an WP_Error we return it here
		if ( is_wp_error( $url ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Get an WP_Error for url : ' . $url );
			}
			return $url;
		}

		if ( empty( $data['body'] ) ) {
			$args = array( 'body' => json_encode( $data ) );
		} else {
			$args = $data;
		}

		$args['headers'] = array(
			'Content-Type' => 'application/json',
			'access_token' => $this->get_token(),
		);

		$response = wp_remote_post( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Get an WP_Error for response : ' . $response );
			}
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response;
	}

	/**
	 * Performs a DELETE request against Asaas API service
	 *
	 * @param string $endpoint   Endpoint for the Asaas API service
	 * @param array  $data       Parameters to send to the endpoint
	 *
	 * @return array|stdClass|WP_Error
	 */
	public function delete( $endpoint, $data = array() ) {
		$url = $this->build_url( $endpoint );

		// If we have an WP_Error we return it here
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		if ( empty( $data['body'] ) ) {
			$args = array( 'body' => json_encode( $data ) );
		} else {
			$args = $data;
		}

		$args['method'] = 'DELETE';
		$args['headers'] = array(
			'Content-Type' 	=> 'application/json',
			'access_token' 	=> $this->api->key,
		);

		$response = wp_remote_request( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		// When having data, return it already
		if ( ! empty( $response->data ) ) {
			return $response->data;
		}

		return $response;
	}

	/**
	 * Returns a list of asaas object for informed endpoint
	 *
	 * @param  string $endpoint String for API endpoint
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_all( $endpoint ) {
		//return $this->get( $endpoint, array( 'limit' => 50 ) );
		return $this->get( $endpoint, null );
	}

	/**
	 * Returns an asaas object for informed id
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  string $obj_id   Asass object id
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_by_id( $endpoint, $obj_id ) {
		return $this->get( $endpoint . '/' . $obj_id, null );
	}

	/**
	 * Returns a list of <endpoint> objets from specific  customers
	 * Possible Endpoints: subscriptions, payments, notifications
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  string $obj_id   Asass object id
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_by_customer( $endpoint, $obj_id ) {
		return $this->get( 'customers/' . $obj_id . '/' . $endpoint, null );
	}

	/**
	 * Returns a list of <endpoint> objets from specific subscription
	 * Possible Endpoints: payments, notifications
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  string $obj_id   Asass object id
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_by_subscription( $endpoint, $obj_id ) {
		return $this->get( 'subscriptions/' . $obj_id  . '/' . $endpoint, null );
	}

	/**
	 * Returns a list of payments objets from specific installment
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  string $obj_id   Asass object id
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_by_installment( $obj_id ) {
		return $this->get( 'payments?installment=' . $obj_id . '/', null );
	}

	/**
	 * Returns an object of customer by email
	 *
	 * @param string $email Email from customer
	 *
	 * @return stdClass|WP_Error
	 */
	public function get_by_email( $email ) {
		$customers = $this->get_all( 'customers' );

		foreach ( $customers as $data ) {
			if ( ! empty($data->customer->email) && $data->customer->email === $email ) {
				return $data->customer;
			}
		}
		return false;
	}

	/**
	 * Insert new entity
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  array  $data  	Entity Data
	 *
	 * @return stdClass|WP_Error
	 */
	public function insert( $endpoint, $data = array() ) {
		return $this->post( $endpoint, $data );
	}

	/**
	 * Update entity by id
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  string $obj_id   Asass object id
	 * @param  array  $data  	Entity Data
	 *
	 * @return stdClass|WP_Error
	 */
	public function update_by_id( $endpoint, $obj_id , $data = array() ) {
		return $this->post( $endpoint . '/' . $obj_id, $data );
	}

	/**
	 * Delete entity
	 *
	 * @param  string $endpoint String for API endpoint
	 * @param  string $obj_id   Asass object id
	 *
	 * @return stdClass|WP_Error
	 */
	public function delete_by_id( $endpoint, $obj_id ) {
		return $this->delete( $endpoint . '/' . $obj_id, null);
	}

	/**
	 * Merge customer data via asaas api with wp_user info
	 *
	 * @param  Object $wp_user WP_User
	 *
	 * @return bool
	 * @return string validate action type
	 */
	public function merge_asaas_customer( $wp_user ) {


		//create customer data array based on wordpress user info
		$customer_data = array(
			'name' 			=> $wp_user->display_name,
			'email' 		=> $wp_user->user_email,
			'mobilePhone' 	=> get_user_meta($wp_user->ID,'celular',true),
			'cpfCnpj'	 	=> get_user_meta($wp_user->ID,'cpf',true),
			'company'	 	=> 'ABEPPS',

		);

		//get customer info or false
		$is_old_cust = $this->get_by_email( $wp_user->user_email );

		// makes user upsert
		if ( ! empty($is_old_cust) ) {
			//customer data update
			$this->update_by_id( 'customers', $is_old_cust->id, $customer_data );
			update_user_meta( $wp_user->ID, '_asass_customer_date', $user_list->dateCreated );
			return array( true, 'update' );
		}

		//creates new customer
		$user_list = $this->insert( 'customers', $customer_data );

		//insert user_meta with some customer extra info
		if ( ! empty( $user_list ) ) {
			update_user_meta( $wp_user->ID, '_asass_customer_id', $user_list->id );
			update_user_meta( $wp_user->ID, '_asass_customer_date', $user_list->dateCreated );
		}

		return array( true, 'insert' );
	}

	/**
	 * Merge payments data via asaas api with wp_user info
	 *
	 * @param  Object $wp_user WP_User
	 * @param  Object $order   WC_Order
	 * @param  Array  $data
	 *
	 * @return bool
	 * @return string validate action type
	 */
	public function merge_asaas_subs( $wp_user, $order, $data ) {

		//get customer and subs info or false
		$is_old_cust = $this->get_by_email( $wp_user->user_email );

		//if user exists, get user subs data
		if ( empty( $is_old_cust ) ) {
			return false;
		}

		//get subscription->id saved on user_meta
		$subs_id = get_user_meta( $wp_user->ID, '_asass_customer_id', true );

		//if subs exists for customer, get data
		$asaas_subs = $this->get_by_customer( 'subscriptions', $is_old_cust->id ) ;

		//create subscription data array
		$subs_data = array(
			'customer' 	    => $is_old_cust->id,
			'billingType'	=> 'BOLETO',
			'dueDate' 	    => $this->get_next_bill_date( 15 ),
			'value' 		=> '152,00',
			'cycle'	 	    => 'MONTHLY',
			'description'	=> 'Assinatura Abepps'
		);

		// checks if exists subscription
		if ( $asaas_subs ) {
			$subs_data = array_merge( $subs_data, array('updatePendingPayments' => true) );
			$subs_list = $this->update_by_id( 'subscriptions', $asaas_subs[0]->id, $subs_data );
			$is_update = true;
		} else {
			$subs_list = $this->insert( 'subscriptions', $subs_data );
		}

		//insert user_meta with some subscription extra info
		update_user_meta( $wp_user->ID, '_asass_subs_id'			, $subs_list->id );
		update_user_meta( $wp_user->ID, '_asass_subs_created'		, $subs_list->dateCreated );
		update_user_meta( $wp_user->ID, '_asass_subs_value'			, $subs_list->value );
		update_user_meta( $wp_user->ID, '_asass_subs_billingtype'	, $subs_list->billingType );
		update_user_meta( $wp_user->ID, '_asass_subs_deleted'		, $subs_list->deleted );
		update_user_meta( $wp_user->ID, '_asass_subs_status'		, $subs_list->status );

		if ( $is_update ) {
			return array( true, 'update' );
		}

		return array( true, 'insert' );
	}



///////////////////////////////////??////////////////////////////////////////
///////////////////////////////////??////////////////////////////////////////

	/**
	 * Do requests in the PagSeguro API.
	 *
	 * @param  string $url      URL.
	 * @param  string $method   Request method.
	 * @param  array  $data     Request data.
	 * @param  array  $headers  Request headers.
	 *
	 * @return array            Request response.
	 */
	protected function do_request( $url, $method = 'POST', $data = array() ) {

		// If we have an WP_Error we return it here
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		if ( empty( $data['body'] ) ) {
			$args = array( 'body' => json_encode( $data ) );
		} else {
			$args = $data;
		}

		$args['headers'] = array(
			'Content-Type' => 'application/json',
			'access_token' => $this->gateway->get_token(),
		);

		return wp_remote_post( esc_url_raw( $url ), $args );
	}



	/**
	 * Safe load XML.
	 *
	 * @param  string $source  XML source.
	 * @param  int    $options DOMDpocment options.
	 *
	 * @return SimpleXMLElement|bool
	 */
	protected function safe_load_xml( $source, $options = 0 ) {
		$old = null;

		if ( '<' !== substr( $source, 0, 1 ) ) {
			return false;
		}

		if ( function_exists( 'libxml_disable_entity_loader' ) ) {
			$old = libxml_disable_entity_loader( true );
		}

		$dom    = new DOMDocument();
		$return = $dom->loadXML( $source, $options );

		if ( ! is_null( $old ) ) {
			libxml_disable_entity_loader( $old );
		}

		if ( ! $return ) {
			return false;
		}

		if ( isset( $dom->doctype ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Unsafe DOCTYPE Detected while XML parsing' );
			}

			return false;
		}

		return simplexml_import_dom( $dom );
	}

	/**
	 * Get order items.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return array           Items list, extra amount and shipping cost.
	 */
	protected function get_order_items( $order ) {
		$items         = array();
		$extra_amount  = 0;
		$shipping_cost = 0;

		// Force only one item.
		if ( 'yes' == $this->gateway->send_only_total ) {
			$items[] = array(
				'description' => $this->sanitize_description( sprintf( __( 'Order %s', 'woocommerce-asaas' ), $order->get_order_number() ) ),
				'amount'      => $this->money_format( $order->get_total() ),
				'quantity'    => 1,
			);
		} else {

			// Products.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $order_item ) {
					if ( $order_item['qty'] ) {
						$item_total = $order->get_item_total( $order_item, false );
						if ( 0 >= $item_total ) {
							continue;
						}

						$item_name = $order_item['name'];

						if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.4.0', '<' ) ) {
							$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );
						} else {
							$item_meta = new WC_Order_Item_Meta( $order_item );
						}

						if ( $meta = $item_meta->display( true, true ) ) {
							$item_name .= ' - ' . $meta;
						}

						$items[] = array(
							'description' => $this->sanitize_description( $item_name ),
							'amount'      => $this->money_format( $item_total ),
							'quantity'    => $order_item['qty'],
						);
					}
				}
			}

			// Fees.
			if ( 0 < count( $order->get_fees() ) ) {
				foreach ( $order->get_fees() as $fee ) {
					$items[] = array(
						'description' => $this->sanitize_description( $fee['name'] ),
						'amount'      => $this->money_format( $fee['line_total'] ),
						'quantity'    => 1,
					);
				}
			}

			// Taxes.
			if ( 0 < count( $order->get_taxes() ) ) {
				foreach ( $order->get_taxes() as $tax ) {
					$items[] = array(
						'description' => $this->sanitize_description( $tax['label'] ),
						'amount'      => $this->money_format( $tax['tax_amount'] + $tax['shipping_tax_amount'] ),
						'quantity'    => 1,
					);
				}
			}

			// Shipping Cost.
			if ( 0 < $order->get_total_shipping() ) {
				$shipping_cost = $this->money_format( $order->get_total_shipping() );
			}

			// Discount.
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.3', '<' ) ) {
				if ( 0 < $order->get_order_discount() ) {
					$extra_amount = '-' . $this->money_format( $order->get_order_discount() );
				}
			}
		}

		return array(
			'items'         => $items,
			'extra_amount'  => $extra_amount,
			'shipping_cost' => $shipping_cost,
		);
	}

	/**
	 * Get the checkout xml.
	 *
	 * @param WC_Order $order Order data.
	 * @param array    $posted Posted data.
	 *
	 * @return string
	 */
	protected function get_checkout_xml( $order, $posted ) {
		$data    = $this->get_order_items( $order );
		$ship_to = isset( $posted['ship_to_different_address'] ) ? true : false;

		// Creates the checkout xml.
		$xml = new WC_PagSeguro_XML( '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><checkout></checkout>' );
		$xml->add_currency( get_woocommerce_currency() );
		$xml->add_reference( $this->gateway->invoice_prefix . $order->id );
		$xml->add_sender_data( $order );
		$xml->add_shipping_data( $order, $ship_to, $data['shipping_cost'] );
		$xml->add_items( $data['items'] );
		$xml->add_extra_amount( $data['extra_amount'] );

		// Checks if is localhost... PagSeguro not accept localhost urls!
		if ( ! in_array( $this->is_localhost(), array( 'localhost', '127.0.0.1' ) ) ) {
			$xml->add_redirect_url( $this->gateway->get_return_url( $order ) );
			$xml->add_notification_url( WC()->api_request_url( 'WC_PagSeguro_Gateway' ) );
		}

		$xml->add_max_uses( 1 );
		$xml->add_max_age( 120 );

		// Filter the XML.
		$xml = apply_filters( 'woocommerce_pagseguro_checkout_xml', $xml, $order );

		return $xml->render();
	}

	/**
	 * Get the direct payment xml.
	 *
	 * @param WC_Order $order Order data.
	 * @param array    $posted Posted data.
	 *
	 * @return string
	 */
	protected function get_payment_xml( $order, $posted ) {
		$data    = $this->get_order_items( $order );
		$ship_to = isset( $posted['ship_to_different_address'] ) ? true : false;
		$method  = isset( $posted['pagseguro_payment_method'] ) ? $this->get_payment_method( $posted['pagseguro_payment_method'] ) : '';
		$hash    = isset( $posted['pagseguro_sender_hash'] ) ? sanitize_text_field( $posted['pagseguro_sender_hash'] ) : '';

		// Creates the payment xml.
		$xml = new WC_PagSeguro_XML( '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><payment></payment>' );
		$xml->add_mode( 'default' );
		$xml->add_method( $method );
		$xml->add_sender_data( $order, $hash );
		$xml->add_currency( get_woocommerce_currency() );
		if ( ! in_array( $this->is_localhost(), array( 'localhost', '127.0.0.1' ) ) ) {
			$xml->add_notification_url( WC()->api_request_url( 'WC_PagSeguro_Gateway' ) );
		}
		$xml->add_items( $data['items'] );
		$xml->add_extra_amount( $data['extra_amount'] );
		$xml->add_reference( $this->gateway->invoice_prefix . $order->id );
		$xml->add_shipping_data( $order, $ship_to, $data['shipping_cost'] );

		// Items related to the payment method.
		if ( 'creditCard' == $method ) {
			$credit_card_token = isset( $posted['pagseguro_credit_card_hash'] ) ? sanitize_text_field( $posted['pagseguro_credit_card_hash'] ) : '';
			$installment       = array(
				'quantity' => isset( $posted['pagseguro_card_installments'] ) ? absint( $posted['pagseguro_card_installments'] ) : '',
				'value'    => isset( $posted['pagseguro_installment_value'] ) ? $this->money_format( $posted['pagseguro_installment_value'] ) : '',
			);
			$holder_data       = array(
				'name'       => isset( $posted['pagseguro_card_holder_name'] ) ? sanitize_text_field( $posted['pagseguro_card_holder_name'] ) : '',
				'cpf'        => isset( $posted['pagseguro_card_holder_cpf'] ) ? sanitize_text_field( $posted['pagseguro_card_holder_cpf'] ) : '',
				'birth_date' => isset( $posted['pagseguro_card_holder_birth_date'] ) ? sanitize_text_field( $posted['pagseguro_card_holder_birth_date'] ) : '',
				'phone'      => isset( $posted['pagseguro_card_holder_phone'] ) ? sanitize_text_field( $posted['pagseguro_card_holder_phone'] ) : '',
			);

			$xml->add_credit_card_data( $order, $credit_card_token, $installment, $holder_data );
		} elseif ( 'eft' == $method ) {
			$bank_name = isset( $posted['pagseguro_bank_transfer'] ) ? sanitize_text_field( $posted['pagseguro_bank_transfer'] ) : '';
			$xml->add_bank_data( $bank_name );
		}

		// Filter the XML.
		$xml = apply_filters( 'woocommerce_pagseguro_payment_xml', $xml, $order );

		return $xml->render();
	}

	/**
	 * Do checkout request.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  array    $posted Posted data.
	 *
	 * @return array
	 */
	public function do_checkout_request( $order, $posted ) {
		// Sets the xml.
		//$xml = $this->get_checkout_xml( $order, $posted );

		//@TODO
		//SETS DATA TO MAKE REQUEST
		$user = wp_get_current_user();

		//Create Asaas Customer for current user
		$api_return = $this->merge_asaas_customer( $user );

		if ( ! $api_return[0] ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Return for Asaas Customer Upsert is ' . $api_return[0]);
			}
			return false;
		}





		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Requesting token for order ' . $order->get_order_number() . ' with the following data: ' . $xml );
		}

		$url      = add_query_arg( array( 'email' => $this->gateway->get_email(), 'token' => $this->gateway->get_token() ), $this->get_checkout_url() );

		$response = $this->do_request( $url, 'POST', $xml, array( 'Content-Type' => 'application/xml;charset=UTF-8' ) );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in generate payment token: ' . $response->get_error_message() );
			}
		} else if ( 401 === $response['response']['code'] ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Invalid token and/or email settings!' );
			}

			return array(
				'url'   => '',
				'data'  => '',
				'error' => array( __( 'Too bad! The email or token from the PagSeguro are invalids my little friend!', 'woocommerce-asaas' ) ),
			);
		} else {
			try {
				libxml_disable_entity_loader( true );
				$body = $this->safe_load_xml( $response['body'], LIBXML_NOCDATA );
			} catch ( Exception $e ) {
				$body = '';

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Error while parsing the PagSeguro response: ' . print_r( $e->getMessage(), true ) );
				}
			}

			if ( isset( $body->code ) ) {
				$token = (string) $body->code;

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'PagSeguro Payment Token created with success! The Token is: ' . $token );
				}

				return array(
					'url'   => $this->get_payment_url( $token ),
					'token' => $token,
					'error' => '',
				);
			}

			if ( isset( $body->error ) ) {
				$errors = array();

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Failed to generate the PagSeguro Payment Token: ' . print_r( $response, true ) );
				}

				foreach ( $body->error as $error_key => $error ) {
					if ( $message = $this->get_error_message( $error->code ) ) {
						$errors[] = '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' . $message;
					}
				}

				return array(
					'url'   => '',
					'token' => '',
					'error' => $errors,
				);
			}
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Error generating the PagSeguro payment token: ' . print_r( $response, true ) );
		}

		// Return error message.
		return array(
			'url'   => '',
			'token' => '',
			'error' => array( '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-asaas' ) ),
		);
	}

	/**
	 * Do payment request.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  array    $posted Posted data.
	 *
	 * @return array
	 */
	public function do_payment_request( $order, $posted ) {
		$payment_method = isset( $posted['pagseguro_payment_method'] ) ? $posted['pagseguro_payment_method'] : '';

		/**
		 * Validate if has selected a payment method.
		 */
		if ( ! in_array( $payment_method, $this->get_available_payment_methods() ) ) {
			return array(
				'url'   => '',
				'data'  => '',
				'error' => array( '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' .  __( 'Please, select a payment method.', 'woocommerce-asaas' ) ),
			);
		}

		// Sets the xml.
		$xml = $this->get_payment_xml( $order, $posted );

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Requesting direct payment for order ' . $order->get_order_number() . ' with the following data: ' . $xml );
		}

		$url      = add_query_arg( array( 'email' => $this->gateway->get_email(), 'token' => $this->gateway->get_token() ), $this->get_transactions_url() );
		$response = $this->do_request( $url, 'POST', $xml, array( 'Content-Type' => 'application/xml;charset=UTF-8' ) );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in requesting the direct payment: ' . $response->get_error_message() );
			}
		} else if ( 401 === $response['response']['code'] ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'The user does not have permissions to use the PagSeguro Transparent Checkout!' );
			}

			return array(
				'url'   => '',
				'data'  => '',
				'error' => array( __( 'You are not allowed to use the PagSeguro Transparent Checkout. Looks like you neglected to installation guide of this plugin. This is not pretty, do you know?', 'woocommerce-asaas' ) ),
			);
		} else {
			try {
				$data = $this->safe_load_xml( $response['body'], LIBXML_NOCDATA );
			} catch ( Exception $e ) {
				$data = '';

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Error while parsing the PagSeguro response: ' . print_r( $e->getMessage(), true ) );
				}
			}

			if ( isset( $data->code ) ) {
				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'PagSeguro direct payment created successfully!' );
				}

				return array(
					'url'   => $this->gateway->get_return_url( $order ),
					'data'  => $data,
					'error' => '',
				);
			}

			if ( isset( $data->error ) ) {
				$errors = array();

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'An error occurred while generating the PagSeguro direct payment: ' . print_r( $response, true ) );
				}

				foreach ( $data->error as $error_key => $error ) {
					if ( $message = $this->get_error_message( $error->code ) ) {
						$errors[] = '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' . $message;
					}
				}

				return array(
					'url'   => '',
					'data'  => '',
					'error' => $errors,
				);
			}
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'An error occurred while generating the PagSeguro direct payment: ' . print_r( $response, true ) );
		}

		// Return error message.
		return array(
			'url'   => '',
			'data'  => '',
			'error' => array( '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-asaas' ) ),
		);
	}

	/**
	 * Process the IPN.
	 *
	 * @param  array $data IPN data.
	 *
	 * @return bool|SimpleXMLElement
	 */
	public function process_ipn_request( $data ) {

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Starting Asaas Request...' );
		}

		// // Valid the post data.
		// if ( ! isset( $data['notificationCode'] ) && ! isset( $data['notificationType'] ) ) {
		// 	if ( 'yes' == $this->gateway->debug ) {
		// 		$this->gateway->log->add( $this->gateway->id, 'Invalid IPN request: ' . print_r( $data, true ) );
		// 	}

		// 	return false;
		// }

		// // Checks the notificationType.
		// if ( 'transaction' != $data['notificationType'] ) {
		// 	if ( 'yes' == $this->gateway->debug ) {
		// 		$this->gateway->log->add( $this->gateway->id, 'Invalid IPN request, invalid "notificationType": ' . print_r( $data, true ) );
		// 	}

		// 	return false;
		// }

		// @TODO Add Asaas request
		//
		// Gets the PagSeguro response.


		$url = $this->build_url( $endpoint, $data );

		// If we have an WP_Error we return it here
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$headers = array(
			'access_token'  => $this->gateway->get_token(),
		);

		$args = array(
			'timeout' 	=> 60,
			'headers' 	=> $headers
		);

		// Get api first response
		$response = wp_remote_get( esc_url_raw( $url ), $args );


		// Check to see if the request was valid.
		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in API GET: ' . $response->get_error_message() );
			}
		} else {
			try {
				// Get first response
				$body = json_decode( wp_remote_retrieve_body( $response ) );
			} catch ( Exception $e ) {
				$body = '';

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Error while parsing the Asaas response body: ' . print_r( $e->getMessage(), true ) );
				}
			}

			if ( isset( $body->object ) ) {
				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Asaas Response is valid! The return is: ' . print_r( $body, true ) );
				}

				return $body;
			}
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'IPN Response: ' . print_r( $response, true ) );
		}

		return false;
	}

	/**
	 * Get session ID.
	 *
	 * @return string
	 */
	public function get_session_id() {

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Requesting session ID...' );
		}

		$url      = add_query_arg( array( 'email' => $this->gateway->get_email(), 'token' => $this->gateway->get_token() ), $this->get_sessions_url() );
		$response = $this->do_request( $url, 'POST' );

		// Check to see if the request was valid.
		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error requesting session ID: ' . $response->get_error_message() );
			}
		} else {
			try {
				$session = $this->safe_load_xml( $response['body'], LIBXML_NOCDATA );
			} catch ( Exception $e ) {
				$session = '';

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Error while parsing the PagSeguro session response: ' . print_r( $e->getMessage(), true ) );
				}
			}

			if ( isset( $session->id ) ) {
				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'PagSeguro session is valid! The return is: ' . print_r( $session, true ) );
				}

				return (string) $session->id;
			}
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Session Response: ' . print_r( $response, true ) );
		}

		return false;
	}
}
