<?php
/*
Plugin Name: درگاه پرداخت پاسارگاد ووکامرس
Plugin URI: http://www.web-tor.com
Description: درگاه پرداخت پاسارگاد برای فروشگاه ووکامرس
Version: 1.0
Author: Siavash Ahmadpour
Author URI: http://www.web-tor.com
Copyright: 2013 Webtor
 */
 
add_action('plugins_loaded', 'woocommerce_pasargad_init', 0);

function woocommerce_pasargad_init() {

    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

if($_GET['msg']!=''){
        add_action('the_content', 'showMessage');
    }

    function showMessage($content){
            return '<div class="box '.htmlentities($_GET['type']).'-box">'.base64_decode($_GET['msg']).'</div>'.$content;
    }
    class WC_Pasargad extends WC_Payment_Gateway {
    protected $msg = array();
        public function __construct(){
            // Go wild in here
            $this -> id = 'pasargad';
            $this -> method_title = __('درگاه پاسارگاد', 'pasargad');
            $this -> has_fields = false;
            $this -> init_form_fields();
            $this -> init_settings();
            $this -> title = $this -> settings['title'];
            $this -> description = $this -> settings['description'];
            $this -> merchant_id = $this -> settings['merchant_id'];
            $this -> terminal_id = $this -> settings['terminal_id'];
        	$this -> zegersot_p = $this -> settings['zegersot_p'];
            $this -> redirect_page_id = $this -> settings['redirect_page_id'];
            $this -> msg['message'] = "";
            $this -> msg['class'] = "";
			add_action( 'woocommerce_api_' . strtolower( get_class( &$this ) ), array( &$this, 'check_pasargad_response' ) );
            add_action('valid-pasargad-request', array(&$this, 'successful_request'));
			
			
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
			
            add_action('woocommerce_receipt_pasargad', array(&$this, 'receipt_page'));
        }

        function init_form_fields(){

            $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('فعال سازی/غیر فعال سازی', 'pasargad'),
                    'type' => 'checkbox',
                    'label' => __('فعال سازی درگاه پرداخت پاسارگاد', 'pasargad'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('عنوان:', 'pasargad'),
                    'type'=> 'text',
                    'description' => __('عنوانی که کاربر در هنگام پرداخت مشاهده می کند', 'pasargad'),
                    'default' => __('پرداخت اینترنت تسوط کارت های شتاب', 'pasargad')),
                'description' => array(
                    'title' => __('توضیحات:', 'pasargad'),
                    'type' => 'textarea',
                    'description' => __('توضیحات قابل نمایش به کاربر در هنگام انتخاب درگاه پرداخت', 'pasargad'),
                    'default' => __('پرداخت از طریق درگاه بانک پاسارگاد', 'pasargad')),
                'merchant_id' => array(
                    'title' => __('Merchant ID', 'pasargad'),
                    'type' => 'text',
                    'description' => __('Merchant ID')),
				'terminal_id' => array(
                    'title' => __('Terminal ID', 'pasargad'),
                    'type' => 'text',
                    'description' => __('Terminal ID')),
				'zegersot_p' => array(
                    'title' => __('واحد پولی'),
                    'type' => 'select',
                    'options' => array(
					'rial' => 'ریال',
					'toman' => 'تومان'
					),
                    'description' => "نیازمند افزونه ریال و تومان هست"),
                'redirect_page_id' => array(
                    'title' => __('صفحه بازگشت'),
                    'type' => 'select',
                    'options' => $this -> get_pages('انتخاب برگه'),
                    'description' => "ادرس بازگشت از پرداخت در هنگام پرداخت"
                )
            );


        }

        public function admin_options(){
            echo '<h3>'.__('درگاه بانک پاسارگاد', 'pasargad').'</h3>';
            echo '<p>'.__('درگاه اینترنتی بانک پاسارگاد').'</p>';
            echo '<table class="form-table">';
            $this -> generate_settings_html();
            echo '</table>';

        }

		
        function payment_fields(){
            if($this -> description) echo wpautop(wptexturize($this -> description));
        }

        function receipt_page($order){
            
            echo '<p>'.__('با تشکر از سفارش شما. در حال انتقال به درگاه پرداخت...', 'pasargad').'</p>';
            echo 'این صفحه خود به خود به روز رسانی می شود ، در صورت نیاز عدم انتقال اتوماتیک به بانک دکمه زیر را کلیک کنید';
            echo $this -> generate_pasargad_form($order);
        }

        function process_payment($order_id){
            $order = &new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
            );
        }

       function check_pasargad_response(){
            global $woocommerce;
		$order_id = $woocommerce->session->zegersot;
		$order = &new WC_Order($order_id);
		if($order_id != ''){
		if($order -> status !='completed'){
		include_once 'parser.php';
        $result = post2https($_GET['tref'],'https://epayment.bankpasargad.com/CheckTransactionResult.aspx');
        $array = makeXMLTree($result);
        var_dump($array);
        echo("<br /><br /><h1>");
        echo $array["resultObj"]["result"];
        echo("</h1>");
        $pasargad_status = $array["resultObj"]["result"];
		if($pasargad_status == true AND $woocommerce->session->zegersot_id==$id_get)
		{
            $userID = get_current_user_id();
            global $wpdb;
            $wpdb->insert( 'transactions',
            array(  'result' => $array["resultObj"]["result"], 'action' => $array["resultObj"]["action"], 'transactionReferenceID' => $array["resultObj"]["transactionReferenceID"], 'invoiceNumber' => $array["resultObj"]["invoiceNumber"], 'invoiceDate' => $array["resultObj"]["invoiceDate"],'amount' => $array["resultObj"]["amount"],  'merchantCode' => $array["resultObj"]["merchantCode"], 'terminalCode' => $array["resultObj"]["terminalCode"], 'traceNumber' => $array["resultObj"]["traceNumber"], 'referenceNumber' => $array["resultObj"]["referenceNumber"], 'transactionDate' => $array["resultObj"]["transactionDate"], 'userID' => $userID ), 
            array('%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' ) 
            );
            $order = new WC_Order( $array["resultObj"]["invoiceNumber"]);
            $orderItems = $order -> get_items();
                                    $message = $orderItems[0];
                                    $this -> msg['message'] =  $message;
                                    $this -> msg['class'] = 'success';
                                                               

										$order -> payment_complete();
                                        $order -> add_order_note('پرداخت انجام شد<br/>کد پیگیری: '.$trans_id .' AND '.$id_get );
                                        $order -> add_order_note($this->msg['message']);
                                        $woocommerce -> cart -> empty_cart();
		}else
		{
		$this -> msg['class'] = 'error';
        $this -> msg['message'] = "پرداخت با موفقيت انجام نشد";
		}
			}else{
			$this -> msg['class'] = 'error';
        $this -> msg['message'] = "قبلا اين سفارش به ثبت رسيده يا صفارشي موجود نيست!";
			}
			}
				$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
                $redirect_url = add_query_arg( array('msg'=> base64_encode($this -> msg['message']), 'type'=>$this -> msg['class']), $redirect_url );

                wp_redirect( $redirect_url );
                exit;
            }



        
        function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }


        public function generate_pasargad_form($order_id){
            global $woocommerce;
            $order = new WC_Order($order_id);
            $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
			$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
			unset( $woocommerce->session->zegersot );
			unset( $woocommerce->session->zegersot_id );
			$woocommerce->session->zegersot = $order_id;
				
			$amount = $order -> order_total; 
            
			require_once("RSAProcessor.php"); 
           
			$processor = new RSAProcessor(plugins_url() . "/pasargad-woocommerce/" . "certificate.xml",RSAKeyType::XMLFile);
            
			$merchantCode = $this -> merchant_id; // كد پذيرنده
			$terminalCode = $this -> terminal_id;; // كد ترمينال
			$amount = $order -> order_total; // مبلغ فاكتور
			$redirectAddress = $redirect_url;
           
			$invoiceNumber = $order_id; //شماره فاكتور
			$timeStamp = date("Y/m/d H:i:s");
			$invoiceDate = date("Y/m/d H:i:s"); //تاريخ فاكتور
			$action = "1003"; 	// 1003 : براي درخواست خريد 
			$data = "#". $merchantCode ."#". $terminalCode ."#". $invoiceNumber ."#". $invoiceDate ."#". $amount ."#". $redirectAddress ."#". $action ."#". $timeStamp ."#";
			$data = sha1($data,true);
            
            
            
			$data =  $processor->sign($data); // امضاي ديجيتال 
			$result =  base64_encode($data); // base64_encode 
		
			
			if($this -> zegersot_p=='toman')
			$amount = $amount*10;
			$redirect = urlencode($redirect_url); 
                ?>
			
			<form Id='FinalFactor' name="FinalFactor" method='post' action='https://epayment.bankpasargad.com/gateway.aspx'>
				<input type="hidden" name='invoiceNumber' value='<?php echo $invoiceNumber ?>' />
				<input type="hidden" name='invoiceDate' value='<?php echo $invoiceDate ?>' />
				<input type="hidden" name='amount' value='<?php echo $amount ?>' />
				<input type="hidden" name='terminalCode' value='<?php echo $terminalCode ?>' />
				<input type="hidden" name='merchantCode' value='<?php echo $merchantCode ?>' />
				<input type="hidden" name='redirectAddress' value='<?php echo $redirectAddress ?>' />
				<input type="hidden" name='timeStamp' value='<?php echo $timeStamp ?>' />
				<input type="hidden" name='action' value='<?php echo $action ?>' />
				<input type="hidden" name='sign' value='<?php echo $result ?>' />
				<input type="submit" id="finalformsubmitbtn" name='submit' value='پرداخت' />
			</form>
			
			<?php
			
			/*$go = "http://pasargad.ir/payment/gateway-$result"; 
			header("Location: $go"); */
        }
		
private function send($url,$api,$amount,$redirect){ 
    $ch = curl_init(); 
    curl_setopt($ch,CURLOPT_URL,$url); 
    curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&amount=$amount&redirect=$redirect"); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
    $res = curl_exec($ch); 
    curl_close($ch); 
    return $res; 
}
	private function get($url,$api,$trans_id,$id_get){ 
    $ch = curl_init(); 
    curl_setopt($ch,CURLOPT_URL,$url); 
    curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&id_get=$id_get&trans_id=$trans_id"); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
    $res = curl_exec($ch); 
    curl_close($ch); 
    return $res; 
} 
        function get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while($has_parent) {
                        $prefix .=  ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }

    }


    function woocommerce_add_pasargad_gateway($methods) {
        $methods[] = 'WC_Pasargad';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_pasargad_gateway' );
}

?>
