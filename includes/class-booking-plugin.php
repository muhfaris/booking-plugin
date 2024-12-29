<?php

if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Booking_Plugin
{
    private static $instance;

    // Singleton pattern.
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Constructor.
    private function __construct()
    {
        $this->define_hooks();
    }

    // Define hooks and filters.
    private function define_hooks()
    {
        // Admin-specific functionality.
        if (is_admin()) {
            include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-admin.php';
            Admin_Dashboard::init();
        }

        // Frontend functionality.
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-frontend.php';
        Booking_Frontend::init();
    }
}
