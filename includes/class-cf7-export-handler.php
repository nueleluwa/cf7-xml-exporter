<?php
/**
 * Export handler
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CF7_Export_Handler {
    
    /**
     * XML Generator instance
     */
    private $xml_generator;
    
    /**
     * Validator instance
     */
    private $validator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->xml_generator = new CF7_XML_Generator();
        $this->validator = new CF7_Validator();
    }
    
    /**
     * Handle AJAX export request
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cf7_export_nonce')) {
            wp_send_json_error(__('Security check failed.', 'cf7-xml-exporter'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'cf7-xml-exporter'));
        }
        
        try {
            $selected_forms = $this->validator->validate_form_ids($_POST['selected_forms'] ?? array());
            $include_submissions = !empty($_POST['include_submissions']);
            $include_settings = !empty($_POST['include_settings']);
            
            if (empty($selected_forms)) {
                wp_send_json_error(__('No valid forms selected for export.', 'cf7-xml-exporter'));
            }
            
            // Check export limit
            $options = get_option('cf7_xml_exporter_options', array());
            $max_limit = $options['max_export_limit'] ?? 100;
            
            if (count($selected_forms) > $max_limit) {
                wp_send_json_error(sprintf(
                    __('Export limit exceeded. Maximum %d forms allowed per export.', 'cf7-xml-exporter'),
                    $max_limit
                ));
            }
            
            // Increase memory limit for large exports
            $this->increase_memory_limit(count($selected_forms));
            
            $xml_content = $this->xml_generator->generate($selected_forms, $include_submissions, $include_settings);
            $filename = $this->generate_filename(count($selected_forms));
            
            // Log successful export
            CF7_XML_Exporter::get_instance()->log(sprintf(
                'Exported %d forms: %s',
                count($selected_forms),
                implode(', ', $selected_forms)
            ));
            
            wp_send_json_success(array(
                'xml' => $xml_content,
                'filename' => $filename,
                'form_count' => count($selected_forms)
            ));
            
        } catch (Exception $e) {
            CF7_XML_Exporter::get_instance()->log('Export failed: ' . $e->getMessage(), 'error');
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle direct export (non-AJAX)
     */
    public function handle_direct_export() {
        if (!wp_verify_nonce($_POST['cf7_export_nonce'] ?? '', 'cf7_export_action')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'cf7-xml-exporter'));
        }
        
        try {
            $selected_forms = $this->validator->validate_form_ids($_POST['selected_forms'] ?? array());
            $include_submissions = !empty($_POST['include_submissions']);
            $include_settings = !empty($_POST['include_settings']);
            
            if (empty($selected_forms)) {
                throw new Exception(__('No valid forms selected for export.', 'cf7-xml-exporter'));
            }
            
            $this->increase_memory_limit(count($selected_forms));
            
            $xml_content = $this->xml_generator->generate($selected_forms, $include_submissions, $include_settings);
            $filename = $this->generate_filename(count($selected_forms));
            
            $this->serve_file($xml_content, $filename);
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>' . 
                     sprintf(__('Export failed: %s', 'cf7-xml-exporter'), esc_html($e->getMessage())) . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Increase memory limit based on export size
     */
    private function increase_memory_limit($form_count) {
        if ($form_count > 50) {
            ini_set('memory_limit', '1024M');
            set_time_limit(300); // 5 minutes
        } elseif ($form_count > 20) {
            ini_set('memory_limit', '512M');
            set_time_limit(120); // 2 minutes
        } elseif ($form_count > 10) {
            ini_set('memory_limit', '256M');
            set_time_limit(60); // 1 minute
        }
    }
    
    /**
     * Generate filename for export
     */
    private function generate_filename($form_count) {
        $site_name = sanitize_title(get_bloginfo('name'));
        $timestamp = date('Y-m-d-H-i-s');
        
        return sprintf(
            'cf7-export-%s-%d-forms-%s.xml',
            $site_name,
            $form_count,
            $timestamp
        );
    }
    
    /**
     * Serve file for download
     */
    private function serve_file($content, $filename) {
        // Clean any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $content;
        exit;
    }
}
?>