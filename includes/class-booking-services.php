<?php

use Booking_Plugin\Booking_Query as Booking_Query;

class Booking_Services
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_services_menu']);
        add_action('admin_init', [$this, 'handle_service_actions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    // Fungsi untuk menangani aksi tambah/edit/hapus layanan
    public function handle_service_actions()
    {
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'delete' && isset($_GET['service_id'])) {
                $this->delete_service(intval($_GET['service_id']));
            }
        }
    }

    // Add admin styles
    public function enqueue_admin_styles($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'booking-manage_services') === false) {
            return;
        }

        wp_enqueue_style('wp-admin');
        wp_enqueue_style('buttons');
        wp_enqueue_style('forms');
        wp_enqueue_style('l10n');
        wp_enqueue_style('lists');
    }

    public function add_services_menu()
    {
        add_submenu_page(
            'booking-dashboard',
            __('Manage Services', 'booking-plugin'),
            __('Manage Services', 'booking-plugin'),
            'manage_bookings',
            'booking-manage_services',
            [$this, 'display_services_page'],
            3
        );
    }

    public function display_services_page()
    {
        // Check for specific action
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        // If action is add or edit, display form
        if ($action === 'add' || $action === 'edit') {
            $this->display_service_form();

            return;
        }

        // Otherwise display the services list
        $services = Booking_Query::get_services_with_cache();


?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Manage Services', 'booking-plugin'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=booking-manage_services&action=add')); ?>" class="page-title-action">
                <?php echo esc_html__('Add New Service', 'booking-plugin'); ?>
            </a>
            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Image', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Service Name', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Description', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Price', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'booking-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service) { ?>
                        <tr>
                            <td>
                                <?php if (!empty($service->image_url)) { ?>
                                    <img src="<?php echo esc_url($service->image_url); ?>" alt="<?php echo esc_attr($service->service_name); ?>" style="max-width: 100px;">
                                <?php } else { ?>
                                    <span><?php esc_html_e('No Image', 'booking-plugin'); ?></span>
                                <?php } ?>
                            </td>
                            <td><strong><?php echo esc_html($service->parent_id ? "-- " . $service->service_name : $service->service_name); ?></strong></strong></td>
                            <td><?php echo esc_html($service->description); ?></td>
                            <td><?php echo esc_html($service->price); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=booking-manage_services&action=edit&service_id=' . $service->id)); ?>"
                                    class="button button-secondary">
                                    <?php echo esc_html__('Edit', 'booking-plugin'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=booking-manage_services&action=delete&service_id=' . $service->id)); ?>"
                                    class="button button-secondary"
                                    onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this service?', 'booking-plugin')); ?>');">
                                    <?php echo esc_html__('Delete', 'booking-plugin'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php
    }

    public function display_service_form()
    {
        // Get service data if editing
        $service = null;

        if (isset($_GET['service_id'])) {
            $service = Booking_Query::get_service_with_cache(intval($_GET['service_id']));
        }

        $services = Booking_Query::get_services_with_cache(['parent_id' => 0]);


        // Save logic
        if (isset($_POST['save_service'])) {

            // Verify nonce
            if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'save_service')) {
                wp_die(__('Security check failed.', 'booking-plugin'));
            }

            // Verify user permissions
            if (!current_user_can('manage_bookings')) {
                wp_die(__('You do not have permission to perform this action.', 'booking-plugin'));
            }

            $service_name = sanitize_text_field($_POST['service_name']);
            $description  = sanitize_textarea_field($_POST['description']);
            $price        = floatval($_POST['price']);
            $image_url    = sanitize_text_field($_POST['image_url']);
            $parent_id    = intval($_POST['parent_id']);

            if ($service) {
                Booking_Query::update_service($service->id, compact('service_name', 'description', 'price', 'image_url', 'parent_id'));
            } else {
                Booking_Query::create_service(compact('service_name', 'description', 'price', 'image_url', 'parent_id'));
            }

            //wp_redirect(admin_url('admin.php?page=booking-manage_services'));
            exit;
        }

        ob_start();
    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo $service ? esc_html__('Edit Service', 'booking-plugin') : esc_html__('Add New Service', 'booking-plugin'); ?>
            </h1>
            <hr class="wp-header-end">

            <form method="post" action="" class="booking-service-form">
                <?php wp_nonce_field('save_service', 'service_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="service_name"><?php echo esc_html__('Service Name', 'booking-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                id="service_name"
                                name="service_name"
                                class="regular-text"
                                value="<?php echo esc_attr($service ? $service->service_name : ''); ?>"
                                required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="parent_id"><?php echo esc_html__('Parent Service', 'booking-plugin'); ?></label>
                        </th>
                        <td>
                            <select name="parent_id" class="regular-text select2-currency">;
                                <option value="">-- None --</option>
                                <?php foreach ($services as $parent_service) { ?>
                                    <option value="<?php echo esc_attr($parent_service->id); ?>" <?php selected($parent_service->id, $service ? $service->parent_id : ''); ?>>
                                        <?php echo esc_html($parent_service->service_name); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description"><?php echo esc_html__('Description', 'booking-plugin'); ?></label>
                        </th>
                        <td>
                            <textarea id="description"
                                name="description"
                                class="large-text"
                                rows="5"><?php echo esc_textarea($service ? $service->description : ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="image"><?php esc_html_e('Image', 'booking-plugin'); ?></label></th>
                        <td>
                            <img id="wk-media-url" name="image_url" src="<?php echo esc_url($service && $service->image_url ? $service->image_url : ''); ?>" alt="Image">
                            <input type="hidden" id="wk-image-url" name="image_url" value="<?php echo esc_attr($service && $service->image_id ? $service->image_id : ''); ?>">
                            <input id="wk-button" type="button" class="button" value="Upload Image" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="price"><?php echo esc_html__('Price', 'booking-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                id="price"
                                name="price"
                                class="regular-text"
                                step="0.01"
                                value="<?php echo esc_attr($service ? $service->price : ''); ?>">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit"
                        name="save_service"
                        class="button button-primary"
                        value="<?php echo $service ? esc_attr__('Update Service', 'booking-plugin') : esc_attr__('Add Service', 'booking-plugin'); ?>">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=booking-manage_services')); ?>"
                        class="button button-secondary">
                        <?php echo esc_html__('Cancel', 'booking-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
<?php
        echo ob_get_clean();
    }

    // Fungsi untuk menghapus layanan
    public function delete_service($service_id)
    {
        Booking_Query::delete_service($service_id);
        // Redirect ke halaman layanan
        wp_redirect(admin_url('admin.php?page=booking-manage_services'));
        exit;
    }
}

new Booking_Services();
