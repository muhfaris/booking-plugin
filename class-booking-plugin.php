<?php

/**
 * Plugin Name: Booking Plugin
 * Plugin URI: https://bahaskode.web.id/tools/booking-plugin
 * Description: Plugin sistem booking untuk layanan kecantikan.
 * Version: 1.0.0
 * Author: Muh Faris
 * Author URI: https://muhfaris.com/
 * License: GPLv2 or later
 * Text Domain: booking-plugin
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @category Booking
 *
 * @author  Muh Faris <me@muhfaris.com>
 * @license GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @see   https://muhfaris.com/
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access is not allowed.');
}

/**
 * Main Plugin Class
 */
final class BK_Booking_Plugin
{

    /**
     * Plugin version
     *
     * @var string
     */
    public const VERSION = '1.0.0';

    /**
     * Plugin instance
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return self
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->define_constants();
        $this->include_files();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants()
    {
        define('BOOKING_PLUGIN_VERSION', self::VERSION);
        define('BOOKING_PLUGIN_FILE', __FILE__);
        define('BOOKING_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('BOOKING_PLUGIN_BASENAME', plugin_basename(__FILE__));
        define('BOOKING_PREFIX_QUERY', 'bk_booking_service_');
        define('OWNER_BOOKING_ROLE', 'owner_booking');
        define('ADMIN_BOOKING_ROLE', 'admin_booking');
    }

    /**
     * Include required files
     */
    private function include_files()
    {
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-plugin.php';
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-admin.php';
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-settings.php';
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-list.php';
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-services.php';
        include_once BOOKING_PLUGIN_PATH . 'includes/class-booking-assets.php';
        include_once BOOKING_PLUGIN_PATH . 'includes/queries/class-booking-service-query.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Initialize plugin after WordPress loads
        add_action('plugins_loaded', [$this, 'init_plugin']);

        // Admin specific hooks
        if (is_admin()) {
            add_action('admin_init', [$this, 'restrict_admin_access']);
            add_action('admin_menu', [$this, 'disable_sidebar_for_owner'], 999);
        }

        // Admin scripts
        add_action('admin_enqueue_scripts', [$this, 'calendar_scripts'], 10);

        // Login redirect
        add_filter('login_redirect', [$this, 'redirect_owner_to_profile'], 10, 3);

        // Admin bar
        // Add this with priority 999 to run after other plugins
        add_filter('show_admin_bar', [$this, 'hide_admin_bar_for_owner'], 999);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Add the media upload script
        add_action('admin_enqueue_scripts', [$this, 'wk_enqueue_script']);

        // Add the select2 script
        add_action('admin_enqueue_scripts', [$this, 'select2_enqueue_assets']);

        // Initialize the assets class
        new Booking_Plugin_Assets();
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        $this->create_tables();
        $this->setup_roles();

        // Flush rewrite rules
        // When registering new custom post types
        // When adding new rewrite endpoints
        // When modifying permalink structures
        // When activating/deactivating a plugin that affects URLs
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables()
    {
        global $wpdb;
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Bookings table
        $sql_bookings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bookings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_name VARCHAR(255) NOT NULL,
            service_description VARCHAR(255) NOT NULL,
            service_price DECIMAL(10,2) NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            booking_date DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Services table
        $sql_services = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bookings_services (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_id BIGINT(20) UNSIGNED NULL,
            service_name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Create index bookings-services
        $sql_services .= "CREATE INDEX idx_bookings_services_parent_id ON {$wpdb->prefix}bookings_services (parent_id);";

        dbDelta($sql_bookings);
        dbDelta($sql_services);
    }

    /**
     * Setup user roles and capabilities
     */
    private function setup_roles()
    {
        // Admin capabilities
        $admin_role = get_role('administrator');

        if ($admin_role) {
            // Add the 'manage_bookings' capability to administrators
            $admin_role->add_cap('manage_bookings');

            // Clone the administrator's capabilities for the owner role
            $admin_capabilities = $admin_role->capabilities;

            // Ensure the 'owner_booking' role exists or create it
            $owner_role = get_role(OWNER_BOOKING_ROLE) ?: add_role(
                OWNER_BOOKING_ROLE,
                __('Owner Booking', 'booking-plugin'),
                $admin_capabilities
            );

            if ($owner_role) {
                // Synchronize the owner role's capabilities with the administrator's
                foreach ($admin_capabilities as $capability => $value) {
                    if ($value) {
                        $owner_role->add_cap($capability);
                    } else {
                        $owner_role->remove_cap($capability);
                    }
                }
            }
        }

        // Customer role (optional)
        $customer_role = get_role('customer');

        if ($customer_role) {
            $customer_role->add_cap('make_bookings');
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clean up if needed
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init_plugin()
    {
        // Load text domain
        load_plugin_textdomain('booking-plugin', false, dirname(BOOKING_PLUGIN_BASENAME) . '/languages');

        // Initialize main plugin class
        BK_Booking_Plugin::get_instance();
    }

    /**
     * Restrict admin access
     */
    public function restrict_admin_access()
    {
        if (is_admin() && !current_user_can('manage_bookings') && !current_user_can('administrator')) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    /**
     * Redirect owner after login
     */
    public function redirect_owner_to_profile($redirect_to, $request, $user)
    {
        if (!($user instanceof WP_User)) {
            return $redirect_to;
        }

        if (in_array(OWNER_BOOKING_ROLE, (array) $user->roles, true)) {
            return admin_url('admin.php?page=booking-dashboard');
        }

        return $redirect_to;
    }

    /**
     * Disable sidebar for owner
     */
    public function disable_sidebar_for_owner()
    {
        if (!current_user_can(OWNER_BOOKING_ROLE)) {
            return;
        }

        $remove_menus = [
            'index.php',
            'edit.php',
            'upload.php',
            'edit.php?post_type=page',
            'edit-comments.php',
            'themes.php',
            'plugins.php',
            'users.php',
            'tools.php',
            'options-general.php',
        ];

        foreach ($remove_menus as $menu) {
            remove_menu_page($menu);
        }
    }

    /**
     * Hide admin bar for owner
     */
    public function hide_admin_bar_for_owner($show)
    {
        // Get the current user
        $current_user = wp_get_current_user();

        // List of roles for which the toolbar should be hidden
        $roles_to_hide_toolbar = [OWNER_BOOKING_ROLE, ADMIN_BOOKING_ROLE]; // Replace with your desired roles

        // Check if the user has any of the roles
        foreach ($roles_to_hide_toolbar as $role) {
            if (in_array($role, $current_user->roles, true)) {
                // Get the user's meta value for toolbar preference
                if ($show_toolbar = get_user_meta($current_user, 'show_admin_bar_front', true)) {
                    return false;
                }

                return false; // Hide the toolbar
            }
        }

        return $show;
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        register_rest_route(
            'booking-plugin/v1',
            '/confirmed-bookings',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_confirmed_bookings'],
                'permission_callback' => function () {
                    $can_manage   = current_user_can('manage_bookings');
                    $current_user = wp_get_current_user();
                    error_log('User ID: ' . $current_user->ID);
                    error_log('User Roles: ' . implode(', ', $current_user->roles));
                    error_log('User Capabilities: ' . json_encode($current_user->allcaps));
                    error_log('Permission check (manage_bookings): ' . ($can_manage ? 'passed' : 'failed'));

                    return current_user_can('manage_bookings');
                },
            ]
        );
    }

    /**
     * Get confirmed bookings for REST API
     */
    public function get_confirmed_bookings()
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT service_name, booking_date FROM {$wpdb->prefix}bookings WHERE status = %s",
                'confirmed'
            )
        );

        return array_map(
            function ($row) {
                return [
                    'title' => sanitize_text_field($row->service_name),
                    'start' => sanitize_text_field($row->booking_date),
                ];
            },
            $results
        );
    }

    /**
     * Admin scripts and styles
     */
    public function calendar_scripts($hook)
    {
        // Calendar page scripts
        if (strpos($hook, 'booking-dashboard') !== false) {
            error_log(BOOKING_PLUGIN_URL . 'assets/js/booking-calendar-init.js');

            wp_register_script(
                'fullcalendar-js',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
                '5.11.3',
                true
            );

            wp_enqueue_script(
                'booking-calendar-init',
                BOOKING_PLUGIN_URL . 'assets/js/booking-calendar-init.js',
                ['jquery', 'fullcalendar-js'],
                self::VERSION,
                true
            );

            // Add REST API support
            wp_localize_script(
                'booking-calendar-init',
                'wpApiSettings',
                [
                    'root'  => esc_url_raw(rest_url()),
                    'nonce' => wp_create_nonce('wp_rest'),
                ]
            );
        }
    }

    /* Add the media upload script */
    public function wk_enqueue_script()
    {
        //Enqueue media.
        wp_enqueue_media();
        // Enqueue custom js file.
        wp_register_script('wk-admin-script', plugins_url(__FILE__), ['jquery']);
        wp_enqueue_script('wk-admin-script');
        wp_enqueue_script('wk-media.js', plugins_url('/assets/js/wk-media.js', __FILE__), ['jquery'], '1.0', true);
    }

    public static function select2_enqueue_assets()
    {
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', ['jquery'], null, true);

        // Inline script to initialize Select2
        wp_add_inline_script('select2-js', "
        jQuery(document).ready(function($) {
            $('.select2-currency').select2({
                placeholder: 'Select a currency', // Placeholder text
                allowClear: true // Allow clearing selection
            });
        });
    ");
    }
}

// Initialize plugin
function bk_booking_plugin()
{
    return BK_Booking_Plugin::get_instance();
}

// Start the plugin
bk_booking_plugin();
