/** ADD FIELD TO ABONAR TO ORDER **/
// Add a value to pay at WooCommerce checkout, the remaing will keep as pending payment.
add_action('woocommerce_review_order_before_payment', 'add_custom_abono_field');

function add_custom_abono_field() {
    echo '<div id="custom_abono_field">'; // Make sure this matches the field name
    woocommerce_form_field('custom_abono', array(
        'type' => 'number',
        'class' => array('custom-abono-class form-row-wide'),
        'label' => __('abono'),
        'placeholder' => __('Ingresa el valor a abonar'),
        'required' => false,
        'custom_attributes' => array('step' => '1', 'min' => '0'),
    ));
    echo '</div>';
}


// Enqueue a custom JavaScript file to trigger order review update
add_action('wp_footer', 'custom_abono_field_js');

function custom_abono_field_js() {
    if (is_checkout()) : ?>
        <script type="text/javascript">
            jQuery(function($){
                // Trigger update when payment input is changed
                $('#custom_abono_field input').on('change', function(){
                    $('body').trigger('update_checkout');
                });
            });
        </script>
    <?php
    endif;
}


// Add the field of pending payment 
add_action('woocommerce_cart_calculate_fees', 'apply_custom_pendiente');

function apply_custom_pendiente($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
    } else {
        $post_data = $_POST;
    }

    if (!empty($post_data['custom_abono'])) {
		$total = WC()->cart->get_subtotal() +  WC()->cart->get_shipping_total();
        $pendiente = $total - (float) $post_data['custom_abono']; // Ensure it's a valid number
        if ($pendiente > 0) {
            // Apply the pending value as a negative fee
            $cart->add_fee(__('Pendiente'), -$pendiente);
        }
    }
}


// Save the custom value to pay and pending in the order
add_action('woocommerce_checkout_update_order_meta', 'save_custom_pendiente_field');

function save_custom_pendiente_field($order_id) {
    if (!empty($_POST['custom_abono'])) {
		$total = WC()->cart->get_subtotal() +  WC()->cart->get_shipping_total();
        update_post_meta($order_id, '_custom_pendiente', sanitize_text_field($total-$_POST['custom_abono']));
    }
}


// Display the custom  field value in the WooCommerce admin order page
add_action('woocommerce_admin_order_data_after_billing_address', 'display_custom_pendiente_admin_order_meta', 10, 1);

function display_custom_pendiente_admin_order_meta($order) {
    $custom_pendiente = get_post_meta($order->get_id(), '_custom_pendiente', true);
    if ($custom_pendiente) {
        echo '<p><strong>' . __('Custom pendiente') . ':</strong> ' . wc_price($custom_pendiente) . '</p>';
    }
}

// Add a custom column to the WooCommerce orders list
add_filter('manage_edit-shop_order_columns', 'add_custom_pendiente_column');

function add_custom_pendiente_column($columns) {
    $columns['_custom_pendiente'] = __('Pendiente', 'your-text-domain'); // Change 'your-text-domain' as needed
    return $columns;
}

// Populate the custom column with the custom_abono value
add_action('manage_shop_order_posts_custom_column', 'populate_custom_pendiente_column', 10, 2);

function populate_custom_pendiente_column($column, $post_id) {
    if ($column === '_custom_pendiente') {
        $custom_pendiente = get_post_meta($post_id, '_custom_pendiente', true); // Retrieve the custom field value
        echo !empty($custom_pendiente) ? wc_price($custom_pendiente) : __('0', 'your-text-domain');
    }
}
