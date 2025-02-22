<?php
// includes/helpers.php

if (!defined('ABSPATH')) exit;

/**
 * Format address components into a single string
 */
function tcm_format_address($street = '', $city = '', $state = '', $zip = '') {
    $address_parts = array_filter(array($street, $city, $state, $zip));
    return implode(', ', $address_parts);
}

/**
 * Get all registered technicians
 */
function tcm_get_technicians() {
    return get_option('tcm_technicians', array());
}

/**
 * Get all registered services
 */
function tcm_get_services() {
    return get_option('tcm_services', array());
}

/**
 * Validate coordinates
 */
function tcm_validate_coordinates($lat, $lng) {
    return is_numeric($lat) && 
           is_numeric($lng) && 
           $lat >= -90 && 
           $lat <= 90 && 
           $lng >= -180 && 
           $lng <= 180;
}

/**
 * Handle image upload and return attachment ID
 */
function tcm_handle_image_upload($file, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload($file, $post_id);

    if (is_wp_error($attachment_id)) {
        return false;
    }

    return $attachment_id;
}

/**
 * Get check-in photos
 */
function tcm_get_checkin_photos($post_id) {
    $photos = get_post_meta($post_id, 'tcm_photos', true);
    if (!is_array($photos)) {
        return array();
    }

    $photo_urls = array();
    foreach ($photos as $photo_id) {
        $url = wp_get_attachment_image_url($photo_id, 'large');
        if ($url) {
            $photo_urls[] = $url;
        }
    }

    return $photo_urls;
}

/**
 * Format date for display
 */
function tcm_format_date($date_string) {
    $timestamp = strtotime($date_string);
    return date('F j, Y - g:i A', $timestamp);
}

/**
 * Get check-in status label
 */
function tcm_get_status_label($status) {
    $labels = array(
        'pending' => __('Pending', 'tech-checkin-maps'),
        'completed' => __('Completed', 'tech-checkin-maps'),
        'in_progress' => __('In Progress', 'tech-checkin-maps'),
        'cancelled' => __('Cancelled', 'tech-checkin-maps')
    );

    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Get check-in meta data
 */
function tcm_get_checkin_meta($post_id) {
    return array(
        'technician' => get_post_meta($post_id, 'tcm_technician', true),
        'service' => get_post_meta($post_id, 'tcm_service_type', true),
        'date' => get_post_meta($post_id, 'tcm_service_date', true),
        'street' => get_post_meta($post_id, 'tcm_street', true),
        'city' => get_post_meta($post_id, 'tcm_city', true),
        'state' => get_post_meta($post_id, 'tcm_state', true),
        'zip' => get_post_meta($post_id, 'tcm_zip', true),
        'latitude' => get_post_meta($post_id, 'tcm_latitude', true),
        'longitude' => get_post_meta($post_id, 'tcm_longitude', true),
        'status' => get_post_meta($post_id, 'tcm_status', true),
        'photos' => tcm_get_checkin_photos($post_id)
    );
}

/**
 * Validate check-in data
 */
function tcm_validate_checkin_data($data) {
    $errors = array();

    if (empty($data['technician'])) {
        $errors[] = __('Technician is required', 'tech-checkin-maps');
    }

    if (empty($data['service_type'])) {
        $errors[] = __('Service type is required', 'tech-checkin-maps');
    }

    if (empty($data['city']) || empty($data['state'])) {
        $errors[] = __('Location is required', 'tech-checkin-maps');
    }

    if (!empty($data['latitude']) && !empty($data['longitude'])) {
        if (!tcm_validate_coordinates($data['latitude'], $data['longitude'])) {
            $errors[] = __('Invalid coordinates', 'tech-checkin-maps');
        }
    }

    return $errors;
}

/**
 * Get recent check-ins
 */
function tcm_get_recent_checkins($limit = 10) {
    $args = array(
        'post_type' => 'tech_checkin',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $query = new WP_Query($args);
    $checkins = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $checkins[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'content' => get_the_content(),
                'date' => get_the_date('Y-m-d H:i:s'),
                'meta' => tcm_get_checkin_meta(get_the_ID())
            );
        }
        wp_reset_postdata();
    }

    return $checkins;
}

