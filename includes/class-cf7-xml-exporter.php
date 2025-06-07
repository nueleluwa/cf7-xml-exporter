<?php
/**
 * Main plugin class
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CF7_XML_Exporter {
    
    /**
     * Plugin version
     */
    const VERSION = CF7_XML_EXPORTER_VERSION;
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * Export handler instance
     */
    public $export_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_init', array($this, 'check_dependencies'));
        add_action('wp_ajax_cf7_export_xml', array($this, 'handle_ajax_export'));
        add_action('cf7_xml_exporter_cleanup', array($this, 'cleanup_temp_files'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('cf7_xml_exporter_cleanup')) {
            wp_schedule_event(time(), 'daily', 'cf7_xml_exporter_cleanup');
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once CF7_XML_EXPORTER_PLUGIN_DIR . 'includes/class-cf7-validator.php';
        require_once CF7_XML_EXPORTER_PLUGIN_DIR . 'includes/class-cf7-xml-generator.php';
        require_once CF7_XML_EXPORTER_PLUGIN_DIR . 'includes/class-cf7-export-handler.php';
        require_once CF7_XML_EXPORTER_PLUGIN_DIR . 'includes/class-cf7-admin.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        $this->admin = new CF7_Admin();
        $this->export_handler = new CF7_Export_Handler();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'cf7-xml-exporter',
            false,
            dirname(CF7_XML_EXPORTER_BASENAME) . '/languages'
        );
    }
    
    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!class_exists('WPCF7_ContactForm')) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            return false;
        }
        return true;
    }
    
    /**
     * Show dependency notice
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Contact Form 7 XML Exporter requires Contact Form 7 plugin to be installed and activated.', 'cf7-xml-exporter'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX export request
     */
    public function handle_ajax_export() {
        $this->export_handler->handle_ajax_request();
    }
    
    /**
     * Cleanup temporary files
     */
    public function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/cf7-xml-exports/';
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '*.xml');
            $now = time();
            
            foreach ($files as $file) {
                if (filemtime($file) < $now - (24 * 60 * 60)) { // 24 hours old
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Get plugin options
     */
    public function get_options() {
        return get_option('cf7_xml_exporter_options', array());
    }
    
    /**
     * Log message if logging is enabled
     */
    public function log($message, $level = 'info') {
        $options = $this->get_options();
        if (!empty($options['enable_logging'])) {
            error_log(sprintf('[CF7 XML Exporter] [%s] %s', strtoupper($level), $message));
        }
    }
}
?>