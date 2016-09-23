Combine the powerful e-commerce solution Magento 2 with the excellent CMS capabilities of WordPress.

This plugin is not meant to replace Magento 2, instead it will allow you to create a seamless user experience for your visitors by integrating the design of Magento and WordPress.

If you will need support, please contact us at http://modernmodules.com/support with description of the issue(s) and access information.

THE FULL COMMERCIAL VERSION OF THIS PLUGIN WITH MANY MORE FEATURES CAN BE DOWNLOADED AT: https://modernmodules.com/plugins/magento-2-wordpress-integration-plugin/

Version 1.0.6b

FEATURES
===========

- Include Magento header in your WordPress theme by our API
- Include Magento css into WordPress theme header automatically
- Manage store views
- Manage including of css and showing Magento header

INSTALLATION
===========

1. Download the WordPress plugin
2. Upload the contents of the ZIP to /wp-content/plugins/
3. Enable the plugin in the plugins settings page
4. Set the right settings in Settings -> Magento 2 Integration
5. Apply the following patch to avoid conflicts between WordPress and Magento 2:

-> Locate {WORDPRESS_ROOT}/wp-includes/l10n.php
WordPress __() function is used for translation but is in conflict with Magento 2. Therefore please find this function at around line 172 and 

REPLACE

function __( $text, $domain = 'default' ) {
return translate( $text, $domain );
}

WITH

function __( $text, $domain = 'default' ) {
if(defined('M2I_MAGE_DIR') && class_exists('M2I_External') && M2I_External::$needs_mage_translate){
return M2I_External::translate(func_get_args());
} else {
return translate( $text, $domain );
}
}

USING THE PLUGIN
=============
    
You can use the following functions in your theme files:

Echo Header: m2i_get_header()
Echo CSS files: m2i_get_links_css_tags()
Get Blocks Names as array: m2i_get_blocks()
Get Available Store Views as array: m2i_get_stores()