<?php
/**
 * Admin page template
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_forms = count($contact_forms);
$plugin_options = get_option('cf7_xml_exporter_options', array());
?>

<div class="wrap">
    <h1><?php _e('Contact Form 7 XML Exporter', 'cf7-xml-exporter'); ?></h1>
    
    <!-- Header Section -->
    <div class="cf7-export-header">
        <h2><?php _e('Export Your Contact Forms', 'cf7-xml-exporter'); ?></h2>
        <p><?php _e('Select the forms you want to export and choose your export options below.', 'cf7-xml-exporter'); ?></p>
        
        <div class="cf7-export-stats">
            <div class="cf7-stat-item">
                <span class="cf7-stat-number"><?php echo $total_forms; ?></span>
                <span class="cf7-stat-label"><?php _e('Total Forms', 'cf7-xml-exporter'); ?></span>
            </div>
            <div class="cf7-stat-item">
                <span class="cf7-stat-number" id="selected-count">0</span>
                <span class="cf7-stat-label"><?php _e('Selected Forms', 'cf7-xml-exporter'); ?></span>
            </div>
            <div class="cf7-stat-item">
                <span class="cf7-stat-number"><?php echo $plugin_options['max_export_limit'] ?? 100; ?></span>
                <span class="cf7-stat-label"><?php _e('Max Export Limit', 'cf7-xml-exporter'); ?></span>
            </div>
        </div>
    </div>
    
    <form method="post" id="cf7-export-form">
        <?php wp_nonce_field('cf7_export_action', 'cf7_export_nonce'); ?>
        
        <!-- Form Selection -->
        <div class="cf7-form-list">
            <div class="cf7-form-list-header">
                <label for="select-all-forms">
                    <input type="checkbox" id="select-all-forms">
                    <strong><?php _e('Select All Forms', 'cf7-xml-exporter'); ?></strong>
                </label>
                <div class="cf7-bulk-actions">
                    <select id="bulk-actions-select">
                        <option value=""><?php _e('Bulk Actions', 'cf7-xml-exporter'); ?></option>
                        <option value="export"><?php _e('Export Selected', 'cf7-xml-exporter'); ?></option>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($contact_forms)) : ?>
                <?php foreach ($contact_forms as $form) : ?>
                    <div class="cf7-form-item">
                        <input type="checkbox" 
                               name="selected_forms[]" 
                               value="<?php echo $form->id(); ?>"
                               id="form-<?php echo $form->id(); ?>"
                               class="cf7-form-checkbox form-checkbox">
                        
                        <div class="cf7-form-info">
                            <div class="cf7-form-title">
                                <label for="form-<?php echo $form->id(); ?>">
                                    <?php echo esc_html($form->title()); ?>
                                    <span class="cf7-form-id">ID: <?php echo $form->id(); ?></span>
                                </label>
                            </div>
                            <div class="cf7-form-meta">
                                <?php 
                                $form_fields = cf7_count_form_fields($form);
                                printf(
                                    __('Slug: %s | Fields: %d | Modified: %s', 'cf7-xml-exporter'),
                                    $form->name(),
                                    $form_fields,
                                    get_post_modified_time('M j, Y', false, $form->id())
                                );
                                ?>
                            </div>
                        </div>
                        
                        <div class="cf7-form-actions">
                            <a href="<?php echo admin_url('admin.php?page=wpcf7&post=' . $form->id() . '&action=edit'); ?>" 
                               class="cf7-form-action-btn"><?php _e('Edit', 'cf7-xml-exporter'); ?></a>
                            <a href="#" onclick="navigator.clipboard.writeText('<?php echo esc_js($form->shortcode()); ?>'); alert('Shortcode copied!');" 
                               class="cf7-form-action-btn"><?php _e('Copy Shortcode', 'cf7-xml-exporter'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="cf7-form-item">
                    <p><?php _e('No Contact Form 7 forms found.', 'cf7-xml-exporter'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Export Options -->
        <div class="cf7-export-options">
            <div class="cf7-option-group">
                <label class="cf7-option-label"><?php _e('Content Options', 'cf7-xml-exporter'); ?></label>
                <div class="cf7-checkbox-group">
                    <div class="cf7-checkbox-item">
                        <input type="checkbox" name="include_settings" id="include-settings" value="1" checked>
                        <label for="include-settings"><?php _e('Include form settings and mail configuration', 'cf7-xml-exporter'); ?></label>
                    </div>
                    <div class="cf7-checkbox-description">
                        <?php _e('Exports email settings, messages, and additional form configuration.', 'cf7-xml-exporter'); ?>
                    </div>
                    
                    <div class="cf7-checkbox-item">
                        <input type="checkbox" name="include_submissions" id="include-submissions" value="1">
                        <label for="include-submissions"><?php _e('Include form submissions (if available)', 'cf7-xml-exporter'); ?></label>
                    </div>
                    <div class="cf7-checkbox-description">
                        <?php _e('Requires a Contact Form 7 database plugin to be installed.', 'cf7-xml-exporter'); ?>
                    </div>
                </div>
            </div>
            
            <div class="cf7-option-group">
                <label class="cf7-option-label"><?php _e('Advanced Options', 'cf7-xml-exporter'); ?></label>
                <div class="cf7-checkbox-group">
                    <div class="cf7-checkbox-item">
                        <input type="checkbox" name="minify_xml" id="minify-xml" value="1">
                        <label for="minify-xml"><?php _e('Minify XML output', 'cf7-xml-exporter'); ?></label>
                    </div>
                    <div class="cf7-checkbox-description">
                        <?php _e('Reduces file size by removing unnecessary whitespace.', 'cf7-xml-exporter'); ?>
                    </div>
                    
                    <div class="cf7-checkbox-item">
                        <input type="checkbox" name="include_metadata" id="include-metadata" value="1" checked>
                        <label for="include-metadata"><?php _e('Include form metadata', 'cf7-xml-exporter'); ?></label>
                    </div>
                    <div class="cf7-checkbox-description">
                        <?php _e('Includes creation date, modification date, and other form metadata.', 'cf7-xml-exporter'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Actions -->
        <div class="cf7-export-actions">
            <button type="submit" name="export_xml" id="export-button" class="cf7-export-btn" disabled>
                <?php _e('Export Selected Forms', 'cf7-xml-exporter'); ?>
            </button>
            <button type="button" class="cf7-secondary-btn" onclick="window.location.href='<?php echo admin_url('admin.php?page=cf7-xml-settings'); ?>'">
                <?php _e('Export Settings', 'cf7-xml-exporter'); ?>
            </button>
            <span class="spinner" id="export-spinner"></span>
        </div>
    </form>
    
    <!-- Status Messages -->
    <div id="export-status" class="cf7-notice" style="display: none;"></div>
    
    <!-- Help Section -->
    <div class="cf7-export-help" style="margin-top: 30px;">
        <h3><?php _e('Need Help?', 'cf7-xml-exporter'); ?></h3>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <p><strong><?php _e('Export Format:', 'cf7-xml-exporter'); ?></strong> <?php _e('The exported XML file contains all form structure, settings, and configuration in a standardized format.', 'cf7-xml-exporter'); ?></p>
            <p><strong><?php _e('Import Support:', 'cf7-xml-exporter'); ?></strong> <?php _e('Import functionality will be available in the next version to restore forms from XML exports.', 'cf7-xml-exporter'); ?></p>
            <p><strong><?php _e('Large Exports:', 'cf7-xml-exporter'); ?></strong> <?php printf(__('For optimal performance, limit exports to %d forms at a time.', 'cf7-xml-exporter'), $plugin_options['max_export_limit'] ?? 100); ?></p>
        </div>
    </div>
</div>

