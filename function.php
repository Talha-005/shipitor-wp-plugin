<?php
/*
Plugin Name: Shipitor
Description: Sends a webhook request when an order is completed in WooCommerce.
Version: 1.0
Author: Ali Raza
Author Email: aliraxayasin@gmail.com
Author URI: https://alirazaofficial.com
*/


/**
 * Add a submenu to WooCommerce settings
 */
add_filter('woocommerce_get_settings_pages', 'add_api_key_settings_sub_menu');

function add_api_key_settings_sub_menu($settings)
{
    $settings[] = include(dirname(__FILE__) . '/includes/class-wc-settings-api-key.php');
    return $settings;
}

define('API_URL', 'https://api.shipitorlabels.com');


include_once(plugin_dir_path(__FILE__) . '/includes/api-orders-page.php');

/**
 * Add a submenu to WooCommerce admin menu
 */
add_action('admin_menu', 'add_api_orders_submenu');

function add_api_orders_submenu()
{
    add_submenu_page(
        'woocommerce',
        __('Shipitor', 'woocommerce'),
        __('Shipitor', 'woocommerce'),
        'manage_options',
        'shipitor-orders',
        'display_api_orders_page'
    );
}

/**
 * Add a custom weight unit for the plugin.
 */

add_filter('woocommerce_billing_fields', 'ts_unrequire_wc_phone_field');
function ts_unrequire_wc_phone_field($fields)
{
    $fields['billing_phone']['required'] = true;
    return $fields;
}

/**
 * Add a custom weight unit for the plugin.
 * If the custom plugin is active, set the weight unit to lbs.
 */

add_filter('woocommerce_get_weight_unit', 'custom_plugin_set_weight_unit');

function custom_plugin_set_weight_unit($unit)
{
    if (is_plugin_active('custom-orders/function.php')) {
        return 'lbs';
    }

    return $unit;
}



/**
 * Hook into WooCommerce order status changes
 * and send a webhook request when the order is completed.
 */

add_action('woocommerce_order_status_processing', 'send_webhook_on_order_processing');

function send_webhook_on_order_processing($order_id)
{
    // $webhook_url = 'https://sqqz452b-5000.euw.devtunnels.ms/wordpress/webhook';
    $webhook_url = API_URL . '/wordpress/webhook';
    // Get the order object
    $order = wc_get_order($order_id);

    // Check if the order is valid
    if (!$order) {
        return;
    }

    // Get relevant order data
    $order_data = $order->get_data();
    $name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();

    // Define your webhook URL

    $shipping_info = array(
        'shipping_total' => $order->get_shipping_total(), // Shipping cost
        'shipping_method' => $order->get_shipping_method(), // Shipping method
        'shipping_address' => array(
            'name' => $name,
            'company' => $order->get_shipping_company(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
            'phone' => $order->get_billing_phone(),
        )
    );

    // Get products' weight
    $product_weight = 0;
    // get the cart items
    $items = $order->get_items();
    foreach ($items as $item) {

        $product = $item->get_product();
        $product_weight += $product->get_weight() * $item->get_quantity();
    }

    // Create webhook payload
    $payload = array(
        'order_id' => $order_id,
        'customer_email' => $order->get_billing_email(),
        'total' => $order->get_total(),
        'currency' => $order->get_currency(),
        'status' => $order->get_status(),
        'shipping_method' => $order->get_shipping_method(),
        'store_name' => get_bloginfo('name'),
        'total_weight' => $product_weight, // Total weight of products in the order
        'shipping' => $shipping_info,
    );

    // Send POST request to webhook URL
    $response = wp_remote_post($webhook_url, array(
        'body' => json_encode($payload),
        'headers' => array(
            'Content-Type' => 'application/json',
            'x-api-token' => '42c436a4484c67b2c9e21cb3',
        ),
    ));


    // Check if the request was successful
    if (is_wp_error($response)) {
        // Handle error
        $error_message = $response->get_error_message();
        error_log("Webhook request failed: $error_message");
    } else {
        // Log successful request
        error_log("Webhook request successful: Order #$order_id");
    }
}
