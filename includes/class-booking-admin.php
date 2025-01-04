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

        global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        // Fetch data for stats
        $total_bookings  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $pending_count   = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $confirmed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'confirmed'");
        $canceled_count  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'canceled'");

?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Admin Dashboard', 'booking-plugin')); ?></h1>

            <div class="booking-plugin">
                <div class="dashboard-stats">
                    <h2><?php echo esc_html(__('Booking Statistics', 'booking-plugin')); ?></h2>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                        <!-- Total Bookings -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-gray-700">Total Bookings</h3>
                            <p class="text-3xl font-bold text-blue-600"><?php echo esc_html($total_bookings); ?></p>
                        </div>

                        <!-- Confirmed Bookings -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-gray-700">Confirmed Bookings</h3>
                            <p class="text-3xl font-bold text-green-600"><?php echo esc_html($confirmed_count); ?></p>
                        </div>

                        <!-- Cancelled Bookings -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-gray-700">Cancelled Bookings</h3>
                            <p class="text-3xl font-bold text-red-600"><?php echo esc_html($canceled_count); ?></p>
                        </div>

                        <!-- Pending Bookings -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-gray-700">Pending Bookings</h3>
                            <p class="text-3xl font-bold text-yellow-600"><?php echo esc_html($pending_count); ?></p>
                        </div>

                        <!-- Active Services -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-gray-700">Active Services</h3>
                            <p class="text-3xl font-bold text-indigo-600">15</p>
                        </div>
                    </div>
                </div>


                <div class="wrap">
                    <h1 class="text-2xl font-bold mb-4"><?php _e('Booking Calendar', 'booking-plugin'); ?></h1>
                    <!-- Elemen untuk kalender -->
                    <div id="booking-calendar" class="border border-gray-300 rounded-lg shadow p-4 bg-white"></div>
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
        </div>
<?php
    }
}

Admin_Dashboard::init();
