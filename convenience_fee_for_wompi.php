/** ADD CONVENIENCE FEE OF 5% */

// Add the custom setting field in WooCommerce -> Settings -> Payments
function custom_woocommerce_gateway_fee_settings( $settings ) {
    $settings[] = array(
        'title'    => __( 'Porcentaje Adicional para Wompi', 'woocommerce' ),
        'desc'     => __( 'Añade el valor adicional por usar Wompi como medio de pago', 'woocommerce' ),
        'id'       => 'wc_convenience_fee_percentage',
        'default'  => '5',
        'type'     => 'number',
        'desc_tip' => true,
        'autoload' => false,
    );
    return $settings;
}
add_filter( 'woocommerce_payment_gateways_settings', 'custom_woocommerce_gateway_fee_settings' );



add_action( 'woocommerce_cart_calculate_fees', 'wompi_extra_fee_for_gateway' );
  
function wompi_extra_fee_for_gateway() {
   $chosen_gateway = WC()->session->get( 'chosen_payment_method' );
   if ( $chosen_gateway == 'wompi' ) {
	  // Get the cart subtotal (numeric value)
        $subtotal = WC()->cart->get_subtotal() +  WC()->cart->get_shipping_total();
        
        // Get the convenience fee percentage from the WooCommerce settings
        $convenience_fee_percentage = get_option('wc_convenience_fee_percentage', 5); // Default is 5% if not set

        // Calculate the convenience fee
        $convenience_fee = $subtotal * ( $convenience_fee_percentage / 100 );

	   
      WC()->cart->add_fee( 'Comisión Wompi', $convenience_fee );
   }
}
 
add_action( 'woocommerce_after_checkout_form', 'refresh_checkout_on_payment_methods_change' );
   
function refresh_checkout_on_payment_methods_change(){
    wc_enqueue_js( "
      $( 'form.checkout' ).on( 'change', 'input[name^=\'payment_method\']', function() {
         $('body').trigger('update_checkout');
        });
   ");
}

/** ADD CONVENIENCE FEE OF 5% */
