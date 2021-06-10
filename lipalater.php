<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              moturigeorge.com
 * @since             1.0.0
 * @package           Lipalater
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce Lipalater Plugin 
 * Plugin URI:        moturigeorge.com
 * Description:       This woocommerce payment plugin is used to offer credit solutions to people willing to buy goods without enough money.
 * Version:           1.0.0
 * Author:            Moturi M. George
 * Author URI:        moturigeorge.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lipalater
 * Domain Path:       /languages
 */


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
add_filter('woocommerce_payment_gateways', 'add_lipalater_gateway');
function add_lipalater_gateway($methods){
        $methods[] = 'WC_Gateway_Lipalater';
        return $methods;
}

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function lipalater_website_home() {
        $dir = dirname(__FILE__);
        do {
            if( file_exists($dir."/wp-config.php") ) {
                return $dir;
            }
        } while( $dir = realpath("$dir/..") );
        return null;
}

require_once(lipalater_website_home()."/wp-config.php");
require_once(lipalater_website_home()."/wp-includes/wp-db.php");



add_action('plugins_loaded', 'woocommerce_lipalater_init', 0);
function woocommerce_lipalater_init(){
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    if (!defined('ABSPATH')) {
        exit;
    } // Exit if accessed directly





class WC_Gateway_Lipalater extends WC_Payment_Gateway{
      /**
 		 * Class constructor, more about it in Step 3
 		 */
public function __construct() {
    global $woocommerce;
    $this->version= '1.00';
	$this->id = 'lipalater'; // payment gateway plugin ID
	$this->icon= apply_filters('woocommerce_lipalater_icon', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/lipalater.png');
	$this->has_fields = true; // in case you need a custom credit card form
	$this->method_title = __('Lipalater Gateway', 'woocommerce');'';
	$this->method_description = 'Description of Lipalater payment gateway'; // will be displayed on the options page
 
	// Method with all the options fields
	$this->init_form_fields();
 
	// Load the settings.
	$this->init_settings();
	$this->enabled = $this->get_option( 'enabled' );
	$this->title = $this->get_option( 'title' );
	$this->description = $this->get_option( 'description' );
	$this->testmode = 'yes' === $this->get_option( 'testmode' );
	$this->api_user = $this->testmode ? $this->get_option( 'test_api_user' ) : $this->get_option( 'api_user' );
	$this->api_password = $this->testmode ? $this->get_option( 'test_api_password' ) : $this->get_option( 'api_password' );
	
	//$this->callback_url= WC()->api_request_url( 'WC_Gateway_Lipalater' );
	$this->callback_url= str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Lipalater', home_url( '/' ) ) );
		
    // Actions
	add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

	// Payment listener/API hook
	add_action('init', array($this, 'check_ipn_response'));
    add_action ('woocommerce_api_'.strtolower( get_class( $this ) ), array( $this, 'check_ipn_response' ) );

	// This action hook saves the settings
	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
 
	// We need custom JavaScript to obtain a token
	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	
 }
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
function init_form_fields(){
	$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable Lipalater Gateway',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls the title which the user sees during checkout.',
			'default'     => 'Lipa Later Loan Facility',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'Use this Payment option to get your products with a flexible payment plan.',
			'default'     => 'Lipalater Loan Facility.',
		),
		'testmode' => array(
			'title'       => 'Test mode',
			'label'       => 'Enable Test Mode',
			'type'        => 'checkbox',
			'description' => 'Place the payment gateway in test mode using test API keys.',
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'api_url' => array(
			'title'       => 'API Url',
			'type'        => 'text',
			'default'     => 'http://staging.lipalater.com/api/v1/application',
		),
		'api_authorization' => array(
			'title'       => 'Authorization',
			'type'        => 'text',
			'default'     => 'LIPALATER-HMAC-SHA256',
		),
		'api_key' => array(
			'title'       => 'X-Authorization-ApiKey',
			'type'        => 'text',
			'default'     => '63be4cdf-fd09-48fc-92bd-e1ef506a02eb',
		),
		'api_secret' => array(
			'title'       => 'API SECRET',
			'type'        => 'text',
			'default'     => '643e4bd43d97347491a3d24417973b17',
		),
		'callback_url' => array(
			'title'       => 'Callback URL',
			'type'        => 'url',
			'description' => 'partner_callback_url ('.str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Lipalater', home_url( '/' ) ) ).')',
			'readonly'    => 'readonly',
			'default'     => str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Lipalater', home_url( '/' ) ) ),
		)
	);
}


/*
* Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
*/
public function payment_scripts() {
    //wp_enqueue_style( 'css-lipalater', plugin_dir_url( __FILE__ ).'/css/lipalater.css', false, $this->version,'all');
    //wp_enqueue_script( 'js-lipalater', plugin_dir_url( __FILE__ ).'/js/lipalater.js', array('jquery'), $this->version, true );
    echo '<style>
    .wc-lipalater-form  .form-row {
    line-height: 0.7;
    }
    .wc-lipalater-form label {
    font-size: 15px;
    line-height: 1;
    margin: 5px 0 3px 0;
    font-weight: 900;
    }
    .wc-lipalater-form input[type="text"], .wc-lipalater-form input[type="number"], .wc-lipalater-form input[type="text"]:focus, .wc-lipalater-form input[type="number"]:focus {
    background-color: transparent;
    border: none;
    border-bottom: 1px solid #9e9e9e;
    border-radius: 0;
    outline: none;
    line-height: 1;
    height: 1.25rem;
    width: 100%;
    font-size: 15px;
    margin: 0 0 5px 0;
    padding: 0;
    -webkit-box-shadow: none;
    box-shadow: none;
    -webkit-box-sizing: content-box;
    box-sizing: content-box;
    -webkit-transition: border .3s, -webkit-box-shadow .3s;
    transition: border .3s, -webkit-box-shadow .3s;
    transition: box-shadow .3s, border .3s;
    transition: box-shadow .3s, border .3s, -webkit-box-shadow .3s;
    }
    
   
    </style>';
}

 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
function payment_fields() {
	// ok, let's display some description before the payment form
	if ( $this->description ) {
		// you can instructions for test mode, I mean test card numbers etc.
		if ( $this->testmode ) {
			$this->description .= ' <strong>TEST MODE ENABLED. Please dont check out using this option yet.</strong>';
			$this->description  = trim( $this->description );
		}
		// display the description with <p> tags etc.
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	// I will echo() the form, but you can close PHP tags and print it directly in HTML
	echo '<div id="wc-'.esc_attr( $this->id ).'-form" class="wc-'.esc_attr( $this->id ).'-form" style="background:transparent;">';
 
	// Add this action hook if you want your custom payment gateway to support it
	do_action( 'woocommerce_lipalater_form_start', $this->id );
 
	// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
	echo '<div class="form-row form-row-first"><label>ID Number <span class="required">*</span></label><input name="id_number" id="id_number" type="number" min="1" autocomplete="off"></div>
		  <div class="form-row form-row-last"><label>Net Salary <span class="required">*</span></label><input name="net_salary" id="net_salary" type="number" min="100.00" autocomplete="off"></div>
		  <div class="form-row form-row-first"><label>Average Monthly Expenses <span class="required">*</span></label><input name="avg_monthly_expenses" id="avg_monthly_expenses" type="number" min="100.00" autocomplete="off"></div>
		  <div class="form-row form-row-last"><label>Salary Payment Method <span class="required">*</span></label><input name="salary_payment_method" id="salary_payment_method" type="text" autocomplete="off"></div>
		  <div class="form-row form-row-first"><label>Repayment Period <span class="required">*</span></label><input name="repayment_period" id="repayment_period" type="number" min="1" autocomplete="off"></div>
		  <div class="form-row form-row-last"><label>Your Occupation Type <span class="required">*</span></label><input name="occupation_type" id="occupation_type" type="text" autocomplete="off"></div>';
	do_action( 'woocommerce_lipalater_form_end', $this->id );
	echo '<div class="clear"></div></div>';
}


/** Fields validation, more in Step 5*/
public function validate_fields(){
	if( empty( $_POST['id_number'])) {wc_add_notice('ID number is required!', 'error');return false;}
	if( empty( $_POST['net_salary'])) {wc_add_notice('Net Salary is required!', 'error');return false;}
	if( empty( $_POST['avg_monthly_expenses'])) {wc_add_notice('Monthly expenses is required!', 'error');return false;}
	if( empty( $_POST['salary_payment_method'])) {wc_add_notice('Salary Payment method is required!', 'error');return false;}
	if( empty( $_POST['repayment_period'])) {wc_add_notice('Your repayment period is required!', 'error');return false;}
	if( empty( $_POST['occupation_type'])) {wc_add_notice('Your occupation type required!', 'error');return false;}
	return true;
}

/*** We're processing the payments here*/
public function process_payment( $order_id ) {
global $woocommerce;
 
	// we need it to get any order detailes
$order = new WC_Order($order_id );
	
$order_data = $order->get_data(); // The Order data
	
## Creation and modified WC_DateTime Object date string ##	
$order_id = $order_data['id'];
$order_parent_id = $order_data['parent_id'];
$order_status = $order_data['status'];
$order_currency = $order_data['currency'];
$order_version = $order_data['version'];
$order_payment_method = $order_data['payment_method'];
$order_payment_method_title = $order_data['payment_method_title'];
$order_payment_method = $order_data['payment_method'];
$order_payment_method = $order_data['payment_method'];

// Using a formated date ( with php date() function as method)
$order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');
$order_date_modified = $order_data['date_modified']->date('Y-m-d H:i:s');

// Using a timestamp ( with php getTimestamp() function as method)
$order_timestamp_created = $order_data['date_created']->getTimestamp();
$order_timestamp_modified = $order_data['date_modified']->getTimestamp();

$order_discount_total = $order_data['discount_total'];
$order_discount_tax = $order_data['discount_tax'];
$order_shipping_total = $order_data['shipping_total'];
$order_shipping_tax = $order_data['shipping_tax'];
$order_total = $order_data['cart_tax'];
$order_total_tax = $order_data['total_tax'];
$order_customer_id = $order_data['customer_id'];

// get coupon information (if applicable)
    $cps = array();
    $cps = $order->get_items( 'coupon' );
    
    $coupon = array();
    foreach($cps as $cp){
        // get coupon titles (and additional details if accepted by the API)
        $coupon[] = $cp['name'];
    }
    
    // get product details
    $items = $order->get_items();
    
    $item_name = array();
    $item_qty = array();
    $item_price = array();
    $item_sku = array();
        
    foreach( $items as $key => $item){
        $item_name[] = $item['name'];
        $item_qty[] = $item['qty'];
        $item_price[] = $item['line_total'];        
        $item_id = $item['product_id'];
        $product = new WC_Product($item_id);
        $item_sku[] = $product->get_sku();
    }
    
    // to test out the API, set $api_mode as ‘sandbox’
    if($this->testmode == 'yes'){
        // sandbox URL example
        $api_url = "http://staging.lipalater.com/api/v1/application"; 
    }
    else{
        // production URL example
        $api_url = $this->api_url;
    }

// Do your code checking stuff here e.g. 
    $myPluginGateway = new WC_Gateway_Lipalater();
	$api_authorization = $myPluginGateway->get_option('api_authorization');
	$time = new DateTime;
    $api_timestamp = $time->format(DateTime::ATOM);
	$api_authorization_apikey = $myPluginGateway->get_option('api_key');
	$api_secret = $myPluginGateway->get_option('api_secret');
	
	$id_number=( isset( $_POST['id_number'] ) ) ? $_POST['id_number'] : '';
	$net_salary=( isset( $_POST['net_salary'] ) ) ? $_POST['net_salary'] : '';
	$monthly_expenses=( isset( $_POST['avg_monthly_expenses'] ) ) ? $_POST['avg_monthly_expenses'] : '';
	$salary_payment_method=( isset( $_POST['salary_payment_method'] ) ) ? $_POST['salary_payment_method'] : '';
	$repayment_period=( isset( $_POST['repayment_period'] ) ) ? $_POST['repayment_period'] : '';
	$occupation_type=( isset( $_POST['occupation_type'] ) ) ? $_POST['occupation_type'] : '';
	
	$re = '/^(?:\+?254|0)?/m';
	$subst = '';
	//$subst = '254';
	$phone_numner = preg_replace($re, $subst, $order_data['billing']['phone']);

$request_string ="POST\n/api/v1/application\navg_monthly_expenses=".$monthly_expenses."&email=".$order_data['billing']['email']."&first_name=".$order_data['billing']['first_name']."&id_number=".$id_number."&item_type=&last_name=".$order_data['billing']['last_name']."&loan_value=".$order->get_total()."&net_salary=".$net_salary."&occupation_type=".$occupation_type."&phone_number=".$phone_numner."&repayment_period=".$repayment_period."&salary_payment_method=".$salary_payment_method."\n\nApiKey=".$api_authorization_apikey."\nTimestamp=".$api_timestamp."\n";


// Create an HMAC
$hmac = hash_hmac("sha256", $request_string, $api_secret, true);
// Base 64 encode the HMAC
$base64_signature = base64_encode($hmac);
# Remove whitespace, new lines and trailing equal 
$base64_signature = rtrim($base64_signature);
$base64_signature = rtrim($base64_signature, "=");

 // setup the data which has to be sent
$payload = array(
      'first_name' => $order_data['billing']['first_name'],
      'last_name' => $order_data['billing']['last_name'],
      'id_number' => $id_number,
      'email' => $order_data['billing']['email'],
      'phone_number' => $phone_numner,
      'net_salary' => $net_salary,
      'avg_monthly_expenses' => $monthly_expenses,
      'loan_value' => $order->get_total(),      
      'salary_payment_method' => $salary_payment_method,
      'item_type' => '',
      'repayment_period' => $repayment_period,
      'occupation_type' => $occupation_type
    );
    
//'item_type' => $item_name,

$curl = curl_init($api_url);

  curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($curl,CURLOPT_ENCODING,"");
  curl_setopt($curl,CURLOPT_MAXREDIRS,10);
  curl_setopt($curl,CURLOPT_TIMEOUT,0);
  curl_setopt($curl,CURLOPT_FOLLOWLOCATION,false);
  curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
  curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"POST");
  curl_setopt($curl,CURLOPT_POSTFIELDS,$payload);
  curl_setopt($curl,CURLOPT_HTTPHEADER,array(
    'Authorization: LIPALATER-HMAC-SHA256',
    'X-Authorization-Timestamp: '.$api_timestamp,
    'X-Authorization-ApiKey: '.$api_authorization_apikey,
    'X-Authorization-Signature: '.$base64_signature
  ));
 
$response = curl_exec($curl);
$server_response = json_decode($response);

$err = curl_error($curl);
curl_close($curl);

if($server_response->description=="Success") {
    add_post_meta( $order->id, '_transaction_id', $server_response->application_id, true );
    
	// Mark as on-hold (we're awaiting the cheque)
    $order->update_status('on-hold', __( 'We are awaiting payment confirmation from Lipalater : ', 'woocommerce' ));
    
    // some notes to customer (replace true with false to make it private)
	//$order->add_order_note( 'Hey, your order has been placed! We are awaiting payment confirmation from Lipalater', true );

    // Remove cart
    $woocommerce->cart->empty_cart();
 
	// Redirect to the thank you page
	return array(
		'result' => 'success',
		'redirect' => $this->get_return_url( $order )
	);
}
else if($err){
    wc_add_notice( __('Lipalater Payment error:', 'woothemes') . $error_message, $err );
	return;
}

else{
    wc_add_notice( __('Website Payment error:', 'woothemes') . $error_message, "error" );
    return array(
		'result'=> 'fail',
		'redirect'	=>$this->get_return_url( $order )
	);
}

} 
/*** End of Payments processing function*/





/* Thank you page 
 *Output data for the order received page.
*/
public function thankyou_page( ) {
$order_id = get_query_var('order-received');
?>
<div id="result">
<p>We are waiting for your loan to be processed. Feel free to continue browsing</p>
</div>
<?
}
/*End of Thank You Page*/




/* In case you need a check_ipn_response, like Lipalater Callback response etc*/
public function check_ipn_response() {
global $wpdb;

$data = json_decode(file_get_contents("php://input"), true);

// set Transaction ID of the product to be edited
$id= $data['id'];
$status= $data['status'];
$description= $data['description'];

$approved_amount= $data['meta_data']['approval_data']['approved_amount'];
$applied_amount= $data['meta_data']['approval_data']['applied_amount'];
$balance_remaining=$applied_amount-$approved_amount;

//$installment= $data['meta_data']['approval_data']['installment'];
//$approved_term= $data['meta_data']['approval_data']['approved_term'];
//$maximum_installment= $data['meta_data']['approval_data']['maximum_installment'];
//$interest_rate= $data['meta_data']['approval_data']['interest_rate'];

//$item_description= $data['meta_data']['item_data']['item_description'];
//$buying_price= $data['meta_data']['item_data']['buying_price'];

$post_id = $wpdb->get_row("SELECT $wpdb->posts.ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID=$wpdb->postmeta.post_id 
WHERE $wpdb->posts.post_type ='shop_order' AND $wpdb->postmeta.meta_key='_transaction_id' AND $wpdb->postmeta.meta_value='$id'", ARRAY_A);

$order_id = $post_id['ID'] ?? '0';
global $woocommerce;
$order = new WC_Order( $order_id );
$order_data = $order->get_data(); // The Order data
	
## Creation and modified WC_DateTime Object date string ##	
$order_id = $order_data['id'];

if($description="Request succesfully processed"){
    update_post_meta( $order_id, '_approved_amount', $approved_amount );
    update_post_meta( $order_id, '_applied_amount', $applied_amount );
    update_post_meta( $order_id, '_balance_remaining', $balance_remaining );
    
    if($applied_amount==$approved_amount){
        $order->payment_complete();
        $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
        $order->reduce_order_stock();
    }
    else{
        // we received the payment
    	$order->update_status( 'pending' );
    	$order->add_order_note( 'Your Order is Partially Paid, Complete the rest of the payments', true );
    	$order->reduce_order_stock();
    }
}
else{
    $order->update_status('failed', __('Payment has Failed.', 'wptut'));
    $order->add_order_note( 'Hey, your order is payment failed', true );
}
}
//End of Check for IPN response













     
}


add_action( 'woocommerce_order_status_changed', 'my_change_status_function', 99, 3 );
function my_change_status_function ($order_id) {
if ( ! $order_id ) {
return;
}
global $product;
date_default_timezone_set("Africa/Nairobi");
$order = new WC_Order( $order_id );
$payment_option = $order->get_payment_method();
$url="https://56b3275c-9b2f-401f-a3dc-4682d4077e77.mock.pstmn.io/process/";
if (($order->data['status'] == 'processing') && ($payment_option == 'lipalater')) {
    
    // setup the data which has to be sent
    $payload = array(
          'firstname' => 'George',
          'lastname' => 'moturi',
          'id' => 12345
    );
    
  $curl = curl_init($url);
  curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($curl,CURLOPT_ENCODING,"");
  curl_setopt($curl,CURLOPT_MAXREDIRS,10);
  curl_setopt($curl,CURLOPT_TIMEOUT,0);
  curl_setopt($curl,CURLOPT_FOLLOWLOCATION,false);
  curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
  curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"POST");
  curl_setopt($curl,CURLOPT_POSTFIELDS,$payload);
  curl_setopt($curl,CURLOPT_HTTPHEADER,array(
    'Authorization: LIPALATER-HMAC-SHA256'
  ));
 
$response = curl_exec($curl);
$server_response = json_decode($response);
$error = curl_error($curl);
curl_close($curl);

    if ($error) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
        exit;
    }
    if($server_response->firstname=="george"){
        $file = plugin_dir_path( __FILE__ ).'/newfile.txt';
        $myfile = fopen($file, "a") or die("Unable to open file!");
        $date=date("Y-m-d h:i:sa");
        $txt = $date."\t".$server_response->firstname."\t".$server_response->lastname."\t".$server_response->id."\n";
        fwrite($myfile, $txt);
        fclose($myfile);
    }
    else {
    }

/*
    $file = plugin_dir_path( __FILE__ ).'/newfile.txt';
    $myfile = fopen($file, "w") or die("Unable to open file!");
    $txt = $payment_option."\n";
    fwrite($myfile, $txt);
    $txt = "B 52\n";
    fwrite($myfile, $txt);
    fclose($myfile);
    */
}

}



