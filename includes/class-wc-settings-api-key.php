<?php
if (!defined('ABSPATH')) {
    exit;
}

include_once plugin_dir_path(__FILE__) . 'functions.php';

/**
 * Add a submenu to WooCommerce settings for the API key
 */
if (!class_exists('WC_Settings_API_Key')) {

    class WC_Settings_API_Key extends WC_Settings_Page
    {
        /**
         * Constructor.
         */
        public function __construct()
        {
            $this->id = 'api_key';
            $this->label = __('API Key', 'woocommerce');
            parent::__construct();
        }

        /**
         * Get settings array
         *
         * @return array
         */
        public function get_settings()
        {

            $get_details = wp_remote_get(API_URL .  '/detail/' . get_bloginfo('name'), array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-api-token' => '42c436a4484c67b2c9e21cb3',
                ),
            ));

            $api_data = wp_remote_retrieve_body($get_details);

            if (empty($api_data)) {
                return array();
            }

            $api_data = json_decode($api_data, true);

            if (is_wp_error($get_details)) {
                $error_message = $get_details->get_error_message();
                error_log("API request failed: $error_message");
                return array();
            }

            $settings = array();

            $fields = array(
                array(
                    'type' => 'text',
                    'id' => 'id',
                    'default' => '',
                    'placeholder' => __('Enter your API key here.', 'woocommerce'),
                    'css' => 'display: none;',
                ),
                array(
                    'title' => __('API Key', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'apiKey',
                    'default' => '',
                    'placeholder' => __('Enter your API key here.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('Name', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'name',
                    'default' => '',
                    'placeholder' => __('Enter your name.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('Company', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'company',
                    'default' => '',
                    'placeholder' => __('Enter your company name.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('Phone', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'phone',
                    'default' => '',
                    'placeholder' => __('Enter your phone number.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('Street', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'street',
                    'default' => '',
                    'placeholder' => __('Enter your street address.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('Street 2', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'street2',
                    'default' => '',
                    'placeholder' => __('Enter your second street address (optional).', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('ZIP', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'zip',
                    'default' => '',
                    'placeholder' => __('Enter your ZIP code.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('City', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'city',
                    'default' => '',
                    'placeholder' => __('Enter your city.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
                array(
                    'title' => __('State', 'woocommerce'),
                    'type' => 'text',
                    'id' => 'state',
                    'default' => '',
                    'placeholder' => __('Enter your state.', 'woocommerce'),
                    'css' => 'display: block; width: 50%; margin-bottom: 20px',
                ),
            );


            foreach ($fields as $field) {
                $field_id = $field['id'];
                $field['default'] = isset($api_data['data'][$field_id]) ? $api_data['data'][$field_id] : '';
                $settings[] = $field;
                // Add hidden ID field if ID is defined
                if (isset($api_data['data']['id'])) {
                    $settings[] = array(
                        'type' => 'hidden',
                        'id' => 'id',
                        'default' => $api_data['data'][$field_id],
                    );
                }
            }

            return apply_filters('woocommerce_api_key_settings', $settings);
        }

        /**
         * Save settings
         */
        public function save()
        {
            $settings = $this->get_settings();

            // Loop through each setting and retrieve its value
            $data = array();
            foreach ($settings as $setting) {
                $setting_id = $setting['id'];
                if (isset($_POST[$setting_id])) {
                    $data[$setting_id] = sanitize_text_field($_POST[$setting_id]);
                }
            }

            // Add the current store name to the data array
            $shop = get_bloginfo('name');

            // Send data through API
            $response = wp_remote_post(API_URL . '/detail/' . $shop, array(
                'body' => json_encode(array_merge($data, array('source' => 'wordpress'))),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-api-token' => '42c436a4484c67b2c9e21cb3',
                ),
            ));

            // Check if the request was successful
            if (is_wp_error($response)) {
                // Handle error
                $error_message = $response->get_error_message();
                error_log("API request failed: $error_message");
            } else {
                // Log successful request
                error_log("API request successful");

                // Save new settings
                foreach ($data as $key => $value) {
                    update_option($key, $value);
                }
            }
        }
    }

    return new WC_Settings_API_Key();
}
