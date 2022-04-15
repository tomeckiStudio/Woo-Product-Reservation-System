<?php
/*
 * Plugin Name:       Woo Product Reservation System
 * Description:       Product reservation system for WooCommerce. Allow the customer to reserve the product before you bring it into the shop.
 * Version:           1.0.1
 * Author:            tomeckiStudio
 * Author URI:        https://tomecki.studio/
 * Text Domain:       woo-product-reservation-system
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die('You do not have permissions to this file!');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	add_action('init', 'wprs_init');
}else{
	add_action('admin_init', 'wprs_plugin_deactivate');
	add_action('admin_notices', 'wprs_woocommerce_missing_notice');
}

function wprs_init(){
	if(is_admin() && current_user_can('administrator')){
		include_once 'includes/wprs-backend.php';
	}
	include_once 'includes/wprs-frontend.php';
}

function wprs_woocommerce_missing_notice(){
	echo sprintf('<div class="error"><p>%s</p></div>', __( 'You need an active WooCommerce for the Woo Product Reservation System plugin to work!', 'woo-product-reservation-system'));
	if (isset($_GET['activate']))
		unset($_GET['activate']);	
}

function wprs_plugin_deactivate(){
	deactivate_plugins(plugin_basename(__FILE__));
}
