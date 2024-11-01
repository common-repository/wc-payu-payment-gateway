<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'wc_gw_payu_settings',
	array(
		'enabled' 		  => array(
			'title'       => __( 'Enable/Disable', 'imma' ),
			'label'       => __( 'Enable Gateway PayU', 'imma' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		),
		'title' 		  => array(
			'title'       => __( 'Title', 'imma' ),
			'type'        => 'text',
			'description' => __( 'Corresponds to the title that the user sees during checkout.', 'imma' ),
			'default'     => __( 'Payment Methods via PayU LATAM', 'imma' ),
			'desc_tip'    => true,
		),
		'description'	  => array(
			'title'       => __( 'Description', 'imma' ),
			'type'        => 'textarea',
			'css' 		  => 'width: 400px;',
			'description' => __( 'Corresponds to the description that the user will see during checkout.', 'imma' ),
			'default'     => __( 'Pay through your favorite payment methods via PayU LATAM.', 'imma' ),
			'desc_tip'    => true,
		),
		'logo' 			  => array(
			'title'       => __( 'Logo', 'imma' ),
			'type'        => 'select',
			'class'       => 'wc-enhanced-select',
			'description' => __( 'This controls the icon/logo which the user sees during checkout.', 'imma' ),
			'default'     => WCGW_PAYU_ASSETS_PATH.'images/payulogo.png',
			'desc_tip'    => true,
			'options'     => array(
								WCGW_PAYU_ASSETS_PATH.'images/payulogo.png' => __( 'Default Logo', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogo2.svg' => __( 'Default Logo (SVG)', 'imma' ),								
								WCGW_PAYU_ASSETS_PATH.'images/payulogoAR.png' => __( 'Argentina', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogoBR.png' => __( 'Brazil', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogoCL.png' => __( 'Chile', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogoCO.png' => __( 'Colombia', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogoMX.png' => __( 'Mexico', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogoPA.png' => __( 'Panama', 'imma' ),
								WCGW_PAYU_ASSETS_PATH.'images/payulogoPE.png' => __( 'Peru', 'imma' ),
							),
		),
		'reduce_stock' 		=> array(
			'title'			=> __( 'Enable/Disable', 'imma' ),
			'label'			=> __( 'Enable Reduce Stock', 'imma' ),
			'type'			=> 'checkbox',
			'description'	=> __( 'By default, once the order is created, the stock of the product is reduced. Regardless of whether the payment is completed correctly or not. Disable this option if the stock should only be reduced when the payment gateway verifies that the payment was approved successfully.', 'imma' ),
			'default'		=> 'yes',
		),
		'paymentaction'   => array(
			'title'       => __( 'Payment action', 'imma' ),
			'type'        => 'select',
			'class'       => 'wc-enhanced-select',
			'description' => __( 'This controls the payment action which the user sees during checkout.', 'imma' ),
			'default'     => 'checkout',
			'desc_tip'    => true,
			'options'     => array(
								'checkout' => __( 'Standart checkout', 'imma' )
							),
		),
		'status_completed'   => array(
			'title'       => __( 'Order status for "approved" payments', 'imma' ),
			'type'        => 'select',
			'class'       => 'wc-enhanced-select',
			'description' => __( 'Select the status that will be automatically assigned to orders once payments are approved by the payment gateway.', 'imma' ),
			'default'     => 'wc-completed',
			'desc_tip'    => true,
			'options'     => wc_get_order_statuses(),
		),
		'lang' 			  => array(
			'title'       => __( 'Language', 'imma' ),
			'type'        => 'select',
			'class'       => 'wc-enhanced-select',
			'description' => __( 'This controls the language which the user sees during checkout.', 'imma' ),
			'default'     => 'ES',
			'desc_tip'    => true,
			'options'     => array(
								'es' => __( 'Spanish', 'imma' ),
								'en' => __( 'English', 'imma' ),
								'pt' => __( 'Portuguese', 'imma' )
							),
		),
		'api_key' 		  => array(
			'title'       => __( 'API Key', 'imma' ),
			'type'        => 'password',
			'description' => __( 'Enter your PayU account credentials to receive automatic notifications related to transactions received from the platform. Learn how to access your PayU account credentials.', 'imma' ),
			'default'     => '',
			'desc_tip'    => true,
		),		
		'api_login' 	  => array(
			'title'       => __( 'API Login', 'imma' ),
			'type'        => 'password',
			'description' => __( 'API Login that identifies you in PayU. You can find it in your customer panel in the configuration option.', 'imma' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'account_id' 	  => array(
			'title'       => __( 'Account id', 'imma' ),
			'type'        => 'text',
			'description' => __( 'User account identifier for each country associated with the store. This variable is used to display the methods available for the country.', 'imma' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'merchant_id' 	  => array(
			'title'       => __( 'Merchant id', 'imma' ),
			'type'        => 'text',
			'description' => __( 'Identifier of your store in the PayU system, you can find this number in your account creation email.', 'imma' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'country' 			=> array(
			'title' 		=> __( 'Select the country', 'imma' ),
			'type' 			=> 'select',
			'class' 		=> 'wc-enhanced-select',
			'description'	=> __( 'This controls the country which the user sees during checkout.', 'imma' ),			
			'options' 		=> array(
						    	'AR' => __( 'Argentina', 'imma' ),
						    	'BR' => __( 'Brazil', 'imma' ),
						    	'CL' => __( 'Chile', 'imma' ),
						    	'MX' => __( 'Mexico', 'imma' ),
						    	'PE' => __( 'Peru', 'imma' ),
						    	'PA' => __( 'Panama', 'imma' ),
						    	'CO' => __( 'Colombia', 'imma' )
			),				
			'description' 	=> '',
			'default' 		=> 'CO',
			'desc_tip' 		=> true,
		),	
		'webhook' 		  => array(
			'title'       => __( 'Webhook Endpoints', 'imma' ),
			'type'        => 'title',
			'description' => sprintf( __( 'You must add the following webhook endpoint <strong style="background-color:#ddd;">&nbsp;%s&nbsp;</strong> to your account settings</a> (if there isn\'t one already enabled). This will enable you to receive notifications on the charge statuses.', 'imma' ), $this->get_url() ),
		),
		'sandbox' 		  => array(
			'title'       => __( 'Test mode', 'imma' ),
			'label'       => __( 'Enable Test Mode', 'imma' ),
			'type'        => 'checkbox',
			'description' => __( 'Place the payment gateway in test mode using test keys.', 'imma' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
		'debug' 		  => array(
			'title'       => __( 'Logging', 'imma' ),
			'label'       => __( 'Log debug messages', 'imma' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'imma' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);
