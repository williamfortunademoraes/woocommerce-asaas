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
	 * Constructor
	 *
	 * @param WC_PagSeguro_Gateway $gateway Payment Gateway instance.
	 */
	public function __construct( $gateway = null ) {
		$this->gateway = $gateway;
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
		return 'https://' . $this->get_environment() . 'asaas.com/api/v3/';
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

		// if ( 'yes' == $this->gateway->tc_transfer ) {
		// 	$methods[] = 'bank-transfer';
		// }

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
		$url = $this->get_api_url() . $endpoint;

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

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Start GET Request for Asaas API for url: ' . $url );
			$this->gateway->log->add( $this->gateway->id, 'Start $data: ' . implode(",", $data) );
		}

		$headers = array(
			'access_token'  => $this->get_token(),
		);

		$args = array(
			'timeout' 	=> 60,
			'headers' 	=> $headers
		);

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Url Args: ' . implode( ",", $args ) );
		}

		// Get api first response
		$response = wp_remote_get( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			if ( isset( $response->errors['http_request_failed'] ) ) {
				$response->errors['http_request_failed'][0] = __( 'Connection timed out while getting data from '. $url , 'boleto-control' );
			}

			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Error Response ');
			}

			return $response;
		}

		// Get first response
		if ( empty( $response ) ) {
			 return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$body_var = get_object_vars( $response );

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Constructs body: ');
		}

		// Return if we have only this data
		if ( ! empty( $response->data ) ) {

			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'We have some data : ' );
			}

			$offset = sizeof( $response->data );

			// If has more values do next requests
			while ( $body_var['hasMore'] ) {

				// Builds url sending offset var
				$url = $this->build_url( $endpoint, array( 'offset' => $offset ) );

				// Gets next page
				$page = wp_remote_get( esc_url_raw( $url ), $args );

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Load page - Offset : ' . $offset );
				}

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

			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'GET Final response : ');
			}

			return $response->data;
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'EMPTY response ' );
		}

		return false;
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

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'POST Request data: ' . $data );
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

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Post First Response: ' );
		}

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Get an WP_Error for response : ' );
			}
			return $response;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Post Final Response: ' );
		}

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
			'access_token' 	=> $this->get_token(),
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
		return $this->post( $endpoint . '/' . $obj_id, $data);
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
	public function merge_asaas_customer() {

		$wp_user = wp_get_current_user();
		//$wp_user = new WP_User(get_current_user_id());

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, ' Merge Wp User Set : ' . implode( ",", $wp_user->ID ) );
		}

		//create customer data array based on wordpress user info
		$customer_data = array(
			'name' 			=> $wp_user->display_name,
			'email' 		=> $wp_user->user_email,
			'mobilePhone' 	=> get_user_meta($wp_user->ID,'celular',true),
			'cpfCnpj'	 	=> get_user_meta($wp_user->ID,'cpf',true),
			'externalReference' => $wp_user->ID,

		);

		//get customer info or false
		$is_old_cust = $api_data->get( 'customers', array( 'email' => $wp_user->user_email ) );
		$cust_id = $is_old_cust[0]->id;

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Cust id : ' . implode( ",", $cust_id  ));
		}

		// makes user upsert
		if ( ! empty( $cust_id ) ) {
			//customer data update
			$this->update_by_id( 'customers', $cust_id->id, $customer_data );
			update_user_meta( $wp_user->ID, '_asass_customer_data', $user_list );
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
	 * Create payments data via asaas api with wp_user info
	 *
	 * @param  Object $wp_user WP_User
	 * @param  Object $order   WC_Order
	 * @param  Array  $data
	 *
	 * @return bool
	 * @return string validate action type
	 */
	public function create_asaas_payment( $wp_user, $order, $data ) {

		//get customer and subs info or false
		$is_old_cust = $this->get_by_email( $wp_user->user_email );

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Get Existing Customer : ' );
		}

		//if user exists, get user subs data
		if ( empty( $is_old_cust ) ) {
			return false;
		}

		//get subscription->id saved on user_meta
		//$subs_id = get_user_meta( $wp_user->ID, '_asass_customer_id', true );

		//if subs exists for customer, get data
		// $asaas_subs = $this->get_by_customer( 'subscriptions', $is_old_cust->id ) ;

		//create subscription data array
		$payment_data = array(
			'customer' 	           => $is_old_cust->id,
			'billingType'	       => 'BOLETO',
			'dueDate' 	           => '20/05/2017',
			'value' 		       => '22,00',
			'externalReference'	   => $order->get_order_number(),
			'description'	       => 'WooAsaas Test'
		);

		// checks if exists subscription
		// if ( $asaas_subs ) {
		// 	$subs_data = array_merge( $subs_data, array('updatePendingPayments' => true) );
		// 	$subs_list = $this->update_by_id( 'subscriptions', $asaas_subs[0]->id, $subs_data );
		// 	$is_update = true;
		// } else {
			$response = $this->insert( 'payments', $payment_data );
		// }

		//insert user_meta with some subscription extra info
		update_user_meta( $wp_user->ID, '_asass_payment_id'			    , $response->id );
		update_user_meta( $wp_user->ID, '_asass_payment_created'		, $response->dateCreated );
		update_user_meta( $wp_user->ID, '_asass_payment_value'			, $response->value );
		update_user_meta( $wp_user->ID, '_asass_payment_billingtype'	, $response->billingType );
		update_user_meta( $wp_user->ID, '_asass_payment_deleted'		, $response->deleted );
		update_user_meta( $wp_user->ID, '_asass_payment_status'		    , $response->status );

		return isset( $is_update ) ? array( true, 'update' ) : array( true, 'insert' );
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
	// protected function do_request( $url, $method = 'POST', $data = array() ) {

	// 	// If we have an WP_Error we return it here
	// 	if ( is_wp_error( $url ) ) {
	// 		return $url;
	// 	}

	// 	if ( empty( $data['body'] ) ) {
	// 		$args = array( 'body' => json_encode( $data ) );
	// 	} else {
	// 		$args = $data;
	// 	}

	// 	$args['headers'] = array(
	// 		'Content-Type' => 'application/json',
	// 		'access_token' => $this->gateway->get_token(),
	// 	);

	// 	return wp_remote_post( esc_url_raw( $url ), $args );
	// }



	/**
	 * Safe load XML.
	 *
	 * @param  string $source  XML source.
	 * @param  int    $options DOMDpocment options.
	 *
	 * @return SimpleXMLElement|bool
	 */
	// protected function safe_load_xml( $source, $options = 0 ) {
	// 	$old = null;

	// 	if ( '<' !== substr( $source, 0, 1 ) ) {
	// 		return false;
	// 	}

	// 	if ( function_exists( 'libxml_disable_entity_loader' ) ) {
	// 		$old = libxml_disable_entity_loader( true );
	// 	}

	// 	$dom    = new DOMDocument();
	// 	$return = $dom->loadXML( $source, $options );

	// 	if ( ! is_null( $old ) ) {
	// 		libxml_disable_entity_loader( $old );
	// 	}

	// 	if ( ! $return ) {
	// 		return false;
	// 	}

	// 	if ( isset( $dom->doctype ) ) {
	// 		if ( 'yes' == $this->gateway->debug ) {
	// 			$this->gateway->log->add( $this->gateway->id, 'Unsafe DOCTYPE Detected while XML parsing' );
	// 		}

	// 		return false;
	// 	}

	// 	return simplexml_import_dom( $dom );
	// }

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
		// $user = wp_get_current_user();

		// if ( 'yes' == $this->gateway->debug ) {
		// 	$this->gateway->log->add( $this->gateway->id, 'Wp User Set : ' . $user );
		// }

		// //Create Asaas Customer for current user
		// $api_return = $this->merge_asaas_customer( $user );

		// if ( 'yes' == $this->gateway->debug ) {
		// 	$this->gateway->log->add( $this->gateway->id, 'Response for Cust Merge : ' . implode( ",", $api_return ) );
		// }

		// if ( ! $api_return[0] ) {
		// 	if ( 'yes' == $this->gateway->debug ) {
		// 		$this->gateway->log->add( $this->gateway->id, 'Return for Asaas Customer Upsert is: ' . $api_return[0]);
		// 	}
		// 		return array(
		// 			'url'   => '',
		// 			'token' => '',
		// 			'error' => 'Erro Retorno API',
		// 		);
		// }

		// if ( 'yes' == $this->gateway->debug ) {
		// 	$this->gateway->log->add( $this->gateway->id, 'Creating payment for order ' . $order->get_order_number() );
		// }

		// $response = $this->create_asaas_payment( $user, $order, $posted );

		//return $response;
		//
		//$url = add_query_arg( array( $response[0] ? 'success' :'fail' ) );

		return array(
			'url'   =>  ($response[0] ? 'success' :'fail'),
			'type'  =>   $response[1],
			'error' => '',
		);



		// $url      = add_query_arg( array( 'email' => $this->gateway->get_email(), 'token' => $this->gateway->get_token() ), $this->get_checkout_url() );

		// $response = $this->do_request( $url, 'POST', $xml, array( 'Content-Type' => 'application/xml;charset=UTF-8' ) );

		// if ( is_wp_error( $response ) ) {
		// 	if ( 'yes' == $this->gateway->debug ) {
		// 		$this->gateway->log->add( $this->gateway->id, 'WP_Error in generate payment token: ' . $response->get_error_message() );
		// 	}
		// } else if ( 401 === $response['response']['code'] ) {
		// 	if ( 'yes' == $this->gateway->debug ) {
		// 		$this->gateway->log->add( $this->gateway->id, 'Invalid token and/or email settings!' );
		// 	}

		// 	return array(
		// 		'url'   => '',
		// 		'data'  => '',
		// 		'error' => array( __( 'Too bad! The email or token from the PagSeguro are invalids my little friend!', 'woocommerce-asaas' ) ),
		// 	);
		// } else {
		// 	try {
		// 		libxml_disable_entity_loader( true );
		// 		$body = $this->safe_load_xml( $response['body'], LIBXML_NOCDATA );
		// 	} catch ( Exception $e ) {
		// 		$body = '';

		// 		if ( 'yes' == $this->gateway->debug ) {
		// 			$this->gateway->log->add( $this->gateway->id, 'Error while parsing the PagSeguro response: ' . print_r( $e->getMessage(), true ) );
		// 		}
		// 	}

		// 	if ( isset( $body->code ) ) {
		// 		$token = (string) $body->code;

		// 		if ( 'yes' == $this->gateway->debug ) {
		// 			$this->gateway->log->add( $this->gateway->id, 'PagSeguro Payment Token created with success! The Token is: ' . $token );
		// 		}

		// 		return array(
		// 			'url'   => $this->get_payment_url( $token ),
		// 			'token' => $token,
		// 			'error' => '',
		// 		);
		// 	}

		// 	if ( isset( $body->error ) ) {
		// 		$errors = array();

		// 		if ( 'yes' == $this->gateway->debug ) {
		// 			$this->gateway->log->add( $this->gateway->id, 'Failed to generate the PagSeguro Payment Token: ' . print_r( $response, true ) );
		// 		}

		// 		foreach ( $body->error as $error_key => $error ) {
		// 			if ( $message = $this->get_error_message( $error->code ) ) {
		// 				$errors[] = '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' . $message;
		// 			}
		// 		}

		// 		return array(
		// 			'url'   => '',
		// 			'token' => '',
		// 			'error' => $errors,
		// 		);
		// 	}
		// }

		// if ( 'yes' == $this->gateway->debug ) {
		// 	$this->gateway->log->add( $this->gateway->id, 'Error generating the PagSeguro payment token: ' . print_r( $response, true ) );
		// }

		// // Return error message.
		// return array(
		// 	'url'   => '',
		// 	'token' => '',
		// 	'error' => array( '<strong>' . __( 'PagSeguro', 'woocommerce-asaas' ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-asaas' ) ),
		// );
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

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, '$posted : ' . implode( ",", $posted ) );
		}

		$payment_method = isset( $posted['asaas_payment_method'] ) ? $posted['asaas_payment_method'] : '';

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Payment Method ' . $payment_method );
		}

		//$wp_user = wp_get_current_user();

		//Create Asaas Customer for current user
		$api_return = $this->merge_asaas_customer();

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Response for Cust Merge : ' . implode( ",", $api_return ) );
		}

		if ( ! $api_return[0] ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Return for Asaas Customer Upsert is: ' . $api_return[0]);
			}
				return array(
					'url'   => '',
					'token' => '',
					'error' => 'Erro Retorno API',
				);
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Creating payment for order ' . $order->get_order_number() );
		}

		$data = $this->create_asaas_payment( $user, $order, $posted );


		//redirects for ...
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

}
