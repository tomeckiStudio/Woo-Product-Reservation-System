<?php
defined('ABSPATH') or die('You do not have permissions to this file!');

class Woo_Product_Reservation_System_Frontend{

	public function __construct(){
		// Register reservation order status
		add_action('init', array($this, 'wprs_register_reservation_status'), 20);
		
		// Add reservation order status
		add_filter('wc_order_statuses', array($this, 'wprs_wc_order_statuses'), 20, 1);
		
		// Change title of product
		add_filter('mini-cart-product-name', array($this, 'wprs_change_product_title'), 20, 2);
		add_filter('the_title', array($this, 'wprs_change_product_title'), 20, 2);
		add_filter('woocommerce_cart_item_name', array($this, 'wprs_change_product_title_on_cart_or_thankyou'), 20, 3);
		add_filter('woocommerce_order_item_name', array($this, 'wprs_change_product_title_on_cart_or_thankyou'), 20, 3);
		
		// Disable the purchase of a non-reservation product when a reservation product is in the shopping cart
		add_filter('woocommerce_is_purchasable', array($this, 'wprs_woocommerce_is_purchasable'), 20, 2); 
		
		// Show message about disabled possibility to buy a non-reservation product when a reservation product is in the shopping cart
		add_action('woocommerce_single_product_summary', array($this, 'wprs_woocommerce_single_product_summary'), 30); 
		
		// Display deadline information
		add_action('woocommerce_before_add_to_cart_button', array($this, 'wprs_woocommerce_before_add_to_cart_button'));
		
		// Change the button text on single product page
		add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'wprs_woocommerce_product_single_add_to_cart_text'), 10, 2); 
		
		// Add the reservation item to your basket if possible.
		add_filter('woocommerce_add_cart_item_data', array($this, 'wprs_woocommerce_add_cart_item_data'), 20, 3);
		
		// Display information about reservation rules
		add_action('woocommerce_after_cart_table', array($this, 'wprs_woocommerce_after_cart_table'));
		
		// Change the button on cart
		add_action('woocommerce_proceed_to_checkout', array($this, 'wprs_woocommerce_proceed_to_checkout'), 30);
		remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
		
		// Change the button text on checkout
		add_filter('woocommerce_order_button_text', array($this, 'wprs_woocommerce_order_button_text'));
		
		// Display information about reservation rules
		add_action('woocommerce_before_checkout_form', array($this, 'wprs_woocommerce_before_checkout_form'), 2);	
		
		// Change order status
		add_action('woocommerce_thankyou', array($this, 'wprs_woocommerce_thankyou'), 10, 1);
		
		// Display information about reservation rules
		add_action('woocommerce_email_after_order_table', array($this, 'wprs_woocommerce_email_after_order_table'), 10, 4);
		
		// Show reservation badge
		add_action('woocommerce_before_shop_loop_item', array($this, 'wprs_woocommerce_before_shop_loop_item'));
		
		// Load styles
		add_action('wp_footer', array($this, 'wprs_style'));
	}
	
	function wprs_register_reservation_status(){
		register_post_status('wc-reserv', array(
			'label' => __('Reservation', 'woo-product-reservation-system'),
			'public' => false,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop(__('Orders in reservation', 'woo-product-reservation-system') . ' <span class="count">(%s)</span>', __('Orders in reservation', 'woo-product-reservation-system') . ' <span class="count">(%s)</span>')
		));
	}
	
	function wprs_wc_order_statuses($order_statuses){
		$order_statuses['wc-reserv'] = __('Reservation', 'woo-product-reservation-system');

		return $order_statuses;
	}
	
	function wprs_change_product_title($title, $post_id){
		if (get_post_type($post_id) === 'product'){
			if(get_post_meta($post_id, 'reservation_active', true) == "yes"){
				return sprintf("%s: %s", __('Reservation', 'woo-product-reservation-system'), $title);
			}
		}

		return $title;
	}
	
	function wprs_change_product_title_on_cart_or_thankyou($title, $cart_item, $cart_item_key){
		if(is_cart() ||  (is_checkout() && !empty(is_wc_endpoint_url('order-received'))))
			return sprintf("%s: %s", __('Reservation', 'woo-product-reservation-system'), $title);
		
		return $title;
	}
	
	function wprs_woocommerce_is_purchasable($status, $product){ 
		if(get_post_meta($product->get_id(), 'reservation_active', true) != "yes"){
			if(!empty(WC()->cart)){
				foreach(WC()->cart->get_cart() as $cart_item_key => $values){
					$product_tmp = $values['data'];
					if(get_post_meta($product_tmp->get_id(), 'reservation_active', true) == "yes"){
						return false;
					}
				}
			}
		}

		return $status;
	}
	
	function wprs_woocommerce_single_product_summary(){ 
		global $product;

		if(get_post_meta($product->get_id(), 'reservation_active', true) != "yes"){
			if(!empty(WC()->cart)){
				foreach(WC()->cart->get_cart() as $cart_item_key => $values){
					$product_tmp = $values['data'];
					if(get_post_meta($product_tmp->get_id(), 'reservation_active', true) == "yes"){
						echo sprintf("<div>%s <br><a href='%s'>%s</a></div>", __('Regular products cannot be purchased with reservation products', 'woo-product-reservation-system'), wc_get_cart_url(), __('Check out the basket', 'woo-product-reservation-system'));
						break;
					}
				}
			}
		}
	}
	
	function wprs_woocommerce_before_add_to_cart_button(){
		global $product;
		$product_id = $product->get_id();

		if(get_post_meta($product_id, 'reservation_active', true) == "yes"){
			if(get_post_meta($product_id, 'reservation_deadline_date', true) != ""){
				$reserv_date = get_post_meta($product_id, 'reservation_deadline_date', true);
				$reserv_time = "";

				if(get_post_meta($product_id, 'reservation_deadline_time', true) != "")
					$reserv_time = get_post_meta($product_id, 'reservation_deadline_time', true);

				echo sprintf("<div>%s %s %s</div>", __('Please, make a reservation by', 'woo-product-reservation-system'), $reserv_date, $reserv_time);
			}
		}
	}
	
	function wprs_woocommerce_product_single_add_to_cart_text($text, $product){
		if(get_post_meta($product->get_id(), 'reservation_active', true) == "yes")
			return __('Reserve', 'woo-product-reservation-system');

		return $text; 
	}
	
	function wprs_woocommerce_add_cart_item_data($cart_item_data, $product_id, $variation_id){
		if(get_post_meta($product_id, 'reservation_active', true) == "yes"){
			$removedFromCart = false;
			if(!empty(WC()->cart)){
				foreach(WC()->cart->get_cart() as $cart_item_key => $values){
					if(get_post_meta($values['data']->get_id(), 'reservation_active', true) != "yes"){
						WC()->cart->remove_cart_item($cart_item_key);
						$removedFromCart = true;
					}
				}
				if($removedFromCart){	
					wc_add_notice(__('You cannot purchase regular products with reservation products. Regular products have been removed from your basket.', 'woo-product-reservation-system'), 'error');
				}
			}
		}else{
			if(!empty(WC()->cart)){
				foreach(WC()->cart->get_cart() as $cart_item_key => $values){
					if(get_post_meta($values['data']->get_id(), 'reservation_active', true) == "yes"){
						return "";
					}
				}
			}
		}

		return $cart_item_data;
	}

	function wprs_woocommerce_after_cart_table(){
		if(!empty(WC()->cart)){
			foreach(WC()->cart->get_cart() as $cart_item_key => $values){
				$product_id = $values['data']->get_id();

				if(get_post_meta($product_id, 'reservation_active', true) == "yes"){
					$note = __('Dear customer,<br>you have made a reservation of a product which does not oblige you to buy it and you do not make any payment for it.<br>The reservation system does not give a 100% guarantee that you will be able to purchase the item.<br><br>We will inform you via e-mail about the status of your reservation.', 'woo-product-reservation-system');
					$warning = __('Please do not pay your order until the reservation is confirmed.', 'woo-product-reservation-system');
					$date = "";

					if(get_post_meta($product_id, 'reservation_reserv_date', true) != "")
						$date = sprintf(__('Reservation confirmation day: %s', 'woo-product-reservation-system'), get_post_meta($product_id, 'reservation_reserv_date', true));

					echo sprintf("<div class=''>%s<br><br>%s<br><font color='#bf002d'>%s</font></div>", $note, $warning, $date);
					break;
				}
			}
		}
	}
	
	function wprs_woocommerce_proceed_to_checkout(){
		$button_text = __('Proceed to checkout', 'woocommerce');
		if(!empty(WC()->cart)){
			foreach(WC()->cart->get_cart() as $cart_item_key => $values){
				if(get_post_meta($values['data']->get_id(), 'reservation_active', true) == "yes"){
					$button_text = __('Reservation summary', 'woo-product-reservation-system');
					break;
				}
			}
		}

		echo sprintf('<a href="%s" class="checkout-button button alt wc-forward">%s</a>', esc_url(wc_get_checkout_url()), $button_text);
	}
	
	function wprs_woocommerce_order_button_text($order_button_text){
		if(!empty(WC()->cart)){
			foreach(WC()->cart->get_cart() as $cart_item_key => $values){
				if(get_post_meta($values['data']->get_id(), 'reservation_active', true) == "yes"){
					return __('Make a reservation', 'woo-product-reservation-system');
				}
			}
		}

		return $order_button_text;
	}
	
	function wprs_woocommerce_before_checkout_form(){
		if(!empty(WC()->cart)){
			foreach(WC()->cart->get_cart() as $cart_item_key => $values){
				$product_id = $values['data']->get_id();

				if(get_post_meta($product_id, 'reservation_active', true) == "yes"){
					$note = __('Dear customer,<br>you have made a reservation of a product which does not oblige you to buy it and you do not make any payment for it.<br>The reservation system does not give a 100% guarantee that you will be able to purchase the item.<br><br>We will inform you via e-mail about the status of your reservation.', 'woo-product-reservation-system');
					$warning = __('Please do not pay your order until the reservation is confirmed.', 'woo-product-reservation-system');
					$date = "";

					if(get_post_meta($product_id, 'reservation_reserv_date', true) != "")
						$date = sprintf(__('Reservation confirmation day: %s', 'woo-product-reservation-system'), get_post_meta($product_id, 'reservation_reserv_date', true));

					echo sprintf("<div class=''>%s<br><br>%s<br><font color='#bf002d'>%s</font></div>", $note, $warning, $date);
					break;
				}
			}
		}
	}
	
	function wprs_woocommerce_thankyou($order_id){  
		if(!$order_id){ return; }    

		$order = wc_get_order($order_id);
		foreach($order->get_items() as $order_item_key => $item){
			if(get_post_meta($item->get_product_id(), 'reservation_active', true) == "yes"){
				$order->update_status('reserv');
				break;
			}
		}
	}
	
	function wprs_woocommerce_email_after_order_table($order, $sent_to_admin, $plain_text, $email){
		if ($email->id == 'new_order'){
			foreach($order->get_items() as $order_item_key => $item){
				$product_id = $item->get_product_id();

				if(get_post_meta($product_id, 'reservation_active', true) == "yes"){
					$note = __('Dear customer,<br>you have made a reservation of a product which does not oblige you to buy it and you do not make any payment for it.<br>The reservation system does not give a 100% guarantee that you will be able to purchase the item.<br><br>We will inform you via e-mail about the status of your reservation.', 'woo-product-reservation-system');
					$warning = __('Please do not pay your order until the reservation is confirmed.', 'woo-product-reservation-system');
					$date = "";

					if(get_post_meta($product_id, 'reservation_reserv_date', true) != "")
						$date = sprintf(__('Reservation confirmation day: %s', 'woo-product-reservation-system'), get_post_meta($product_id, 'reservation_reserv_date', true));

					echo sprintf("<div class=''>%s<br><br>%s<br><font color='#bf002d'>%s</font></div>", $note, $warning, $date);
					break;
				}
			}
		}
	}
	
	function wprs_woocommerce_before_shop_loop_item(){
		global $post;

		if(get_post_meta($post->ID, 'reservation_active', true) == "yes"){
			echo sprintf("<div class='reserv-badge'>%s</div>", __('Reservation', 'woo-product-reservation-system'));
		}
	}
	
	public function wprs_style(){
		?>
		<style>
			.reserv-badge{
				position: absolute;
				z-index: 20;
				width: 150%;
				color: #fff;
				font-weight: 600;
				font-size: 20px;
				transform: translateX(-39%) translateY(92%) rotate(-45deg);
				text-align:center;
				background-color:#00ad0e;
			}
			.products .product .woocommerce-loop-product__link{
				overflow: hidden;
			}
		</style>
		<?php
	}

}
$wprs_frontend = new Woo_Product_Reservation_System_Frontend();
