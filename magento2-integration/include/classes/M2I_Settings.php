<?php

/**
 * M2I_Settings class for providing of using options in the M2I plugin
 * 
 * @since 1.0.5 Singleton implemented
 *
 * @method string get_active_tab()
 * @method string get_page_name()
 * @method array get_options()
 * @method array get_page_tabs()
 */
class M2I_Settings {

    /** @since 1.0.5 */
    static private $instance = null;
    
    /** @var array */
    protected $options;
    
    /** @since 1.0.5 */
    protected $page_name;
    
    /** @since 1.0.5 */
    protected $page_tabs;

    /** @var array Store in self callbacks objects for futher using them as output functions for <b>texts</b> options */
    public $text_callbacks;

    /** @var array Store in self callbacks objects for futher using them as output functions for <b>checkboxes</b> options */
    public $flag_callbacks;

    /**
     *  @var array Store in self callbacks objects for futher using them as output functions for <b>selects</b> options 
     * 
     *  @since 0.4.9
     */
    public $select_callbacks;

    /**
     *  @var array Store fields params for using them in the <b>add_fields()</b> 
     * 
     *  @since 0.4.2
     */
    public $fields_params;
    
    /**
     * Init. once a time
     * 
     * @since 1.0.5 
     */
    static final function init(){
        if(self::$instance === null){
            self::$instance = new self;
        }
    }
    
    /**
     * Return instance of current class
     * 
     * @return M2I_Settings
     * @since 1.0.5 
     */
    static final function get_instance(){
        return self::$instance;
    }

    /**
     *  Constructs ability for using options and constants in the M2I plugin 
     * 
     *  @since 1.0.5 Final protected
     */
    final protected function __construct() {
        $this->page_name = basename(__FILE__);
        $this->page_tabs = array('m2i_settings_general' => __('General Settings'));
        
        $this->register_filters();
        $this->register_fields_params();
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('current_screen', array($this, 'screen'));
    }

    /**
     *  Method for <b>admin_menu</b> hook. <br>
     *  Start point for all methods in that object. 
     */
    function admin_menu() {
        $this->add_sections();
        $this->add_fields();
        add_options_page(
                __('Magento 2 Integration'), 'Magento 2 Integration', 'manage_options', basename(__FILE__), array($this, 'page')
        );
    }

    /**
     *  Registers all params for all fields.<br>
     *  Some of them can be used for init. process of M2I plugin.<br>
     *  As example: constants <i>M2I_MAGE_DIR</i>, <i>M2I_MAGE_BASE_NAME</i>.
     * 
     *  @since 0.4.2
     */
    function register_fields_params() {
        $section = $page = 'm2i_settings_general';

        $this->register_field_params('m2i_mage_dir', __('Absolute path to Magento 2 root directory'), 'text', $page, $section);
        $this->register_field_params('m2i_mage_store_code', __('Store View'), 'select', $page, $section);

        $section = 'm2i_settings_content';
        
        $this->register_field_params('m2i_mage_auto_adding', __('Add header automatically'), 'flag', $page, $section, array(
             'description' => __('recommended')
        ));
        $this->register_field_params('m2i_mage_header_flag', __('Show header'), 'flag', $page, $section);
        $this->register_field_params('m2i_mage_styles_flag', __('Include CSS files'), 'flag', $page, $section);
        
        //$this->register_field_params('m2i_mage_auto_adding', __('Add header/footer automatically'), 'flag', $page, $section);

        $this->register_field_params('m2i_use_mage_layout_names', __('Enable Magento Layout approach'), 'flag', $page, $section, array(
            'description' => __('Fetch elements from Magento 2 by using Magento Layout structure, otherwise will used DOM aproach (not recommended).'),
            'dependencies' => array(
                'hide' => array('m2i_mage_header_class', 'm2i_mage_header_tag'),
                'show' => array('m2i_mage_header_block_name')
            )
        ));

        $this->register_field_params('m2i_mage_header_block_name', __('Name for header block'), 'select', $page, $section);

        $this->register_field_params('m2i_mage_header_class', __('Class for header section'), 'text', $page, $section);
        $this->register_field_params('m2i_mage_header_tag', __('HTML tag for header section'), 'text', $page, $section);

    }

    /**
     *  Adds all sections for well work of M2I sub-pages
     * 
     *  @since 0.4.2 
     */
    function add_sections() {
        $section = $page = 'm2i_settings_general';

        add_settings_section(
                $section, __('Configuration settings'), function() {
            echo '<p>' . __('Please, configure the options below to activate the integration with Magento 2. After validation make sure to save the options.') . '</p>';
        }, $page
        );

        $section = 'm2i_settings_content';

        add_settings_section(
                $section, __('Automatic Integration settings'), function() {
            echo '<p>' . __('The options below are optional and will not work with all themes. This plugin will try to automatically show styles, header and footer. When this feature does not work for your setup, then please use our custom functions in your template files (see documentation at <a href="https://modernmodules.com" target="_blank">https://modernmodules.com</a>).') . '</p>';
        }, $page
        );
        
    }

