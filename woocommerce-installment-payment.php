<?php

/**
 * Plugin Name:       خرید اقساطی ووکامرس
 * Plugin URI:        https://www.techsima.ir
 * Description:       محاسبه قیمت برای خرید اقساطی
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            سجاد ابراهیمی راد
 * Author URI:        https://www.techsima.ir/
 */

function wc_installment_styles() {
    wp_register_style( 'wc-installment-style', plugins_url( 'woocommerce-cheque/wc-installment-style.css' ) );
    wp_enqueue_style( 'wc-installment-style' );
}
add_action( 'wp_enqueue_scripts', 'wc_installment_styles' );


add_action( 'plugins_loaded', function () {

	if ( ! class_exists( 'Persian_Woocommerce_Gateways' ) ) {
		return add_action( 'admin_notices', function () { ?>
			<div class="notice notice-error">
				<p>برای استفاده از درگاه پرداخت به پرداخت ملت ووکامرس باید ووکامرس پارسی 3.3.6 به بالا را نصب نمایید.</p>
			</div>
			<?php
		} );
	}

	require 'inc/wc-payment-mellat-installment.php';
    require 'inc/front-end-calc.php';
    require 'inc/back-end-calc.php';
    
}, 999 );