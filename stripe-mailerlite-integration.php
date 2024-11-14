<?php
/*
Plugin Name: Stripe to MailerLite Integration
Description: Automatically subscribes Stripe checkout customers to specified MailerLite mailing lists by allowing you to map individual products to mailing lists.
Version: 1.0
Author: Ryan T. M. Reiffenberger
Author URI: https://www.fallstech.group
*/

if (!defined('ABSPATH')) exit;

// Step 1: Register Settings Page in the Admin Dashboard
function smi_add_settings_page() {
    add_options_page('Stripe to MailerLite', 'Stripe to MailerLite', 'manage_options', 'smi-settings', 'smi_render_settings_page');
}
add_action('admin_menu', 'smi_add_settings_page');

// Step 2: Render the Settings Page
function smi_render_settings_page() {
    $stripe_products = smi_get_stripe_products();
    $mailerlite_groups = smi_get_mailerlite_groups();
    ?>
    <div class="wrap">
        <h1>Stripe to MailerLite Integration Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('smi_settings');
            do_settings_sections('smi-settings');
            submit_button();
            ?>

            <h2>Product to Mailing List Mapping</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Stripe Product</th>
                        <th>MailerLite List</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stripe_products as $product_id => $product_name) : ?>
                        <tr>
                            <td><?php echo esc_html($product_name); ?></td>
                            <td>
                                <select name="smi_product_mapping[<?php echo esc_attr($product_id); ?>]">
                                    <option value="none">None</option>
                                    <?php foreach ($mailerlite_groups as $group_id => $group_name) : ?>
                                        <option value="<?php echo esc_attr($group_id); ?>" <?php selected(get_option('smi_product_mapping')[$product_id] ?? '', $group_id); ?>>
                                            <?php echo esc_html($group_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button('Save Product Mappings'); ?>
        </form>
    </div>
    <?php
}

// Step 3: Register Settings Fields
function smi_register_settings() {
    register_setting('smi_settings', 'smi_stripe_secret_key');
    register_setting('smi_settings', 'smi_mailerlite_api_key');
    register_setting('smi_settings', 'smi_webhook_secret');
    register_setting('smi_settings', 'smi_product_mapping'); // Stores product-to-list mapping

    add_settings_section('smi_settings_section', 'API Credentials', null, 'smi-settings');

    add_settings_field('smi_stripe_secret_key', 'Stripe Secret Key', 'smi_render_stripe_secret_key', 'smi-settings', 'smi_settings_section');
    add_settings_field('smi_mailerlite_api_key', 'MailerLite API Key', 'smi_render_mailerlite_api_key', 'smi-settings', 'smi_settings_section');
    add_settings_field('smi_webhook_secret', 'Stripe Webhook Secret', 'smi_render_webhook_secret', 'smi-settings', 'smi_settings_section');
}
add_action('admin_init', 'smi_register_settings');

function smi_render_stripe_secret_key() {
    $value = get_option('smi_stripe_secret_key');
    echo '<input type="password" name="smi_stripe_secret_key" value="' . esc_attr($value) . '" class="regular-text" />';
}

function smi_render_mailerlite_api_key() {
    $value = get_option('smi_mailerlite_api_key');
    echo '<input type="password" name="smi_mailerlite_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
}

function smi_render_webhook_secret() {
    $value = get_option('smi_webhook_secret');
    echo '<input type="password" name="smi_webhook_secret" value="' . esc_attr($value) . '" class="regular-text" />';
}

// Helper: Get Stripe Products
function smi_get_stripe_products() {
    $stripe_secret_key = get_option('smi_stripe_secret_key');
    if (!$stripe_secret_key) return [];

    // Prepare the request URL and headers
    $url = "https://api.stripe.com/v1/products";
    $headers = [
        'Authorization' => 'Bearer ' . $stripe_secret_key
    ];

    // Log the request to Stripe
    $log_file = plugin_dir_path(__FILE__) . 'stripe_request_log.txt'; // Path to the log file
    file_put_contents($log_file, "Stripe Request URL: " . $url . "\n" . "Headers: " . print_r($headers, true) . "\n", FILE_APPEND);

    $response = wp_remote_get($url, ['headers' => $headers]);

    // Log the response from Stripe
    file_put_contents($log_file, "Stripe Response: " . print_r(wp_remote_retrieve_body($response), true) . "\n", FILE_APPEND);

    $products = [];
    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($body['data'])) {
            foreach ($body['data'] as $product) {
                $products[$product['id']] = $product['name'];
            }
        }
    }

    return $products;
}

