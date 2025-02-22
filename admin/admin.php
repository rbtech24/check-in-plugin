<?php
/**
* Admin functionality for Tech Check-in Maps
*
* @package Tech_Checkin_Maps
* @file admin/admin.php
*/

if (!defined('ABSPATH')) exit;

// Remove default menu items and add our custom menu
add_action('admin_menu', 'tcm_setup_admin_menu', 999);
function tcm_setup_admin_menu() {
   // Remove automatic menu items
   global $submenu;
   if (isset($submenu['edit.php?post_type=tech_checkin'])) {
       unset($submenu['edit.php?post_type=tech_checkin']);
   }

   // Add our custom submenu items
   add_submenu_page(
       'edit.php?post_type=tech_checkin',
       __('All Check-ins', 'tech-checkin-maps'),
       __('All Check-ins', 'tech-checkin-maps'),
       'manage_options',
       'edit.php?post_type=tech_checkin',
       null
   );

   add_submenu_page(
       'edit.php?post_type=tech_checkin',
       __('Add New Check-in', 'tech-checkin-maps'),
       __('Add New Check-in', 'tech-checkin-maps'),
       'manage_options',
       'post-new.php?post_type=tech_checkin'
   );

   add_submenu_page(
       'edit.php?post_type=tech_checkin',
       __('Service Types', 'tech-checkin-maps'),
       __('Service Types', 'tech-checkin-maps'),
       'manage_options',
       'tcm-service-types',
       'tcm_service_types_page'
   );

   add_submenu_page(
       'edit.php?post_type=tech_checkin',
       __('Settings', 'tech-checkin-maps'),
       __('Settings', 'tech-checkin-maps'),
       'manage_options',
       'tcm-settings',
       'tcm_settings_page'
   );
}

// Service types page callback
function tcm_service_types_page() {
   // Handle form submission
   if (isset($_POST['submit']) && check_admin_referer('tcm_service_types')) {
       $services = array_map('trim', explode("\n", $_POST['services']));
       $services = array_filter($services);
       update_option('tcm_services', $services);
       echo '<div class="notice notice-success"><p>' . __('Service types updated successfully.', 'tech-checkin-maps') . '</p></div>';
   }

   $services = get_option('tcm_services', array());
   ?>
   <div class="wrap">
       <h1><?php _e('Service Types', 'tech-checkin-maps'); ?></h1>
       
       <form method="post" action="">
           <?php wp_nonce_field('tcm_service_types'); ?>
           
           <div class="form-field">
               <label for="services"><?php _e('Service Types', 'tech-checkin-maps'); ?></label>
               <p class="description"><?php _e('Enter each service type on a new line.', 'tech-checkin-maps'); ?></p>
               <textarea name="services" id="services" rows="10" class="large-text"><?php echo esc_textarea(implode("\n", $services)); ?></textarea>
           </div>

           <?php submit_button(__('Save Service Types', 'tech-checkin-maps')); ?>
       </form>
   </div>
   <?php
}

// Add custom columns to check-ins list
add_filter('manage_tech_checkin_posts_columns', 'tcm_add_checkin_columns');
function tcm_add_checkin_columns($columns) {
   $new_columns = array();
   $new_columns['cb'] = $columns['cb'];
   $new_columns['title'] = __('Service Type', 'tech-checkin-maps');
   $new_columns['technician'] = __('Technician', 'tech-checkin-maps');
   $new_columns['location'] = __('Location', 'tech-checkin-maps');
   $new_columns['date'] = $columns['date'];
   return $new_columns;
}

// Fill custom columns for check-ins
add_action('manage_tech_checkin_posts_custom_column', 'tcm_fill_checkin_columns', 10, 2);
function tcm_fill_checkin_columns($column, $post_id) {
   switch ($column) {
       case 'technician':
           echo esc_html(get_post_meta($post_id, 'tcm_technician', true));
           break;
       case 'location':
           $city = get_post_meta($post_id, 'tcm_city', true);
           $state = get_post_meta($post_id, 'tcm_state', true);
           if ($city && $state) {
               echo esc_html("$city, $state");
           }
           break;
   }
}

// Make columns sortable
add_filter('manage_edit-tech_checkin_sortable_columns', 'tcm_sortable_checkin_columns');
function tcm_sortable_checkin_columns($columns) {
   $columns['technician'] = 'technician';
   $columns['location'] = 'location';
   return $columns;
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(TCM_PLUGIN_DIR . 'tech-checkin-maps.php'), 'tcm_add_plugin_links');
function tcm_add_plugin_links($links) {
   $settings_link = '<a href="' . admin_url('edit.php?post_type=tech_checkin&page=tcm-settings') . '">' . __('Settings', 'tech-checkin-maps') . '</a>';
   array_unshift($links, $settings_link);
   return $links;
}

// Register and handle custom bulk actions
add_filter('bulk_actions-edit-tech_checkin', 'tcm_register_bulk_actions');
function tcm_register_bulk_actions($bulk_actions) {
   $bulk_actions['delete'] = __('Delete', 'tech-checkin-maps');
   return $bulk_actions;
}

add_filter('handle_bulk_actions-edit-tech_checkin', 'tcm_handle_bulk_actions', 10, 3);
function tcm_handle_bulk_actions($redirect_to, $action, $post_ids) {
   if ($action !== 'delete') {
       return $redirect_to;
   }

   foreach ($post_ids as $post_id) {
       wp_delete_post($post_id, true);
   }

   $redirect_to = add_query_arg('bulk_deleted', count($post_ids), $redirect_to);
   return $redirect_to;
}

// Display admin notices for bulk actions
add_action('admin_notices', 'tcm_bulk_action_notices');
function tcm_bulk_action_notices() {
   if (!empty($_REQUEST['bulk_deleted'])) {
       $count = intval($_REQUEST['bulk_deleted']);
       $message = sprintf(
           _n(
               '%s check-in permanently deleted.',
               '%s check-ins permanently deleted.',
               $count,
               'tech-checkin-maps'
           ),
           number_format_i18n($count)
       );
       echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
   }
}