add_action( 'woocommerce_admin_order_data_after_shipping_address', 'lipalater_editable_order_meta_general' );
function lipalater_editable_order_meta_general( $order_id ){ 
$order = new WC_Order( $order_id );
$order->get_id();
$order_data = $order->get_data(); // The Order data
$order_id = $order_data['id'];

	    /*get all the meta data values we need*/
	    $transaction_id = get_post_meta( $order_id, '_transaction_id', true );
		$approved_amount = get_post_meta( $order_id, '_approved_amount', true );
		$applied_amount = get_post_meta( $order_id, '_applied_amount', true );
		$balance_remaining = get_post_meta( $order_id, '_balance_remaining', true );
	if($transaction_id){
?>
	<br class="clear"/>
	<h4>Lipalater Payment Message</h4>
	<div class="address">
	    <p><strong>Approved Amount :</strong> <?php echo wpautop( $approved_amount ) ?></p>
	    <p><strong>Balance Remaining :</strong> <?php echo wpautop( $applied_amount-$approved_amount ) ?></p>
	</div>
<?php
}
}

// Add a custom column before "actions" last column
add_filter( 'manage_edit-shop_order_columns', 'custom_shop_order_column', 100 );
function custom_shop_order_column( $columns ){
    $ordered_columns = array();
    foreach( $columns as $key => $column ){
        $ordered_columns[$key] = $column;
        if( 'order_date' == $key ){
            $ordered_columns['transaction_id'] = __( 'Lipalater id', 'woocommerce');
        }
    }
    return $ordered_columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'custom_shop_order_list_column_content', 10, 1 );
function custom_shop_order_list_column_content( $column ) {
    global $post, $the_order;
    if ( 'transaction_id' === $column ) {
        echo $the_order->get_transaction_id();
    }
}


/*** Add the meta _transaction_id in the search fields. */
function wc_custom_order_filter_fields( $fields ) {
    if ( ! in_array( '_transaction_id', $fields ) ) {
        array_push( $fields, '_transaction_id' );
    }
    return $fields;
}
add_filter( 'woocommerce_shop_order_search_fields', 'wc_custom_order_filter_fields' );


}
}
else{
    echo  "Woocommerce is Not Active";
}