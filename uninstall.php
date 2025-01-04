<?php

function delete_options_by_prefix($prefix)
{
    global $wpdb;

    // Get all option names with the specified prefix
    $options = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $prefix . '%'
        )
    );

    // Delete each option individually
    foreach ($options as $option_name) {
        delete_option($option_name);
    }
}

function delete_site_options_by_prefix($prefix)
{
    // Ensure it's a multisite setup
    if (!is_multisite()) {
        return;
    }

    global $wpdb;
    // Get all site option names with the specified prefix
    $site_options = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
            $prefix . '%'
        )
    );

    // Delete each site option individually
    foreach ($site_options as $meta_key) {
        delete_site_option($meta_key);
    }
}

function delete_transients()
{
    // Clear all transients related to your plugin
    global $wpdb;

    $prefix = "bk_booking_service_"; // Define your query prefix constant
    $query = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_' . $wpdb->esc_like($prefix) . '%');
    $wpdb->query($query);

    // Also remove expired transients
    $query = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_' . $wpdb->esc_like($prefix) . '%');
    $wpdb->query($query);
}

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$option_name = 'bk_booking';
delete_options_by_prefix($option_name);
delete_site_options_by_prefix($option_name);

global $wpdb;

// Drop the tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bookings");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bookings_services");

// Clean up options
delete_option('booking_plugin_settings');

// Clean up transients
delete_transients();
