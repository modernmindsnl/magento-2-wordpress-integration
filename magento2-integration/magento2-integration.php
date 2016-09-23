<?php

/**
 * Plugin Name: Magento 2 WordPress Integration
 *
 * Author: ModernModules.com
 *
 * Description: Integrate Magento 2 with WordPress so users will have an unified user experience (basic version).
 *
 * Version: 1.0.6b
 * 
 * Author URI: https://modernmodules.com/
 * 
 */
if (!defined('ABSPATH')) {
    die('No script kiddies please!');
}

define('M2I_URL', plugins_url('', __FILE__));
define('M2I_URL_JS', M2I_URL . '/js');
define('M2I_URL_IMG', M2I_URL . '/images');
define('M2I_URL_CSS', M2I_URL . '/css');
define('M2I_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('M2I_PATH_PHP', M2I_PATH . '/include');
define('M2I_PATH_CLASSES', M2I_PATH_PHP . '/classes');
define('M2I_PLUGIN_VERSION', '1.0.6b');

add_action('setup_theme', 'm2i_plugin_init');

/** Entry point for the plugin */
function m2i_plugin_init() {
    require_once(M2I_PATH_CLASSES . '/M2I_Settings.php');
    require_once(M2I_PATH_CLASSES . '/M2I_External.php');
    require_once(M2I_PATH_PHP . '/functions.php');
    
    add_action('admin_enqueue_scripts', 'm2i_init_admin_media');
    
    add_action('wp_ajax_m2i_check_magento', 'm2i_check_magento');
    add_action('wp_ajax_m2i_notices', 'm2i_notices');
    
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'm2i_add_action_links');

    if(!m2i_is_php_version_compatible()){
        add_action('admin_notices', 'm2i_admin_php_version__error');
    } elseif (m2i_is_req_option_missed()) {
        add_action('admin_notices', 'm2i_admin_config__error');
    } else {
        M2I_External::init();
        
        if(!is_admin()){
            M2I_External::launch();
        }
        
        include_once(M2I_PATH_CLASSES . '/M2I_Content.php');
    }
}

/** @return bool */
function m2i_is_req_option_missed() {
    $options = array(
        M2I_MAGE_DIR
    );
    return in_array(false, $options, true);
}

/**
 *  @return bool 
 * 
 *  @since 0.5
 */
function m2i_is_php_version_compatible(){
    $is_compatible = true;
    
    if(version_compare(phpversion(), "5.5.0", "<")){
        $is_compatible = false;
    }

    return $is_compatible;
}

function m2i_init_admin_media($page) {
    
    wp_enqueue_script('m2i_notices', M2I_URL_JS . '/notices.js', array('jquery'), M2I_PLUGIN_VERSION, true);
    
    if (strpos($page, 'M2I_Settings') === false) {
        return;
    }

    wp_enqueue_script('m2i_settings_script', M2I_URL_JS . '/admin_settings.js', array('jquery'), M2I_PLUGIN_VERSION, true);
    wp_enqueue_script('jquery-ui-tooltip');
    wp_enqueue_script('tooltips', M2I_URL_JS . '/tooltip.js', array('jquery', 'jquery-ui-tooltip'), M2I_PLUGIN_VERSION, true);
    wp_localize_script('m2i_settings_script', 'm2i_urls', array('js' => M2I_URL_JS, 'img' => M2I_URL_IMG));
    
    wp_enqueue_style('m2i_settings_css', M2I_URL_CSS . '/admin_settings.css', M2I_PLUGIN_VERSION);
    wp_enqueue_style('jquery_ui', M2I_URL . '/jquery_ui/jquery-ui.css', M2I_PLUGIN_VERSION);
}

/** 
 * Function for ajax online checking if main options are configured in the right way 
 * 
 * @since 0.2
 */
function m2i_check_magento() {
    M2I_External::init(true);
    M2I_External::launch();

    if (m2i_is_success()) {
        /* Success */ 
        echo 0;
        wp_die();
    }

    echo 4;
    wp_die();
}

