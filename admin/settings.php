<?php
if (!defined('ABSPATH')) exit;

// Register Settings
function tcm_register_settings() {
    // API Settings
    register_setting('tcm_options', 'tcm_google_maps_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    
    register_setting('tcm_options', 'tcm_openai_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));

    // Default Location
    register_setting('tcm_options', 'tcm_default_location', array(
        'type' => 'array',
        'default' => array(
            'lat' => '37.7749',
            'lng' => '-122.4194'
        )
    ));

    // Technicians Array
    register_setting('tcm_options', 'tcm_technicians', array(
        'type' => 'array',
        'default' => array()
    ));

    // Services Array
    register_setting('tcm_options', 'tcm_services', array(
        'type' => 'array',
        'default' => array()
    ));
}
add_action('admin_init', 'tcm_register_settings');

// Settings Page
function tcm_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle page creation
    if (isset($_POST['tcm_create_page']) && check_admin_referer('tcm_create_page', 'tcm_page_nonce')) {
        $page_data = array(
            'post_title'    => 'Tech Check-in Form',
            'post_content'  => '[tech_checkin_form]',
            'post_status'   => 'publish',
            'post_type'     => 'page'
        );

        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {
            add_settings_error(
                'tcm_messages',
                'tcm_page_created',
                __('Check-in page created successfully!', 'tech-checkin-maps'),
                'updated'
            );
        }
    }

    // Save Settings Notice
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'tcm_messages',
            'tcm_message',
            __('Settings Saved', 'tech-checkin-maps'),
            'updated'
        );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('tcm_messages'); ?>

        <form action="options.php" method="post">
            <?php settings_fields('tcm_options'); ?>
            
            <table class="form-table">
                <!-- API Key Section -->
                <tr>
                    <th scope="row">
                        <label for="tcm_google_maps_api_key"><?php _e('Google Maps API Key', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="tcm_google_maps_api_key"
                               name="tcm_google_maps_api_key"
                               value="<?php echo esc_attr(get_option('tcm_google_maps_api_key')); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter your Google Maps API key.', 'tech-checkin-maps'); ?>
                            <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                                <?php _e('Get one here', 'tech-checkin-maps'); ?>
                            </a>
                        </p>
                    </td>
                </tr>

                <!-- Facebook Integration -->
                <tr>
                    <th scope="row">
                        <label for="tcm_facebook_page_token"><?php _e('Facebook Page Access Token', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="tcm_facebook_page_token"
                               name="tcm_facebook_page_token"
                               value="<?php echo esc_attr(get_option('tcm_facebook_page_token')); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tcm_facebook_page_id"><?php _e('Facebook Page ID', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="tcm_facebook_page_id"
                               name="tcm_facebook_page_id"
                               value="<?php echo esc_attr(get_option('tcm_facebook_page_id')); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <!-- Google Business Integration -->
                <tr>
                    <th scope="row">
                        <label for="tcm_google_api_key"><?php _e('Google Business API Key', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="tcm_google_api_key"
                               name="tcm_google_api_key"
                               value="<?php echo esc_attr(get_option('tcm_google_api_key')); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tcm_google_location_id"><?php _e('Google Business Location ID', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="tcm_google_location_id"
                               name="tcm_google_location_id"
                               value="<?php echo esc_attr(get_option('tcm_google_location_id')); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <!-- OpenAI API Key Section -->
                <tr>
                    <th scope="row">
                        <label for="tcm_openai_api_key"><?php _e('OpenAI API Key', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="tcm_openai_api_key"
                               name="tcm_openai_api_key"
                               value="<?php echo esc_attr(get_option('tcm_openai_api_key')); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php _e('Enter your OpenAI API key for GPT integration.', 'tech-checkin-maps'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Default Location Section -->
                <tr>
                    <th scope="row">
                        <label for="tcm_default_location_lat"><?php _e('Default Map Location', 'tech-checkin-maps'); ?></label>
                    </th>
                    <td>
                        <?php
                        $location = get_option('tcm_default_location', array(
                            'lat' => '37.7749',
                            'lng' => '-122.4194'
                        ));
                        ?>
                        <input type="text" 
                               id="tcm_default_location_lat"
                               name="tcm_default_location[lat]"
                               value="<?php echo esc_attr($location['lat']); ?>"
                               class="small-text"
                               placeholder="Latitude">
                        <input type="text" 
                               id="tcm_default_location_lng"
                               name="tcm_default_location[lng]"
                               value="<?php echo esc_attr($location['lng']); ?>"
                               class="small-text"
                               placeholder="Longitude">
                        <p class="description"><?php _e('Default map center coordinates', 'tech-checkin-maps'); ?></p>
                    </td>
                </tr>

                <!-- Technicians Section -->
                <tr>
                    <th scope="row"><?php _e('Technicians', 'tech-checkin-maps'); ?></th>
                    <td>
                        <div id="tcm-technicians-list" class="tcm-repeatable-list">
                            <?php
                            $technicians = get_option('tcm_technicians', array());
                            foreach ($technicians as $tech) {
                                echo '<div class="tcm-list-item">';
                                echo '<input type="text" name="tcm_technicians[]" value="' . esc_attr($tech) . '" class="regular-text">';
                                echo '<button type="button" class="button remove-item">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                            <div class="tcm-list-item">
                                <input type="text" name="tcm_technicians[]" class="regular-text">
                                <button type="button" class="button add-item">Add Technician</button>
                            </div>
                        </div>
                    </td>
                </tr>

                <!-- Services Section -->
                <tr>
                    <th scope="row"><?php _e('Services', 'tech-checkin-maps'); ?></th>
                    <td>
                        <div id="tcm-services-list" class="tcm-repeatable-list">
                            <?php
                            $services = get_option('tcm_services', array());
                            foreach ($services as $service) {
                                echo '<div class="tcm-list-item">';
                                echo '<input type="text" name="tcm_services[]" value="' . esc_attr($service) . '" class="regular-text">';
                                echo '<button type="button" class="button remove-item">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                            <div class="tcm-list-item">
                                <input type="text" name="tcm_services[]" class="regular-text">
                                <button type="button" class="button add-item">Add Service</button>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <!-- Shortcode Information -->
<div class="tcm-pwa-info" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
    <h2><?php _e('Progressive Web App (PWA)', 'tech-checkin-maps'); ?></h2>
    <p><?php _e('The check-in form is available as a Progressive Web App. Users can install it on their mobile devices when visiting the check-in page.', 'tech-checkin-maps'); ?></p>
    <p><strong><?php _e('Features:', 'tech-checkin-maps'); ?></strong></p>
    <ul style="list-style-type: disc; margin-left: 20px;">
        <li><?php _e('Works offline', 'tech-checkin-maps'); ?></li>
        <li><?php _e('Can be installed on mobile home screen', 'tech-checkin-maps'); ?></li>
        <li><?php _e('Available only on the check-in page', 'tech-checkin-maps'); ?></li>
    </ul>
</div>

<div class="tcm-page-creator" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
    <h2><?php _e('Create Check-in Page', 'tech-checkin-maps'); ?></h2>
    <p><?php _e('Click the button below to automatically create a page with the check-in form.', 'tech-checkin-maps'); ?></p>
    <form method="post" action="">
        <?php wp_nonce_field('tcm_create_page', 'tcm_page_nonce'); ?>
        <input type="submit" name="tcm_create_page" class="button button-primary" value="<?php _e('Create Check-in Page', 'tech-checkin-maps'); ?>">
    </form>
</div>

<div class="tcm-shortcode-info" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
    <h2><?php _e('Shortcodes', 'tech-checkin-maps'); ?></h2>
    <p><?php _e('Use these shortcodes to display check-in forms and lists on your pages:', 'tech-checkin-maps'); ?></p>
    
    <h4><?php _e('Check-in Form:', 'tech-checkin-maps'); ?></h4>
    <code>[tech_checkin_form]</code>
    
    <h4><?php _e('Display Check-ins:', 'tech-checkin-maps'); ?></h4>
    <code>[display_tech_checkins]</code>
    
    <p><?php _e('Optional parameters for displaying check-ins:', 'tech-checkin-maps'); ?></p>
    <ul style="list-style-type: disc; margin-left: 20px;">
        <li><code>title="Your Title"</code> - <?php _e('Custom title for the check-ins section', 'tech-checkin-maps'); ?></li>
        <li><code>service="Service Type"</code> - <?php _e('Filter by service type', 'tech-checkin-maps'); ?></li>
        <li><code>location="City"</code> - <?php _e('Filter by location', 'tech-checkin-maps'); ?></li>
    </ul>
    
    <p><?php _e('Example with title:', 'tech-checkin-maps'); ?></p>
    <code>[display_tech_checkins title="Recent Sprinkler Repair Reviews"]</code>
    
    <p><?php _e('Example with title and location:', 'tech-checkin-maps'); ?></p>
    <code>[display_tech_checkins title="Sprinkler Services in Houston" location="Houston"]</code>
    
    <p><?php _e('Example with title and service type:', 'tech-checkin-maps'); ?></p>
    <code>[display_tech_checkins title="Recent Sprinkler Repairs" service="Sprinkler Repair"]</code>
    
    <p><?php _e('Example with all parameters:', 'tech-checkin-maps'); ?></p>
    <code>[display_tech_checkins title="Houston Sprinkler Services" service="Sprinkler Repair" location="Houston"]</code>
</div>

    <style>
        .tcm-repeatable-list {
            margin-bottom: 15px;
        }
        .tcm-list-item {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tcm-list-item .button {
            flex-shrink: 0;
        }
        .tcm-shortcode-info code {
            display: inline-block;
            padding: 5px 10px;
            background: #f0f0f1;
            border-radius: 3px;
            margin: 5px 0;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Add item
        $('.add-item').on('click', function() {
            const container = $(this).closest('.tcm-repeatable-list');
            const item = $(this).closest('.tcm-list-item');
            const clone = item.clone();
            
            clone.find('input').val('');
            clone.find('.add-item')
                .removeClass('add-item')
                .addClass('remove-item')
                .text('Remove');
            
            container.append(clone);
        });

        // Remove item
        $(document).on('click', '.remove-item', function() {
            $(this).closest('.tcm-list-item').remove();
        });
    });
    </script>
    <?php
}

// Validate API Key
function tcm_validate_api_key($api_key) {
    if (empty($api_key)) {
        add_settings_error(
            'tcm_google_maps_api_key',
            'tcm_api_key_error',
            __('API Key cannot be empty', 'tech-checkin-maps'),
            'error'
        );
        return '';
    }
    return sanitize_text_field($api_key);
}

// Test API Key
function tcm_test_api_key() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $api_key = get_option('tcm_google_maps_api_key');
    
    $test_url = "https://maps.googleapis.com/maps/api/geocode/json?address=test&key={$api_key}";
    $response = wp_remote_get($test_url);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if ($data->status === 'OK' || $data->status === 'ZERO_RESULTS') {
        wp_send_json_success('API Key is valid');
    } else {
        wp_send_json_error('Invalid API Key');
    }
}
add_action('wp_ajax_tcm_test_api_key', 'tcm_test_api_key');