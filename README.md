markdown
# Contact Form 7 XML Exporter Pro

A comprehensive WordPress plugin that allows administrators to export Contact Form 7 forms in XML format with advanced features and import capabilities.

## Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Create the following directory structure in your WordPress plugins folder:

```
/wp-content/plugins/cf7-xml-exporter/
```

3. Upload all files maintaining the directory structure shown below:

```
cf7-xml-exporter/
├── cf7-xml-exporter.php
├── includes/
│   ├── class-cf7-xml-exporter.php
│   ├── class-cf7-admin.php
│   ├── class-cf7-export-handler.php
│   ├── class-cf7-xml-generator.php
│   └── class-cf7-validator.php
├── assets/
│   ├── js/
│   │   └── cf7-xml-exporter.js
│   └── css/
│       └── cf7-xml-exporter.css
├── templates/
│   └── admin-page.php
└── README.md
```

4. Activate the plugin through the WordPress admin panel

### Method 2: Upload via WordPress Admin

1. Zip all files maintaining the directory structure
2. Go to Plugins → Add New → Upload Plugin
3. Upload the zip file and activate

## File Organization Guide

### Core Files

#### `/cf7-xml-exporter.php` - Main Plugin File
- Plugin header and metadata
- Constants definition
- Activation/deactivation hooks
- Plugin initialization

#### `/includes/class-cf7-xml-exporter.php` - Main Plugin Class
- Plugin singleton pattern
- Dependency management
- Hook initialization
- Component orchestration

#### `/includes/class-cf7-admin.php` - Admin Interface
- Admin menu creation
- Page rendering
- Form handling
- Asset enqueueing
- Settings management

#### `/includes/class-cf7-export-handler.php` - Export Logic
- AJAX request handling
- Direct export processing
- Memory management
- File serving
- Error handling

#### `/includes/class-cf7-xml-generator.php` - XML Generation
- XML structure creation
- Form data processing
- Settings serialization
- Metadata inclusion

#### `/includes/class-cf7-validator.php` - Validation
- Form ID validation
- File upload validation
- XML content validation
- Security checks

### Frontend Assets

#### `/assets/js/cf7-xml-exporter.js` - JavaScript Functionality
- AJAX handling
- User interface interactions
- Progress indicators
- File downloads
- Form validation

#### `/assets/css/cf7-xml-exporter.css` - Styling
- Admin interface styling
- Responsive design
- Progress bars
- Modal dialogs

### Templates

#### `/templates/admin-page.php` - Main Admin Page Template
- Export interface HTML
- Form selection UI
- Options configuration
- Help documentation

## Usage Instructions

### Basic Export

1. Navigate to **Contact → Export to XML**
2. Select forms using checkboxes or "Select All"
3. Choose export options:
   - Include form settings
   - Include submissions (if available)
   - Include metadata
   - Minify XML output
4. Click "Export Selected Forms"
5. Download will start automatically

### Advanced Features

#### Bulk Operations
- Use "Select All" to choose all forms
- Use bulk actions dropdown for batch operations
- Monitor selected form count in real-time

#### Export Limits
- Configure maximum export limits in settings
- Memory management for large exports
- Progress indicators for long operations

#### Settings Configuration
- Go to **Contact → XML Settings**
- Configure export limits
- Enable/disable logging
- Manage caching options

## XML Structure

The exported XML follows this structure:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<contact_forms version="2.0" generator="CF7 XML Exporter Pro" exported_at="2025-06-07T12:00:00+00:00">
    <form>
        <meta>
            <id>123</id>
            <title>Contact Form</title>
            <slug>contact-form-1</slug>
            <created>2025-01-01T00:00:00+00:00</created>
            <modified>2025-06-01T00:00:00+00:00</modified>
            <status>publish</status>
        </meta>
        <content type="form"><![CDATA[...]]></content>
        <settings>
            <mail>...</mail>
            <messages>...</messages>
            <additional_settings><![CDATA[...]]></additional_settings>
        </settings>
    </form>
</contact_forms>
```

## Troubleshooting

### Common Issues

#### Memory Limit Errors
- Reduce number of forms per export
- Increase PHP memory limit in wp-config.php:
```php
ini_set('memory_limit', '512M');
```

#### Timeout Issues
- Export fewer forms at once
- Increase PHP max execution time
- Use AJAX export instead of direct export

#### Permission Errors
- Ensure user has 'manage_options' capability
- Check file permissions on uploads directory

### Debug Logging

Enable logging in plugin settings to troubleshoot issues:

1. Go to **Contact → XML Settings**
2. Enable "Enable Logging"
3. Check WordPress debug.log for error messages

## Requirements

- WordPress 5.0 or higher
- Contact Form 7 plugin (active)
- PHP 7.4 or higher
- Administrator privileges

## Features

### Current Features
- ✅ Export multiple forms to XML
- ✅ Include form settings and mail configuration
- ✅ Progress indicators and AJAX support
- ✅ Memory management for large exports
- ✅ Form validation and error handling
- ✅ Responsive admin interface
- ✅ Settings configuration
- ✅ Debug logging
- ✅ Security measures (nonces, capability checks)

### Upcoming Features
- 🔄 Import functionality
- 🔄 Scheduled exports
- 🔄 JSON export format
- 🔄 Email export notifications
- 🔄 Export history tracking

## Support

For support and feature requests, please contact the plugin developer or submit issues through the appropriate channels.

## License

This plugin is licensed under GPL v2 or later.
```

---

## File Placement Summary

Here's exactly where each code goes:

### 1. **Main Plugin File**
- **File**: `cf7-xml-exporter.php` (root directory)
- **Content**: Plugin header, constants, initialization

### 2. **Core Classes** (in `/includes/` directory)
- `class-cf7-xml-exporter.php` - Main plugin class
- `class-cf7-admin.php` - Admin interface
- `class-cf7-export-handler.php` - Export processing
- `class-cf7-xml-generator.php` - XML generation
- `class-cf7-validator.php` - Validation logic

### 3. **Frontend Assets** (in `/assets/` directory)
- `js/cf7-xml-exporter.js` - JavaScript functionality
- `css/cf7-xml-exporter.css` - Styling

### 4. **Templates** (in `/templates/` directory)
- `admin-page.php` - Main admin page template

### 5. **Documentation**
- `README.md` - Installation and usage guide (root directory)

This improved structure provides:
- **Better organization** with separated concerns
- **Enhanced security** with proper validation
- **Improved performance** with caching and memory management
- **Better user experience** with progress indicators and AJAX
- **Extensibility** for future features like import functionality
- **Maintainability** with modular code structure

Each file has a specific responsibility, making the codebase easier to maintain and extend.
