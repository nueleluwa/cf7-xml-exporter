<?php
/**
 * Admin functionality
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper function to count form fields - define it here so it's always available
if (!function_exists('cf7_count_form_fields')) {
    function cf7_count_form_fields($form) {
        $form_content = $form->prop('form');
        $field_count = 0;
        
        // Count common CF7 field types
        $field_types = array('text', 'email', 'tel', 'url', 'number', 'date', 'textarea', 'select', 'checkbox', 'radio', 'file');
        
        foreach ($field_types as $type) {
            $field_count += preg_match_all('/\[' . $type . '[\s\*]/', $form_content);
        }
        
        return $field_count;
    }
}

class CF7_Admin {
    
    /**
     * Form cache
     */
    private $form_cache = array();
    
    /**
     * Constructor
     */
    public function __construct() {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_init', array($this, 'handle_direct_export'));
}
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wpcf7',
            __('Export to XML', 'cf7-xml-exporter'),
            __('Export to XML', 'cf7-xml-exporter'),
            'manage_options',
            'cf7-xml-exporter',
            array($this, 'export_page')
        );
        
        add_submenu_page(
            'wpcf7',
            __('Import from XML', 'cf7-xml-exporter'),
            __('Import from XML', 'cf7-xml-exporter'),
            'manage_options',
            'cf7-xml-importer',
            array($this, 'import_page')
        );
        
        add_submenu_page(
            'wpcf7',
            __('XML Export Settings', 'cf7-xml-exporter'),
            __('XML Settings', 'cf7-xml-exporter'),
            'manage_options',
            'cf7-xml-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        $allowed_pages = array(
            'contact_page_cf7-xml-exporter',
            'contact_page_cf7-xml-importer',
            'contact_page_cf7-xml-settings'
        );
        
        if (!in_array($hook, $allowed_pages)) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'cf7-xml-exporter-js',
            CF7_XML_EXPORTER_PLUGIN_URL . 'assets/js/cf7-xml-exporter.js',
            array('jquery'),
            CF7_XML_EXPORTER_VERSION,
            true
        );
        
        wp_localize_script('cf7-xml-exporter-js', 'cf7ExporterAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_export_nonce'),
            'strings' => array(
                'select_forms' => __('Please select at least one form to export.', 'cf7-xml-exporter'),
                'export_success' => __('Export completed successfully!', 'cf7-xml-exporter'),
                'export_error' => __('Export failed. Please try again.', 'cf7-xml-exporter'),
                'ajax_error' => __('An error occurred during export. Please try again.', 'cf7-xml-exporter')
            )
        ));
        
        wp_enqueue_style(
            'cf7-xml-exporter-css',
            CF7_XML_EXPORTER_PLUGIN_URL . 'assets/css/cf7-xml-exporter.css',
            array(),
            CF7_XML_EXPORTER_VERSION
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('cf7_xml_exporter_options', 'cf7_xml_exporter_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($options) {
        $sanitized = array();
        
        $sanitized['max_export_limit'] = isset($options['max_export_limit']) 
            ? absint($options['max_export_limit']) : 100;
        $sanitized['enable_logging'] = isset($options['enable_logging']) 
            ? (bool) $options['enable_logging'] : false;
        $sanitized['cache_forms'] = isset($options['cache_forms']) 
            ? (bool) $options['cache_forms'] : true;
            
        return $sanitized;
    }
    
    /**
     * Export page
     */
    public function export_page() {
        if (!$this->check_cf7_dependency()) {
            return;
        }
        
        $contact_forms = $this->get_contact_forms();
        include CF7_XML_EXPORTER_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Import page
     */
    public function import_page() {
        if (!$this->check_cf7_dependency()) {
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Import Contact Forms from XML', 'cf7-xml-exporter') . '</h1>';
        echo '<p>' . __('Import functionality will be available in the next version.', 'cf7-xml-exporter') . '</p>';
        echo '</div>';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('XML Export Settings', 'cf7-xml-exporter'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cf7_xml_exporter_options');
                $options = get_option('cf7_xml_exporter_options', array());
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Maximum Export Limit', 'cf7-xml-exporter'); ?></th>
                        <td>
                            <input type="number" name="cf7_xml_exporter_options[max_export_limit]" 
                                   value="<?php echo esc_attr($options['max_export_limit'] ?? 100); ?>" 
                                   min="1" max="1000" />
                            <p class="description"><?php _e('Maximum number of forms that can be exported at once.', 'cf7-xml-exporter'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable Logging', 'cf7-xml-exporter'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cf7_xml_exporter_options[enable_logging]" 
                                       value="1" <?php checked(!empty($options['enable_logging'])); ?> />
                                <?php _e('Enable debug logging for troubleshooting', 'cf7-xml-exporter'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cache Forms', 'cf7-xml-exporter'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cf7_xml_exporter_options[cache_forms]" 
                                       value="1" <?php checked(!empty($options['cache_forms'])); ?> />
                                <?php _e('Cache form data for better performance', 'cf7-xml-exporter'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Check CF7 dependency
     */
    private function check_cf7_dependency() {
        if (!class_exists('WPCF7_ContactForm')) {
            echo '<div class="notice notice-error"><p>' . 
                 __('Contact Form 7 plugin is required for this exporter to work.', 'cf7-xml-exporter') . 
                 '</p></div>';
            return false;
        }
        return true;
    }
    
    /**
     * Get all Contact Form 7 forms with caching
     */
    public function get_contact_forms() {
        if (!class_exists('WPCF7_ContactForm')) {
            return array();
        }
        
        $options = get_option('cf7_xml_exporter_options', array());
        $cache_enabled = !empty($options['cache_forms']);
        
        if ($cache_enabled && !empty($this->form_cache)) {
            return $this->form_cache;
        }
        
        $args = array(
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $forms = array();
        $posts = get_posts($args);
        
        foreach ($posts as $post) {
            $form = WPCF7_ContactForm::get_instance($post->ID);
            if ($form) {
                $forms[] = $form;
            }
        }
        
        if ($cache_enabled) {
            $this->form_cache = $forms;
        }
        
        return $forms;
    }

    /**
 * Handle direct export (non-AJAX fallback)
 */
public function handle_direct_export() {
    if (!isset($_POST['export_xml']) || !wp_verify_nonce($_POST['cf7_export_nonce'] ?? '', 'cf7_export_action')) {
        return;
    }
    
    $export_handler = new CF7_Export_Handler();
    $export_handler->handle_direct_export();
}
    
    /**
     * Get single contact form with caching
     */
    public function get_contact_form($id) {
        $options = get_option('cf7_xml_exporter_options', array());
        $cache_enabled = !empty($options['cache_forms']);
        
        if ($cache_enabled && isset($this->form_cache[$id])) {
            return $this->form_cache[$id];
        }
        
        $form = WPCF7_ContactForm::get_instance($id);
        
        if ($cache_enabled && $form) {
            $this->form_cache[$id] = $form;
        }
        
        return $form;
    }
}
?>