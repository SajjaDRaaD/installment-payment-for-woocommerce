<?php 

add_action( 'woocommerce_checkout_create_order', 'custom_checkout_field_update_meta', 10, 2 );
function custom_checkout_field_update_meta( $order, $data ){

    $payment_method = $order->get_payment_method();

    if($payment_method == "mellat_inst"){

        $order_total = $order->get_total();
        $darsadpishpardakht = $_POST['pish_pardakht'];
        $pishpardakht = ($order_total * $darsadpishpardakht) / 100;
    
        $tedadghest = $_POST['tedad_ghest'];
        $mandeh = $order_total - $pishpardakht;
        $darsad = (($tedadghest * 3) * $mandeh) / 100;
        $mandeh = $mandeh + $darsad;
        $mablaghe_ghest = $mandeh / $tedadghest;
        $tamam_shode = $mandeh + $pishpardakht;


        if( isset($_POST['pish_pardakht']) && ! empty($_POST['pish_pardakht']) ){
            $order->update_meta_data( 'pish_pardakht_percent', sanitize_text_field($_POST['pish_pardakht']));    
            $order->update_meta_data( 'pish_pardakht', sanitize_text_field($pishpardakht));    
        }
    
        if( isset($_POST['tedad_ghest']) && ! empty($_POST['tedad_ghest']) ){
            $order->update_meta_data( 'tedad_ghest', sanitize_text_field($_POST['tedad_ghest']));
            $order->update_meta_data( 'mablaghe_ghest', sanitize_text_field($mablaghe_ghest));
            $order->update_meta_data( 'tamam_shode', sanitize_text_field($tamam_shode));
            
        }

    }
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'add_installment_data_admin_page', 10, 1 );

function add_installment_data_admin_page($order){

    $pish_pardakht_percent = get_post_meta( $order->get_id(), 'pish_pardakht_percent', true );
    $pish_pardakht = get_post_meta( $order->get_id(), 'pish_pardakht', true );
    $tedad_ghest = get_post_meta( $order->get_id(), 'tedad_ghest', true );
    $mablaghe_ghest = get_post_meta( $order->get_id(), 'mablaghe_ghest', true );
    $tamam_shode = get_post_meta( $order->get_id(), 'tamam_shode', true );

    echo '<p><strong>'.__('درصد پیش پرداخت').':</strong> <br/>' . $pish_pardakht_percent . ' درصد</p>';
    echo '<p><strong>'.__('مبلغ پیش پرداخت').':</strong> <br/>' . $pish_pardakht . ' تومان</p>';
    echo '<p><strong>'.__('تعداد اقساط').':</strong> <br/>' . $tedad_ghest . ' ماه</p>';
    echo '<p><strong>'.__('مبلغ اقساط').':</strong> <br/>' . $mablaghe_ghest . ' تومان</p>';
    echo '<p><strong>'.__('مبلغ تمام شده').':</strong> <br/>' . $tamam_shode . ' تومان</p>';


}

add_action( 'init', 'register_installment_order_status' );
function register_installment_order_status() {
    register_post_status( 'wc-awaiting-identity', array(
        'label'                     => 'در انتظار تایید مدارک',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'در انتظار تایید مدارک (%s)', 'در انتظار تایید مدارک (%s)' )
    ) );

    register_post_status( 'wc-not-approved', array(
        'label'                     => 'عدم تایید مدارک',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'عدم تایید مدارک (%s)', 'عدم تایید مدارک (%s)' )
    ) );

}


add_filter( 'wc_order_statuses', 'add_installment_statuses' );
function add_installment_statuses( $order_statuses ) {
 
    $new_order_statuses = array();
 
    foreach ( $order_statuses as $key => $status ) {
 
        $new_order_statuses[ $key ] = $status;
 
        if ( 'wc-processing' === $key ) {

            $new_order_statuses['wc-awaiting-identity'] = 'در انتظار تایید مدارک';
            $new_order_statuses['wc-not-approved'] = 'عدم تایید مدارک';

        }
    }
 
    return $new_order_statuses;
}

add_action( 'woocommerce_checkout_order_processed', 'update_installment_order_status', 10, 3 );
function update_installment_order_status( $order_id, $posted_data, $order ){
    
    $payment_method = $order->get_payment_method();

    if($payment_method == "mellat_inst"){
        $order->update_status( 'awaiting-identity' );
    }
}

add_filter( 'woocommerce_available_payment_gateways', 'installment_available_payment_gateways' );
function installment_available_payment_gateways( $available_gateways ) {

    if( is_admin() ) {
        return $available_gateways;
    }
    
    $url_arr = explode('/', $_SERVER['REQUEST_URI']);
    if($url_arr[2] == 'order-pay'){
        $order_id = intval($url_arr[3]);
        $order = wc_get_order($order_id);
        $paymentMethod = $order->get_payment_method();
        
        if($paymentMethod == "mellat_inst"){
            unset( $available_gateways['mellat'] );
            unset( $available_gateways['WC_ZPal'] );
            
                $installmentDetail = array(
                'pishPardakht' => get_post_meta( $order_id, 'pish_pardakht', true ),
                'tedadGhest' => get_post_meta( $order_id, 'tedad_ghest', true ),
                'mablagheGhest' => get_post_meta( $order_id, 'mablaghe_ghest', true ),
                'tamamShode' => get_post_meta( $order_id, 'tamam_shode', true )
            );
            
            ?>

    <section class="woocommerce-installment-details" style="margin-bottom:45px;">
        
        <h2 class="woocommerce-order-details__title">جزئیات خرید اقساطی</h2>

        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

            <thead>
                <tr>
                <th style="font-weight:bold;">اطلاعات</th>
                <th style="font-weight:bold;">مجموع</th>
                </tr>
            </thead>

            <tbody>

                <tr>

                <td style="font-weight:bold;"><?php echo "مبلغ پیش پرداخت:" ?></td>

                <td style="font-weight:bold;"><?php echo number_format($installmentDetail['pishPardakht']) . " تومان"; ?></td>

                </tr>

                <tr>

                <td style="font-weight:bold;"><?php echo "تعداد اقساط:" ?></td>

                <td style="font-weight:bold;"><?php echo $installmentDetail['tedadGhest'] . " ماه"; ?></td>

                </tr>
                
                <tr>

                <td style="font-weight:bold;"><?php echo "مبلغ هر قسط:" ?></td>

                <td style="font-weight:bold;"><?php echo number_format($installmentDetail['mablagheGhest']) . " تومان"; ?></td>

                </tr>

                <tr>

                <td style="font-weight:bold;"><?php echo "مبلغ تمام شده فاکتور:" ?></td>

                <td style="font-weight:bold;"><?php echo number_format($installmentDetail['tamamShode']) . " تومان"; ?></td>

                </tr>

            </tbody>

        </table>

    </section>

<?php
            
        }else if($paymentMethod == "mellat"){
            unset( $available_gateways['mellat_inst'] );
        }
        
    return $available_gateways;
    }else {
        return $available_gateways;
    }
}