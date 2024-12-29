<?php

/**
 * Plugin Name: Booking Plugin
 * Plugin URI: https://example.com/
 * Description: Plugin sistem booking untuk layanan kecantikan.
 * Version: 1.0.0
 * Author: Muh Faris
 * Author URI: https://example.com/
 * License: GPLv2 or later
 * Text Domain: booking-plugin
 */

// Prevent direct access to the file.
if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

// Define constants.
define('BOOKING_PLUGIN_VERSION', '1.0.0');
define('BOOKING_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files.
require_once BOOKING_PLUGIN_PATH . 'includes/class-booking-plugin.php';

// Load Admin Class
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-admin.php';
// Load Settings Class
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-settings.php';

// Load Dashboard Class
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-dashboard.php';

// Memuat file untuk manajemen layanan
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-services.php';

// Initialize the plugin.
function booking_plugin_init()
{
    Booking_Plugin::get_instance();
}

add_action('plugins_loaded', 'booking_plugin_init');

function create_booking_table()
{
    global $wpdb;

    $prefix_table = $wpdb->prefix;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for bookings
    $table_bookings = $prefix_table . 'bookings';
    $sql1 = "CREATE TABLE $table_bookings (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_name VARCHAR(255) NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        booking_date DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Table for booking services
    $table_booking_services = $prefix_table . 'booking_services';
    $sql2 = "CREATE TABLE $table_booking_services (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include the WordPress file for dbDelta
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Execute the queries
    dbDelta($sql1);
    dbDelta($sql2);
}

// Hook into WordPress activation to run this function
register_activation_hook(__FILE__, 'create_booking_table');


// Activation hook.
function booking_plugin_activate()
{
    include_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Get the Owner role, or create it if not exists
    $owner_role = get_role('owner_booking');

    if (!$owner_role) {
        $owner_role = add_role(
            'owner_booking',
            __('Owner Booking', 'booking-plugin'),
            [
                'read' => true,
                'manage_bookings' => true,
            ]
        );
    } else {
        // Add the 'manage_bookings' capability to the existing Owner role
        $owner_role->add_cap('manage_bookings');
    }

    // Add 'manage_bookings' capability to the Admin role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('manage_bookings');
    }

    // Optional: Add a capability to Customer (if required)
    $customer_role = get_role('customer');
    if ($customer_role) {
        // Customers can only book, no admin capabilities
        $customer_role->add_cap('make_bookings');
    }

    create_booking_table();
}

register_activation_hook(__FILE__, 'booking_plugin_activate');

// Redirect users without proper capabilities from WordPress admin
function restrict_admin_access()
{
    if (is_admin()) {
        if (!current_user_can('manage_bookings') && !current_user_can('administrator')) {
            wp_redirect(home_url());  // Redirect to the homepage or another page
            exit;
        }
    }
}

add_action('admin_init', 'restrict_admin_access');

// Redirect Owner to profile page after login
function redirect_owner_to_profile($redirect_to, $request, $user)
{
    // Check if the user is an Owner
    if (in_array('owner_booking', (array) $user->roles)) {
        // Redirect Owner to the custom profile page (replace 'profile' with the actual URL or slug)
        return home_url('/wp-admin/admin.php?page=booking-dashboard');  // or custom URL for Owner's profile page
    }

    // Default redirect for other roles (Admin, Customer, etc.)
    return $redirect_to;
}

add_filter('login_redirect', 'redirect_owner_to_profile', 10, 3);

// Disable WordPress sidebar for Owner role
function disable_sidebar_for_owner()
{
    // Check if the current user is an Owner
    if (current_user_can('owner_booking')) {
        // Remove the default WordPress sidebar
        remove_action('admin_menu', 'remove_dashboard_widgets');
        remove_action('admin_menu', 'wp_dashboard_setup');
        remove_menu_page('index.php');  // Dashboard
        remove_menu_page('edit.php');  // Posts
        remove_menu_page('upload.php');  // Media
        remove_menu_page('edit.php?post_type=page');  // Pages
        remove_menu_page('edit-comments.php');  // Comments
        remove_menu_page('themes.php');  // Appearance
        remove_menu_page('plugins.php');  // Plugins
        remove_menu_page('users.php');  // Users
        remove_menu_page('tools.php');  // Tools
        remove_menu_page('options-general.php');  // Settings
    }
}

add_action('admin_menu', 'disable_sidebar_for_owner', 999);

// Hide admin bar for Owner role
function hide_admin_bar_for_owner($show_admin_bar)
{
    if (current_user_can('owner_booking')) {
        return false;  // Hide the admin bar
    }
    return $show_admin_bar;
}

add_filter('show_admin_bar', 'hide_admin_bar_for_owner');
