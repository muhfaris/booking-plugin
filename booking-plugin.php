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

// Load Settings Class
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-settings.php';

// Initialize the plugin.
function booking_plugin_init()
{
    Booking_Plugin::get_instance();
}

add_action('plugins_loaded', 'booking_plugin_init');

// Activation hook.
function booking_plugin_activate()
{
    include_once ABSPATH . 'wp-admin/includes/upgrade.php';
    global $wpdb;

    $table_name = $wpdb->prefix . 'booking';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_name VARCHAR(255) NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        booking_date DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}

register_activation_hook(__FILE__, 'booking_plugin_activate');
