<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'WMimmaMenuPayU', false ) ) {
	return new WMimmaMenuPayU();
}

/**
 * WMimmaMenuPayU Class.
 */
if ( !class_exists( 'WMimmaMenuPayU' ) ) {
	class WMimmaMenuPayU {
	    
		public function __construct () {
			add_action( 'add_meta_boxes', array( $this, 'wc_add_order_history_box' ), 10 );
			add_action( 'wp_ajax_imma_close_admin_notices', array( $this, 'imma_do_close_admin_notices' ), 10 );
			add_action( 'wp_ajax_imma_replicate_payu_transaction', array( $this, 'imma_do_replicate_payu_transaction' ), 10 );
			add_action( 'add_meta_boxes', array( $this, 'wc_add_order_payu_box' ), 10 );			
		} //End __construct()

		public function __clone () {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), WM_VERSION );
		} //End __clone()

		public function __wakeup () {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), WM_VERSION );
		} //End __wakeup()

		public function wc_add_order_history_box () {
			$screen = 'shop_order';
			$hpos_is_enabled = false;
			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
				$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
				if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
					$hpos_is_enabled = true;
				}
			}
			if ( $hpos_is_enabled == true ) {
				if ( function_exists('wc_get_page_screen_id') ) {
					$screen = wc_get_page_screen_id( 'shop-order' );
				} else {
					$screen = 'woocommerce_page_wc-orders';
				}
			}
	    	add_meta_box( 'transactions-history-payu-id', __( 'PayU transactions history', 'imma' ),  array( $this, 'wc_add_order_thistory_box_function' ), $screen, 'normal', 'low' );
		} //End wc_add_order_history_box()

		public function wc_add_order_thistory_box_function ( $post ) {
			$thistory = array();
			$order_id = 0;
			$_order = null;
			$out = '<div style="background-color: #3699FF;border-color: #3699FF;color: #FFFFFF;padding: 1rem;">'.__("This order does not have any recorded history", "imma").'.</div>';
			if ( is_a($post, 'WC_Order') ) {
				$_order = $post;
			} else {
				$_order = wc_get_order( $post->ID );
			}
			if ( is_object($_order) ) {
				$found_data = false;
				$note_content_style = "";
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
				    $params = array( $_order->get_id() );
			        $sql .= " AND meta_key = %s";
			        $params[] = 'htransaction';
					$sql = $wpdb->prepare( $sql, $params );
					$result = $wpdb->get_results( $sql, ARRAY_A );
					if ( is_countable($result) && count($result) > 0 ) {
						$thistory = maybe_unserialize( $result[0]['meta_value'] );
					}
				} else {
					$thistory = get_post_meta( $_order->get_id(), 'htransaction', true );
				}
				if ( is_array($thistory) && !empty($thistory) ) {
					$out = '<ul class="order_notes">';
					foreach ( $thistory as $value ) {
						if ( !isset($value['x_ref_payco']) ) {
							$data_value = "";
							foreach ( $value as $key => $datavalue ) {
								if ( 'payment_method_type' == $key ) {
									switch ( intval($datavalue) ) {
										case 2: $datavalue = __("CREDIT CARD", "imma"); break;
										case 4: $datavalue = __("PSE", "imma"); break;
										case 5: $datavalue = __("ACH", "imma"); break;
										case 6: $datavalue = __("DEBIT CARD", "imma"); break;
										case 7: $datavalue = __("CASH", "imma"); break;
										case 8: $datavalue = __("REFERENCED", "imma"); break;
										case 10: $datavalue = __("BANK REFERENCED", "imma"); break;
										case 14: $datavalue = __("SPEI", "imma"); break;
										default: break;
									}
								 } else if ( 'response_message_pol' == $key ) {
									switch ( $datavalue ) {
										case 'ERROR': $datavalue = __('General error', 'imma'); break;
										case 'APPROVED': $datavalue = __('The transaction was approved', 'imma'); break;
										case 'ANTIFRAUD_REJECTED': $datavalue = __('The transaction was rejected by the anti-fraud system', 'imma'); break;
										case 'BANK_FRAUD_REJECTED': $datavalue = __('The transaction was rejected due to suspected fraud at the financial institution', 'imma'); break;
										case 'PAYMENT_NETWORK_REJECTED': $datavalue = __('The financial network rejected the transaction', 'imma'); break;
										case 'ENTITY_DECLINED': $datavalue = __('The transaction was declined by the bank or financial network because of an error', 'imma'); break;
										case 'INTERNAL_PAYMENT_PROVIDER_ERROR': $datavalue = __('An error has occurred in the system trying to process the payment', 'imma'); break;
										case 'INACTIVE_PAYMENT_PROVIDER': $datavalue = __('The payment provider was not active', 'imma'); break;
										case 'DIGITAL_CERTIFICATE_NOT_FOUND': $datavalue = __('The financial network reported an authentication error', 'imma'); break;
										case 'INVALID_EXPIRATION_DATE_OR_SECURITY_CODE': $datavalue = __('The security code or expiration date was invalid', 'imma'); break;
										case 'INVALID_RESPONSE_PARTIAL_APPROVAL': $datavalue = __('Invalid response type. The entity response is a partial approval and should be automatically canceled by the system', 'imma'); break;
										case 'INSUFFICIENT_FUNDS': $datavalue = __('The account had insufficient funds', 'imma'); break;
										case 'CREDIT_CARD_NOT_AUTHORIZED _FOR_INTERNET_TRANSACTIONS': $datavalue = __('The credit card was not authorized for internet transactions', 'imma'); break;
										case 'INVALID_TRANSACTION': $datavalue = __('The financial network reported that the transaction was invalid', 'imma'); break;
										case 'INVALID_CARD': $datavalue = __('The card is invalid', 'imma'); break;
										case 'EXPIRED_CARD': $datavalue = __('The card has expired', 'imma'); break;
										case 'RESTRICTED_CARD': $datavalue = __('The card has a restriction', 'imma'); break;
										case 'CONTACT_THE_ENTITY': $datavalue = __('You should contact the bank', 'imma'); break;
										case 'REPEAT_TRANSACTION': $datavalue = __('You must repeat the transaction', 'imma'); break;
										case 'ENTITY_MESSAGING_ERROR': $datavalue = __('The transaction was not accepted by the bank for some reason', 'imma'); break;
										case 'BANK_UNREACHABLE': $datavalue = __('The bank was not available', 'imma'); break;
										case 'EXCEEDED_AMOUNT': $datavalue = __('The transaction exceeds the amount set by the bank', 'imma'); break;
										case 'NOT_ACCEPTED_TRANSACTION': $datavalue = __('The transaction was not accepted by the bank for some reason', 'imma'); break;
										case 'ERROR_CONVERTING_TRANSACTION_AMOUNTS': $datavalue = __('An error occurred converting the amounts to the payment currency', 'imma'); break;
										case 'EXPIRED_TRANSACTION': $datavalue = __('The transaction expired', 'imma'); break;
										case 'PENDING_TRANSACTION_REVIEW': $datavalue = __('The transaction was stopped and must be revised, this can occur because of security filters', 'imma'); break;
										case 'PENDING_TRANSACTION_CONFIRMATION': $datavalue = __('The transaction is subject to confirmation', 'imma'); break;
										case 'PENDING_TRANSACTION_TRANSMISSION': $datavalue = __('The transaction is subject to be transmitted to the financial network. This usually applies to transactions with cash payment means', 'imma'); break;
										case 'PAYMENT_NETWORK_BAD_RESPONSE': $datavalue = __('The message returned by the financial network is inconsistent', 'imma'); break;
										case 'PAYMENT_NETWORK_NO_CONNECTION': $datavalue = __('Could not connect to the financial network', 'imma'); break;
										case 'PAYMENT_NETWORK_NO_RESPONSE': $datavalue = __('Financial Network did not respond', 'imma'); break;
										case 'FIX_NOT_REQUIRED': $datavalue = __('Transactions clinic: internal handling code', 'imma'); break;
										case 'AUTOMATICALLY_FIXED_AND_SUCCESS_REVERSAL': $datavalue = __('Transactions clinic: internal handling code. Query API', 'imma'); break;
										case 'AUTOMATICALLY_FIXED_AND_UNSUCCESS_REVERSAL': $datavalue = __('Transactions clinic: internal handling code. Query API', 'imma'); break;
										case 'AUTOMATIC_FIXED_NOT_SUPPORTED': $datavalue = __('Transactions clinic: internal handling code. Query API', 'imma'); break;
										case 'NOT_FIXED_FOR_ERROR_STATE': $datavalue = __('Transactions clinic: internal handling code. Query API', 'imma'); break;
										case 'ERROR_FIXING_AND_REVERSING': $datavalue = __('Transactions clinic: internal handling code. Query API', 'imma'); break;
										case 'ERROR_FIXING_INCOMPLETE_DATA': $datavalue = __('Transactions clinic: internal handling code. Query API', 'imma'); break;
										default: break;
									}								
								}
								$data_value .= '<strong>' . $key . '</strong>: ' . $datavalue . '<br>';
							}
							$note_content_style = "background: #F5F5F5; color: #6D7278";
							$out .= '<li class="note" style="margin-bottom: 10px;"><div class="note_content" style="'.$note_content_style.'"><p style="column-count: 4;">'.$data_value.'</p></div></li>';
							$found_data = true;
						}
					}
					$out .= '</ul>';
				}
				if ( $found_data == false ) $out = '<div style="background-color: #3699FF;border-color: #3699FF;color: #FFFFFF;padding: 1rem;">'.__("This order does not have an history associated PayU payment method", "imma").'.</div>';
			}
			echo $out;
		} //End wc_add_order_thistory_box_function()

		public function imma_do_close_admin_notices () {
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				update_user_meta( $user_id, 'imma_wm_ads', strtotime( date('Y-m-d', strtotime('+3 day')) ) );
			}
			wp_die();
		} //End imma_do_close_admin_notices()

		public function imma_do_replicate_payu_transaction () {
			if ( isset($_POST['action'], $_POST['dcod'], $_POST['dpostid']) && $_POST['action'] == "imma_replicate_payu_transaction" && $_POST['dcod'] != "" && $_POST['dpostid'] != "" ) {
				$x_cod_response = intval( wc_clean( wp_unslash( $_POST['dcod'] ) ) );
				$post_id = intval( wc_clean( wp_unslash( $_POST['dpostid'] ) ) );
				$wc_order = wc_get_order( $post_id );
				$dmethod = "";
				if ( isset($_POST['dmethod']) && $_POST['dmethod'] != "" ) {
					$dmethod = wc_clean( wp_unslash( $_POST['dmethod'] ) );
				}
				if ( is_object($wc_order) && $wc_order->get_payment_method() == 'payu' ) {
					switch ( $x_cod_response ) {
						case 4:
							$wc_gateway_payu = new WC_Gateway_PayU();
							$status = $wc_gateway_payu->get_status_completed( true );
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 5:
							$wc_order->update_status( 'failed', __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 6:
							$wc_order->update_status( 'failed', __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 7:
							$status = 'processing';
							if ( $dmethod == "OFFLINE" ) {
								$status = 'pending';
							}
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 10:
							$status = 'processing';
							if ( $dmethod == "OFFLINE" ) {
								$status = 'pending';
							}
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 12:
							$status = 'processing';
							if ( $dmethod == "OFFLINE" ) {
								$status = 'pending';
							}
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 14:
							$status = 'processing';
							if ( $dmethod == "OFFLINE" ) {
								$status = 'pending';
							}
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 15:
							$status = 'processing';
							if ( $dmethod == "OFFLINE" ) {
								$status = 'pending';
							}
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						case 18:
							$status = 'processing';
							if ( $dmethod == "OFFLINE" ) {
								$status = 'pending';
							}
							$wc_order->update_status( $status, __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
						default:
							$wc_order->update_status( 'failed', __('Manual process by IMMAGIT PayU', 'imma'), true );
						break;
					}
				}
			}

			wp_die();
		} //End imma_do_replicate_payu_transaction()

		public function wc_add_order_payu_box () {
			$screen = 'shop_order';
			$hpos_is_enabled = false;
			if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil') ) {
				$orderUtil = new \Automattic\WooCommerce\Utilities\OrderUtil();
				if ( $orderUtil::custom_orders_table_usage_is_enabled() ) {
					$hpos_is_enabled = true;
				}
			}
			if ( $hpos_is_enabled == true ) {
				if ( function_exists('wc_get_page_screen_id') ) {
					$screen = wc_get_page_screen_id( 'shop-order' );
				} else {
					$screen = 'woocommerce_page_wc-orders';
				}
			}
	    	add_meta_box( 'data-payu-box', __( 'PayU Data', 'imma' ),  array( $this, 'wc_add_data_payu_box_function' ), $screen, 'side', 'low' );
		} //End wc_add_order_payu_box()		

		public function wc_add_data_payu_box_function ( $post ) {
			$token = "";
			$dcod = "";
			$dmethod = "CREDIT_CARD";	
			$order_id = 0;
			$wc_order = null;
			if ( is_a($post, 'WC_Order') ) {
				$wc_order = $post;
			} else {
				$wc_order = wc_get_order( $post->ID );
			}
			if ( is_object($wc_order) && $wc_order->get_payment_method() == 'payu' ) {
				$transaction = $wc_order->get_transaction_id();
				if ( $transaction != "" ) {
					$isOkData = null;
					$wc_gateway_payu = new WC_Gateway_PayU();
					if ( $wc_gateway_payu->get_api_key() == "" || $wc_gateway_payu->get_api_login() == "" ) {
						echo '<div style="background-color: #3699FF;border-color: #3699FF;color: #FFFFFF;padding: 1rem;">'.sprintf( __("You do not have an API KEY or an API LOGIN. Go to %s again and save the changes. If the problem persists, contact technical support.", "imma"), '<a style="color: #591d23;" href="'.admin_url( "admin.php?page=wc-settings&tab=checkout&section=payu" ).'">'.__("settings", "imma").'</a>' ).'</div>';
					} else {
						$body = array(
							'test' => $wc_gateway_payu->get_sandbox(),
							'language' => $wc_gateway_payu->get_lang(),
							'command' => 'ORDER_DETAIL',
							'merchant' => array(
											'apiLogin' => $wc_gateway_payu->get_api_login(),
											'apiKey' => $wc_gateway_payu->get_api_key()
										),
							'details' => array( 'orderId' => $transaction )
						);
				        $response = wp_remote_post( $wc_gateway_payu->get_url( 'production_reports' ), array(
				            'timeout' => 60,
				            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
				            'body' => json_encode( $body ),
				        ) );
						$isOkData = simplexml_load_string( wp_remote_retrieve_body($response) );
				        if ( is_object($isOkData) && isset($isOkData->code, $isOkData->result) && $isOkData->code == "SUCCESS" ) {
				        	if ( is_object($isOkData->result) && isset($isOkData->result->payload) && is_object($isOkData->result->payload) ) {
								if ( isset($isOkData->result->payload->id) && $isOkData->result->payload->id != "" && !is_null($isOkData->result->payload->id) ) {
									$dcod = $isOkData->result->payload->id;
								}
					        	if ( !empty($isOkData->result->payload->transactions) ) {
									$txt = '<strong style="font-size: 14px;">'.__("Status of the transactions", "imma").'</strong><br><br>';
					        		$found_data = false;
					        		$txt .= '<table style="width: 100%;">';
					        		foreach ( $isOkData->result->payload->transactions as $value ) {
					        			if ( isset($value->transaction) && is_object($value->transaction) ) {
						        			if ( isset($value->transaction->transactionResponse, $value->transaction->transactionResponse->state) )
						        				$txt .= '<tr><td style="font-weight: 600;">state</td><td>'.$value->transaction->transactionResponse->state.'</td></tr>';
						        			if ( isset($value->transaction->paymentMethod) ) {
						        				$txt .= '<tr><td style="font-weight: 600;">payment Method</td><td>'.$value->transaction->paymentMethod.'</td></tr>';
						        				if ( isset($value->transaction->paymentCountry) && $value->transaction->paymentCountry == "CO" ) {
							        				if ( in_array( mb_strtoupper($value->transaction->paymentMethod), array('EFECTY', 'OTHERS_CASH', 'BANK_REFERENCED' ) ) ) {
							        					$dmethod = 'OFFLINE';
							        				}
							        			}
						        			}
						        			if ( isset($value->transaction->transactionResponse, $value->transaction->transactionResponse->paymentNetworkResponseErrorMessage) )
						        				$txt .= '<tr><td style="font-weight: 600;">payment Error</td><td>'.$value->transaction->transactionResponse->paymentNetworkResponseErrorMessage.'</td></tr>';
						        			if ( isset($value->transaction->transactionResponse, $value->transaction->transactionResponse->responseCode) ) {
												$string = str_replace("_", " ", $value->transaction->transactionResponse->responseCode);
						        				$txt .= '<tr><td style="font-weight: 600;">response</td><td>'.$string.'</td></tr>';						        				
						        			}
						        			if ( isset($value->transaction->transactionResponse, $value->transaction->transactionResponse->operationDate) )
						        				$txt .= '<tr><td style="font-weight: 600;">date</td><td>'.$value->transaction->transactionResponse->operationDate.'</td></tr>';
						        			if ( isset($value->transaction->userAgent) )
						        				$txt .= '<tr><td style="font-weight: 600;">userAgent</td><td>'.$value->transaction->userAgent.'</td></tr>';
						        			if ( isset($value->transaction->payer, $value->transaction->payer->fullName) )
						        				$txt .= '<tr><td style="font-weight: 600;">fullName</td><td>'.$value->transaction->payer->fullName.'</td></tr>';
						        			if ( isset($value->transaction->payer, $value->transaction->payer->emailAddress) )
						        				$txt .= '<tr><td style="font-weight: 600;">email</td><td>'.$value->transaction->payer->emailAddress.'</td></tr>';
						        			if ( isset($value->transaction->payer, $value->transaction->payer->contactPhone) )
						        				$txt .= '<tr><td style="font-weight: 600;">contactPhone</td><td>'.$value->transaction->payer->contactPhone.'</td></tr>';
						        			if ( isset($value->transaction->payer, $value->transaction->payer->dniNumber) )
						        				$txt .= '<tr><td style="font-weight: 600;">dniNumber</td><td>'.$value->transaction->payer->dniNumber.'</td></tr>';
						        			$billingAddress = array();
						        			if ( isset($value->transaction->payer, $value->transaction->payer->billingAddress, $value->transaction->payer->billingAddress->street1) )
						        				$billingAddress[] = $value->transaction->payer->billingAddress->street1;
						        			if ( isset($value->transaction->payer, $value->transaction->payer->billingAddress, $value->transaction->payer->billingAddress->street2) )
						        				$billingAddress[] = $value->transaction->payer->billingAddress->street2;
						        			if ( isset($value->transaction->payer, $value->transaction->payer->billingAddress, $value->transaction->payer->billingAddress->city) )
						        				$billingAddress[] = $value->transaction->payer->billingAddress->city;
						        			if ( isset($value->transaction->payer, $value->transaction->payer->billingAddress, $value->transaction->payer->billingAddress->state) )
						        				$billingAddress[] = $value->transaction->payer->billingAddress->state;
						        			if ( isset($value->transaction->payer, $value->transaction->payer->billingAddress, $value->transaction->payer->billingAddress->country) )
						        				$billingAddress[] = $value->transaction->payer->billingAddress->country;
						        			if ( isset($value->transaction->payer, $value->transaction->payer->billingAddress, $value->transaction->payer->billingAddress->postalCode) )
						        				$billingAddress[] = $value->transaction->payer->billingAddress->postalCode;
						        			if ( !empty($billingAddress) )
						        				$txt .= '<tr><td style="font-weight: 600;">Address</td><td>'.implode(",", $billingAddress).'</td></tr>';
						        			if ( isset($value->transaction->additionalValues, $value->transaction->additionalValues->entry) && !empty($value->transaction->additionalValues->entry) ) {
												foreach ( $value->transaction->additionalValues->entry as $value_entry ) {
													if ( isset($value_entry->string, $value_entry->additionalValue, $value_entry->additionalValue->value, $value_entry->additionalValue->currency) ) {
														$string = str_replace("_", " ", $value_entry->string);
														$txt .= '<tr><td style="font-weight: 600;">'.$string.'</td><td>'.$value_entry->additionalValue->value.' '.$value_entry->additionalValue->currency.'</td></tr>';
													}
												}
						        			}
					        			}
									}
									$txt .= '</table>';
						        	$disabled = 'disabled="disabled"';
						        	if ( $dcod != "" ) $disabled = "";
									$txt = $txt . '<button id="replicate-payu-transaction-btn" class="button" style="width: 100%;margin-top: 12px;" '.$dcod.'>'.__("Replicate transaction status", "imma").'</button>';
						      		echo '<div style="background: #F5F5F5; color: #6D7278;padding: 1rem; word-wrap: break-word;">'.$txt.'</div>';
						        	?>
						        	<script>
									    <?php if ( $dcod != "" ) { ?>
										    jQuery('#replicate-payu-transaction-btn').click(function(e) {
												e.preventDefault();
												var widgetContainer = jQuery('#data-payu-box');
												var widgetTextLoading = "<?php echo __('Replicating information...', 'imma'); ?>";
												var widgetBtnCod = parseInt( "<?php echo $dcod; ?>", 10 );
												var widgetDmethod = "<?php echo $dmethod; ?>";
												var wcOrder = "<?php echo $wc_order->get_id(); ?>";
												var widgetTextError = "<?php echo __('Sorry, there was an error trying to replicate the information. Please try again in a few minutes or contact technical support for assistance.', 'imma'); ?>";
												widgetContainer.block({
													message: null,
													overlayCSS: {
														background: '#fff',
														opacity: 0.6
													}
												});
										        var data = {
										            "action" : "imma_replicate_payu_transaction",
										            "dcod" : widgetBtnCod,
										            "dmethod" : widgetDmethod,
										            "dpostid" : wcOrder
										        };
										        jQuery.post(ajaxurl, data, function(response) {
										        	location.reload();
										        }).error(function(data){
										        	alert(widgetTextError);
										        });
										    });
										<?php } ?>
						        	</script>
						        	<?php
					        	}
					        } else {
				        		$isOkData = json_decode(json_encode($isOkData), true);
								echo '<div style="background-color: #F64E60;border-color: #F64E60;color: #ffffff;padding: 1rem; word-wrap: break-word;">'.sprintf( __("We are sorry, the data for this transaction could not be loaded due to the following reason: %s If the problem persists, contact technical support.", "imma"), '<br><br>' . serialize($isOkData) . '<br><br>' ).'</div>';
					        }
				        } else {
				        	$isOkData = json_decode(json_encode($isOkData), true);
							echo '<div style="background-color: #F64E60;border-color: #F64E60;color: #ffffff;padding: 1rem; word-wrap: break-word;">'.sprintf( __("We are sorry, the data for this transaction could not be loaded due to the following reason: %s If the problem persists, contact technical support.", "imma"), '<br><br>' . serialize($isOkData) . '<br><br>' ).'</div>';
				        }
					}
				} else {
					echo '<div style="background-color: #3699FF;border-color: #3699FF;color: #FFFFFF;padding: 1rem;">'.__("This order does not have a valid reference to be consulted in PayU", "imma").'.</div>';
				}
			} else {
				echo '<div style="background-color: #3699FF;border-color: #3699FF;color: #FFFFFF;padding: 1rem;">'.__("This order does not have an associated PayU payment method", "imma").'.</div>';
			}
		} //End wc_add_data_payu_box_function()
	}
}

return new WMimmaMenuPayU();