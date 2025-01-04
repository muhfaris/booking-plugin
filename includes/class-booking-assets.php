<?php

class Booking_Plugin_Assets
{

    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_admin_styles'] );
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_frontend_styles'] );
    }

    public function enqueue_admin_styles( $hook )
    {
        // Only load on plugin pages
        if ( strpos( $hook, 'booking-' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'booking-plugin-tailwind',
            BOOKING_PLUGIN_URL . 'assets/css/plugin.min.css',
            [],
            BOOKING_PLUGIN_VERSION
        );
    }

    public function enqueue_frontend_styles()
    {
        // Only load on pages where your plugin is used
        if ( !$this->is_plugin_page() ) {
            return;
        }

        wp_enqueue_style(
            'booking-plugin-tailwind',
            BOOKING_PLUGIN_URL . 'assets/css/plugin.min.css',
            [],
            BOOKING_PLUGIN_VERSION
        );
    }

    private function is_plugin_page()
    {
        // Add your logic to determine if current page uses your plugin
        return has_shortcode( get_post()->post_content, 'booking-form' ) ||
            is_page( 'booking' );
    }
}