// Helper: Get MailerLite Groups
function smi_get_mailerlite_groups() {
    $mailerlite_api_key = get_option('smi_mailerlite_api_key');
    if (!$mailerlite_api_key) {
        echo '<div class="notice notice-error"><p>MailerLite API key is missing. Please enter it in the settings.</p></div>';
        return [];
    }

    // Prepare the request URL and headers
    $url = "https://connect.mailerlite.com/api/groups";
    $headers = [
        'Authorization' => 'Bearer ' . $mailerlite_api_key,
        'Content-Type' => 'application/json',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ];

    // Log the request to MailerLite
    $log_file = plugin_dir_path(__FILE__) . 'mailerlite_request_log.txt'; // Path to the log file
    file_put_contents($log_file, "MailerLite Request URL: " . $url . "\n" . "Headers: " . print_r($headers, true) . "\n", FILE_APPEND);

    $response = wp_remote_get($url, ['headers' => $headers]);

    // Log the response from MailerLite
    file_put_contents($log_file, "MailerLite Response: " . print_r(wp_remote_retrieve_body($response), true) . "\n", FILE_APPEND);

    $groups = [];

    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>Error retrieving MailerLite groups: ' . esc_html($response->get_error_message()) . '</p></div>';
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Print full response for debugging
        $response_code = wp_remote_retrieve_response_code($response);
        file_put_contents($log_file, "MailerLite API Response Code: " . $response_code . "\n", FILE_APPEND);

        if (isset($body['data']) && !empty($body['data'])) {
            foreach ($body['data'] as $group) {
                $groups[$group['id']] = $group['name'];
            }
        } else {
            echo '<div class="notice notice-warning"><p>No groups data found in the MailerLite response.</p></div>';
        }
    }

    return $groups;
}

// Webhook Endpoint to Handle Stripe Events
function smi_handle_stripe_webhook() {
    // Get the raw body from php://input
    $input = file_get_contents("php://input");

    // Log the raw request data
    $log_file = plugin_dir_path(__FILE__) . 'stripe_webhook_request_log.txt';
    file_put_contents($log_file, "Webhook Request: " . $input . "\n", FILE_APPEND);

    // Decode the event
    $event = json_decode($input);

    // Log the event object
    file_put_contents($log_file, "Decoded Webhook Event: " . print_r($event, true) . "\n", FILE_APPEND);

    // Get the webhook secret from the options
    $webhook_secret = get_option('smi_webhook_secret');
    if (!$webhook_secret) {
        file_put_contents($log_file, "Webhook secret not found\n", FILE_APPEND); // Log if secret is missing
        status_header(400);
        exit;
    }

    // Get the Stripe signature from the request header
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    if (!$sig_header) {
        file_put_contents($log_file, "Missing Stripe signature header\n", FILE_APPEND); // Log if header is missing
        status_header(400);
        exit;
    }

    // Verify the signature using the raw input and the provided signature
    if (!smi_verify_stripe_signature($input, $sig_header, $webhook_secret)) {
        status_header(400);
        file_put_contents($log_file, "Webhook Signature Verification Failed\n", FILE_APPEND); // Log failure
        exit;
    }

    // Process the event if it's a checkout session completed event
    if ($event->type == 'checkout.session.completed') {
        $session = $event->data->object;
        $session_id = $session->id;
        $customer_email = $session->customer_details->email;

        file_put_contents($log_file, "Processing checkout.session.completed event for session ID: {$session_id}\n", FILE_APPEND); // Log event type

        // Fetch line items for the session from Stripe
        $line_items = smi_get_stripe_line_items($session_id);

        foreach ($line_items as $item) {
            // Access the product ID from each line item
            $product_id = $item['price']['product'];
            // Use the product ID as needed
            file_put_contents($log_file, "Product ID: {$product_id} | Customer Email: {$customer_email}", FILE_APPEND);
            
            // Retrieve the mapped MailerLite group for this product ID
            $mapping = get_option('smi_product_mapping')[$product_id] ?? 'none';
        
            if ($mapping !== 'none' && $customer_email) {
                // Add the customer to the mapped MailerLite group
                file_put_contents($log_file, "Adding customer: {$customer_email} to group: {$mapping} for product: {$product_id}\n", FILE_APPEND);
                smi_add_to_mailerlite($customer_email, $mapping);
            }
        }
    }

    // Log successful processing
    file_put_contents($log_file, "Webhook processed successfully\n", FILE_APPEND);
    status_header(200); // Respond with 200 OK
    exit;
}
add_action('rest_api_init', function () {
    register_rest_route('smi/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'smi_handle_stripe_webhook',
    ));
});

