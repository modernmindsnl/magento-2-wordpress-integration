<?php

/**
 * M2I_Content object trying to paste all styles from Magento 2 to your frontend in the end of "<head>" tag, <br>
 * add automaticaly "<header>" and "<footer>" to the current WordPress theme.
 *
 */
class M2I_Content {
    
    /**
     *  Full path to current template, which would be defined, if 'mage_auto_adding' is not on
     * 
     *  @since 1.0.1
     */
    static $template = '';

    /** @var int Primary priority */
    protected $priority = 50;

    /** Construct init. for wp_head actions */
    function __construct() {
        global $m2i_options;
        
        if (m2i_is_success()) {
            add_action('wp_head', array($this, 'add_styles_css'), $this->priority);
            
            if($m2i_options['mage_auto_adding'] === 'on'){
                add_action('template_include', array($this, 'template_override'), $this->priority);
            }
        }
    }

    /** Callback for pasting css */
    function add_styles_css() {
        global $m2i_options;
        
        if (!empty($m2i_options['mage_styles_flag'])) {
            echo m2i_get_links_css_tags();
        }
        
    }
    
    /**
     *  Hook for making all WordPress pages through template.php 
     * 
     *  @since 1.0.1
     */
    function template_override($template){
        self::$template = $template;
        return M2I_PATH_PHP . '/template.php';
    }

}

new M2I_Content;
