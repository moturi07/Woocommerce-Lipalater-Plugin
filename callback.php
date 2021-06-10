<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once("../../../wp-config.php");
include_once("../../../wp-includes/wp-db.php");


// get id of product to be edited
$data = json_decode(file_get_contents("php://input"), true);

// set ID property of product to be edited
/*{
  "id": "string",
  "status": "string",
  "description": "string",
  "meta_data": {
    "approval_data": {
      "approved_amount": "string",
      "installment": "string",
      "approved_term": "string",
      "maximum_installment": "string",
      "interest_rate": "string",
      "applied_amount": "string"
    },
    "item_data": {
      "item_description": "string",
      "buying_price": "string"
    }
  }
}
*/

$id= $data['id'];
$status= $data['status'];
$description= $data['description'];

$approved_amount= $data['meta_data']['approval_data']['approved_amount'];
$applied_amount= $data['meta_data']['approval_data']['applied_amount'];
$balance_remaining=$applied_amount-$approved_amount;

$post_id = $wpdb->get_row("SELECT $wpdb->posts.ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID=$wpdb->postmeta.post_id 
WHERE $wpdb->posts.post_type ='shop_order' AND $wpdb->postmeta.meta_key='_transaction_id' AND $wpdb->postmeta.meta_value='$id'", ARRAY_A);

$order_id = $post_id['ID'] ?? '0';
global $woocommerce;
$order = new WC_Order( $order_id );

if($description="Request succesfully processed"){
    update_post_meta( $order->id, '_approved_amount', $approved_amount );
    update_post_meta( $order->id, '_applied_amount', $applied_amount );
    update_post_meta( $order->id, '_balance_remaining', $balance_remaining );
    
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
?>