// Helper: Get Stripe Line Items
function smi_get_stripe_line_items($session_id) {
    $stripe_secret_key = get_option('smi_stripe_secret_key');
    if (!$stripe_secret_key) return [];

    // Prepare the request URL and headers
    $url = "https://api.stripe.com/v1/checkout/sessions/{$session_id}/line_items";
    $headers = [
        'Authorization' => 'Bearer ' . $stripe_secret_key
    ];

    $response = wp_remote_get($url, ['headers' => $headers]);

    // Check for errors
    if (is_wp_error($response)) {
        error_log('Error fetching line items: ' . $response->get_error_message());
        return [];
    }

    $line_items = [];
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($body['data'])) {
        $line_items = $body['data'];
    }

    return $line_items;
}

// Helper to Add to MailerLite
function smi_add_to_mailerlite($email, $group_id) {
    $api_key = get_option('smi_mailerlite_api_key');
    $log_file = plugin_dir_path(__FILE__) . 'mailerlite_request_log.txt'; // Path to the log file

    // Step 1: Add the subscriber
    $url_add_subscriber = "https://connect.mailerlite.com/api/subscribers";
    $request_data = array('email' => $email);

    file_put_contents($log_file, "Adding Subscriber Request Data: " . print_r($request_data, true) . "\n", FILE_APPEND);

    $response = wp_remote_post($url_add_subscriber, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ),
        'body' => json_encode($request_data),
    ));

    // Check for errors in adding the subscriber
    if (is_wp_error($response)) {
        error_log('MailerLite API request to add subscriber failed: ' . $response->get_error_message());
        return;
    }

    // Log the response from MailerLite when adding the subscriber
    $response_body = wp_remote_retrieve_body($response);
    file_put_contents($log_file, "Add Subscriber Response: " . print_r($response_body, true) . "\n", FILE_APPEND);

    $subscriber_data = json_decode($response_body, true);
    $subscriber_id = $subscriber_data['data']['id'] ?? null; // Adjusted to extract ID from within "data"

    if (!$subscriber_id) {
        error_log('Subscriber ID not found in response');
        return;
    }

    // Step 2: Add the subscriber to the specified group with the correct URL format
    $url_add_to_group = "https://connect.mailerlite.com/api/subscribers/{$subscriber_id}/groups/{$group_id}";

    file_put_contents($log_file, "Adding Subscriber to Group Request Data: subscriber_id={$subscriber_id}, group_id={$group_id}\n", FILE_APPEND);

    $group_response = wp_remote_post($url_add_to_group, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ),
    ));

    // Log the response from MailerLite when adding to the group
    file_put_contents($log_file, "Add to Group Response: " . print_r(wp_remote_retrieve_body($group_response), true) . "\n", FILE_APPEND);

    if (is_wp_error($group_response)) {
        error_log('MailerLite API request to add subscriber to group failed: ' . $group_response->get_error_message());
    }
}

// Helper: Verify Stripe Webhook Signature
function smi_verify_stripe_signature($payload, $sig_header, $webhook_secret) {
    // Stripe sends a signature in the format: t=<timestamp>,v1=<signature>
    $sig_header_parts = explode(',', $sig_header);
    $timestamp = null;
    $stripe_signature = null;

    // Parse the Stripe signature header
    foreach ($sig_header_parts as $part) {
        if (strpos($part, 't=') === 0) {
            //$timestamp = substr($part, 2); // Extract timestamp value
            $timestamp = substr($part, 2);
        } elseif (strpos($part, 'v1=') === 0) {
            $stripe_signature = substr($part, 3); // Extract signature value
        }
    }

    if (!$timestamp || !$stripe_signature) {
        return false; // Missing necessary signature data
    }

    // Ensure the timestamp is within an acceptable window (e.g., 5 minutes)
    $current_time = time();
    if (abs($current_time - $timestamp) > 5 * 60) {
        // Log or return false if the timestamp is too far out of range
        error_log('Timestamp is too old. Current time: ' . $current_time . ', Stripe timestamp: ' . $timestamp);
        return false;
    }

    // Prepare the expected signature by hashing the payload and timestamp with the webhook secret
    $expected_signature = hash_hmac('sha256', $timestamp . '.' . $payload, $webhook_secret);

    // Compare the received signature to the expected signature
    return hash_equals($stripe_signature, $expected_signature);
}