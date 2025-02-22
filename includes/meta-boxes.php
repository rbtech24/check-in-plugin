<?php
// includes/meta-boxes.php

if (!defined('ABSPATH')) exit;

// Add meta boxes
function tcm_add_meta_boxes() {
    add_meta_box(
        'tcm_location_details',
        __('Location Details', 'tech-checkin-maps'),
        'tcm_location_meta_box',
        'tech_checkin',
        'normal',
        'high'
    );

    add_meta_box(
        'tcm_service_details',
        __('Service Details', 'tech-checkin-maps'),
        'tcm_service_meta_box',
        'tech_checkin',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'tcm_add_meta_boxes');

// Location meta box callback
function tcm_location_meta_box($post) {
    wp_nonce_field('tcm_location_meta_box', 'tcm_location_meta_box_nonce');

    $street = get_post_meta($post->ID, 'tcm_street', true);
    $city = get_post_meta($post->ID, 'tcm_city', true);
    $state = get_post_meta($post->ID, 'tcm_state', true);
    $zip = get_post_meta($post->ID, 'tcm_zip', true);
    $lat = get_post_meta($post->ID, 'tcm_latitude', true);
    $lng = get_post_meta($post->ID, 'tcm_longitude', true);

    ?>
    <div class="tcm-meta-box">
        <p>
            <label for="tcm-location-input"><?php _e('Search Address:', 'tech-checkin-maps'); ?></label>
            <input type="text" id="tcm-location-input" class="widefat" placeholder="<?php _e('Enter address...', 'tech-checkin-maps'); ?>">
        </p>

        <div id="tcm-map" style="height: 300px; margin: 10px 0;"></div>

        <div class="tcm-location-fields">
            <p>
                <label for="tcm-street"><?php _e('Street Address:', 'tech-checkin-maps'); ?></label>
                <input type="text" id="tcm-street" name="tcm_street" value="<?php echo esc_attr($street); ?>" class="widefat">
            </p>
            <p>
                <label for="tcm-city"><?php _e('City:', 'tech-checkin-maps'); ?></label>
                <input type="text" id="tcm-city" name="tcm_city" value="<?php echo esc_attr($city); ?>" class="widefat">
            </p>
            <p>
                <label for="tcm-state"><?php _e('State:', 'tech-checkin-maps'); ?></label>
                <input type="text" id="tcm-state" name="tcm_state" value="<?php echo esc_attr($state); ?>" class="widefat">
            </p>
            <p>
                <label for="tcm-zip"><?php _e('ZIP Code:', 'tech-checkin-maps'); ?></label>
                <input type="text" id="tcm-zip" name="tcm_zip" value="<?php echo esc_attr($zip); ?>" class="widefat">
            </p>
            <input type="hidden" id="tcm-latitude" name="tcm_latitude" value="<?php echo esc_attr($lat); ?>">
            <input type="hidden" id="tcm-longitude" name="tcm_longitude" value="<?php echo esc_attr($lng); ?>">
        </div>
    </div>
    <?php
}

// Service meta box callback
function tcm_service_meta_box($post) {
    wp_nonce_field('tcm_service_meta_box', 'tcm_service_meta_box_nonce');

    $technician = get_post_meta($post->ID, 'tcm_technician', true);
    $service_type = get_post_meta($post->ID, 'tcm_service_type', true);
    $service_date = get_post_meta($post->ID, 'tcm_service_date', true);

    ?>
    <div class="tcm-meta-box">
        <p>
            <label for="tcm-technician"><?php _e('Technician:', 'tech-checkin-maps'); ?></label>
            <select id="tcm-technician" name="tcm_technician" class="widefat">
                <option value=""><?php _e('Select Technician', 'tech-checkin-maps'); ?></option>
                <?php
                $technicians = get_option('tcm_technicians', array());
                foreach ($technicians as $tech) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($tech),
                        selected($technician, $tech, false),
                        esc_html($tech)
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <label for="tcm-service-type"><?php _e('Service Type:', 'tech-checkin-maps'); ?></label>
            <select id="tcm-service-type" name="tcm_service_type" class="widefat">
                <option value=""><?php _e('Select Service Type', 'tech-checkin-maps'); ?></option>
                <?php
                $services = get_option('tcm_services', array());
                foreach ($services as $service) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($service),
                        selected($service_type, $service, false),
                        esc_html($service)
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <label for="tcm-service-date"><?php _e('Service Date:', 'tech-checkin-maps'); ?></label>
            <input type="date" id="tcm-service-date" name="tcm_service_date" value="<?php echo esc_attr($service_date); ?>" class="widefat">
        </p>
    </div>
    <?php
}

// Save meta box data
function tcm_save_meta_box_data($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['tcm_location_meta_box_nonce']) || !isset($_POST['tcm_service_meta_box_nonce'])) {
        return;
    }

    // Verify nonces
    if (!wp_verify_nonce($_POST['tcm_location_meta_box_nonce'], 'tcm_location_meta_box') ||
        !wp_verify_nonce($_POST['tcm_service_meta_box_nonce'], 'tcm_service_meta_box')) {
        return;
    }

    // If this is an autosave, don't do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save location data
    $location_fields = array('street', 'city', 'state', 'zip', 'latitude', 'longitude');
    foreach ($location_fields as $field) {
        if (isset($_POST['tcm_' . $field])) {
            update_post_meta($post_id, 'tcm_' . $field, sanitize_text_field($_POST['tcm_' . $field]));
        }
    }

    // Save service data
    $service_fields = array('technician', 'service_type', 'service_date');
    foreach ($service_fields as $field) {
        if (isset($_POST['tcm_' . $field])) {
            update_post_meta($post_id, 'tcm_' . $field, sanitize_text_field($_POST['tcm_' . $field]));
        }
    }
}
add_action('save_post_tech_checkin', 'tcm_save_meta_box_data');