/**
 * Get check-ins by technician
 */
function tcm_get_technician_checkins($technician_name, $limit = -1) {
    $args = array(
        'post_type' => 'tech_checkin',
        'posts_per_page' => $limit,
        'meta_query' => array(
            array(
                'key' => 'tcm_technician',
                'value' => $technician_name,
                'compare' => '='
            )
        )
    );

    return tcm_get_checkins_by_query($args);
}

/**
 * Get check-ins by service type
 */
function tcm_get_service_checkins($service_type, $limit = -1) {
    $args = array(
        'post_type' => 'tech_checkin',
        'posts_per_page' => $limit,
        'meta_query' => array(
            array(
                'key' => 'tcm_service_type',
                'value' => $service_type,
                'compare' => '='
            )
        )
    );

    return tcm_get_checkins_by_query($args);
}

/**
 * Get check-ins by date range
 */
function tcm_get_checkins_by_date($start_date, $end_date, $limit = -1) {
    $args = array(
        'post_type' => 'tech_checkin',
        'posts_per_page' => $limit,
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true
            )
        )
    );

    return tcm_get_checkins_by_query($args);
}

/**
 * Reusable function to get check-ins by WP_Query args
 */
function tcm_get_checkins_by_query($args) {
    $query = new WP_Query($args);
    $checkins = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $checkins[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'content' => get_the_content(),
                'date' => get_the_date('Y-m-d H:i:s'),
                'meta' => tcm_get_checkin_meta(get_the_ID())
            );
        }
        wp_reset_postdata();
    }

    return $checkins;
}

/**
 * Generate export data for check-ins with rate limiting
 */
function tcm_generate_export_data($checkins) {
    // Set time limit to prevent timeout
    set_time_limit(300);

    // Enable output buffering
    if (ob_get_level()) ob_end_clean();

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="checkins-export.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $handle = fopen('php://output', 'w');

    // Write headers
    $headers = array(
        'Date',
        'Technician',
        'Service Type',
        'Location',
        'Status',
        'Details'
    );
    fputcsv($handle, $headers);

    // Write data in chunks
    foreach ($checkins as $checkin) {
        $meta = $checkin['meta'];
        $location = tcm_format_address($meta['street'], $meta['city'], $meta['state'], $meta['zip']);

        $row = array(
            $checkin['date'],
            $meta['technician'],
            $meta['service'],
            $location,
            $meta['status'],
            strip_tags($checkin['content'])
        );

        fputcsv($handle, $row);
        flush();

        // Add small delay to prevent rate limiting
        usleep(10000); // 10ms delay
    }

    fclose($handle);
    exit();
}
/**
 * Send email notifications for check-ins
 */
function tcm_send_notification_email($checkin_id, $type = 'new') {
    $checkin = tcm_get_checkin_meta($checkin_id);
    $admin_email = get_option('admin_email');

    switch($type) {
        case 'new':
            $subject = sprintf('New Check-in: %s', $checkin['service']);
            $message = sprintf(
                "New check-in received:\n\nTechnician: %s\nService: %s\nLocation: %s\nDate: %s",
                $checkin['technician'],
                $checkin['service'],
                tcm_format_address($checkin['street'], $checkin['city'], $checkin['state'], $checkin['zip']),
                $checkin['date']
            );
            break;

        case 'feedback':
            $subject = sprintf('New Feedback for Check-in: %s', $checkin['service']);
            $message = sprintf(
                "New feedback received for check-in:\n\nService: %s\nRating: %s\nComment: %s",
                $checkin['service'],
                get_post_meta($checkin_id, 'tcm_rating', true),
                get_post_meta($checkin_id, 'tcm_feedback', true)
            );
            break;
    }

    wp_mail($admin_email, $subject, $message);
}
/**
 * Enhanced location-based query
 */