    /**
     *  Adds fields to related sections and sub-pages. Uses for this <i>$this->fields_params</i> assoc. array.
     * 
     *  @since 0.4.2
     */
    function add_fields() {
        foreach ($this->fields_params as $id => $field_params) {
            extract($field_params);
            add_settings_field($id, $title, $callback, $page, $section, $args);
            register_setting($page, $id);
        }
    }

    /**
     *  Creates a suffix from the option id
     * 
     *  @param string $id Id of the option
     *  @return string Id part without <i>m2i_</i> slug
     * 
     *  @since 0.3
     */
    protected function get_suffix($id) {
        return substr($id, 4);
    }

    /**
     *  Generates html content for the description section under option
     *  
     *  @param string $description Description text for an option
     * 
     *  @since 0.4
     */
    protected function get_description($description) {
        return sprintf('<br><p class="description">%1$s</p>', $description);
    }

    /**
     *  Checks if defined constant can be constant with rule <b>$will_be_constant</b>
     *  
     *  @param string $constant_id
     *  @param bool $will_be_constant
     *  
     *  @throws LogicException
     * 
     *  @since 0.4
     */
    protected function can_be_constant_exception($constant_id, $will_be_constant) {
        // TODO: Create specific class for M2I_Constant_Exception
        if (defined($constant_id) && !$will_be_constant) {
            throw new LogicException("$constant_id can not be defined. It is against the rules!");
        }
    }

    /**
     *  Register settings field params with callback by id to <i>$this->fields_params</i>
     * 
     *  @param string $id Id of the option
     *  @param string $title Tittle for the option
     *  @param string $type Type of the option
     *  @param string $page Page for futher displaying
     *  @param string $section Section for futher displaying
     *  @param array $args Arguments to be passed for the option
     * 
     *  @since 0.4.2
     */
    protected function register_field_params($id, $title, $type, $page, $section, $args = array()) {
        $property = "{$type}_callbacks";

        if (property_exists($this, $property)) {
            $method = "create_{$type}_option";

            if (method_exists($this, $method)) {
                $this->{$property}[$id] = $this->{$method}($id);
                $this->fields_params[$id] = array(
                    'title' => $title,
                    'callback' => $this->{$property}[$id],
                    'page' => $page,
                    'section' => $section,
                    'args' => $args
                );
            }
        }
    }

    /**
     *  Creates callback specified to checkbox(flag) option
     * 
     *  @param string $id Id of option
     *  @return callback
     * 
     *  @since 0.3
     */
    protected function create_flag_option($id) {
        $suffix = $this->get_suffix($id);
        $option_value = apply_filters('m2i_flag_value_' . $suffix, get_option($id), $id);

        $this->options[$suffix] = $option_value;

        return function($args) use ($id, $option_value) {
            echo "<input type='checkbox' id='$id' name='$id' " .
            (!empty($option_value) ? 'checked=\'checked\'' : '') .
            (isset($args['dependencies']['hide']) ? 'data-dependencies-hide=\'' . implode(',', $args['dependencies']['hide']) . '\'' : '') .
            (isset($args['dependencies']['show']) ? 'data-dependencies-show=\'' . implode(',', $args['dependencies']['show']) . '\'' : '') .
            " />";

            if (isset($args['description'])) {
                echo $this->get_description($args['description']);
            }
        };
    }

    /**
     *  Creates callback specified to select option
     * 
     *  @param string $id Id of option
     *  @return callback
     * 
     *  @since 0.4.9
     */
    protected function create_select_option($id) {
        $suffix = $this->get_suffix($id);
        $option_value = apply_filters('m2i_select_checked_value_' . $suffix, get_option($id), $id);
        $values_filter = 'm2i_select_values_' . $suffix;

        $this->options[$suffix] = $option_value;

        return function($args) use ($id, $option_value, $values_filter) {
            $values = apply_filters($values_filter, array($option_value => $option_value));

            printf('<select name="%1$s" id="%1$s">', $id);
            foreach ($values as $id => $item_value) {
                printf('<option value="%s" %s>%s</option>', $id, $id == $option_value ? 'selected' : '', $item_value);
            }
            print('</select>');

            if (isset($args['description'])) {
                echo $this->get_description($args['description']);
            }
        };
    }

