<?php

if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Booking_Admin
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
    }

    public static function add_admin_menu()
    {
        add_menu_page(
            __('Booking Dashboard', 'booking-plugin'),
            __('Booking', 'booking-plugin'),
            'manage_options',
            'booking-dashboard',
            [__CLASS__, 'render_admin_dashboard'],
            'dashicons-calendar-alt'
        );
    }

    public static function render_admin_dashboard()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking';

        // Handle delete action.
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $wpdb->delete($table_name, ['id' => $id]);
            echo '<p style="color: green;">' . esc_html__('Booking deleted successfully.', 'booking-plugin') . '</p>';
        }

        // Handle export action.
        if (isset($_GET['action']) && $_GET['action'] === 'export') {
            self::export_bookings_to_csv();
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        echo '<h1>' . esc_html__('Booking Dashboard', 'booking-plugin') . '</h1>';
        echo '<a href="?page=booking-dashboard&action=export" class="button-primary">' . esc_html__('Export to CSV', 'booking-plugin') . '</a>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>
            <tr>
                <th>' . esc_html__('Service Name', 'booking-plugin') . '</th>
                <th>' . esc_html__('Customer Name', 'booking-plugin') . '</th>
                <th>' . esc_html__('Email', 'booking-plugin') . '</th>
                <th>' . esc_html__('Booking Date', 'booking-plugin') . '</th>
                <th>' . esc_html__('Created At', 'booking-plugin') . '</th>
            </tr>
          </thead>';
        echo '<tbody>';
        if (!empty($results)) {
            foreach ($results as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['service_name']) . '</td>';
                echo '<td>' . esc_html($row['customer_name']) . '</td>';
                echo '<td>' . esc_html($row['customer_email']) . '</td>';
                echo '<td>' . esc_html($row['booking_date']) . '</td>';
                echo '<td>' . esc_html($row['created_at']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__('No bookings found.', 'booking-plugin') . '</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    private static function export_bookings_to_csv()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'booking';

        // Fetch all bookings.
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        if (empty($results)) {
            wp_die(__('No data available for export.', 'booking-plugin'));
        }

        // Set headers for CSV download.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bookings.csv');

        // Open output stream.
        $output = fopen('php://output', 'w');

        // Add CSV header row.
        fputcsv($output, ['ID', 'Service Name', 'Customer Name', 'Email', 'Booking Date', 'Created At']);

        // Add data rows.
        foreach ($results as $row) {
            fputcsv(
                $output,
                [
                $row['id'],
                $row['service_name'],
                $row['customer_name'],
                $row['customer_email'],
                $row['booking_date'],
                $row['created_at'],
                ]
            );
        }

        fclose($output);
        exit;
    }
}
