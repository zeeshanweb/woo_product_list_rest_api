<?php
/*
Plugin Name: Woo API
Plugin URI: http://test.com/
Description: Woo API plugin.
Version: 1.0.0
*/
if ( ! defined( 'ABSPATH' ) )
{
	die();
}
define( 'WOOAPI_PATH', plugin_basename( __FILE__ ) );
define( 'WOOAPI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOAPI_URL_PATH', plugins_url( '', __FILE__ ) );
$woo_api_key = get_option('woo_api_key');
define( 'WOO_KEY', $woo_api_key['consumer_key'] );
define( 'WOO_SECRET_KEY', $woo_api_key['consumer_secret'] );

include_once( WOOAPI_PLUGIN_PATH.'admin/admin_functions.php' );
include_once( WOOAPI_PLUGIN_PATH.'admin/woo_listing.php' );
include_once( WOOAPI_PLUGIN_PATH.'admin/wp_product_list_class.php' );
include_once( WOOAPI_PLUGIN_PATH.'admin/wp_order_list_class.php' );