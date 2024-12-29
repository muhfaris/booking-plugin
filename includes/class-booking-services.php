<?php
class Booking_Services
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_services_menu'));
        add_action('admin_init', array($this, 'handle_service_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
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
    public function enqueue_admin_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'manage_services') === false) {
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
            'manage_services',
            array($this, 'display_services_page')
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
        global $wpdb;
        $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}booking_services");
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Manage Services', 'booking-plugin'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=manage_services&action=add')); ?>" class="page-title-action">
                <?php echo esc_html__('Add New Service', 'booking-plugin'); ?>
            </a>
            <hr class="wp-header-end">
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Service Name', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Description', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Price', 'booking-plugin'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'booking-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo esc_html($service->service_name); ?></td>
                            <td><?php echo esc_html($service->description); ?></td>
                            <td><?php echo esc_html($service->price); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=manage_services&action=edit&service_id=' . $service->id)); ?>" 
                                   class="button button-secondary">
                                    <?php echo esc_html__('Edit', 'booking-plugin'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=manage_services&action=delete&service_id=' . $service->id)); ?>" 
                                   class="button button-secondary" 
                                   onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this service?', 'booking-plugin')); ?>');">
                                    <?php echo esc_html__('Delete', 'booking-plugin'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function display_service_form()
    {
        global $wpdb;
        
        // Get service data if editing
        $service = null;
        if (isset($_GET['service_id'])) {
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}booking_services WHERE id = %d",
                intval($_GET['service_id'])
            ));
        }

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
                        <th scope="row">
                            <label for="price"><?php echo esc_html__('Price', 'booking-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   class="regular-text" 
                                   step="0.01" 
                                   value="<?php echo esc_attr($service ? $service->price : ''); ?>" 
                                   required>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" 
                           name="save_service" 
                           class="button button-primary" 
                           value="<?php echo $service ? esc_attr__('Update Service', 'booking-plugin') : esc_attr__('Add Service', 'booking-plugin'); ?>">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=manage_services')); ?>" 
                       class="button button-secondary">
                        <?php echo esc_html__('Cancel', 'booking-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
        // Save logic
        if (isset($_POST['save_service'])) {
            $service_name = sanitize_text_field($_POST['service_name']);
            $description = sanitize_textarea_field($_POST['description']);
            $price = floatval($_POST['price']);

            if ($service) {
                $wpdb->update("{$wpdb->prefix}booking_services", compact('service_name', 'description', 'price'), ['id' => $service->id]);
            } else {
                $wpdb->insert("{$wpdb->prefix}booking_services", compact('service_name', 'description', 'price'));
            }

            wp_redirect(admin_url('admin.php?page=manage_services'));
            exit;
        }

    }

    // Fungsi untuk menghapus layanan
    public function delete_service($service_id)
    {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}booking_services", array('id' => $service_id));

        // Redirect ke halaman layanan
        wp_redirect(admin_url('admin.php?page=manage_services'));
        exit;
    }

}

new Booking_Services();
