<?php
// Display API Orders page
include_once plugin_dir_path(__FILE__) . 'functions.php';


/**
 * Display the API Orders page
 */
function display_api_orders_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html__('Shipitor', 'woocommerce'); ?></h1>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Order ID', 'woocommerce'); ?></th>
                    <th><?php echo esc_html__('Customer', 'woocommerce'); ?></th>
                    <th><?php echo esc_html__('Fulfillment status', 'woocommerce'); ?></th>
                    <th><?php echo esc_html__('Tracking', 'woocommerce'); ?></th>
                    <th><?php echo esc_html__('Delivery method', 'woocommerce'); ?></th>
                    <th><?php echo esc_html__('Label', 'woocommerce'); ?></th>
                    <!-- Add more table headers as needed -->
                </tr>
            </thead>
            <tbody>
                <?php
                // Call the API to retrieve order data
                // $api_data = wp_remote_get('https://sqqz452b-5000.euw.devtunnels.ms/orders/' . get_bloginfo('name'), array(
                //     'headers' => array(
                //         'Content-Type' => 'application/json',
                //         'x-api-token' => '42c436a4484c67b2c9e21cb3',
                //     ),
                // ));

                $api_data = wp_remote_get(API_URL . '/orders/' . get_bloginfo('name'), array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'x-api-token' => '42c436a4484c67b2c9e21cb3',
                    ),
                ));

                $api_data = wp_remote_retrieve_body($api_data);
                $api_data = json_decode($api_data, true);

                // Check if data is available
                if ($api_data) {
                    foreach ($api_data['data'] as $order) {
                ?>
                        <tr>
                            <td><?php echo esc_html($order['orderId']); ?></td>
                            <td><?php echo esc_html($order['toName']); ?></td>
                            <td><?php echo esc_html($order['fulfillmentStatus']); ?></td>
                            <td><?php echo esc_html($order['labelTracking']); ?></td>
                            <td><?php echo esc_html($order['labelType']); ?></td>
                            <td>
                                <?php if ($order['isDownloaded'] != true) { ?>
                                    <button class="complete_order" data-id="<?php echo $order['id']; ?>" data-order-id="<?php echo $order['orderId']; ?>" data-tracking="<?php echo $order['labelTracking']; ?>" data-label-company="<?php echo $order['labelCompany']; ?>" data-label-id="<?php echo $order['labelOrderId']; ?>">Download</button>
                                <?php } ?>

                            </td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center"><?php echo esc_html__('No orders found.', 'woocommerce'); ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Attach click event listener to download buttons
            const downloadButtons = document.querySelectorAll('.complete_order');
            downloadButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const id = button.getAttribute('data-id');
                    const orderId = button.getAttribute('data-order-id');
                    const tracking = button.getAttribute('data-tracking');
                    const labelCompany = button.getAttribute('data-label-company');
                    const labelOrderId = button.getAttribute('data-label-id');


                    // Send AJAX request
                    sendAjaxRequest(id, orderId, tracking, labelCompany, labelOrderId);
                });
            });

            // Function to send AJAX request
            function sendAjaxRequest(id, orderId, tracking, labelCompany, labelOrderId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // Handle success response
                        const apiURL = xhr.responseText;
                        // Open the PDF in a new tab
                        window.open(apiURL, '_blank');
                        console.log(xhr);
                    } else {
                        // Handle error response
                        console.error('Error:', xhr.statusText);
                    }
                };
                xhr.onerror = function() {
                    // Handle network errors
                    console.error('Request failed');
                };
                // Prepare data to send
                const data = new URLSearchParams();
                data.append('action', 'shipitor_fulfill_order');
                data.append('id', id);
                data.append('order_id', orderId);
                data.append('tracking', tracking);
                data.append('label_company', labelCompany);
                data.append('label_id', labelOrderId);
                // Send request
                xhr.send(data);
            }
        });
    </script>
<?php
}



/**
 * Fulfill order and download label via AJAX
 */
add_action('wp_ajax_shipitor_fulfill_order', 'shipitor_fulfill_order');
function shipitor_fulfill_order()
{
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $tracking = isset($_POST['tracking']) ? sanitize_text_field($_POST['tracking']) : '';
    $label_company = isset($_POST['label_company']) ? sanitize_text_field($_POST['label_company']) : '';
    $label_id = isset($_POST['label_id']) ? ($_POST['label_id']) : 0;

    // chnage the order status to completed
    $order = wc_get_order($order_id);
    $order->update_status('completed');

    // when order status id completed then save the tracking number and label_company in order details so admin can see it not in order meta
    $order->update_meta_data('tracking_number', $tracking);
    $order->update_meta_data('label_company', $label_company);
    $order->save();



    // Call functions to fulfill order and download label
    $apiUrl = download_label($label_company, $label_id, $order_id, $id);

    echo $apiUrl;

    wp_die(); // Always include this at the end
}

/**
 * Fulfill order via API
 */
function fulfill_order($order_id, $id)
{
    try {
        $response = wp_remote_post(API_URL .  '/wordpress/fulfillment', array(
            'method' => 'POST',
            'body' => json_encode(array(
                'shop' => get_bloginfo('name'),
                'rowId' => $id,
                'orderId' => $order_id,
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-token' => '42c436a4484c67b2c9e21cb3',
            ),
        ));
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            throw new Exception("Failed to fulfill order");
        }
    } catch (Exception $error) {
        return $error->getMessage();
    }
}

/**
 * Download label via API
 */
function download_label($label_company, $label_id, $order_id, $id)
{
    try {
        fulfill_order($order_id, $id);

        $apiURL = '';
        if ($label_company === "UPS") {
            $apiURL = 'https://api2.shipitor.com/order/download/ups/' . $label_id;
        } else if ($label_company === "USPS") {
            $apiURL = 'https://api2.shipitor.com/order/download/usps/' . $label_id;
        }

        return $apiURL;
    } catch (Exception $error) {
        return $error->getMessage();
    }
}

?>