function tcm_get_checkins_by_location($location_args, $radius = 25, $limit = -1) {
    $args = array(
        'post_type' => 'tech_checkin',
        'posts_per_page' => $limit,
        'meta_query' => array('relation' => 'AND')
    );

    if (!empty($location_args['city'])) {
        $args['meta_query'][] = array(
            'key' => 'tcm_city',
            'value' => $location_args['city'],
            'compare' => 'LIKE'
        );
    }

    if (!empty($location_args['state'])) {
        $args['meta_query'][] = array(
            'key' => 'tcm_state',
            'value' => $location_args['state'],
            'compare' => '='
        );
    }

    if (!empty($location_args['zip'])) {
        $args['meta_query'][] = array(
            'key' => 'tcm_zip',
            'value' => $location_args['zip'],
            'compare' => '='
        );
    }

    return tcm_get_checkins_by_query($args);
}

/**
 * Analytics tracking
 */
function tcm_track_checkin_view($post_id) {
    $views = (int)get_post_meta($post_id, 'tcm_view_count', true);
    update_post_meta($post_id, 'tcm_view_count', $views + 1);

    // Track location data
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $viewing_time = current_time('mysql');

    $analytics_data = array(
        'ip' => $user_ip,
        'timestamp' => $viewing_time,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
    );

    add_post_meta($post_id, 'tcm_view_analytics', $analytics_data);
}

/**
 * Get analytics summary
 */
function tcm_get_analytics_summary($post_id) {
    $views = get_post_meta($post_id, 'tcm_view_count', true);
    $analytics = get_post_meta($post_id, 'tcm_view_analytics');

    return array(
        'total_views' => $views,
        'view_history' => $analytics,
        'unique_visitors' => count(array_unique(array_column($analytics, 'ip')))
    );
}
/**
 * Cache management
 */
function tcm_get_cached_checkins($args, $cache_time = 3600) {
    $cache_key = 'tcm_checkins_' . md5(serialize($args));
    $cached_data = get_transient($cache_key);

    if ($cached_data === false) {
        $checkins = tcm_get_checkins_by_query($args);
        set_transient($cache_key, $checkins, $cache_time);
        return $checkins;
    }

    return $cached_data;
}

function tcm_clear_checkin_cache($post_id) {
    // Clear specific post cache
    wp_cache_delete($post_id, 'tech_checkin_post');

    // Clear related caches
    $cache_keys = array(
        'tcm_checkins_recent',
        'tcm_checkins_location_' . get_post_meta($post_id, 'tcm_city', true),
        'tcm_checkins_service_' . get_post_meta($post_id, 'tcm_service', true),
        'tcm_checkins_tech_' . get_post_meta($post_id, 'tcm_technician', true),
        'tcm_checkins_date_' . get_the_date('Y-m', $post_id)
    );

    foreach ($cache_keys as $key) {
        wp_cache_delete($key, 'tech-checkin-maps');
        delete_transient($key);
    }

    // Clear page cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

/**
 * Auto-post check-in to Facebook
 */
function tcm_post_to_facebook($post_id) {
    $fb_page_token = get_option('tcm_facebook_page_token');
    $fb_page_id = get_option('tcm_facebook_page_id');
    
    if (empty($fb_page_token) || empty($fb_page_id)) {
        tcm_log_error('facebook_error', 'Missing Facebook credentials');
        return false;
    }

    $meta = tcm_get_checkin_meta($post_id);
    $photos = get_post_meta($post_id, 'tcm_photos', true);
    $after_photo = !empty($photos['after']) ? wp_get_attachment_url($photos['after']) : '';
    
    $message = sprintf(
        "New %s completed in %s, %s! 🛠️\n\nOur technician %s provided excellent service. Check out the results!\n\n#%s #ServicePro #QualityService",
        $meta['service'],
        $meta['city'],
        $meta['state'],
        $meta['technician'],
        str_replace(' ', '', $meta['service'])
    );

    $url = "https://graph.facebook.com/v18.0/{$fb_page_id}/photos";
    $args = array(
        'method' => 'POST',
        'body' => array(
            'message' => $message,
            'url' => $after_photo,
            'access_token' => $fb_page_token
        )
    );

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        tcm_log_error('facebook_error', $response->get_error_message());
        return false;
    }

    return true;
}

