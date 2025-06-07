<?php
/**
 * XML Generator
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CF7_XML_Generator {
    
    /**
     * XML version
     */
    const XML_VERSION = '2.0';
    
    /**
     * Generate XML content for selected forms
     */
    public function generate($form_ids, $include_submissions = false, $include_settings = true) {
        if (empty($form_ids)) {
            throw new Exception(__('No forms provided for XML generation.', 'cf7-xml-exporter'));
        }
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $this->create_root_element($xml, $form_ids);
        $xml->appendChild($root);
        
        foreach ($form_ids as $form_id) {
            $contact_form = WPCF7_ContactForm::get_instance($form_id);
            
            if (!$contact_form) {
                CF7_XML_Exporter::get_instance()->log("Form ID {$form_id} not found, skipping", 'warning');
                continue;
            }
            
            $form_element = $this->create_form_element($xml, $contact_form, $include_settings, $include_submissions);
            $root->appendChild($form_element);
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Create root XML element
     */
    private function create_root_element($xml, $form_ids) {
        $root = $xml->createElement('contact_forms');
        $root->setAttribute('version', self::XML_VERSION);
        $root->setAttribute('generator', 'CF7 XML Exporter Pro v' . CF7_XML_EXPORTER_VERSION);
        $root->setAttribute('exported_at', current_time('c'));
        $root->setAttribute('site_url', get_site_url());
        $root->setAttribute('site_name', get_bloginfo('name'));
        $root->setAttribute('form_count', count($form_ids));
        $root->setAttribute('wp_version', get_bloginfo('version'));
        $root->setAttribute('cf7_version', WPCF7_VERSION);
        
        return $root;
    }
    
    /**
     * Create form element
     */
    private function create_form_element($xml, $contact_form, $include_settings, $include_submissions) {
        $form_element = $xml->createElement('form');
        
        // Add metadata
        $meta_element = $xml->createElement('meta');
        $this->add_element($xml, $meta_element, 'id', $contact_form->id());
        $this->add_element($xml, $meta_element, 'title', $contact_form->title());
        $this->add_element($xml, $meta_element, 'slug', $contact_form->name());
        $this->add_element($xml, $meta_element, 'created', get_post_time('c', false, $contact_form->id()));
        $this->add_element($xml, $meta_element, 'modified', get_post_modified_time('c', false, $contact_form->id()));
        $this->add_element($xml, $meta_element, 'status', get_post_status($contact_form->id()));
        $form_element->appendChild($meta_element);
        
        // Add form content
        $content_element = $xml->createElement('content');
        $content_element->setAttribute('type', 'form');
        $content_element->appendChild($xml->createCDATASection($contact_form->prop('form')));
        $form_element->appendChild($content_element);
        
        if ($include_settings) {
            $this->add_settings_elements($xml, $form_element, $contact_form);
        }
        
        if ($include_submissions) {
            $this->add_submissions_element($xml, $form_element, $contact_form);
        }
        
        return $form_element;
    }
    
    /**
     * Add settings elements
     */
    private function add_settings_elements($xml, $form_element, $contact_form) {
        $settings_element = $xml->createElement('settings');
        
        // Mail settings
        $mail = $contact_form->prop('mail');
        if (!empty($mail)) {
            $mail_element = $this->create_mail_element($xml, $mail, 'mail');
            $settings_element->appendChild($mail_element);
        }
        
        // Mail 2 settings
        $mail2 = $contact_form->prop('mail_2');
        if (!empty($mail2) && !empty($mail2['active'])) {
            $mail2_element = $this->create_mail_element($xml, $mail2, 'mail_2');
            $settings_element->appendChild($mail2_element);
        }
        
        // Messages
        $messages = $contact_form->prop('messages');
        if (!empty($messages)) {
            $messages_element = $xml->createElement('messages');
            foreach ($messages as $key => $value) {
                $message_item = $xml->createElement('message');
                $message_item->setAttribute('key', $key);
                $message_item->appendChild($xml->createCDATASection($value));
                $messages_element->appendChild($message_item);
            }
            $settings_element->appendChild($messages_element);
        }
        
        // Additional settings
        $additional_settings = $contact_form->prop('additional_settings');
        if (!empty($additional_settings)) {
            $additional_element = $xml->createElement('additional_settings');
            $additional_element->appendChild($xml->createCDATASection($additional_settings));
            $settings_element->appendChild($additional_element);
        }
        
        $form_element->appendChild($settings_element);
    }
    
    /**
     * Create mail element
     */
    private function create_mail_element($xml, $mail_settings, $type) {
        $mail_element = $xml->createElement($type);
        
        foreach ($mail_settings as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $this->add_element($xml, $mail_element, $key, $value);
        }
        
        return $mail_element;
    }
    
    /**
     * Add submissions element (placeholder for future enhancement)
     */
    private function add_submissions_element($xml, $form_element, $contact_form) {
        $submissions_element = $xml->createElement('submissions');
        $submissions_element->setAttribute('note', 'Submission data requires a Contact Form 7 database plugin');
        
        // Future: Add actual submission data if database plugin is available
        // Example: CF7 to Database, Contact Form CFDB7, etc.
        
        $form_element->appendChild($submissions_element);
    }
    
    /**
     * Helper method to add element with CDATA if needed
     */
    private function add_element($xml, $parent, $name, $value) {
        $element = $xml->createElement($name);
        
        if (strlen($value) > 100 || strpos($value, '<') !== false || strpos($value, '&') !== false) {
            $element->appendChild($xml->createCDATASection($value));
        } else {
            $element->appendChild($xml->createTextNode($value));
        }
        
        $parent->appendChild($element);
    }
}
?>