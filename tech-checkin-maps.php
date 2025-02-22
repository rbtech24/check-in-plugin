<?php
/**
 * Plugin Name: Tech Check-in Maps for SEO
 * Description: Service technician check-in system with maps integration for SEO
 * Version: 2.2.7
 * Author: Rod Bartruff
 * Text Domain: tech-checkin-maps
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('TCM_VERSION', '1.0.0');
define('TCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TCM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once TCM_PLUGIN_DIR . 'admin/admin.php';
require_once TCM_PLUGIN_DIR . 'admin/settings.php';
require_once TCM_PLUGIN_DIR . 'includes/post-types.php';
require_once TCM_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once TCM_PLUGIN_DIR . 'includes/helpers.php';

// Initialize plugin
function tcm_init() {
    // Load translations
    load_plugin_textdomain('tech-checkin-maps', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Register manifest route and handler
    add_action('init', function() {
        add_rewrite_rule(
            'manifest\.json$',
            'index.php?manifest=1',
            'top'
        );
    });

    add_action('parse_request', function($wp) {
        if (isset($wp->query_vars['manifest'])) {
            header('Content-Type: application/manifest+json');
            $manifest_path = plugin_dir_path(__FILE__) . 'public/manifest.json';
            echo file_get_contents($manifest_path);
            exit;
        }
    });

    // Register scripts and styles
    add_action('wp_enqueue_scripts', 'tcm_enqueue_scripts');
    add_action('admin_enqueue_scripts', 'tcm_admin_enqueue_scripts');
}
add_action('plugins_loaded', 'tcm_init');

// Temporary: Remove after running once
add_action('init', function() {
    flush_rewrite_rules();
});

// Enqueue public scripts and styles
function tcm_enqueue_scripts() {
    // Register PWA assets
    if (is_page('check-in')) {
        wp_enqueue_script('tcm-pwa', TCM_PLUGIN_URL . 'public/js/pwa.js', array(), TCM_VERSION, true);
        wp_localize_script('tcm-pwa', 'tcmPwa', array(
            'scope' => '/check-in/'
        ));
    }

    $api_key = get_option('tcm_google_maps_api_key');
    $default_location = get_option('tcm_default_location', array(
        'lat' => '30.1543',  // Magnolia, TX
        'lng' => '-95.7544'
    ));

// PWA assets are now handled in tcm_enqueue_scripts()

// Handle feedback submission
add_action('wp_ajax_tcm_submit_feedback', 'tcm_handle_feedback_submission');
add_action('wp_ajax_nopriv_tcm_submit_feedback', 'tcm_handle_feedback_submission');

    // Google Maps
    if (!empty($api_key)) {
        wp_enqueue_script(
            'google-maps',
            "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places",
            array(),
            null,
            true
        );
    }

    // Plugin scripts
    wp_enqueue_style('tcm-style', TCM_PLUGIN_URL . 'public/css/style.css', array(), TCM_VERSION);
    wp_enqueue_script('tcm-form-handler', TCM_PLUGIN_URL . 'public/js/form-handler.js', array('jquery'), TCM_VERSION, true);

    // Localize script
    wp_localize_script('tcm-form-handler', 'tcmAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'defaultLocation' => $default_location
    ));
}

// Enqueue admin scripts and styles
function tcm_admin_enqueue_scripts($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'))) return;

    wp_enqueue_script('tcm-admin', TCM_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), TCM_VERSION, true);
    wp_enqueue_style('tcm-admin', TCM_PLUGIN_URL . 'admin/css/admin.css', array(), TCM_VERSION);
}

// Register shortcodes
add_shortcode('tech_checkin_form', 'tcm_form_shortcode');
add_shortcode('display_tech_checkins', 'tcm_display_shortcode');

function tcm_form_shortcode($atts) {
    ob_start();
    include TCM_PLUGIN_DIR . 'public/templates/form-template.php';
    return ob_get_clean();
}

function tcm_display_shortcode($atts) {
    ob_start();
    include TCM_PLUGIN_DIR . 'public/templates/display-template.php';
    return ob_get_clean();
}

// Handle form submissions
add_action('wp_ajax_tcm_submit_checkin', 'tcm_handle_form_submission');
add_action('wp_ajax_nopriv_tcm_submit_checkin', 'tcm_handle_form_submission');

function tcm_handle_form_submission() {
    check_ajax_referer('tcm-ajax-nonce', 'security');

    if (empty($_POST['technician']) || empty($_POST['service'])) {
        wp_send_json_error('Required fields are missing');
    }

    $details = sanitize_textarea_field($_POST['details']);
    $service = sanitize_text_field($_POST['service']);
    $location = sprintf('%s, %s', 
        sanitize_text_field($_POST['city']),
        sanitize_text_field($_POST['state'])
    );

    // Enhance service details with GPT
    $enhanced_details = tcm_enhance_service_details($details, $service, $location);

    $post_data = array(
        'post_title'   => $service,
        'post_content' => $enhanced_details,
        'post_type'    => 'tech_checkin',
        'post_status'  => 'publish'
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error('Error creating check-in');
    }

    // Save meta data
    $meta_fields = array(
        'tcm_technician' => 'technician',
        'tcm_service'    => 'service',
        'tcm_latitude'   => 'latitude',
        'tcm_longitude'  => 'longitude',
        'tcm_city'       => 'city',
        'tcm_state'      => 'state',
        'tcm_street'     => 'street',
        'tcm_zip'        => 'zip'
    );

    foreach ($meta_fields as $meta_key => $post_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
        }
    }

    // Handle photos
    if (!empty($_FILES)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $photos = array();

        if (!empty($_FILES['photos_before'])) {
            $before_id = media_handle_upload('photos_before', $post_id);
            if (!is_wp_error($before_id)) {
                $photos['before'] = $before_id;
            }
        }

        if (!empty($_FILES['photos_after'])) {
            $after_id = media_handle_upload('photos_after', $post_id);
            if (!is_wp_error($after_id)) {
                $photos['after'] = $after_id;
            }
        }

        if (!empty($photos)) {
            update_post_meta($post_id, 'tcm_photos', $photos);
        }
    }

    wp_send_json_success('Check-in submitted successfully');
}



// Add rewrite rules
function tcm_add_rewrite_rules() {
    add_rewrite_rule(
        '^service/([^/]+)/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?post_type=tech_checkin&tcm_service=$matches[1]&tcm_state=$matches[2]&tcm_city=$matches[3]&tcm_date=$matches[4]&post_name=$matches[5]',
        'top'
    );
    add_rewrite_rule(
        '^service/([^/]+)/([^/]+)-([^/]+)/?$',
        'index.php?post_type=tech_checkin&tcm_service=$matches[1]&tcm_city=$matches[2]&tcm_state=$matches[3]',
        'top'
    );
}
add_action('init', 'tcm_add_rewrite_rules');

// Add query vars
function tcm_add_query_vars($vars) {
    $vars[] = 'tcm_service';
    $vars[] = 'tcm_city';
    $vars[] = 'tcm_state';
    $vars[] = 'tcm_date';
    return $vars;
}
add_filter('query_vars', 'tcm_add_query_vars');

// Activation hook
register_activation_hook(__FILE__, 'tcm_activate');
function tcm_activate() {
    // Create default options
    add_option('tcm_default_location', array(
        'lat' => '30.1543',  // Magnolia, TX
        'lng' => '-95.7544'
    ));

    // Register post type
    tcm_register_post_types();

    // Add rewrite rules
    tcm_add_rewrite_rules();

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'tcm_deactivate');
function tcm_deactivate() {
    flush_rewrite_rules();
}

// Add SEO optimization
add_action('wp_head', 'tcm_add_checkin_meta');
function tcm_add_checkin_meta() {
    if (is_singular('tech_checkin')) {
        $meta = get_post_meta(get_the_ID());
        $service = $meta['tcm_service'][0] ?? '';
        $city = $meta['tcm_city'][0] ?? '';
        $state = $meta['tcm_state'][0] ?? '';
        $street = $meta['tcm_street'][0] ?? '';
        $date = get_the_date('Y-m-d');

        $description = sprintf(
            'Professional %s services in %s, %s. Recent service work completed on %s with before and after photos. Licensed and insured technicians.',
            esc_html($service),
            esc_html($city),
            esc_html($state),
            esc_html($date)
        );

        // Basic SEO
        // Basic Meta Tags
        echo '<meta name="description" content="' . esc_attr($description) . '">';
        echo '<meta name="keywords" content="' . esc_attr("$service, $city $service, professional $service $state, $service near me, $city $state $service") . '">';
        echo '<meta name="robots" content="index, follow">';
        echo '<meta name="author" content="' . esc_attr(get_bloginfo('name')) . '">';

        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image">';
        echo '<meta name="twitter:title" content="' . esc_attr("$service in $city, $state") . '">';
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">';
        if (!empty($photos['after'])) {
            echo '<meta name="twitter:image" content="' . esc_url(wp_get_attachment_url($photos['after'])) . '">';
        }

        // Additional Open Graph
        echo '<meta property="og:locale" content="en_US">';
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">';
        echo '<meta property="og:updated_time" content="' . esc_attr(get_the_modified_date('c')) . '">';
        if (!empty($photos['after'])) {
            echo '<meta property="og:image" content="' . esc_url(wp_get_attachment_url($photos['after'])) . '">';
            echo '<meta property="og:image:width" content="1200">';
            echo '<meta property="og:image:height" content="630">';
        }

        // Open Graph
        echo '<meta property="og:title" content="' . esc_attr("$service in $city, $state") . '">';
        echo '<meta property="og:description" content="' . esc_attr($description) . '">';
        echo '<meta property="og:type" content="article">';
        echo '<meta property="article:published_time" content="' . esc_attr($date) . '">';

        // Location Data
        echo '<meta name="geo.region" content="' . esc_attr($state) . '">';
        echo '<meta name="geo.placename" content="' . esc_attr($city) . '">';
        echo '<meta name="geo.address" content="' . esc_attr("$street, $city, $state") . '">';

        if (!empty($meta['tcm_latitude'][0]) && !empty($meta['tcm_longitude'][0])) {
            echo '<meta name="geo.position" content="' . esc_attr($meta['tcm_latitude'][0]) . ';' . esc_attr($meta['tcm_longitude'][0]) . '">';
            echo '<meta name="ICBM" content="' . esc_attr($meta['tcm_latitude'][0] . ',' . $meta['tcm_longitude'][0]) . '">';
        }

        // Schema.org
        $photos = get_post_meta($post->ID, 'tcm_photos', true);
        $before_photo = !empty($photos['before']) ? wp_get_attachment_url($photos['before']) : '';
        $after_photo = !empty($photos['after']) ? wp_get_attachment_url($photos['after']) : '';

        global $post;
        if (!$post) {
            $post = get_post();
        }

        if ($post) {
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => "$service in $city, $state",
                'description' => $description,
                'provider' => array(
                    '@type' => 'LocalBusiness',
                    'name' => get_bloginfo('name'),
                    'address' => array(
                        '@type' => 'PostalAddress',
                        'streetAddress' => $street,
                        'addressLocality' => $city,
                        'addressRegion' => $state,
                        'addressCountry' => 'US'
                    ),
                    'geo' => array(
                        '@type' => 'GeoCoordinates',
                        'latitude' => $meta['tcm_latitude'][0] ?? '',
                        'longitude' => $meta['tcm_longitude'][0] ?? ''
                    ),
                    'image' => array($before_photo, $after_photo),
                'areaServed' => array(
                    '@type' => 'City',
                    'name' => $city
                )
            ),
            'serviceType' => $service,
            'category' => $service,
            'offers' => array(
                '@type' => 'Offer',
                'availability' => 'https://schema.org/InStock',
                'areaServed' => array(
                    '@type' => 'City',
                    'name' => $city,
                    'containedIn' => $state
                )
            ),
            'review' => array(
                '@type' => 'Review',
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => '5'
                ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_post_meta(get_the_ID(), 'tcm_technician', true)
                )
            )
        );

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }
}
}