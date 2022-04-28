<?php

add_action( 'wp_footer','installment_scripts');
function installment_scripts(){
    if(is_checkout() && ! is_wc_endpoint_url()):
    ?>
    <script type="text/javascript">
    $ = jQuery;

    function separate(Number) {
        Number+= '';
        Number= Number.replace(',', '');
        x = Number.split('.');
        y = x[0];
        z= x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(y))
        y= y.replace(rgx, '$1' + ',' + '$2');
        return y+ z;
    }

    $( document ).ready(function() {


        $('form.checkout').on('change', 'input[name="payment_method"]', function(){
            $(document.body).trigger('update_checkout');
        });
        
        $('form.checkout').on('change', 'select[name="pish_pardakht"]', function(){

            var cart_total = $("#cart-total-ghesti").val();
            var pish_pardakht_percent = $('select[name="pish_pardakht"]').val();
            var tedad_ghest = $('select[name="tedad_ghest').val();

            var pish_pardakht = (cart_total * pish_pardakht_percent) / 100;
            var mandeh = cart_total - pish_pardakht;
            var darsad = ((tedad_ghest * 3) * mandeh) / 100;
            mandeh = mandeh + darsad;
            var mablaghe_ghest = mandeh / tedad_ghest;
            var final_price = (mablaghe_ghest * tedad_ghest) + pish_pardakht;

            $("#ghesti-info .pish-pardakht-text").text(`مبلغ پیش پرداخت: ${separate(parseInt(pish_pardakht))} تومان`)
            $("#ghesti-info .ghesti-text").text(`مبلغ هر قسط: ${separate(parseInt(mablaghe_ghest))} تومان`)
            $("#ghesti-info .final-cart-text").text(`مبلغ تمام شده: ${separate(parseInt(final_price))} تومان`)

            $(document.body).trigger('update_checkout');

        });

        $('form.checkout').on('change', 'select[name="tedad_ghest"]', function(){
            var cart_total = $("#cart-total-ghesti").val();
            var pish_pardakht_percent = $('select[name="pish_pardakht"]').val();
            var tedad_ghest = $('select[name="tedad_ghest').val();

            var pish_pardakht = (cart_total * pish_pardakht_percent) / 100;
            var mandeh = cart_total - pish_pardakht;
            var darsad = ((tedad_ghest * 3) * mandeh) / 100;
            mandeh = mandeh + darsad;
            var mablaghe_ghest = mandeh / tedad_ghest;
            var final_price = (mablaghe_ghest * tedad_ghest) + pish_pardakht;

            $("#ghesti-info .pish-pardakht-text").text(`مبلغ پیش پرداخت: ${separate(parseInt(pish_pardakht))} تومان`)
            $("#ghesti-info .ghesti-text").text(`مبلغ هر قسط: ${separate(parseInt(mablaghe_ghest))} تومان`)
            $("#ghesti-info .final-cart-text").text(`مبلغ تمام شده: ${separate(parseInt(final_price))} تومان`)
        });
    });
    </script>
    <?php
    endif;
}