/** 
 * Function for ajax disabling of m2i notices in the admin panel
 * 
 * @since 1.0.0
 */
function m2i_notices(){
    if(!empty($_POST['id'])){
        $id = $_POST['id'];
        $notices = get_option('m2i_notices');
        if(!$notices){
            $notices = array();
        }
        if(strpos($id, '__error') !== false){
            $notices['errors'][$id] = true;
        }
        update_option('m2i_notices', $notices);
        /* Success */
        echo 1;
    }
    wp_die();
}

/* Errors messages for the admin panel */

function m2i_admin_config__error() {
    m2i_notice__error(__('Irks! You have not configured all options for <b>Magento 2 integration</b> plugin. Please, go to %settings%.'), __FUNCTION__);
}

function m2i_admin_autoload_file__error() {
    m2i_notice__error(__('Irks! You have not configured Magento root directory in the right way for <b>Magento 2 integration</b> plugin. Please, go to %settings%.'), __FUNCTION__);
}

function m2i_admin_bootstrap_class__error() {
    m2i_notice__error(__('Irks! <b>Magento 2 integration</b> plugin can not find a Bootstrap class. Please, go to %settings% and reconfigure directory parameters.'), __FUNCTION__);
}

function m2i_admin_store_code__error(){
    m2i_notice__error(__('Irks! <b>Magento 2 integration</b> plugin can not find selected store. Please, reconfigure store parameter.'), __FUNCTION__);
}

function m2i_admin_php_version__error(){
    m2i_notice__error(__('Irks! <b>Magento 2 integration</b> plugin requires PHP 5.5 or higher!'), __FUNCTION__);
}

/** 
 *  Function for outputting formatted error notice, can be used in specified callbacks for wp notice actions
 * 
 *  @param string $message Error message
 *  @param string $id Unique id for current notice
 *  @param string $clases Clases separated with gap
 *  @param string $custom_css Custom CSS if needed
 * 
 *  @since 0.2
 *  @since 1.0.0 Added <b>$id</b> parameter
 *  
 */
function m2i_notice__error($message, $id, $clases = 'notice notice-error is-dismissible', $custom_css = ''){
    $notices = get_option('m2i_notices');
    if(!$notices || ($notices && empty($notices['errors'][$id]))){
        printf('<div class="%s" id="%s" style="%s"><p>%s</p></div>', $clases, $id, $custom_css,
                str_replace('%settings%', '<a href="' . admin_url('options-general.php?page=M2I_Settings.php') . '">settings</a>', $message));
    }
}

/**
 *  @return array Additional links for the backend plugins menu
 * 
 *  @since 0.2.5
 */
function m2i_add_action_links( $links ) {
    $mylinks = array(
        '<a href="' . admin_url('options-general.php?page=M2I_Settings.php') . '">' . __('Settings') . '</a>'
    );
    
    return array_merge($mylinks, $links);
}

register_deactivation_hook( __FILE__, 'm2i_on_deactivation' );
/**
 * Deactivation hook, used to restore noices, etc.
 * 
 * @since 1.0.3
 */
function m2i_on_deactivation(){
    
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );
        
        /* Restore all removed notices by user for using while next activation */
        $notices = get_option('m2i_notices');
        array_walk($notices, function(&$item){
            foreach($item as &$id){
                $id = false;
            }
        });
        update_option('m2i_notices', $notices);
        unset($notices);
        
}
/**
 * Checking if that tab is tab for base settings in the admin panel
 * 
 * @since 1.0.5
 */
function m2i_is_active_base_tab(){
    global $pagenow;
    $settings_obj = M2I_Settings::get_instance();
    
    return (is_admin() && $pagenow === 'options-general.php' && (isset($_GET['page']) && $_GET['page'] === $settings_obj->get_page_name()) 
                       && (($settings_obj->get_active_tab() === key($settings_obj->get_page_tabs())) || !isset($_GET['tab']))
            );
}

if ( !function_exists('is_ajax') ) {
    /** @since 1.0.5 */
    function is_ajax() {
        return (defined('DOING_AJAX') && DOING_AJAX);
    }
}
