<?php
// includes/post-types.php

if (!defined('ABSPATH')) exit;

// Register post type
function tcm_register_post_types() {
    $labels = array(
        'name'                  => _x('Tech Check-ins', 'Post type general name', 'tech-checkin-maps'),
        'singular_name'         => _x('Tech Check-in', 'Post type singular name', 'tech-checkin-maps'),
        'menu_name'            => _x('Tech Check-ins', 'Admin Menu text', 'tech-checkin-maps'),
        'add_new'              => _x('Add New', 'check-in', 'tech-checkin-maps'),
        'add_new_item'         => __('Add New Check-in', 'tech-checkin-maps'),
        'edit_item'            => __('Edit Check-in', 'tech-checkin-maps'),
        'new_item'             => __('New Check-in', 'tech-checkin-maps'),
        'view_item'            => __('View Check-in', 'tech-checkin-maps'),
        'view_items'           => __('View Check-ins', 'tech-checkin-maps'),
        'search_items'         => __('Search Check-ins', 'tech-checkin-maps'),
        'not_found'            => __('No check-ins found.', 'tech-checkin-maps'),
        'not_found_in_trash'   => __('No check-ins found in Trash.', 'tech-checkin-maps'),
        'all_items'            => __('All Check-ins', 'tech-checkin-maps'),
        'featured_image'       => __('Service Photo', 'tech-checkin-maps'),
        'set_featured_image'   => __('Set service photo', 'tech-checkin-maps'),
        'remove_featured_image' => __('Remove service photo', 'tech-checkin-maps'),
        'menu_icon'            => 'dashicons-location'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => array(
            'slug' => 'service',
            'with_front' => false
        ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'revisions', 'author'),
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-location',
    );

    register_post_type('tech_checkin', $args);
}
add_action('init', 'tcm_register_post_types');

// Register taxonomies if needed
function tcm_register_taxonomies() {
    // Service Type Taxonomy
    $labels = array(
        'name'              => _x('Service Types', 'taxonomy general name', 'tech-checkin-maps'),
        'singular_name'     => _x('Service Type', 'taxonomy singular name', 'tech-checkin-maps'),
        'search_items'      => __('Search Service Types', 'tech-checkin-maps'),
        'all_items'         => __('All Service Types', 'tech-checkin-maps'),
        'parent_item'       => __('Parent Service Type', 'tech-checkin-maps'),
        'parent_item_colon' => __('Parent Service Type:', 'tech-checkin-maps'),
        'edit_item'         => __('Edit Service Type', 'tech-checkin-maps'),
        'update_item'       => __('Update Service Type', 'tech-checkin-maps'),
        'add_new_item'      => __('Add New Service Type', 'tech-checkin-maps'),
        'new_item_name'     => __('New Service Type Name', 'tech-checkin-maps'),
        'menu_name'         => __('Service Types', 'tech-checkin-maps'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'service-type'),
        'show_in_rest'      => true,
    );

    register_taxonomy('service_type', array('tech_checkin'), $args);
}
add_action('init', 'tcm_register_taxonomies');

// Filter to replace placeholders in the permalink structure
function tcm_post_type_link($post_link, $post) {
    if ($post->post_type === 'tech_checkin') {
        $service = get_post_meta($post->ID, 'tcm_service', true);
        $city = get_post_meta($post->ID, 'tcm_city', true);
        $state = get_post_meta($post->ID, 'tcm_state', true);
        $service = sanitize_title($service);
        $city = sanitize_title($city);
        $state = sanitize_title($state);
        $date = get_the_date('Y-m-d', $post->ID);

        $post_link = str_replace('%tcm_service%', $service, $post_link);
        $post_link = str_replace('%tcm_city%', $city, $post_link);
        $post_link = str_replace('%tcm_state%', $state, $post_link);
        $post_link = str_replace('%tcm_date%', $date, $post_link);
    $post_link = str_replace('%post_name%', $post->post_name, $post_link);
    }
    return $post_link;
}
add_filter('post_type_link', 'tcm_post_type_link', 10, 2);

?>