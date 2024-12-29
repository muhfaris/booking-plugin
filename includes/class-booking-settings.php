<?php
if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Booking_Settings
{
    /**
     * Register hooks for the settings page.
     */
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Add a settings page under the "Settings" menu.
     */
    public static function add_settings_page()
    {
        add_options_page(
            __('Booking Settings', 'booking-plugin'),
            __('Booking Settings', 'booking-plugin'),
            'manage_options',
            'booking-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Render the settings page content.
     */
    public static function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Booking Settings', 'booking-plugin')); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('booking-settings');
        do_settings_sections('booking-settings');
        submit_button();
        ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings and fields.
     */
    public static function register_settings()
    {
        add_settings_section(
            'booking_general_settings',
            __('General Settings', 'booking-plugin'),
            null,
            'booking-settings'
        );

        add_settings_field(
            'admin_email_template',
            __('Admin Email Template', 'booking-plugin'),
            function () {
                $value = get_option('admin_email_template', '');
                echo '<textarea name="admin_email_template" rows="10" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                echo '<p class="description">' . __('Use placeholders like {customer_name}, {service_name}, {booking_date}.', 'booking-plugin') . '</p>';
            },
            'booking-settings',
            'booking_general_settings'
        );

        add_settings_field(
            'customer_email_template',
            __('Customer Email Template', 'booking-plugin'),
            function () {
                $value = get_option('customer_email_template', '');
                echo '<textarea name="customer_email_template" rows="10" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                echo '<p class="description">' . __('Use placeholders like {customer_name}, {service_name}, {booking_date}.', 'booking-plugin') . '</p>';
            },
            'booking-settings',
            'booking_general_settings'
        );

        // Register the settings.
        register_setting('booking-settings', 'admin_email_template');
        register_setting('booking-settings', 'customer_email_template');
    }
}

Booking_Settings::init();
