<?php

/**
 * Plugin Name:        Wordpress Per-Product Payment Plugin
 * Plugin URI:         https://github.com/Sandman2-0/Wordpress-Per-Product-Payments
 * Description:        Designate payment methods to specific Woocommerce products or limit the usage of payment methods for certain products.
 * Version:            1.1.0
 * Requires at least:  5.2
 * Author:             Sandman2.0
 * Author URI:         https://github.com/Sandman2-0
 */

 add_action('admin_menu', 'wporg_options_page');

 function wporg_options_page() {
     add_menu_page(
         'Per-Product Payments',        //Page Title
         'Per-Product Payments',        //Menu Title
         'manage_options',              //Making sure the user is admin
         'per-product-payments',        //Menu slug
         'wporg_render_options_page',   //Rendering the options page
         'dashicons-products',          //Menu Icon
         20
     );
 }
 
 function wporg_render_options_page() {
     include plugin_dir_path(__FILE__) . 'admin/view.php'; //Integrating the menu page
 }
 
 //Filter WooCommerce available payment gateways
 add_filter('woocommerce_available_payment_gateways', 'wtwh_unset_gateway_by_config');
 
 function wtwh_unset_gateway_by_config($available_gateways) {
     if (is_admin() || !is_checkout()) return $available_gateways;
 
     $config_file = plugin_dir_path(__FILE__) . 'admin/payment_gateway_blocker_config.json'; //State the config file location
     if (!file_exists($config_file)) { //Check if the config file exists
         return $available_gateways;
     }
 
     //Load configuration from the file
     $config = json_decode(file_get_contents($config_file), true);
 
     foreach (WC()->cart->get_cart_contents() as $key => $values) { //Loop through each product within the cart
         foreach ($config as $entry) {                              //Loop through the entries within the config file
             if ($values['product_id'] == $entry['product_id']) {   //Check if a pulled product ID from cart matches with config
                 unset($available_gateways[$entry['gateway_id']]);  //If satisfied, remove corresponding gateway
             }
         }
     }
 
     return $available_gateways; //Return the modified list of available gateways
 }

 include_once plugin_dir_path(__FILE__) . 'update-checker.php'; //Including the update checker
?>