add_action( 'woocommerce_review_order_before_submit', 'add_installment_fields_before_submit', 20 );
function add_installment_fields_before_submit(){
    $domain = 'woocommerce';
    $checkout = WC()->checkout;

    global $woocommerce;
    $cart = $woocommerce->cart;
    $cart_total = $woocommerce->cart->total;

    if('mellat_inst' === WC()->session->get('chosen_payment_method')){

    woocommerce_form_field( 'pish_pardakht', array(
        'type'          => 'select',
        'label'         => __('میزان پیش پرداخت', $domain ),
        'placeholder'   => __('میزان پیش پرداخت"', $domain ),
        'class'         => array('my-field-class form-row-wide'),
        'required'      => true,
        'options'     => array(
            '30' => __('30 درصد از مبلغ کل', 'woocommerce' ),
            '40' => __('40 درصد از مبلغ کل', 'woocommerce'),
            '50' => __('50 درصد از مبلغ کل', 'woocommerce' ),
            '60' => __('60 درصد از مبلغ کل', 'woocommerce' ),
            '70' => __('70 درصد از مبلغ کل', 'woocommerce' ),

            )
    ), $checkout->get_value( 'pish_pardakht' ) );

    woocommerce_form_field( 'tedad_ghest', array(
        'type'          => 'select',
        'label'         => __('تعداد اقساط', $domain ),
        'placeholder'   => __('تعداد اقساط', $domain ),
        'class'         => array('my-field-class form-row-wide'),
        'required'      => true,
        'options'     => array(
            '3' => __('3 قسط', 'woocommerce' ),
            '5' => __('5 قسط', 'woocommerce'),
            '8' => __('8 قسط', 'woocommerce' ),
            '10' => __('10 قسط', 'woocommerce' ),
            '12' => __('12 قسط', 'woocommerce' ),

            )
    ), $checkout->get_value( 'tedad_ghest' ) );


    ?>
    <input id="cart-total-ghesti" type="hidden" value="<?php echo $cart_total ?>"/>
    <div id="ghesti-info">
        <p class="pish-pardakht-text"></p>
        <p class="ghesti-text"></p>
        <p class="final-cart-text"></p>
    </div>
    <div class="installment-alert">					
        <h5>توجه:</h5>
        <ul>
            <li>تنها چک های طرح (صیادی) پذیرفته میشوند و خرید اقساطی با سفته امکانپذیر نمی‌باشد.</li>
            <li>خرید اقساطی برای کاربرانی که سابقه‌ی برگشت چک در حساب بانکی خود دارند، امکانپذیر نمی‌باشد.</li>
            <li>چک ها می بایست فاقد هرگونه قلم خوردگی، پشت نویسی و یا مخدوشی باشند.</li>
            <li>تحویل چک ها به آی‌دیجی، توسط ارسال پستی و یا مراجعه حضوری امکانپذیر می‌باشد.</li>
            <li>جهت کسب اطلاعات بیشتر با پشتیبانی تماس بگیرید: </li>
            <li>12 123 123 -021</li>
        </ul>					
    </div>
    <script type="text/javascript">
    $ = jQuery;
    $( document ).ready(function() {

        var cart_total = $("#cart-total-ghesti").val();
        var pish_pardakht_percent = $('select[name="pish_pardakht"]').val();
        var tedad_ghest = $('select[name="tedad_ghest').val();
        
        var pish_pardakht = (cart_total * pish_pardakht_percent) / 100;

        var mandeh = cart_total - pish_pardakht;

        var darsad = ((tedad_ghest * 3) * mandeh) / 100;
        mandeh = mandeh + darsad;
        var mablaghe_ghest = mandeh / tedad_ghest;

        var final_price = (mablaghe_ghest * tedad_ghest) + pish_pardakht;


        $("#ghesti-info .pish-pardakht-text").text(`مبلغ پیش پرداخت: ${separate(parseInt(pish_pardakht))} تومان`)
            $("#ghesti-info .ghesti-text").text(`مبلغ هر قسط: ${separate(parseInt(mablaghe_ghest))} تومان`)
            $("#ghesti-info .final-cart-text").text(`مبلغ تمام شده: ${separate(parseInt(final_price))} تومان`)

    });
    </script>
    <?php
    }
}

add_action( 'woocommerce_checkout_process', 'installment_checkout_process' );
function installment_checkout_process() {
    if ( isset($_POST['pish_pardakht']) && empty($_POST['pish_pardakht']) ) {
        wc_add_notice( __( 'لطفا مبلغ پیش پرداخت را انتخاب کنید.' ), 'error' );
    }
    if ( isset($_POST['tedad_ghest']) && empty($_POST['tedad_ghest']) ) {
        wc_add_notice( __( 'لطفا تعداد اقساط را انتخاب کنید.' ), 'error' );
    }
}

add_action('woocommerce_cart_calculate_fees','add_installment_fee',10,1);
function add_installment_fee($cart){
    if(is_admin() && ! defined('DOING_AJAX'))
        return;

    if('mellat_inst' === WC()->session->get('chosen_payment_method')){
        
        $extra_cost = 4;
        $cart_total = $cart->cart_contents_total;
        $fee = ($cart_total * $extra_cost) / 100;
        if($fee != 0)
        $cart->add_fee('ارزش افزوده',$fee,true);
    }
}

add_action( 'woocommerce_order_details_after_order_table', 'add_installment_fields_order_detail', 10, 1 );
function add_installment_fields_order_detail($order){
    $orderId = $order->get_id();
    $orderGateWay = $order -> get_payment_method();
    $installmentDetail = array(
        'pishPardakht' => get_post_meta( $orderId, 'pish_pardakht', true ),
        'tedadGhest' => get_post_meta( $orderId, 'tedad_ghest', true ),
        'mablagheGhest' => get_post_meta( $orderId, 'mablaghe_ghest', true ),
        'tamamShode' => get_post_meta( $orderId, 'tamam_shode', true )
    );
    if ($orderGateWay == "mellat_inst") {
?>

    <section class="woocommerce-installment-details" style="margin-top:45px;">
        
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
    }
}