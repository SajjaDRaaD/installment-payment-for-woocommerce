<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Woocommerce_Ir_Gateway_Mellat_Inst' ) ) {

	Persian_Woocommerce_Gateways::register( 'Mellat_Inst' );

    add_filter('woocommerce_currencies', 'add_IR_currency_Installment');

    function add_IR_currency_Installment($currencies)
    {
        $currencies['IRR'] = __('ریال', 'woocommerce');
        $currencies['IRT'] = __('تومان', 'woocommerce');
        $currencies['IRHR'] = __('هزار ریال', 'woocommerce');
        $currencies['IRHT'] = __('هزار تومان', 'woocommerce');

        return $currencies;
    }

    add_filter('woocommerce_currency_symbol', 'add_IR_currency_symbol_Installment', 10, 2);

    function add_IR_currency_symbol_Installment($currency_symbol, $currency)
    {
        switch ($currency) {
            case 'IRR':
                $currency_symbol = 'ریال';
                break;
            case 'IRT':
                $currency_symbol = 'تومان';
                break;
            case 'IRHR':
                $currency_symbol = 'هزار ریال';
                break;
            case 'IRHT':
                $currency_symbol = 'هزار تومان';
                break;
        }
        return $currency_symbol;
    }

	class Woocommerce_Ir_Gateway_Mellat_Inst extends Persian_Woocommerce_Gateways {

		public function __construct() {

			$this->method_title = 'اقساطی';

			parent::init( $this );
		}

		public function fields() {
			return array(
				'terminal'   => array(
					'title'       => 'ترمینال آیدی',
					'type'        => 'text',
					'description' => 'شماره ترمینال درگاه بهپرداخت ملت',
					'default'     => '',
					'desc_tip'    => true
				),
				'username'   => array(
					'title'       => 'نام کاربری',
					'type'        => 'text',
					'description' => 'نام کاربری درگاه بهپرداخت ملت',
					'default'     => '',
					'desc_tip'    => true
				),
				'password'   => array(
					'title'       => 'کلمه عبور',
					'type'        => 'text',
					'description' => 'کلمه عبور درگاه بهپرداخت ملت',
					'default'     => '',
					'desc_tip'    => true
				),
				'shortcodes' => array(
					'transaction_id' => 'کد رهگیری (کد مرجع تراکنش)',
					'SaleOrderId'    => 'شماره درخواست تراکنش',
				)
			);
		}


		public function process_payment($order_id)
		{

			$order = new WC_Order($order_id);
			$orderStatus = $order->get_status();

			WC()->cart->empty_cart();

			if( $orderStatus == "pending" ){
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			);
			} else {
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}

		}

		public function request( $order ) {

			$this->nusoap();

			$order_id = $order->get_id();
			$currency = $order->get_currency();
			$currency = apply_filters('Mellat_Inst_Currency', $currency, $order_id);
			
			$Terminal       = $this->option( 'terminal' );
			$Username       = $this->option( 'username' );
			$Password       = $this->option( 'password' );
			$PaymentID      = date( 'ymdHis' );
			$pish_pardakht = get_post_meta( $order->get_id(), 'pish_pardakht', true );                         
			$Amount         = (int)$pish_pardakht;

            $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
            $strToLowerCurrency = strtolower($currency);
            if (
                ($strToLowerCurrency === strtolower('IRT')) ||
                ($strToLowerCurrency === strtolower('TOMAN')) ||
                $strToLowerCurrency === strtolower('Iran TOMAN') ||
                $strToLowerCurrency === strtolower('Iranian TOMAN') ||
                $strToLowerCurrency === strtolower('Iran-TOMAN') ||
                $strToLowerCurrency === strtolower('Iranian-TOMAN') ||
                $strToLowerCurrency === strtolower('Iran_TOMAN') ||
                $strToLowerCurrency === strtolower('Iranian_TOMAN') ||
                $strToLowerCurrency === strtolower('تومان') ||
                $strToLowerCurrency === strtolower('تومان ایران'
                )
            ) {
                $Amount *= 10;
            } else if (strtolower($currency) === strtolower('IRHT')) {
                $Amount *= 10000;
            } else if (strtolower($currency) === strtolower('IRHR')) {
                $Amount *= 1000;
            } else if (strtolower($currency) === strtolower('IRR')) {
                $Amount *= 1;
            }


            $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency);
            $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency);
            $Amount = apply_filters('woocommerce_order_amount_total_Mellat_Inst_gateway', $Amount, $currency);


			$CallBackUrl    = $this->get_verify_url();
			$AdditionalData = 'WC OrderID: ' . $this->get_order_props( 'order_number' );

			try {

				$client = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' );

				$Result = $client->call( 'bpPayRequest', array(
					'terminalId'     => $Terminal,
					'userName'       => $Username,
					'userPassword'   => $Password,
					'orderId'        => $PaymentID,
					'amount'         => $Amount,
					'callBackUrl'    => $CallBackUrl,
					'additionalData' => $AdditionalData,
					'localDate'      => date( "Ymd" ),
					'localTime'      => date( "His" ),
					'payerId'        => '0'
				), 'http://interfaces.core.sw.bps.com/' );

				if ( $client->getError() ) {
					return $client->getError();
				}

				if ( ! empty( $client->fault ) ) {
					return is_string( $client->fault ) ? $client->fault : print_r( $client->fault, true );
				}

				$Result = explode( ',', $Result );

				if ( $Result[0] == '0' ) {

					$form = '<form method="post" action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat">
							<input type="hidden" name="RefId" value="' . trim( $Result[1] ) . '"/>
						</form>';

					return $this->submit_form( $form );

				} else {
					return $this->errors( $Result[0] );
				}
			} catch ( SoapFault $e ) {
				return $e->getMessage();
			}
		}

		public function verify( $order ) {

			$this->nusoap();

			$Terminal        = $this->option( 'terminal' );
			$Username        = $this->option( 'username' );
			$Password        = $this->option( 'password' );
			$ResCode         = $this->post( 'ResCode', '0' );
			$SaleOrderId     = $this->post( 'SaleOrderId' );
			$SaleReferenceId = $this->post( 'SaleReferenceId' );

			$this->check_verification( $SaleOrderId . $SaleReferenceId );

			$parameters = array(
				'terminalId'      => $Terminal,
				'userName'        => $Username,
				'userPassword'    => $Password,
				'orderId'         => $SaleOrderId,
				'saleOrderId'     => $SaleOrderId,
				'saleReferenceId' => $SaleReferenceId
			);

			$status = 'failed';
			$error  = $ResCode;

			if ( $ResCode == '0' ) {

				try {

					$client    = new nusoap_client( 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl' );
					$namespace = 'http://interfaces.core.sw.bps.com/';

					$result = $client->call( $method = 'bpVerifyRequest', $parameters, $namespace );
					if ( $result == '0' ) {

						$result = $client->call( $method = 'bpInquiryRequest', $parameters, $namespace );
						if ( $result == '0' ) {

							$result = $client->call( $method = 'bpSettleRequest', $parameters, $namespace );
							if ( in_array( $result, array( '0', '45' ) ) ) {
								$status = 'completed';
							}
						}
					}

					if ( $status != 'completed' ) {
						if ( ! ( $error = $result ) && ! ( $error = $client->getError() ) && ! empty( $client->fault ) ) {
							$error = is_string( $client->fault ) ? $client->fault : print_r( $client->fault, true );
						}
						$client->call( 'bpReversalRequest', $parameters, $namespace );
					}

					$error .= '__' . $method;
				} catch ( SoapFault $e ) {
					$error = $e->getMessage();
				}
			}

			$status         = $error == '17' ? 'cancelled' : $status;
			$error          = $this->errors( $error );
			$transaction_id = $SaleReferenceId;

			$this->set_shortcodes( array( 'transaction_id' => $transaction_id, 'SaleOrderId' => $SaleOrderId ) );

			return compact( 'status', 'transaction_id', 'error' );
		}

		private function errors( $error ) {

			$method = '';
			$error  = explode( '__bp', $error );

			if ( ! empty( $error[1] ) ) {
				$method = $error[1] . ':::';
			}

			$error = reset( $error );

			switch ( $error ) {

				case '-2':
					$message = 'شکست در ارتباط با بانک.';
					break;

				case '-1':
					$message = 'شکست در ارتباط با بانک.';
					break;

				case '11':
					$message = 'شماره کارت معتبر نیست.';
					break;

				case '12':
					$message = 'موجودی کافی نیست.';
					break;

				case '13':
					$message = 'رمز دوم شما صحیح نیست.';
					break;

				case '14':
					$message = 'دفعات مجاز ورود رمز بیش از حد است.';
					break;

				case '15':
					$message = 'کارت معتبر نیست.';
					break;

				case '16':
					$message = 'دفعات برداشت وجه بیش از حد مجاز است.';
					break;

				case '17':
					$message = 'شما از انجام تراکنش منصرف شده اید.';
					break;

				case '18':
					$message = 'تاریخ انقضای کارت گذشته است.';
					break;

				case '19':
					$message = 'مبلغ برداشت وجه بیش از حد مجاز است.';
					break;

				case '111':
					$message = 'صادر کننده کارت نامعتبر است.';
					break;

				case '112':
					$message = 'خطای سوییچ صادر کننده کارت رخ داده است.';
					break;

				case '113':
					$message = 'پاسخی از صادر کننده کارت دریافت نشد.';
					break;

				case '114':
					$message = 'دارنده کارت مجاز به انجام این تراکنش نمی باشد.';
					break;

				case '21':
					$message = 'پذیرنده معتبر نیست.';
					break;

				case '23':
					$message = 'خطای امنیتی رخ داده است.';
					break;

				case '24':
					$message = 'اطلاعات کاربری پذیرنده معتبر نیست.';
					break;

				case '25':
					$message = 'مبلغ نامعتبر است.';
					break;

				case '31':
					$message = 'پاسخ نامعتبر است.';
					break;

				case '32':
					$message = 'فرمت اطلاعات وارد شده صحیح نیست.';
					break;

				case '33':
					$message = 'حساب نامعتبر است.';
					break;

				case '34':
					$message = 'خطای سیستمی رخ داده است.';
					break;

				case '35':
					$message = 'تاریخ نامعتبر است.';
					break;

				case '41':
					$message = 'شماره درخواست تکراری است.';
					break;

				case '42':
					$message = 'همچین تراکنشی وجود ندارد.';
					break;

				case '43':
					$message = 'قبلا درخواست Verify داده شده است';
					break;

				case '44':
					$message = 'درخواست Verify یافت نشد.';
					break;

				case '45':
					$message = 'تراکنش قبلا Settle شده است.';
					break;

				case '46':
					$message = 'تراکنش Settle نشده است.';
					break;

				case '47':
					$message = 'تراکنش Settle یافت نشد.';
					break;

				case '48':
					$message = 'تراکنش قبلا Reverse شده است.';
					break;

				case '49':
					$message = 'تراکنش Refund یافت نشد.';
					break;

				case '412':
					$message = 'شناسه قبض نادرست است.';
					break;

				case '413':
					$message = 'شناسه پرداخت نادرست است.';
					break;

				case '414':
					$message = 'سازمان صادر کننده قبض معتبر نیست.';
					break;

				case '415':
					$message = 'زمان جلسه کاری به پایان رسیده است.';
					break;

				case '416':
					$message = 'خطا در ثبت اطلاعات رخ داده است.';
					break;

				case '417':
					$message = 'شناسه پرداخت کننده نامعتبر است.';
					break;

				case '418':
					$message = 'اشکال در تعریف اطلاعات مشتری رخ داده است.';
					break;

				case '419':
					$message = 'تعداد دفعات ورود اطلاعات بیش از حد مجاز است.';
					break;

				case '421':
					$message = 'IP معتبر نیست.';
					break;

				case '51':
					$message = 'تراکنش تکراری است.';
					break;

				case '54':
					$message = 'تراکنش مرجع موجود نیست.';
					break;

				case '55':
					$message = 'تراکنش نامعتبر است.';
					break;

				case '61':
					$message = 'خطا در واریز رخ داده است.';
					break;

				default:
					$message = ! empty( $error ) ? $error : 'در حین پرداخت خطای سیستمی رخ داده است.';
					break;
			}

			if ( ! is_numeric( $error ) ) {
				$error = '';
			} else {
				$error = ':::' . $error . ':::';
			}

			return $method . $error . $message;
		}
	}
}

