<?php
if (!defined('ABSPATH')) {
    exit;  // Exit if accessed directly.
}

class Booking_Settings
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_shortcode('booking_service_list', [__CLASS__, 'shortcode_service_list']);
    }

    public static function shortcode_service_list()
    {
        $style = get_option('service_display_style', 'style1');

        global $wpdb;
        $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bookings_services WHERE parent_id IS NULL", ARRAY_A);

        ob_start();
        echo '<div class="service-list ' . esc_attr($style) . '">';

        foreach ($services as $service) {
            switch ($style) {
                case 'style1':
?>
                    <div class="service-item style1">
                        <h3><?php echo esc_html($service['name']); ?></h3>
                        <p><?php echo esc_html($service['description']); ?></p>
                        <p class="price">Rp <?php echo number_format($service['price'], 0, ',', '.'); ?></p>
                    </div>
                <?php
                    break;

                case 'style2':
                ?>
                    <div class="service-item style2">
                        <img src="<?php echo esc_url($service['image']); ?>" alt="<?php echo esc_attr($service['name']); ?>" />
                        <h3><?php echo esc_html($service['name']); ?></h3>
                        <p class="price">Rp <?php echo number_format($service['price'], 0, ',', '.'); ?></p>
                    </div>
                <?php
                    break;

                case 'style3':
                ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <img src="<?php echo esc_url($service['image']); ?>" alt="<?php echo esc_attr($service['name']); ?>" class="w-full h-40 object-cover" />
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($service['name']); ?></h3>
                            <p class="text-gray-500 text-sm"><?php echo esc_html($service['description']); ?></p>
                            <p class="text-lg font-bold text-green-600 mt-2">Rp <?php echo number_format($service['price'], 0, ',', '.'); ?></p>
                        </div>
                    </div>
        <?php
                    break;
            }
        }
        echo '</div>';

        return ob_get_clean();
    }

    public static function add_settings_page()
    {
        add_menu_page(
            __('Booking', 'booking-plugin'),
            __('Booking', 'booking-plugin'),
            'manage_bookings',
            'booking-dashboard',
            ['Admin_Dashboard', 'render_dashboard_page'],
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            'booking-dashboard',
            __('Settings', 'booking-plugin'),
            __('Settings', 'booking-plugin'),
            'manage_bookings',
            'booking-settings',
            [__CLASS__, 'render_settings_page'],
            100
        );
    }

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

    public static function register_settings()
    {
        add_settings_section(
            'booking_general_settings',
            __('General Settings', 'booking-plugin'),
            null,
            'booking-settings'
        );

        add_settings_field(
            'bk_booking_currency',
            __('Currency', 'booking-plugin'),
            function () {
                // Default selected value
                $value = get_option('bk_booking_currency', 'IDR');

                // Include the currencies file
                $currencies = require plugin_dir_path(__FILE__) . '/helper/currencies.php'; // Adjust the path as needed

                // Start the select dropdown
                echo '<select name="bk_booking_currency" class="regular-text select2-currency">';

                // Loop through currency options and create an option tag
                foreach ($currencies as $code => $currency) {
                    $selected = selected($value, $code, false); // Mark the option as selected if it matches the saved value
                    echo "<option value='{$code}' {$selected}>{$currency['name']} ({$code})</option>";
                }

                // End the select dropdown
                echo '</select>';
            },
            'booking-settings',
            'booking_general_settings'
        );

        add_settings_field(
            'bk_booking_admin_email_template',
            __('Admin Email Template', 'booking-plugin'),
            function () {
                $value = get_option('bk_booking_admin_email_template', '');
                echo '<textarea name="bk_booking_admin_email_template" rows="10" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                echo '<p class="description">' . __('Use placeholders like {customer_name}, {service_name}, {booking_date}.', 'booking-plugin') . '</p>';
            },
            'booking-settings',
            'booking_general_settings'
        );

        add_settings_field(
            'bk_booking_customer_email_template',
            __('Customer Email Template', 'booking-plugin'),
            function () {
                $value = get_option('bk_booking_customer_email_template', '');
                echo '<textarea name="bk_booking_customer_email_template" rows="10" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                echo '<p class="description">' . __('Use placeholders like {customer_name}, {service_name}, {booking_date}.', 'booking-plugin') . '</p>';
            },
            'booking-settings',
            'booking_general_settings'
        );

        add_settings_section(
            'bk_booking_service_display_style',
            __('Service Style', 'booking-plugin'),
            function () {
        ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Pilih Gaya Tampilan</th>
                    <td>
                        <select name="bk_booking_service_display_style" id="service_display_style">
                            <option value="style1" <?php selected(get_option('bk_booking_service_display_style'), 'style1'); ?>>Style 1</option>
                            <option value="style2" <?php selected(get_option('bk_booking_service_display_style'), 'style2'); ?>>Style 2</option>
                            <option value="style3" <?php selected(get_option('bk_booking_service_display_style'), 'style3'); ?>>Style 3</option>
                        </select>
                    </td>
                </tr>
            </table>
<?php
            },
            'booking-settings'
        );

        register_setting('booking-settings', 'bk_booking_currency');
        register_setting('booking-settings', 'bk_booking_admin_email_template');
        register_setting('booking-settings', 'bk_booking_customer_email_template');
        register_setting('booking-settings', 'bk_booking_service_display_style');
    }
}

Booking_Settings::init();