    /**
     *  Creates callback specified to text option. <br>
     *  Also can <b>define</b> the constant from the option or vice versa, if needed.
     * 
     *  @param string $id Id of option
     *  @return callback
     * 
     */
    protected function create_text_option($id) {
        $suffix = $this->get_suffix($id);
        $constant_id = strtoupper($id);
        $will_be_constant = apply_filters('m2i_text_will_be_constant_' . $suffix, false, $id, $constant_id);

        $this->can_be_constant_exception($constant_id, $will_be_constant);

        if (!($is_constant = defined($constant_id))) {
            $option_value = apply_filters('m2i_text_value_' . $suffix, get_option($id), $id);

            if ($will_be_constant) {
                define($constant_id, $option_value);
            } else {
                $this->options[$suffix] = $option_value;
            }
        } else {
            $option_value = constant($constant_id);
        }

        return function($args) use ($id, $option_value, $is_constant) {
            $format = '<input type="text" class="regular-text code %1$s" %1$s id="%2$s" name="%2$s" value="%3$s">';
            printf($format, $is_constant ? 'disabled' : '', $id, esc_attr($option_value));

            if (isset($args['description'])) {
                echo $this->get_description($args['description']);
            }
        };
    }

    /**
     *  Registers filters, used in the settigns page, mainly for options
     */
    protected function register_filters() {
        add_filter('m2i_text_will_be_constant_mage_dir', array($this, 'text_constants_filter'), 10, 2);
        add_filter('m2i_select_checked_value_mage_store_code', function($value) {
            return $value === false ? 'default' : $value;
        });
        add_filter('m2i_select_values_mage_store_code', array($this, 'select_values_mage_store_code'));
        
        add_filter('m2i_mage_auto_adding', array($this, 'value_mage_flags_filter'));
        add_filter('m2i_flag_value_use_mage_layout_names', array($this, 'value_mage_flags_filter'));
        add_filter('m2i_select_checked_value_mage_header_block_name', function($value) {
            return $value === false ? 'base-header-container' : $value;
        });
        add_filter('m2i_select_values_mage_header_block_name', array($this, 'select_values_mage_block_name'));
        add_filter('m2i_text_value_mage_dir', array($this, 'text_value_mage_dir_filter'));
        add_filter('m2i_text_value_mage_header_tag', array($this, 'text_value_mage_tags_filter'));

        add_filter('m2i_flag_value_mage_header_flag', array($this, 'value_mage_flags_filter'));
        add_filter('m2i_flag_value_mage_styles_flag', array($this, 'value_mage_flags_filter'));

        do_action('m2i_register_settings_filters');
    }

    /* FILTERS */

    function text_constants_filter($will_be_constant, $id) {
        switch ($id) {
            case 'm2i_mage_dir':
            case 'm2i_mage_base_name':
                $will_be_constant = true;
        }

        return $will_be_constant;
    }

    function text_value_mage_dir_filter($value) {
        return empty($value) ? ABSPATH : $value;
    }

    function text_value_mage_tags_filter($value) {
        return empty($value) ? 'div' : $value;
    }

    function value_mage_flags_filter($value) {
        return $value === false ? 'on' : $value;
    }
    
    function select_values_mage_block_name($value) {
        $blocks = m2i_get_blocks();
        $values = array_merge($value, array_combine($blocks, $blocks));
        asort($values);
        return $values;
    }

    function select_values_mage_store_code($value) {
        $stores = m2i_get_stores();
        $values = array_merge($value, $stores);
        asort($values);
        return $values;
    }

    /**
     * FUNCTION OF RENDERING PAGE
     */
    function page($atts = array()) {
        $active_tab = $this->get_active_tab();
        ?>
        <div class="wrap">
            <div class="options-panel">
                <h2><?php echo 'Magento 2 Integration ' . __('Settings'); ?></h2>
                
                <?php $this->output_page_tabs(); ?>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields($active_tab);

                    do_settings_sections($active_tab);

                    submit_button();
                    ?>
                </form>
            </div>
            <div class="banner-block">
                <a href="https://modernmodules.com/m2ilinkpremium" target="_blank"><img src="https://modernmodules.com/pluginclude/m2i-premium.png" /></a>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php
    }
    
    /** @since 1.0.5 */
    function output_page_tabs(){
        $active_tab = $this->get_active_tab();
        echo '<h2 class="nav-tab-wrapper">';
        foreach($this->page_tabs as $key => $name){
            printf('<a href="?page=%s&tab=%s" class="nav-tab%s">%s</a>', $this->page_name, $key, $active_tab === $key ? ' nav-tab-active' : '', $name);
        }
        echo '</h2>';
    }
    
    /** @since 1.0.5 */
    public function __call($name, $params) {
        if(strpos($name, 'get_') === 0){
            $name = substr($name, 4);
            switch($name){
                case 'active_tab':
                    return (isset($_GET['tab']) ? $_GET['tab'] : key($this->page_tabs));
                default:
                    if(property_exists($this, $name)){
                        return $this->{$name};
                    }
                    break;
            }
        }
        
        return null;
    }
    
    /** @since 0.4.9 */
    function screen() {
        $current_screen = get_current_screen();

        if ($current_screen->id === 'settings_page_M2I_Settings') {
            M2I_External::launch();
        }
    }

}

M2I_Settings::init();
