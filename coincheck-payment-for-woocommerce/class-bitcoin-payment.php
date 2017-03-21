<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_TLZS_Bitcoin_Payment_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'coincheck_bitcoin_payment';
        $this->icon =Coincheck_Bitcoin_Payment_URL. '/images/logo/Bitcoin_Logo_Horizontal_Dark.svg';
        $this->order_button_text  = __( 'Coincheckに進んでください', 'Bitcoin_Payment' );
        $this->has_fields = false;
  
        $this->method_title       = __('Bitcoin Payment Gateway','Bitcoin_Payment');
        $this->method_description = __( 'Allows bitcoin payments.', 'Bitcoin_Payment' );

        // Load the form fields.
        $this->init_form_fields();
  
        // Load the settings.
        $this->init_settings();
      
        // Define user set variables
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->instructions = $this->get_option( 'instructions', $this->description );
        $this->enabled = $this->get_option('enabled');
        $this->access = $this->get_option('access_key');
        // Actions
        //add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        //add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
        add_action('woocommerce_thankyou_'.$this->id, array($this, 'thankyou_page'));
        add_filter ( 'woocommerce_payment_gateways', array($this,'woocommerce_add_gateway') );
        // Customer Emails
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);

    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
        $this->form_fields = array (
            'enabled' => array (
                'title'       => __('Enable/Disable','Bitcoin_Payment'),
                'type'        => 'checkbox',
                'label'       => __('Enable/Disable the bitcoin payment','Bitcoin_Payment'),
                'default'     => 'no',
                'section'     => 'default'
            ),
            'title' => array (
                'title'       => __('Payment gateway title','Bitcoin_Payment'),
                'type'        => 'text',
                'default'     =>  __('Bitcoin Payment','Bitcoin_Payment'),
                'desc_tip'    => true,
                'css'         => 'width:400px',
                'section'     => 'default'
            ),
            'description' => array (
                'title'       => __('Payment gateway description','Bitcoin_Payment'),
                'type'        => 'textarea',
                'default'     => __('Bitcoin payment','Bitcoin_Payment'),
                'desc_tip'    => true,
                'css'         => 'width:400px',
                'section'     => 'default'
            ),
            'instructions' => array(
                'title'       => __( 'Instructions', 'Bitcoin_Payment' ),
                'type'        => 'textarea',
                'css'         => 'width:400px',
                'description' => __( 'Instructions that will be added to the thank you page.', 'Bitcoin_Payment' ),
                'default'     => '',
                'section'     => 'default'
            ),
            'access_key' => array(
                'title'       => __( 'access key', 'Bitcoin_Payment' ),
                'type'        => 'text',
                'css'         => 'width:400px',
                'default'     => '',
                'section'     => 'default'
            ),
            'access_secret' => array(
                'title'       => __( 'access secret', 'Bitcoin_Payment' ),
                'type'        => 'text',
                'css'         => 'width:400px',
                'default'     => '',
                'section'     => 'default'
            ),
                
            'recv_secret' => array (
                'title'       => __( 'Callback key for signature','Bitcoin_Payment'),
                'type'        => 'text',
                'default'     => '1',
                'description' => __(  'detail: https://coincheck.com/payment/shop','Bitcoin_Payment'),
                'css'         => 'width:400px;',
                'section'     => 'default'
            )
        );
    }

    function woocommerce_add_gateway( $methods ) {
        $methods[] = 'WC_TLZS_Bitcoin_Payment_Gateway';
        return $methods;
    }
    /* Admin Panel Options.*/
    function admin_options() {
        ?>
        <h3><?php _e('Bitcoin Payment','Bitcoin_Payment'); ?></h3>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table> <?php
    }


   public function is_available() {
        if ($this->enabled === "yes") {
            // Required fields check
            if (!$this->access) {
                return false;
            }
            if ( !$this->get_option('access_secret')) {
                return false;
            }
            if ( !$this->get_option('recv_secret')) {
                return false;
            }
            return true
            
        }
        return false;
    }
    

    
    /**
     * Output for the order received page.
     */
    public function thankyou_page() {
        if ( $this->instructions )
            echo wpautop( wptexturize( $this->instructions ) );
    }

    public function notify(){
       
        $get_data = $_GET;
        if(!isset($get_data['recv_secret'])){
            return;
        }
        
        if($get_data['recv_secret']!=$this->get_option('recv_secret')){
            return;
        }
        $data = $_POST;
        if(!isset($data['order'])
            && !isset($data['mispayment'])){
                return;
        }

        try{
            $order = new WC_Order($get_data['order_id']);
            if(isset($data['mispayment'])){
                //$order = new WC_Order($data['mispayment']['id']);     
                if(!$order){
                    throw new Exception('Unknow Order (id:'.$get_data['order_id'].')');
                }
                $order->update_status('failed', __('coincheck payment failed .', 'coincheck_bitcoin_payment'));
                ob_clean();
                exit;
            }else {
                //$order = new WC_Order($data['order']['id']);
                if(!$order){
                    throw new Exception('Unknow Order (id:'.$get_data['order_id'].')');
                }
                $type = $data['order']['status'];
                switch($type){
                    case 'received':
                        $order->update_status( 'on-hold', __( 'Coincheckの支払いは処理しています.', 'coincheck_bitcoin_payment' ) );
                        $order->add_order_note(__( 'Coincheckの支払いは処理しています.','coincheck_bitcoin_payment'));
                        break;
                    case 'confirmed':
                        $order->payment_complete($get_data['order_id']);
                        $order->add_order_note(__( 'Coincheckの支払いは成功しました.','coincheck_bitcoin_payment'));
                        //WC()->cart->empty_cart();
                        break;
                    case 'invalid_payment':
                        $order->update_status('failed', __('Coincheckの支払いは無効となります。', 'coincheck_bitcoin_payment'));
                }
            }
            
        }catch(Exception $e){
            //looger
            $logger = new WC_Logger();
            $logger->add( 'coincheck_bitcoin_payment', $e->getMessage() );
    
            ob_clean();
            
            exit;
        }
        ob_clean();
        exit;
    }
    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( $this->instructions ) );
        }
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {

        $order = wc_get_order( $order_id );

        if(!$order||!$order->needs_payment()){
            return array (
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
        
        if(!function_exists('curl_init')){
            throw new Exception('please install php-curl',500);
        }
        $this->getButtonObject($order);
        

        return array(
            'result'    => 'success',
            'redirect'  => $this->buttonHtml
        );
    }
    
    /**
     * Get the transaction URL.
     * @param  WC_Order $order
     * @return string
     */
    public function get_transaction_url( $order ) {
        
        $this->view_transaction_url = MDL_COINCHECK_API_BASE. '?cmd=_view-a-trans&id=%s';
    
        return parent::get_transaction_url( $order );
    }
    
     /* 決済用のボタン作成 */
    public function getButtonObject($arrOrder)
    {
        $strUrl = MDL_COINCHECK_API_BASE . 'ec/buttons';
        $intNonce = time();
        $strCallbackUrl = Coincheck_Bitcoin_Payment_URL. "/class-coincheck-callback.php?recv_secret=" . $this->get_option("recv_secret") . "&order_id=" . $arrOrder->id;
        $arrQuery = array("button" => array(
            "name" => (get_bloginfo('name') ." # 注文 #" . $arrOrder->id),
            "email" => $arrOrder->billing_email,
            "currency" => "JPY",
            //"currency" => $arrOrder->order_currency,
            "amount" => $arrOrder->order_total,
            "callback_url" => $strCallbackUrl,
            "success_url" => $arrOrder->get_checkout_order_received_url() ,
            "max_times" => 1
        ));
        
                     
        $strAccessKey = $this->get_option("access_key");
        $strAccessSecret = $this->get_option("access_secret");
        $strMessage = $intNonce . $strUrl . http_build_query($arrQuery);
        # hmacで署名
        $strSignature = hash_hmac("sha256", $strMessage, $strAccessSecret);

        $headers = array(
            'ACCESS-KEY:'.$strAccessKey,
            'ACCESS-NONCE:'.$intNonce,
            'ACCESS-SIGNATURE:'.$strSignature
        );
        $ch = curl_init($strUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arrQuery));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error=curl_error($ch);
        curl_close($ch);
        if($httpStatusCode!=200){
            throw new Exception($this->title."が利用できない。サイトの管理者へご連絡ください。",$httpStatusCode);
        }
        $arrJson = json_decode($response, true);
        $this->buttonHtml = $arrJson["button"]["url"];
    }
}
