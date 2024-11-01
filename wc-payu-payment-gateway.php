<?php
/**
 * Plugin Name: IMMAGIT PayU Payment Gateway for WooCommerce
 * Description: Receive online payments made with credit cards, bank transfers, cash and more from seven (7) countries through the PayU Latam service in your WooCommerce + WordPress store.
 * Plugin URI: https://immagit.com/producto/woocommerce-payu-latam-gateway/
 * Version: 1.1.3.1
 * Author: IMMAGIT
 * Author URI: https://immagit.com/
 * Requires at least: 5.6
 * Tested up to: 6.4
 * WC requires at least: 3.6.0
 * WC tested up to: 8.2.0
 * Requires PHP: 7.0
 * 
 * Text Domain: imma
 * Domain Path: /i18n/languages/
 * Function slug: wc_gw_payu_ 
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_gw_payu_missing_wc_notice' ) ) {
	function wc_gw_payu_missing_wc_notice() {
		echo '<div class="error"><p><strong>' . sprintf( __( 'WC PayU Gateway requires WooCommerce to be installed and active. You can download %s here.', 'imma' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
	}
}

if ( ! function_exists( 'wc_gw_payu_missing_curl_notice' ) ) {
	function wc_gw_payu_missing_curl_notice() {
		echo '<div class="error"><p><strong>' . __( 'WC PayU Gateway requires cURL extension to be installed and active.', 'imma' ) . '</strong></p></div>';
	}
}

if ( ! function_exists( 'wc_gw_payu_woomelly_ads' ) ) {
	function wc_gw_payu_woomelly_ads() {
		$imma_wm_ads_key = get_site_transient( 'imma_wm_ads_key' );
		if ( $imma_wm_ads_key === false )  {
			set_site_transient( 'imma_wm_ads_key', 'wc_gw_payu', 3600 );
			$imma_wm_ads_key = 'wc_gw_payu';
		}
		if ( $imma_wm_ads_key === 'wc_gw_payu' ) {
			$now = time();
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				$timeads = intval( get_user_meta( $user_id, 'imma_wm_ads', true ) );
				if ( $now >= $timeads ) {
					$out = '<div id="immawmads" class="notice notice-success" style="padding-right: 38px; position: relative;"><p><a href="https://woomelly.com/?utm_source=payuplugin&utm_medium=wpadmin&utm_content=ads1&utm_campaign=woomelly" target="_blank"><img src="'.WCGW_PAYU_ASSETS_PATH.'/images/woomellyads1.gif"></a></p><a id="immaclosewmads" class="notice-dismiss" style="text-decoration: none;"><span class="screen-reader-text">Dismiss this notice.</span></a></div>';
					$out .= '<script>jQuery("#immaclosewmads").click(function(e){
			   				e.preventDefault();
			   				jQuery("#immawmads").remove();
					        var data = {
					            "action" : "imma_close_admin_notices",
					        };
					        jQuery.post(ajaxurl, data, function(response) {
					        }).error(function(data){
					        });
			   			});</script>';
			   		echo $out;			
				}
			}
		}
	}
}

if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
	add_action( 'before_woocommerce_init', function () {\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true ); } );
	add_action('before_woocommerce_init', function () { \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true ); } );
}

if ( ! function_exists( 'wc_gw_payu_init' ) ) {
	add_action( 'plugins_loaded', 'wc_gw_payu_init' );
	function wc_gw_payu_init() {
	    $domain = 'imma';
	    $locale = apply_filters( 'wc_gw_payu__plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/i18n/languages/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', 'wc_gw_payu_missing_wc_notice' );
			return;
		}

		if ( ! extension_loaded( 'curl' ) ) {
			add_action( 'admin_notices', 'wc_gw_payu_missing_curl_notice' );
			return;
		}

		if ( ! class_exists( 'WCGW_PayU' ) ) :
			if ( !defined( 'WCGW_PAYU_VERSION' ) )
				define( 'WCGW_PAYU_VERSION', '1.1.3.1' );
			if ( !defined( 'WCGW_PAYU_MAIN_FILE' ) )
				define( 'WCGW_PAYU_MAIN_FILE', __FILE__ );
			if ( !defined( 'WCGW_PAYU_PLUGIN_URL' ) )
				define( 'WCGW_PAYU_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
			if ( !defined( 'WCGW_PAYU_PLUGIN_PATH' ) )
				define( 'WCGW_PAYU_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			if ( !defined( 'WCGW_PAYU_ASSETS_PATH' ) )
				define( 'WCGW_PAYU_ASSETS_PATH', esc_url( trailingslashit( plugins_url( '/assets', __FILE__ ) ) ) );
			class WCGW_PayU {
				private static $instance;
				
				public static function get_instance() {
					if ( null === self::$instance ) {
						self::$instance = new self();
					}
					return self::$instance;
				}
				
				public function __clone() {}
				
				public function __wakeup() {}
				
				public function __construct() {
					add_action( 'admin_init', array( $this, 'install' ) );
					$this->init();
				}
				
				public function init() {
					if ( is_admin() ) {}
					require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-imma.php';
					require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-payu.php';
					require_once dirname( __FILE__ ) . '/includes/admin/payu-admin.php';					
					//register_deactivation_hook( plugin_basename( __FILE__ ), array( $this, 'plugin_payu_deactivation_hook' ) );
					add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
					add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
					//add_action( 'woocommerce_maintenance_task_event_payu', array( $this, 'wc_gw_payu_maintenance_task_event' ), 10 );
					if ( !class_exists( 'FunctionsGatewayIMMA') ) { require_once dirname( __FILE__ ) . '/includes/class-functions-gateway-imma.php'; }
				}

				public function install() {
					if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
						return;
					}
				}

				public function plugin_action_links( $links ) {
					$plugin_links = array(
						'<a href="admin.php?page=wc-settings&tab=checkout&section=payu">' . __( 'Settings', 'imma' ) . '</a>',
					);
					return array_merge( $plugin_links, $links );
				}

				public function add_gateways( $methods ) {
					$methods[] = 'WC_Gateway_PayU';
					return $methods;
				}
			}
			WCGW_PayU::get_instance();
		endif;

		if ( ! class_exists( 'Woomelly' ) ) {
			add_action( 'admin_notices', 'wc_gw_payu_woomelly_ads' );
			return;
		}		
	}
}