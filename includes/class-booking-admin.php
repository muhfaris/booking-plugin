<?php

if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Admin_Dashboard
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
    }

    public static function add_admin_menu()
    {
        add_submenu_page(
            'booking-dashboard',  // Parent slug
            __('Admin Dashboard', 'booking-plugin'),  // Page title
            __('Dashboard', 'booking-plugin'),  // Menu title
            'manage_bookings',  // Capability
            'booking-dashboard',  // Menu slug
            [__CLASS__, 'render_dashboard_page']  // Callback function
        );
    }

    /**
     * Render the Admin Dashboard page.
     */
    public static function render_dashboard_page()
    {
        if (!current_user_can('manage_bookings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'booking-plugin'));
        }

        // Enqueue custom admin CSS
        wp_enqueue_style('admin-dashboard-css', plugin_dir_url(__FILE__) . '../assets/css/admin-dashboard.css');

        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        // Fetch data for stats
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $confirmed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'confirmed'");
        $canceled_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'canceled'");

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Admin Dashboard', 'booking-plugin')); ?></h1>
            
            <div class="dashboard-stats">
                <h2><?php echo esc_html(__('Booking Statistics', 'booking-plugin')); ?></h2>
                <ul>
                    <li><?php echo esc_html(__('Total Bookings:', 'booking-plugin')); ?> <strong><?php echo esc_html($total_bookings); ?></strong></li>
                    <li><?php echo esc_html(__('Pending Bookings:', 'booking-plugin')); ?> <strong><?php echo esc_html($pending_count); ?></strong></li>
                    <li><?php echo esc_html(__('Confirmed Bookings:', 'booking-plugin')); ?> <strong><?php echo esc_html($confirmed_count); ?></strong></li>
                    <li><?php echo esc_html(__('Canceled Bookings:', 'booking-plugin')); ?> <strong><?php echo esc_html($canceled_count); ?></strong></li>
                </ul>
            </div>

            <div class="recent-bookings">
                <h2><?php echo esc_html(__('Recent Bookings', 'booking-plugin')); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(__('ID', 'booking-plugin')); ?></th>
                            <th><?php echo esc_html(__('Customer Name', 'booking-plugin')); ?></th>
                            <th><?php echo esc_html(__('Service', 'booking-plugin')); ?></th>
                            <th><?php echo esc_html(__('Date', 'booking-plugin')); ?></th>
                            <th><?php echo esc_html(__('Status', 'booking-plugin')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $recent_bookings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY booking_date DESC LIMIT 5");

        if (!empty($recent_bookings)) {
            foreach ($recent_bookings as $booking) {
                echo '<tr>';
                echo '<td>' . esc_html($booking->id) . '</td>';
                echo '<td>' . esc_html($booking->customer_name) . '</td>';
                echo '<td>' . esc_html($booking->service_name) . '</td>';
                echo '<td>' . esc_html($booking->booking_date) . '</td>';
                echo '<td>' . esc_html(ucfirst($booking->status)) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html(__('No bookings found.', 'booking-plugin')) . '</td></tr>';
        }
        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}

Admin_Dashboard::init();
