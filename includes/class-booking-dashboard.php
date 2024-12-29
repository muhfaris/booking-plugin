<?php
if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Booking_List
{
    /**
     * Initialize hooks.
     */
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_dashboard_page']);
    }

    /**
     * Add the dashboard page under Booking.
     */
    public static function add_dashboard_page()
    {
        add_submenu_page(
            'booking-dashboard',  // Parent slug
            __('Booking List', 'booking-plugin'),  // Page title
            __('Booking List', 'booking-plugin'),  // Menu title
            'manage_bookings',  // Capability
            'booking-list',  // Menu slug
            [__CLASS__, 'render_dashboard_page']  // Callback function
        );
    }

    /**
     * Render the dashboard page.
     */
    public static function render_dashboard_page()
    {
        // Fetch filter values from the request.
        $status_filter = isset($_GET['booking_status']) ? sanitize_text_field($_GET['booking_status']) : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Booking List', 'booking-plugin')); ?></h1>

            <!-- Filter Form -->
            <form method="get" action="">
                <input type="hidden" name="page" value="booking-dashboard">
                <label for="booking_status"><?php echo esc_html(__('Filter by Status:', 'booking-plugin')); ?></label>
                <select name="booking_status" id="booking_status">
                    <option value=""><?php echo esc_html(__('All Statuses', 'booking-plugin')); ?></option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php echo esc_html(__('Pending', 'booking-plugin')); ?></option>
                    <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php echo esc_html(__('Confirmed', 'booking-plugin')); ?></option>
                    <option value="canceled" <?php selected($status_filter, 'canceled'); ?>><?php echo esc_html(__('Canceled', 'booking-plugin')); ?></option>
                </select>
                <button type="submit" class="button"><?php echo esc_html(__('Filter', 'booking-plugin')); ?></button>
            </form>

            <!-- Booking Table -->
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
                    global $wpdb;
        $table_name = $wpdb->prefix . 'bookings';

        // Build query with optional filter.
        $query = "SELECT * FROM $table_name";
        if ($status_filter) {
            $query .= $wpdb->prepare(' WHERE status = %s', $status_filter);
        }

        $bookings = $wpdb->get_results($query);

        if (!empty($bookings)) {
            foreach ($bookings as $booking) {
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
        <?php
    }
}

Booking_List::init();
