=== Simple CSV Import Export ===
Contributors: takazawashota
Tags: csv, import, export, posts, pages, custom post types
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


CSV Import Export with a simple interface to bulk import/export posts, pages, and custom post types using CSV files.

== Description ==

Simple CSV Import Export is an easy-to-use plugin that allows you to bulk import and export WordPress posts, pages, and custom post types using CSV files.

= Main Features =

* Bulk import for posts, pages, and custom post types
* Update existing posts
* Automatic creation of categories, tags, and custom taxonomies
* Support for custom fields
* Set featured images
* CSV testing and validation function
* Error checking and detailed reports
* UTF-8 encoding support

= How to Use =

1. Install and activate the plugin
2. Go to Tools > Simple CSV Import Export
3. Select either Import or Export
4. Upload your CSV file to import, or export posts/pages/custom post types as CSV

= Importable Data =

* Post title
* Post content
* Author
* Publish date
* Categories
* Tags
* Custom fields
* Featured image
* Custom taxonomies
* Post status
* Other metadata

= CSV Format =

The basic CSV format can be checked in the “CSV Format Specification” tab in the admin panel.  
A sample CSV file is also available for download.

= Notes =

* CSV files must be encoded in UTF-8
* For large imports, please check your server settings
* We recommend backing up your site before importing

= Links =

* [Official Website](https://sokulabo.com/products/simple-csv-import-export/)

== Installation ==

1. Download the plugin as a zip file
2. Upload and install it from the WordPress admin panel
3. Activate the plugin
4. Start using it from Tools > Simple CSV Import Export

== Frequently Asked Questions ==

= Is there a limit to how much data I can import at once? =

It depends on your server settings (memory limit, execution time, etc.). The plugin automatically adjusts batch sizes to safely handle imports.

= Can I update existing posts? =

Yes. You can update existing posts by specifying the "post_id" column in the CSV file.

= Does it support custom post types? =

Yes, it supports all custom post types.

= Can I import/export custom fields? =

Yes, custom field values can be imported and exported.

= How do I set featured images? =

By specifying the image URL in the "post_thumbnail" column, the image will be automatically uploaded to the media library and set as the featured image.

== Screenshots ==

1. Main screen
2. Import settings
3. Export settings
4. CSV test function
5. Format specification screen

== Changelog ==

= 1.0.0 =
* Initial release
* Implemented import/export functionality
* Added CSV test functionality
* Optimized batch processing
* Added sample CSV download function
* Implemented detailed error report feature

== Upgrade Notice ==

= 1.0.0 =
Initial release – install and start using.

== Privacy Policy ==

This plugin does not collect personal information.  
However, please handle with care if the data you import/export contains personal information.
