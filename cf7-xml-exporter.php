<?php
/**
 * Plugin Name: Contact Form 7 XML Exporter Pro
 * Plugin URI: https://brela.agency
 * Description: Advanced XML export functionality for Contact Form 7 forms with import capabilities
 * Version: 2.0.0
 * Author: Brela Agency
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Text Domain: cf7-xml-exporter
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF7_XML_EXPORTER_VERSION', '2.0.0');
define('CF7_XML_EXPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7_XML_EXPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF7_XML_EXPORTER_BASENAME', plugin_basename(__FILE__));

// Load the main plugin class
require_once CF7_XML_EXPORTER_PLUGIN_DIR . 'includes/class-cf7-xml-exporter.php';

// Initialize the plugin
function cf7_xml_exporter_init() {
    new CF7_XML_Exporter();
}
add_action('plugins_loaded', 'cf7_xml_exporter_init');

// Activation hook
register_activation_hook(__FILE__, 'cf7_xml_exporter_activate');
function cf7_xml_exporter_activate() {
    if (!class_exists('WPCF7_ContactForm')) {
        deactivate_plugins(CF7_XML_EXPORTER_BASENAME);
        wp_die(__('Contact Form 7 XML Exporter requires Contact Form 7 plugin to be installed and activated.', 'cf7-xml-exporter'));
    }
    
    // Create necessary database tables or options
    add_option('cf7_xml_exporter_version', CF7_XML_EXPORTER_VERSION);
    
    // Set default options
    $default_options = array(
        'max_export_limit' => 100,
        'enable_logging' => false,
        'cache_forms' => true
    );
    add_option('cf7_xml_exporter_options', $default_options);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'cf7_xml_exporter_deactivate');
function cf7_xml_exporter_deactivate() {
    // Clean up temporary files
    wp_clear_scheduled_hook('cf7_xml_exporter_cleanup');
}
?>