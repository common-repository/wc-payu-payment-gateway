<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_PayU class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayU extends WC_Gateways_IMMA {

	public static $log_enabled = false;
	public $enabled;
	public $title;
	public $description;
	public $icon;
	public $api_key;
	public $api_login;
	public $merchant_id;
	public $account_id;
	public $country;
	public $sandbox;
	public $debug;
	public $reduce_stock;
	public $redirect_logo;
	public $paymentaction;
	public $status_completed;
	public $lang;

	public static $log = false;

	public function __construct() {
		$this->id             			= 'payu';
		$this->method_title   			= 'PayU';
		$this->method_description 		= __( 'The WC PayU payment gateway works by sending the customer\'s data to PayU LATAM to later process the purchase from that platform.', 'imma' );
		$this->has_fields         		= false;
		$this->supports           		= array( 'products');
										//tokenization, default_credit_card_form, refunds

		$this->init_form_fields();
		$this->init_settings();

		// Get setting values.
		$this->enabled              	= $this->get_option( 'enabled' );
		$this->title               		= $this->get_option( 'title' );
		$this->description          	= $this->get_option( 'description' );
		$this->icon 					= $this->get_option( 'logo' );
		$this->api_key             		= $this->get_option( 'api_key' );
		$this->api_login          		= $this->get_option( 'api_login' );
		$this->merchant_id        		= $this->get_option( 'merchant_id' );
		$this->account_id          		= $this->get_option( 'account_id' );
		$this->country          		= $this->get_option( 'country' );
		$this->sandbox         			= $this->get_option( 'sandbox', 'no' );
		$this->debug          			= $this->get_option( 'debug', 'no' );
		$this->reduce_stock				= $this->get_option( 'reduce_stock', 'yes' );
		self::$log_enabled    			= $this->debug;
		$this->redirect_logo 			= WCGW_PAYU_ASSETS_PATH.'images/redirect-v1.png';
		$this->paymentaction 			= $this->get_option( 'paymentaction', 'checkout' );
		$this->status_completed 		= $this->get_option( 'status_completed', 'wc-completed' );
		$this->lang 					= $this->get_option( 'lang', 'ES' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'wc_gw_payu_admin_options' ) );
		add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'wc_gw_payu_check_response' ), 10, 1 );
		add_filter( 'woocommerce_thankyou_order_id', array( $this, 'wc_gw_payu_pre_thankyou_page' ), 10, 1 );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'wc_gw_payu_thankyou_page' ), 10, 1 );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'wc_gw_payu_email_paybutton' ), 10, 3 );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'wc_gw_payu_complete_order_status' ), 10, 3 );
		add_action( 'do_payu_check_response', array( $this, 'wc_gw_do_payu_check_response' ), 10, 2 );
		add_filter( 'woocommerce_can_restore_order_stock', array( $this, 'wc_gw_payu_can_restore_order_stock' ), 10, 2 );
	}

	public function needs_setup() {
		return true;
	} //End needs_setup()

	public function is_available() {

		if ( 'yes' === $this->enabled ) {
			if ( ( $this->api_key == "" || $this->api_login == "" || $this->merchant_id == "" || $this->account_id == "" ) && $this->sandbox == 'no' ) {
				return false;
			}
			return true;
		}

		return parent::is_available();
	} //End is_available()

	public function get_paymentaction() {
		return $this->paymentaction;
	} //End get_paymentaction()

	public function get_status_completed( $format = false ) {
		if ( $format === true ) {
			return str_replace( "wc-", "", $this->status_completed );
		}
		return $this->status_completed;
	} //End get_status_completed()

	public function get_api_key() {
		$api_key = "";
		if ( $this->sandbox == 'no' ) {
			$api_key = $this->api_key;
		} else {
			$api_key = '4Vj8eK4rloUd272L48hsrarnUA';
		}
		return $api_key; 
	} //End get_api_key()

	public function get_api_login() {
		$api_login = "";
		if ( $this->sandbox == 'no' ) {
			$api_login = $this->api_login;
		} else {
			$api_login = 'pRRXKOl8ikMmt9u';
		}
		return $api_login; 
	} //End get_api_login()

	public function get_merchant_id() {
		$merchant_id = "";
		if ( $this->sandbox == 'no' ) {
			$merchant_id = $this->merchant_id;
		} else {
			$merchant_id = '508029';
		}
		return $merchant_id; 
	} //End get_merchant_id()

	public function get_account_id() {
		$account_id = "";
		if ( $this->sandbox == 'no' ) {
			$account_id = $this->account_id;
		} else {
			switch ( $this->country ) {
				case 'AR':
					$account_id = '512322';
				break;
				case 'BR':
					$account_id = '512327';
				break;
				case 'CL':
					$account_id = '512325';
				break;
				case 'MX':
					$account_id = '512324';
				break;
				case 'PE':
					$account_id = '512323';
				break;
				case 'PA':
					$account_id = '512326';
				break;
				case 'CO':
					$account_id = '512321';
				break;
			}
		}
		return $account_id; 
	} //End get_merchant_id()

	public function get_lang() {
		return $this->lang;
	} //End get_lang()

	public function get_url( $key = 'wcapi', $value = null ) {
		$_return = "";
		switch ( $key ) {
			case 'production_payments':
				if ( $this->sandbox =="yes" ) {
					$_return = 'https://'.'sandbox.api.payulatam.com'.'/payments-api/4.0/service.cgi';
				} else {
					$_return = 'https://'.'api.payulatam.com'.'/payments-api/4.0/service.cgi';
				}
			break;
			case 'production_reports':
				if ( $this->sandbox =="yes" ) {
					$_return = 'https://'.'sandbox.api.payulatam.com'.'/reports-api/4.0/service.cgi';
				} else {
					$_return = 'https://'.'api.payulatam.com'.'/reports-api/4.0/service.cgi';
				}
			break;			
			case 'production_subscriptions':
				if ( $this->sandbox =="yes" ) {
					$_return = 'https://'.'sandbox.api.payulatam.com'.'/payments-api/rest/v4.3/';
				} else {
					$_return = 'https://'.'api.payulatam.com'.'/payments-api/rest/v4.3/';
				}
			break;			
			case 'checkout':
				if ( $this->sandbox =="yes" ) {
					$_return = 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu';
				} else {
					$_return = 'https://checkout.payulatam.com/ppp-web-gateway-payu/';
				}
			break;			
			default:
				$_return = trailingslashit( get_home_url() . '/wc-api/WC_Gateway_PayU' );
				//get_site_url() . '/?wc-api=WC_Gateway_WOOPayu';
				//$webhook_url = add_query_arg( 'wc-api', 'WC_Gateway_PayU', trailingslashit( get_home_url() ) );
			break;
		}

		return $_return;
	} //End get_url()

	public function get_sandbox() {
		if ( $this->sandbox =="yes" )
			return true;
		else
			return false;
	} //End get_sandbox()

	public function get_reduce_stock_completed() {
		return wc_string_to_bool( $this->reduce_stock );
	} //End get_reduce_stock_completed()

	public function admin_options() {
		$token = __("Inactive", "imma");
		if ( $this->get_api_login() != "" && $this->get_api_key() != "" ) {
			$body = array(
				'test' => $this->get_sandbox(),
				'language' => $this->get_lang(),
				'command' => 'PING',
				'merchant' => array(
						'apiLogin' => $this->get_api_login(),
						'apiKey' => $this->get_api_key()
				)
			);
	        $response = wp_remote_post( $this->get_url( 'production_reports' ), array(
	            'timeout' => 60,
	            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
	            'body' => json_encode( $body ),
	        ) );
			$data = simplexml_load_string( wp_remote_retrieve_body($response) );
			if ( is_object($data) && isset($data->code) && $data->code == 'SUCCESS' ) {
				$token = __("Active", "imma");
			}
		}
		echo '<h2>' . esc_html( $this->get_method_title() );
		wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		echo '</h2>';
		$add_method_description = '<div style="border-style: dashed !important; background-color: #F1FAFF !important; padding: 15px !important; border: 1px solid #009EF7;">
									<ul style="color: #181C32 !important; font-weight: 600 !important;">
										<li> - ' . __("The PayU gateway only works with the following currencies: Argentine Pesos (ARS), Real Brasileño (BRL), Chilean Peso (CLP), Colombian Peso (COP), Mexican Peso (MXN), Peruvian Nuevo Sol (PEN) or American Dollar (USD).", "imma") . '</li>
										<li>- ' . sprintf( __("Token: %s", "imma"), $token ) . '</li>
									</ul>
								</div>';
		echo wp_kses_post( wpautop( $this->get_method_description() . $add_method_description ) );
		echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>'; // WPCS: XSS ok.
	} //End admin_options()

	public function init_form_fields() {
		$this->form_fields = require( dirname( __FILE__ ) . '/admin/payu-settings.php' );
	} //End init_form_fields()

	public function wc_gw_payu_admin_options() {
		$saved = parent::process_admin_options();
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->clear( 'payu' );
		}
		return $saved;
	} //End wc_gw_payu_admin_options()

	public function wc_gw_payu_get_payment_args( $order ) {
		if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( 'wc_gw_payu_get_payment_args' );

		$description = "";
	    $descripcionParts = array();
	    foreach ( $order->get_items() as $value ) {
	        $description = $value['name'];
	        $strip = array( "~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]","}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;","â€”", "â€“", ",", "<", ".", ">", "/", "?" );
	        $clean = trim( str_replace( $strip, "", strip_tags( $description ) ) );
	        $clean = preg_replace( '/\s+/', "_", $clean );
	        $clearData = str_replace( '_', ' ', $clean );
	        $descripcionParts[] = $clearData;
	    }
	    $description = implode( ' - ', $descripcionParts );
	    unset( $descripcionParts );
		//$productinfo =  sprintf( __("%s | Order #%s", "woopayu"), get_bloginfo( 'name' ), $order->get_id() );
	    $webhook_url = trailingslashit( get_home_url() . '/wc-api/WC_Gateway_PayU' );
		$response_url = $order->get_checkout_order_received_url();
		//$acepted_url = $this->get_checkout_order_received_url( $order, array( 'xrpayco' => 1 ) );
		//$rejected_url = $this->get_checkout_order_received_url( $order, array( 'xrpayco' => 2 ) );
		//$pending_url = $this->get_checkout_order_received_url( $order , array( 'xrpayco' => 3 ) );

		$base_tax = 0;
		$tax =  $order->get_total_tax();
		$total = $order->get_total();
        if ( $tax > 0 ) {
            $base_tax = $total - $tax;
            $base_tax = wc_format_decimal( $base_tax, 2 );
        } else {
            $base_tax = 0;
            $tax = 0;
        }
        $total = wc_format_decimal( $total, 2 );
        $referenceCode = time();

		$hpos_is_enabled = false;
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
			$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
			if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
				$hpos_is_enabled = true;
			}
		}
		if ( $hpos_is_enabled == true ) {
			global $wpdb;
			$exists = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = %s", $order->get_id(), 'payu_reference_code') );
		    if ( $exists ) {
		        $sql = "UPDATE {$wpdb->prefix}wc_orders_meta SET meta_value = %s WHERE order_id = %d AND meta_key = %s";
		        $params = array( $referenceCode, $order->get_id(), 'payu_reference_code' );
		        $sql = $wpdb->prepare( $sql, $params );
		        $result = $wpdb->query( $sql );
		    } else {
		        $result = $wpdb->insert(
		            "{$wpdb->prefix}wc_orders_meta",
		            array(
		                'order_id' => $order->get_id(),
		                'meta_key' => 'payu_reference_code',
		                'meta_value' => $referenceCode
		            ),
		            array('%d', '%s', '%s')
		        );
		    }
		} else {
			update_post_meta( $order->get_id(), 'payu_reference_code', $referenceCode );
		}
		
		$hash = $this->get_gw_payu_signature( $order );

		$args = array(
			'merchantId'					=> $this->get_merchant_id(),
			'referenceCode' 				=> $referenceCode, //$order->get_order_key(),
			'accountId'						=> $this->get_account_id(),
			'description' 					=> $description,
			'currency' 						=> $order->get_currency(),
			'amount' 						=> $total,
			'tax' 							=> $tax,
			//discount
			'taxReturnBase' 				=> $base_tax,
			//additionalValue
			'signature' 					=> $hash,
			//algorithmSignature
			'test' 							=> ( ($this->sandbox=="yes")? 1 : 0 ),
			'lng'							=> $this->get_lang(),
			'extra1' 						=> "woocommerce",
			'extra2' 						=> $order->get_id(),			
			//extra3
			//template
			'responseUrl' 					=> $response_url,
			'confirmationUrl' 				=> $webhook_url,
			//sourceUrl
			//airline
			'billingAddress' 				=> $order->get_billing_address_1() . " " . $order->get_billing_city() . " " . $order->get_billing_state(), //WC()->countries->get_states( $country )[$state];
			//'shippingAddress'
			'billingCity' 					=> $order->get_billing_city(),
			//'shippingCity'
			'zipCode' 						=> $order->get_billing_postcode(),
			'billingCountry' 				=> $order->get_billing_country(),
			//'shippingCountry'
			'buyerEmail' 					=> $order->get_billing_email(),
			'buyerFullName'					=> $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
			//paymentMethods
			//administrativeFee
			//taxAdministrativeFee
			//taxAdministrativeFeeReturnBase
			'payerEmail' 					=> $order->get_billing_email(),
			//expirationDate
			'payerFullName'					=> $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
			//payerDocument
			//payerDocumentType
			//iin
			//paymentMethodsDescription
			//pseBanks
		);
		$billing_phone = intval( $order->get_billing_phone() );
		if ( $billing_phone > 111111 ) {
			$args['telephone'] = $billing_phone;
			$args['mobilePhone'] = $billing_phone;
			$args['payerPhone'] = $billing_phone;
			$args['payerMobilePhone'] = $billing_phone;
		}
		return $args;
	} //End wc_gw_payu_get_payment_args()

	public function wc_gw_payu_check_response() {
		@ob_clean();
		header( 'HTTP/1.1 200 OK' ); //http_response_code();

		if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( "wc_gw_payu_check_response" . serialize($_REQUEST) );

		if ( ! empty( $_REQUEST ) ) {
			if ( isset($_REQUEST['merchant_id']) && $_REQUEST['merchant_id'] != "" ) {
				do_action( 'do_payu_check_response', $_REQUEST, null );
				wp_die( __( 'Checking IPN response is valid', 'imma' ), 'Checking IPN response', array( 'response' => 200 ) );
				exit;				
			}
		}
		wp_die( __( 'Unauthorized Access', 'imma' ), 'Unauthorized Access', array( 'response' => 500 ) );
		exit;
	} // End wc_gw_payu_check_response()

	public function wc_gw_payu_validate_ipn( $order, $sign, $state_pol ) {
		$hash = $this->get_gw_payu_signature( $order, $state_pol );
		if ( $hash == $sign ) {
			return true;
		}
		return false;
	} // End wc_gw_payu_validate_ipn()

	public function wc_gw_payu_pre_thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( is_object($order) && $this->id == $order->get_payment_method() ) {

			if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( 'wc_gw_payu_pre_thankyou_page' );

			$x_merchantId = 0;
			if ( isset($_GET['merchantId']) ) {
				$x_merchantId = intval( wc_clean( wp_unslash( $_GET['merchantId'] ) ) );
			}
			if ( $x_merchantId > 0 ) {
				$order = apply_filters( 'do_payu_check_response', $_GET, $order );
			}
			if ( $order->get_status() == 'on-hold' ) {
				$order->update_status( 'pending', __( 'The status is changed to pending payment (always) so that the buyer can use other payment methods if he wants it that way.', 'imma' ), true );
			}			

		}

		return $order_id;
	} //End wc_gw_payu_pre_thankyou_page()

	public function wc_gw_payu_thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$out = "";
		$wm_continue = true;
		$payu_method_type = 0;
		if ( is_object($order) && $this->id == $order->get_payment_method() ) {
			
			if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( 'wc_gw_payu_thankyou_page' );

			if ( !in_array( $order->get_status(), array( 'processing', 'cancelled', 'completed', 'failed', 'refunded' ) ) ) {
				$hpos_is_enabled = false;
				if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
					$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
					if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
						$hpos_is_enabled = true;
					}
				}
				if ( $hpos_is_enabled == true ) {
					global $wpdb;
				    $sql = "SELECT meta_key, meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d";
				    $params = array( $order->get_id() );
				    $sql .= " AND meta_key = %s";
				    $params[] = 'payu_method_type';
					$sql = $wpdb->prepare( $sql, $params );
					$result = $wpdb->get_results( $sql, ARRAY_A );
					if ( is_countable($result) && count($result) > 0 ) {
						$payu_method_type = maybe_unserialize( $result[0]['meta_value'] );
					}
				} else {
					$payu_method_type = get_post_meta( $order->get_id(), 'payu_method_type', true );
				}
				$payu_method_type = intval($payu_method_type);
				if ( in_array($payu_method_type, array(7, 8, 10)) && $order->get_status() == "pending" ) {
					$wm_continue = false;
				}
				if ( $wm_continue == true ) {
					$another_payment_text = __('Do you want to use another payment method?', 'imma');
					$another_payment_text = '<a class="button imma-new-gateway" style="width: 100%;margin-top: 10px;text-align: center;" href="' . $order->get_checkout_payment_url() . '">' . $another_payment_text . '</a>';
					$another_payment_text = apply_filters( 'wc_gw_payu_another_payment_text', $another_payment_text, $this->id, $order, $this );
					$payu_payment_text = '<a class="imma-new-gateway" style="width: 100%;margin-top: 10px;text-align: center;background-color: unset;border: none; display: block;"><img src="'.WCGW_PAYU_ASSETS_PATH.'images/btnpay.png'.'"></a>';
					$out .= '<p>' . sprintf( __( 'Thank you for your order, please click the button below to pay with PayU LATAM service. %s', 'imma' ), $another_payment_text ) . '</p>';
					$args_array = array();
					$payu_args = array();
					$payu_args = $this->wc_gw_payu_get_payment_args( $order );
					foreach ( $payu_args as $key => $value ) {
						$args_array[] =  '<input name="'.$key.'" type="hidden"  value="'.$value.'">';
					}
					$script = 'jQuery( "body" ).block({
						message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to PayU LATAM to make payment.', 'imma' ) ) . '",
						baseZ: 99999,
						overlayCSS: {
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        "20px",
						    zindex:         "9999999",
						    textAlign:      "center",
						    color:          "#555",
						    border:         "3px solid #aaa",
						    backgroundColor:"#fff",
						    cursor:         "wait",
						    lineHeight:		"24px",
						}
					});
					jQuery( "#submit_woopayu_payment_form" ).click(function() { jQuery("#woopayu_payment_form").submit(); });
					jQuery("#woopayu_payment_form").submit();';
					wc_enqueue_js( $script );
					if ( !empty($args_array) ) {
						$out .= '<form action="'.$this->get_url("checkout").'" method="post" id="woopayu_payment_form" target="_top">'. $payu_payment_text . implode( '', $args_array ) . '</form>';
					}
				}
			}
			echo $out;
		}
	} //End wc_gw_payu_thankyou_page()

	public function wc_gw_payu_email_paybutton( $order, $sent_to_admin, $plain_text ) {
		if ( is_object($order) && $this->id == $order->get_payment_method() && $plain_text == false && $sent_to_admin == false ) {
			$url = $order->get_checkout_payment_url();
			$pay_text = __( 'Pay now via PayU LATAM', 'imma' );
			$paymentlink = '<a class="button gatewaypay" href="'. $order->get_checkout_payment_url() .'">' . $pay_text . '</a>';
			$paymentlink = apply_filters( 'woocommerce_email_after_order_table_paylink', $paymentlink, $url, $pay_text, $order, $sent_to_admin, $plain_text );
			echo wp_kses_post( wpautop( wptexturize( $paymentlink ) ) . PHP_EOL );
		}
	} //End wc_gw_payu_email_paybutton()

	public function wc_gw_payu_complete_order_status( $status, $order_id, $order ) {
		if ( $this->id == $order->get_payment_method() ) {
			
			if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( 'wc_gw_payu_complete_order_status' );
			
			$status = 'completed';
		}
		return $status;
	} //End wc_gw_payu_complete_order_status()

	public function wc_gw_do_payu_check_response( $data_request, $order ) {
		if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( "wc_gw_do_payu_check_response" . serialize($data_request) );

		$merchant_id = $this->get_merchant_id();
		$payu_order_id = 0;
		$x_polPaymentMethodType = "";

		if ( $order == null ) {
			if ( $merchant_id != "" && isset($data_request['extra2']) && intval($data_request['extra2']) > 0 ) {
				$payu_order_id = intval($data_request['extra2']);
				if ( $payu_order_id > 0 ) {
					$order = wc_get_order( $payu_order_id );
				} else {
					if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error order_id %s", serialize($data_request) ) );
				}				
			} else {
				if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error data_request %s", serialize($data_request) ) );
			}
		}
		if ( $order != null && is_object($order) ) {
			$data_request_temp = $data_request;
			if ( isset($data_request_temp['transactionId']) ) {
				$data_request_temp['transaction_id'] = $data_request_temp['transactionId'];
			}
			$this->wc_gw_payu_save_thistory( $data_request_temp, $order );
			$order_status = $order->get_status();
			if ( $order_status != "trash" ) {
				if ( (isset($data_request['signature'])||isset($data_request['sign']))
					&& (isset($data_request['polTransactionState'])||isset($data_request['state_pol']))
					&& (isset($data_request['merchantId'])||isset($data_request['merchant_id']))
					&& (isset($data_request['polResponseCode'])||isset($data_request['response_code_pol']))
					&& (isset($data_request['lapResponseCode'])||isset($data_request['response_message_pol'])) ) {
					$x_signature = "";
					if ( isset($data_request['signature']) ) {
						$x_signature = wc_clean( wp_unslash( $data_request['signature'] ) );
					} else {
						$x_signature = wc_clean( wp_unslash( $data_request['sign'] ) );
					}
					$x_polTransactionState = "";
					if ( isset($data_request['polTransactionState']) ) {
						$x_polTransactionState = wc_clean( wp_unslash( $data_request['polTransactionState'] ) );
					} else {
						$x_polTransactionState = wc_clean( wp_unslash( $data_request['state_pol'] ) );
					}
					$x_merchant_id = "";
					if ( isset($data_request['merchantId']) ) {
						$x_merchant_id = wc_clean( wp_unslash( $data_request['merchantId'] ) );
					} else {
						$x_merchant_id = wc_clean( wp_unslash( $data_request['merchant_id'] ) );
					}
					$x_polResponseCode = "";
					if ( isset($data_request['polResponseCode']) ) {
						$x_polResponseCode = wc_clean( wp_unslash( $data_request['polResponseCode'] ) );
					} else {
						$x_polResponseCode = wc_clean( wp_unslash( $data_request['response_code_pol'] ) );
					}
					if ( isset($data_request['polPaymentMethodType']) ) {
						$x_polPaymentMethodType = intval( wc_clean( wp_unslash( $data_request['polPaymentMethodType'] ) ) );
					} else {
						$x_polPaymentMethodType = intval( wc_clean( wp_unslash( $data_request['payment_method_id'] ) ) );
					}
					$hpos_is_enabled = false;
					if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
						$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
						if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
							$hpos_is_enabled = true;
						}
					}
					if ( $hpos_is_enabled == true ) {
						global $wpdb;
						$exists = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = %s", $order->get_id(), 'payu_method_type') );
					    if ( $exists ) {
					        $sql = "UPDATE {$wpdb->prefix}wc_orders_meta SET meta_value = %s WHERE order_id = %d AND meta_key = %s";
					        $params = array( $x_polPaymentMethodType, $order->get_id(), 'payu_method_type' );
					        $sql = $wpdb->prepare( $sql, $params );
					        $result = $wpdb->query( $sql );
					    } else {
					        $result = $wpdb->insert(
					            "{$wpdb->prefix}wc_orders_meta",
					            array(
					                'order_id' => $order->get_id(),
					                'meta_key' => 'payu_method_type',
					                'meta_value' => $x_polPaymentMethodType
					            ),
					            array('%d', '%s', '%s')
					        );
					    }
					} else {
						update_post_meta( $order->get_id(), 'payu_method_type', $x_polPaymentMethodType );
					}
					$x_lapResponseCode = "";
					if ( isset($data_request['lapResponseCode']) ) {
						$x_lapResponseCode = wc_clean( wp_unslash( $data_request['lapResponseCode'] ) );
					} else {
						$x_lapResponseCode = wc_clean( wp_unslash( $data_request['response_message_pol'] ) );
					}
					$x_reference_pol = wc_clean( wp_unslash( $data_request['reference_pol'] ) );
					$x_transactionState = "";
					if ( isset($data_request['transactionState']) ) {
						$x_transactionState = wc_clean( wp_unslash( $data_request['transactionState'] ) );
					}
					if ( $merchant_id == $x_merchant_id ) {
						if ( $x_polTransactionState > 0 && $x_signature != "" && $x_polResponseCode != "" && $x_lapResponseCode != "" && $x_reference_pol != "" ) {
							switch ( $x_polTransactionState ) {
								case 4: //transaccion aprobada (processing) (completed si es Virtual and Downloadable)
									$status = $this->get_status_completed( true );
									if ( $status == 'completed' ) {
										$order->payment_complete( $x_reference_pol );
									} else {
										$order->set_transaction_id( $x_reference_pol );
										$order->update_status( $status, __('Transaction approved successfully', 'imma'), true );
									}
								break;
								case 5:  //transaccion caducada
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( 'failed', $x_lapResponseCode, true );
								break;
								case 6: //transaccion rechazada
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( 'failed', $x_lapResponseCode, true );
								break;
								case 7: //transaccion pendiente (pending)
									$status = 'processing';
									if ( in_array($x_polPaymentMethodType, array(7, 8, 10)) ) {
										$status = 'pending';
									}
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( $status, $x_lapResponseCode, true );
								break;
								case 10: //transaccion pendiente (pending)
									$status = 'processing';
									if ( in_array($x_polPaymentMethodType, array(7, 8, 10)) ) {
										$status = 'pending';
									}
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( $status, $x_lapResponseCode, true );
								break;
								case 12: //transaccion pendiente (pending)
									$status = 'processing';
									if ( in_array($x_polPaymentMethodType, array(7, 8, 10)) ) {
										$status = 'pending';
									}
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( $status, $x_lapResponseCode, true );
								break;
								case 14: //transaccion pendiente (pending)
									$status = 'processing';
									if ( in_array($x_polPaymentMethodType, array(7, 8, 10)) ) {
										$status = 'pending';
									}
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( $status, $x_lapResponseCode, true );
								break;
								case 15: //transaccion pendiente (pending)
									$status = 'processing';
									if ( in_array($x_polPaymentMethodType, array(7, 8, 10)) ) {
										$status = 'pending';
									}
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( $status, $x_lapResponseCode, true );
								break;
								case 18: //transaccion pendiente (pending)
									$status = 'processing';
									if ( in_array($x_polPaymentMethodType, array(7, 8, 10)) ) {
										$status = 'pending';
									}
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( $status, $x_lapResponseCode, true );
								break;
								default:
									if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error payu data response 001 %s", serialize($data_request) ) );
									$order->set_transaction_id( $x_reference_pol );
									$order->update_status( 'failed', sprintf( "Error x_polTransactionState %s", $x_polTransactionState ), true );
								break;
							}
						} else {
							if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error payu data response 002 %s", serialize($data_request) ) );
						}
					} else {
						if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error merchantid %s - %s ", $merchant_id, $x_merchant_id ) );
					}
				} else {
					if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error payu data response 003 %s", serialize($data_request) ) );								
				}
			} else {
				if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error order_status %s", $order_status ) );
			}
			do_action( 'after_wc_gw_payu_check_response', $order, $data_request );
		} else {
			if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( sprintf( "Error order %s", serialize($order) ) );
		}

		return $order;
	} //End wc_gw_do_payu_check_response()

	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'payu' ) );
		}	
	} //End log()

	public function wc_gw_payu_can_restore_order_stock( $value, $order ) {
		if ( $order->get_status() != 'cancelled' ) {
			$value = false;
		}
		return $value;
	} //End wc_gw_payu_can_restore_order_stock()

	public function wc_gw_payu_round ( $value, $format = false ) {
		$value = floatval( $value );
		if ( $format == true ) {
			return round( $value, 2, PHP_ROUND_HALF_EVEN );
		} else {
			return round( $value, 2 );			
		}
	} //End wc_gw_payu_round()

	public function get_gw_payu_signature ( $order, $state_pol = null ) {
		//$txnid = $order->get_order_key();
		$hpos_is_enabled = false;
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
			$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
			if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
				$hpos_is_enabled = true;
			}
		}
		if ( $hpos_is_enabled == true ) {
			global $wpdb;
		    $sql = "SELECT meta_key, meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d";
		    $params = array( $order->get_id() );
	        $sql .= " AND meta_key = %s";
	        $params[] = 'payu_reference_code';
			$sql = $wpdb->prepare( $sql, $params );
			$result = $wpdb->get_results( $sql, ARRAY_A );
			if ( is_countable($result) && count($result) > 0 ) {
				$txnid = maybe_unserialize( $result[0]['meta_value'] );
			}
		} else {
			$txnid = get_post_meta( $order->get_id(), 'payu_reference_code', true );
		}
		$txnid = trim( $txnid );
		$total = $this->wc_gw_payu_round( $order->get_total(), true );

		if ( $state_pol != null ) {
			$str = $this->api_key."~".$this->merchant_id."~".$txnid."~".$total."~".$order->get_currency()."~".$state_pol;
		} else {
			$str = $this->api_key."~".$this->merchant_id."~".$txnid."~".$total."~".$order->get_currency();
		}

		$hash = strtolower( md5( $str ) );

		if ( $this->debug == 'yes' ) WC_Gateway_PayU::log( 'get_gw_payu_signature ' . $hash . ' - ' . $txnid );

		return $hash;
	} //End get_gw_payu_signature()

	public function wc_gw_payu_save_thistory( $data_request, $order ) {
		$wpdb = null;
		$payuyh = array();
		$hpos_is_enabled = false;
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
			$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
			if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
				$hpos_is_enabled = true;
			}
		}
		if ( $hpos_is_enabled == true ) {
			global $wpdb;
		    $sql = "SELECT meta_key, meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d";
		    $params = array( $order->get_id() );
	        $sql .= " AND meta_key = %s";
	        $params[] = 'htransaction';
			$sql = $wpdb->prepare( $sql, $params );
			$result = $wpdb->get_results( $sql, ARRAY_A );
			if ( is_countable($result) && count($result) > 0 ) {
				$payuyh = maybe_unserialize( $result[0]['meta_value'] );
			}
		} else {
			$payuyh = get_post_meta( $order->get_id(), 'htransaction', true );
		}
		if ( !is_array($payuyh) ) {
			unset( $payuyh );
			$payuyh = array();
		}
		$save_htransaction = true;
		if ( !empty($payuyh) ) {
			foreach ( $payuyh as $value ) {
				if ( isset($value['transaction_id'], $data_request['transaction_id']) && $value['transaction_id'] == $data_request['transaction_id'] ) {
					$save_htransaction = false;
					break;
				}
			}
		}
		if ( $save_htransaction == true ) {
			$payuyh[] = $data_request;
			if ( $hpos_is_enabled == true ) {
				if ( is_array( $payuyh ) || is_object( $payuyh ) ) {
					$payuyh = serialize( $payuyh );
				}
				if ( is_null($wpdb) ) global $wpdb;
				$exists = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = %s", $order->get_id(), 'htransaction') );
			    if ( $exists ) {
			        $sql = "UPDATE {$wpdb->prefix}wc_orders_meta SET meta_value = %s WHERE order_id = %d AND meta_key = %s";
			        $params = array( $payuyh, $order->get_id(), 'htransaction' );
			        $sql = $wpdb->prepare( $sql, $params );
			        $result = $wpdb->query( $sql );
			    } else {
			        $result = $wpdb->insert(
			            "{$wpdb->prefix}wc_orders_meta",
			            array(
			                'order_id' => $order->get_id(),
			                'meta_key' => 'htransaction',
			                'meta_value' => $payuyh
			            ),
			            array('%d', '%s', '%s')
			        );
			    }
			} else {
				update_post_meta( $order->get_id(), 'htransaction', $payuyh );
			}
		}
	} //End wc_gw_payu_save_thistory()	
}