/**
 * Post check-in to Google Business Profile
 */
function tcm_post_to_google_business($post_id) {
    $google_api_key = get_option('tcm_google_api_key');
    $location_id = get_option('tcm_google_location_id');
    
    if (empty($google_api_key) || empty($location_id)) {
        tcm_log_error('google_error', 'Missing Google Business credentials');
        return false;
    }

    $meta = tcm_get_checkin_meta($post_id);
    $photos = get_post_meta($post_id, 'tcm_photos', true);
    $after_photo = !empty($photos['after']) ? wp_get_attachment_url($photos['after']) : '';

    $post_data = array(
        'topicType' => 'LOCAL_POST',
        'languageCode' => 'en-US',
        'summary' => sprintf(
            "Completed %s in %s, %s. Professional service by %s. #ServicePro",
            $meta['service'],
            $meta['city'],
            $meta['state'],
            $meta['technician']
        ),
        'callToAction' => array(
            'actionType' => 'LEARN_MORE',
            'url' => get_permalink($post_id)
        )
    );

    if ($after_photo) {
        $post_data['media'] = array(
            array('mediaFormat' => 'PHOTO', 'sourceUrl' => $after_photo)
        );
    }

    $url = "https://mybusinessaccounts.googleapis.com/v4/{$location_id}/localPosts";
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $google_api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($post_data)
    );

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        tcm_log_error('google_error', $response->get_error_message());
        return false;
    }

    return true;
}


    foreach ($cache_keys as $key) {
        delete_transient($key);
    }
}

// Hook to clear cache on check-in update
add_action('save_post_tech_checkin', 'tcm_clear_checkin_cache');

/**
 * Enhance service details with GPT
 */
function tcm_enhance_service_details($details, $service_type, $location) {
    $api_key = get_option('tcm_openai_api_key');
    if (empty($api_key)) {
        tcm_log_error('config_error', 'OpenAI API key not configured');
        return $details;
    }

    $prompt = sprintf(
        "Write a short, focused paragraph about this %s service call. Describe what the technician specifically did during this service visit in %s. Include these service details: %s. Keep it concise and factual, focusing on the actual work performed.",
        $service_type,
        $location,
        $details
    );

    $response = tcm_api_request('https://api.openai.com/v1/chat/completions', 
        array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                )
            ))
        ),
        array('choices' => array(array('message' => array('content' => $details))))
    );

    if (!$response) {
        return $details;
    }

    $body = json_decode($response, true);
    return $body['choices'][0]['message']['content'] ?? $details;
}
/**
 * Log errors with detailed information
 */
function tcm_log_error($error_type, $message, $context = array()) {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'type' => $error_type,
        'message' => $message,
        'context' => $context,
        'user_id' => get_current_user_id(),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    );

    // Store in WordPress error log
    error_log(wp_json_encode($log_entry));
    
    // Store in database for admin review
    $existing_logs = get_option('tcm_error_logs', array());
    array_unshift($existing_logs, $log_entry);
    update_option('tcm_error_logs', array_slice($existing_logs, 0, 100));
}

/**
 * Enhanced API request handler with fallbacks
 */
function tcm_api_request($url, $args = array(), $fallback = null) {
    $response = wp_remote_request($url, wp_parse_args($args, array(
        'timeout' => 15,
        'redirection' => 5
    )));

    if (is_wp_error($response)) {
        tcm_log_error('api_failure', $response->get_error_message(), array(
            'url' => $url,
            'args' => $args
        ));
        return $fallback;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        tcm_log_error('api_error', "API returned status $status_code", array(
            'url' => $url,
            'response' => wp_remote_retrieve_body($response)
        ));
        return $fallback;
    }

    return wp_remote_retrieve_body($response);
}
