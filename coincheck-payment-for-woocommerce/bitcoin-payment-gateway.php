<?php
/*
 * Plugin Name: BitCoin Payment for WooCommerce ( coincheck )
 * Plugin URI: https://coincheck.com/ja/documents/payment/bitcoin/
 * Description: Login and Pay with coincheck for your shop orders.
 * Version: 1.0.0
 * Author: Choxq
 * Author URI: https://coincheck.com/ja/
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('Coincheck_Bitcoin_Payment_FILE')) {
    define('Coincheck_Bitcoin_Payment_FILE', __FILE__);
}

if (!defined('Coincheck_Bitcoin_Payment_DIR')) {
    define('Coincheck_Bitcoin_Payment_DIR', rtrim ( plugin_dir_path ( Coincheck_Bitcoin_Payment_FILE ), '/' ));
}

if (!defined('Coincheck_Bitcoin_Payment_URL')) {
    define('Coincheck_Bitcoin_Payment_URL', rtrim ( plugin_dir_url ( Coincheck_Bitcoin_Payment_FILE ), '/' ));
}

if (!defined('MDL_COINCHECK_API_BASE')) {
    define('MDL_COINCHECK_API_BASE', 'https://coincheck.com/api/');
}

add_filter ( 'plugin_action_links_'.plugin_basename( Coincheck_Bitcoin_Payment_FILE ),'Bitcoin_payment_plugin_action_links',10,1 );
function Bitcoin_payment_plugin_action_links($links) {
    $settings = array('settings' => '<a href="' . admin_url ( 'admin.php?page=wc-settings&tab=checkout&section=coincheck_bitcoin_payment') . '">'.__('Setting','Bitcoin_Payment').'</a>');
    return array_merge ($settings , $links );
}

function tlzs_bitcoin_pay_run(){
    static $bitcoin_Payment_Gateway;
    if(!isset($bitcoin_Payment_Gateway)){
        require_once ( Coincheck_Bitcoin_Payment_DIR.'/class-bitcoin-payment.php');
        $bitcoin_Payment_Gateway=new WC_TLZS_Bitcoin_Payment_Gateway();
    }
    return $bitcoin_Payment_Gateway;
}
        
add_action('plugins_loaded', 'init_bitcoin_gateway', 0);
  
function init_bitcoin_gateway() {
  
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
    tlzs_bitcoin_pay_run()->notify();
}





 
