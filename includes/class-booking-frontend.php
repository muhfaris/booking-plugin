<?php
if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Booking_Frontend
{
    public static function init()
    {
        add_shortcode('booking_form', [__CLASS__, 'render_booking_form']);
    }

    public static function render_booking_form()
    {
        ob_start();

        if (isset($_POST['booking_submit'])) {
            self::handle_booking_submission();
        }

        ?>
    <form method="post">
        <label for="service_name"><?php esc_html_e('Service Name', 'booking-plugin'); ?></label>
        <input type="text" id="service_name" name="service_name" required>

        <label for="customer_name"><?php esc_html_e('Your Name', 'booking-plugin'); ?></label>
        <input type="text" id="customer_name" name="customer_name" required>

        <label for="customer_email"><?php esc_html_e('Your Email', 'booking-plugin'); ?></label>
        <input type="email" id="customer_email" name="customer_email" required>

        <label for="booking_date"><?php esc_html_e('Booking Date', 'booking-plugin'); ?></label>
        <input type="datetime-local" id="booking_date" name="booking_date" required>

        <button type="submit" name="booking_submit"><?php esc_html_e('Book Now', 'booking-plugin'); ?></button>
    </form>
        <?php

        return ob_get_clean();
    }

    private static function handle_booking_submission()
    {
        if (!isset($_POST['booking_submit'])) {
            return;
        }

        global $wpdb;

        // Sanitize inputs.
        $service_name = sanitize_text_field($_POST['service_name']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $booking_date = sanitize_text_field($_POST['booking_date']);

        // Validate inputs.
        $errors = [];

        if (empty($service_name)) {
            $errors[] = __('Service Name is required.', 'booking-plugin');
        }

        if (empty($customer_name)) {
            $errors[] = __('Your Name is required.', 'booking-plugin');
        }

        if (empty($customer_email) || !is_email($customer_email)) {
            $errors[] = __('A valid Email is required.', 'booking-plugin');
        }

        if (empty($booking_date) || !strtotime($booking_date)) {
            $errors[] = __('A valid Booking Date is required.', 'booking-plugin');
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p style="color: red;">' . esc_html($error) . '</p>';
            }
            return;
        }

        // Insert into database.
        $table_name = $wpdb->prefix . 'booking';
        $wpdb->insert(
            $table_name,
            [
                'service_name' => $service_name,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'booking_date' => $booking_date,
            ]
        );

        echo '<p style="color: green;">' . esc_html__('Thank you for your booking!', 'booking-plugin') . '</p>';

        // Get templates from settings.
        $admin_template = get_option('admin_email_template', '');
        $customer_template = get_option('customer_email_template', '');

        // Prepare email content with placeholders.
        $admin_message = str_replace(
            ['{customer_name}', '{service_name}', '{booking_date}'],
            [$customer_name, $service_name, $booking_date],
            $admin_template
        );

        $customer_message = str_replace(
            ['{customer_name}', '{service_name}', '{booking_date}'],
            [$customer_name, $service_name, $booking_date],
            $customer_template
        );

        // Send email to admin.
        wp_mail(
            $admin_email,
            __('New Booking Received', 'booking-plugin'),
            $admin_message
        );

        // Send email to customer.
        wp_mail(
            $customer_email,
            __('Your Booking Confirmation', 'booking-plugin'),
            $customer_message
        );

        // Send email notification.
        $admin_email = get_option('admin_email');
    }
}
