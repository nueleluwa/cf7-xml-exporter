<?php
/**
 * Validation functionality
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CF7_Validator {
    
    /**
     * Validate and sanitize form IDs
     */
    public function validate_form_ids($form_ids) {
        if (!is_array($form_ids)) {
            return array();
        }
        
        $valid_ids = array();
        
        foreach ($form_ids as $form_id) {
            $form_id = absint($form_id);
            
            if ($form_id <= 0) {
                continue;
            }
            
            // Check if form exists
            if ($this->form_exists($form_id)) {
                $valid_ids[] = $form_id;
            } else {
                CF7_XML_Exporter::get_instance()->log("Invalid form ID: {$form_id}", 'warning');
            }
        }
        
        return array_unique($valid_ids);
    }
    
    /**
     * Check if form exists
     */
    public function form_exists($form_id) {
        if (!class_exists('WPCF7_ContactForm')) {
            return false;
        }
        
        $form = WPCF7_ContactForm::get_instance($form_id);
        return $form && $form->id() === $form_id;
    }
    
    /**
     * Validate export options
     */
    public function validate_export_options($options) {
        $validated = array();
        
        $validated['include_submissions'] = !empty($options['include_submissions']);
        $validated['include_settings'] = !empty($options['include_settings']);
        $validated['format'] = isset($options['format']) && $options['format'] === 'json' ? 'json' : 'xml';
        
        return $validated;
    }
    
    /**
     * Validate file upload for import
     */
    public function validate_upload_file($file) {
        if (empty($file) || !is_array($file)) {
            throw new Exception(__('No file uploaded.', 'cf7-xml-exporter'));
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(__('File upload error.', 'cf7-xml-exporter'));
        }
        
        $allowed_types = array('text/xml', 'application/xml');
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception(__('Invalid file type. Only XML files are allowed.', 'cf7-xml-exporter'));
        }
        
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            throw new Exception(__('File too large. Maximum size is 10MB.', 'cf7-xml-exporter'));
        }
        
        return true;
    }
    
    /**
     * Validate XML content
     */
    public function validate_xml_content($xml_content) {
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_message = __('Invalid XML format.', 'cf7-xml-exporter');
            
            if (!empty($errors)) {
                $error_message .= ' ' . $errors[0]->message;
            }
            
            throw new Exception($error_message);
        }
        
        // Check if it's a CF7 export file
        if ($xml->getName() !== 'contact_forms') {
            throw new Exception(__('This is not a valid Contact Form 7 export file.', 'cf7-xml-exporter'));
        }
        
        return true;
    }
}
?>