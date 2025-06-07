/**
 * CF7 XML Exporter JavaScript
 * 
 * @package CF7_XML_Exporter
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    var CF7Exporter = {
        
        init: function() {
            this.bindEvents();
            this.initializeElements();
        },
        
        bindEvents: function() {
            // Select all functionality
            $('#select-all-forms').on('change', this.handleSelectAll);
            
            // Individual checkbox changes
            $(document).on('change', '.form-checkbox', this.handleIndividualSelect);
            
            // Export form submission
            $('#cf7-export-form').on('submit', this.handleExport);
            
            // Import form submission (future)
            $('#cf7-import-form').on('submit', this.handleImport);
            
            // Bulk actions
            $('#bulk-actions-select').on('change', this.handleBulkActions);
        },
        
        initializeElements: function() {
            // Initialize tooltips if available
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip();
            }
            
            // Initialize progress bars
            this.initProgressBars();
        },
        
        handleSelectAll: function() {
            var isChecked = $(this).prop('checked');
            $('.form-checkbox').prop('checked', isChecked);
            CF7Exporter.updateSelectAllState();
        },
        
        handleIndividualSelect: function() {
            CF7Exporter.updateSelectAllState();
        },
        
        updateSelectAllState: function() {
            var total = $('.form-checkbox').length;
            var checked = $('.form-checkbox:checked').length;
            
            $('#select-all-forms').prop('checked', total === checked);
            $('#selected-count').text(checked);
            
            // Update export button state
            if (checked > 0) {
                $('#export-button').prop('disabled', false);
            } else {
                $('#export-button').prop('disabled', true);
            }
        },
        
        handleExport: function(e) {
            e.preventDefault();
            
            var selectedForms = CF7Exporter.getSelectedForms();
            
            if (selectedForms.length === 0) {
                CF7Exporter.showNotice(cf7ExporterAjax.strings.select_forms, 'error');
                return;
            }
            
            CF7Exporter.startExport(selectedForms);
        },
        
        getSelectedForms: function() {
            return $('.form-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
        },
        
        startExport: function(selectedForms) {
            var $spinner = $('#export-spinner');
            var $button = $('#export-button');
            var $status = $('#export-status');
            
            // Show loading state
            $spinner.addClass('is-active');
            $button.prop('disabled', true);
            $status.hide();
            
            // Show progress bar
            CF7Exporter.showProgress(0);
            
            var data = {
                action: 'cf7_export_xml',
                selected_forms: selectedForms,
                include_submissions: $('#include-submissions').is(':checked') ? 1 : 0,
                include_settings: $('#include-settings').is(':checked') ? 1 : 0,
                nonce: cf7ExporterAjax.nonce
            };
            
            $.ajax({
                url: cf7ExporterAjax.ajax_url,
                type: 'POST',
                data: data,
                timeout: 300000, // 5 minutes
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    // Progress simulation (since we can't track real progress)
                    CF7Exporter.simulateProgress();
                    return xhr;
                },
                success: function(response) {
                    CF7Exporter.handleExportSuccess(response);
                },
                error: function(xhr, textStatus, errorThrown) {
                    CF7Exporter.handleExportError(textStatus, errorThrown);
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);
                    CF7Exporter.hideProgress();
                }
            });
        },
        
        simulateProgress: function() {
            var progress = 0;
            var interval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) {
                    progress = 90;
                    clearInterval(interval);
                }
                CF7Exporter.updateProgress(progress);
            }, 500);
        },
        
        handleExportSuccess: function(response) {
            if (response.success) {
                CF7Exporter.updateProgress(100);
                CF7Exporter.downloadFile(response.data.xml, response.data.filename);
                CF7Exporter.showNotice(
                    cf7ExporterAjax.strings.export_success + 
                    ' (' + response.data.form_count + ' forms)',
                    'success'
                );
            } else {
                CF7Exporter.showNotice(
                    response.data || cf7ExporterAjax.strings.export_error,
                    'error'
                );
            }
        },
        
        handleExportError: function(textStatus, errorThrown) {
            var message = cf7ExporterAjax.strings.ajax_error;
            
            if (textStatus === 'timeout') {
                message = 'Export timed out. Please try with fewer forms.';
            }
            
            CF7Exporter.showNotice(message, 'error');
        },
        
        downloadFile: function(content, filename) {
            var blob = new Blob([content], { type: 'application/xml' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            
            a.href = url;
            a.download = filename;
            a.style.display = 'none';
            
            document.body.appendChild(a);
            a.click();
            
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },
        
        showNotice: function(message, type) {
            var $status = $('#export-status');
            var className = 'notice-' + type;
            
            $status.removeClass('notice-success notice-error notice-warning')
                   .addClass('notice ' + className)
                   .html('<p>' + message + '</p>')
                   .show();
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $status.fadeOut();
                }, 5000);
            }
        },
        
        initProgressBars: function() {
            $('body').append(
                '<div id="cf7-progress-modal" class="cf7-modal" style="display: none;">' +
                    '<div class="cf7-modal-content">' +
                        '<h3>Exporting Forms...</h3>' +
                        '<div class="cf7-progress-bar">' +
                            '<div class="cf7-progress-fill"></div>' +
                        '</div>' +
                        '<div class="cf7-progress-text">0%</div>' +
                    '</div>' +
                '</div>'
            );
        },
        
        showProgress: function(percentage) {
            $('#cf7-progress-modal').show();
            this.updateProgress(percentage || 0);
        },
        
        updateProgress: function(percentage) {
            var $modal = $('#cf7-progress-modal');
            var $fill = $modal.find('.cf7-progress-fill');
            var $text = $modal.find('.cf7-progress-text');
            
            percentage = Math.min(100, Math.max(0, percentage));
            
            $fill.css('width', percentage + '%');
            $text.text(Math.round(percentage) + '%');
        },
        
        hideProgress: function() {
            $('#cf7-progress-modal').hide();
        },
        
        handleImport: function(e) {
            e.preventDefault();
            // Future implementation
            alert('Import functionality will be available in the next version.');
        },
        
        handleBulkActions: function() {
            var action = $(this).val();
            var selectedForms = CF7Exporter.getSelectedForms();
            
            if (action && selectedForms.length > 0) {
                switch (action) {
                    case 'export':
                        CF7Exporter.startExport(selectedForms);
                        break;
                    case 'delete':
                        // Future implementation
                        break;
                }
            }
            
            // Reset select
            $(this).val('');
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        CF7Exporter.init();
    });
    
    // Export to global scope for external access
    window.CF7Exporter = CF7Exporter;
    
})(jQuery);