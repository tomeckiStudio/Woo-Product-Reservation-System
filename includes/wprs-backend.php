<?php
defined('ABSPATH') or die('You do not have permissions to this file!');

class Woo_Product_Reservation_System_Backend{

	public function __construct(){
		// Add reservation order status to select on admin orders page
		add_filter('bulk_actions-edit-shop_order', array($this, 'wprs_bulk_actions_edit_shop_order'));
		
		// Register the reservation tab
		add_filter('woocommerce_product_data_tabs', array($this, 'wprs_product_reservation_tab'), 100);	

		// Add the reservation tab
		add_filter('woocommerce_product_data_panels', array($this,'wprs_reservation_fields'));

		// Save reservation fields
		add_action('woocommerce_process_product_meta', array($this, 'wprs_reservation_save'),20,1);
	}
	
	function wprs_bulk_actions_edit_shop_order($bulk_actions){
		$bulk_actions['mark_reserv'] = __('Change status to reservation', 'woo-product-reservation-system');
		return $bulk_actions;
	}
	
	public function wprs_product_reservation_tab($original_tabs) {
		$new_tab['woo-product-reservation-system'] = array(
			'label' => __('Reservation', 'woo-product-reservation-system'),
			'target' => 'reserv_product_data',
		);

		$tabs = array_slice($original_tabs, 0, 1, true);
		$tabs = array_merge($tabs, $new_tab);
		$tabs = array_merge($tabs, array_slice($original_tabs, 1, null, true));

		return $tabs;
	}

	public function wprs_reservation_fields(){
		global $post;
		echo '<div id="reserv_product_data" class="panel woocommerce_options_panel">';
			woocommerce_wp_checkbox(array(
				'id' => 'reservation_active',
				'label' => __('Reservation product', 'woo-product-reservation-system'),
				'desc_tip'    => 'true',
				'description' => __('Tick if the product is to be a reservation product.', 'woo-product-reservation-system')
			));
			woocommerce_wp_text_input(array(
				'id' => 'reservation_reserv_date',
				'label' => __('Reservation confirmation date', 'woo-product-reservation-system'),
				'desc_tip'    => 'true',
				'description' => __('Enter the date on which the reservation is confirmed.', 'woo-product-reservation-system')
			));
			woocommerce_wp_text_input(array(
				'id' => 'reservation_deadline_date',
				'label' => __('Last day for making reservations', 'woo-product-reservation-system'),
				'desc_tip'    => 'true',
				'description' => __('Until this date, customers can reserve the product.', 'woo-product-reservation-system')
			));
			woocommerce_wp_text_input(array(
				'id' => 'reservation_deadline_time',
				'label' => __('Time of the last day for making reservations', 'woo-product-reservation-system'),
				'desc_tip'    => 'true',
				'description' => __('Until this time, customers can reserve the product.', 'woo-product-reservation-system')
			));
		echo "</div>";
	}

	public function wprs_reservation_save($post_id){			
		$product = wc_get_product($post_id);
			
		$product->update_meta_data('reservation_active', (isset($_POST['reservation_active'])?$_POST['reservation_active']:''));    
		$product->update_meta_data('reservation_reserv_date', (isset($_POST['reservation_reserv_date'])?$_POST['reservation_reserv_date']:''));    
		$product->update_meta_data('reservation_deadline_date', (isset($_POST['reservation_deadline_date'])?$_POST['reservation_deadline_date']:''));    
		$product->update_meta_data('reservation_deadline_time', (isset($_POST['reservation_deadline_time'])?$_POST['reservation_deadline_time']:''));    

		$product->save();
	}
}
$wprs_backend = new Woo_Product_Reservation_System_Backend();
