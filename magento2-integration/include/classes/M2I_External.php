<?php

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ErrorHandler;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Static class of managing the connection between WordPress and Magento 2 for further usages
 */
class M2I_External {

    /** @var Bootstrap|null */
    static protected $bootstrap = null;

    /** @var \Magento\Framework\App\Http|null */
    static protected $app = null;

    /** @var \Magento\Framework\App\ResponseInterface|null */
    static protected $response = null;

    /** @var \Magento\Framework\View\LayoutInterface|null */
    static protected $layout = null;

    /** @var array Store of real WordPress $_SERVER by key <b>'wp'</b> and Magento2 $_SERVER by key <b>'mage'</b> */
    static protected $params;

    /**
     *  @var ErrorHandler|null Magento2 error handler 
     * 
     *  @since 0.4.9
     */
    static protected $error_handler = null;

    /**
     *  @var bool Is "launch" method able to do main things? 
     * 
     *  @since 0.4.9
     */
    static protected $can_launch = false;

    /**
     *  @var bool Is "launch" method was executed normaly?
     *  
     *  @since 0.4.9
     */
    static $was_launched = false;

    /** @var bool */
    static $is_ajax = false;

    /** @var string */
    static $mage_dir;

    /** @var bool */
    static $needs_mage_translate = false;

    /**
     *  Init. for Magento 2 external process (can be used with ajax and normal execution)
     * 
     *  @param bool $is_ajax Should be executed in the ajax mode? 
     * 
     *  @since 0.3.5 Added $is_ajax param.
     */
    static function init($is_ajax = false) {
        
        self::$is_ajax = $is_ajax;
        self::init_dir();

        self::$bootstrap = self::init_bootstrap();

        if (is_object(self::$bootstrap)) {
            self::modify_server_env();
            self::$app = self::init_app();
            self::restore_server_env();
            self::$can_launch = true;
        }
    }

    /**
     *  Launches fully all Magento2 processes 
     * 
     *  @since 0.4.9
     */
    static function launch() {
        if (self::$can_launch && !self::$was_launched) {
            self::modify_server_env();
            self::$needs_mage_translate = true;
            
            try {
                self::launch_app();
                $obj = self::$bootstrap->getObjectManager();
                self::$layout = $obj->get('Magento\Framework\View\Layout');
                
                /* @var Magento\Framework\View\DesignInterface $design */
                $design = $obj->get('Magento\Framework\View\DesignInterface');
                $theme = $design->getConfigurationDesignTheme('frontend');
                $design->setDesignTheme($theme);
                /* @var \Magento\Framework\App\AreaInterface $area */
                $area = $obj->create('\Magento\Framework\App\AreaInterface', array('areaCode' => $design->getArea()));
                $area->load();
                /* @var Magento\Framework\TranslateInterface $translate */
                $translate = $obj->get('Magento\Framework\TranslateInterface');
                $locale = $design->getLocale();
                /* @var Magento\Framework\View\Result\Page $resultPage */
                $resultPage = $obj->get('Magento\Framework\View\Result\Page');
                $resultPage->getConfig()->setPageLayout('2columns-right');
                $resultPage->addHandle('cms_page_view');
                $resultPage->addPageLayoutHandles(['id' => 'no-route']);
                $resultPage->initLayout();
                $contentHeadingBlock = $resultPage->getLayout()->getBlock('page_content_heading');
                
            } catch (NoSuchEntityException $e) {
                self::maybe_store_error($e);
            }
            
            self::$needs_mage_translate = false;
            self::restore_server_env();

            /* TODO: Think more about error handler */
            /* restore_error_handler(); */
            self::$was_launched = true;
        }
    }
    
    /**
     * @param NoSuchEntityException $e Error to analize
     * 
     * @since 1.0.4
     */
    static protected function maybe_store_error(NoSuchEntityException $e){
        $message = $e->getMessage();
        if (stripos($message, 'store') !== false && stripos($message, 'not found') !== false){
            self::store_code_error();
        }
    }

    /** @return \Magento\Framework\App\Response\Http */
    static function get_response() {
        return self::$response;
    }

    /**
     * An transformed alias for __() function in Magento2 
     * 
     * @param array Arguments
     * @return \Magento\Framework\Phrase
     *  
     * @since  0.2.5
     */
    static function translate($argc) {
        $text = array_shift($argc);
        if (!empty($argc) && is_array($argc[0])) {
            $argc = $argc[0];
        }
        return new \Magento\Framework\Phrase($text, $argc);
    }

    /**
     *  Do a basic role of bootstrap.php file
     * 
     *  @param string $autoload_file_path Path to autoload file
     */
    static protected function pre_load($autoload_file_path) {
        require_once $autoload_file_path;

        $umaskFile = BP . '/magento_umask';
        $mask = file_exists($umaskFile) ? octdec(file_get_contents($umaskFile)) : 002;
        umask($mask);
    }

    /**
     * Universal init. of Magento Dir. name and base name (working both for ajax and normal execution) 
     * 
     * @since 0.2
     */
    static protected function init_dir() {
        self::$mage_dir = M2I_MAGE_DIR;

        if (is_ajax() && self::$is_ajax) {
            self::$mage_dir = $_POST['m2i_mage_dir'];
        } else {
            self::$is_ajax = false;
        }
    }

    /** @return Bootstrap */
    static protected function init_bootstrap() {
        $autoload_file_path = self::$mage_dir . '/app/autoload.php';

        if (!is_file($autoload_file_path)) {
            self::autoload_file_error();
        } else {
            self::pre_load($autoload_file_path);

            if (!class_exists('Magento\Framework\App\Bootstrap')) {
                self::bootstrap_class_error();
            } else {
                self::$params['mage'] = self::get_converted_params();
                
                self::$params['mage'][Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = array(
                    DirectoryList::PUB => array(DirectoryList::URL_PATH => ''),
                    DirectoryList::MEDIA => array(DirectoryList::URL_PATH => 'media'),
                    DirectoryList::STATIC_VIEW => array(DirectoryList::URL_PATH => 'static'),
                    DirectoryList::UPLOAD => array(DirectoryList::URL_PATH => 'media/upload'),
                );
                
                self::maybe_enable_selected_store();
                self::$bootstrap = Bootstrap::create(BP, self::$params['mage']);
            }
        }
        
        return self::$bootstrap;
    }
    
    /**
     * Think before enabling selected store. Not enable if it is base settings page of current plugin.
     * 
     * @since 1.0.4
     */
    static protected function maybe_enable_selected_store(){
        global $m2i_options;
        
        if(!(!is_ajax() && m2i_is_active_base_tab())){
            self::$params['mage'][StoreManager::PARAM_RUN_TYPE] = 'store';
            self::$params['mage'][StoreManager::PARAM_RUN_CODE] = $m2i_options['mage_store_code'];
        }
    }

    /** @return \Magento\Framework\AppInterface */
    static protected function init_app() {
        return self::$app = is_object(self::$bootstrap) ? self::$bootstrap->createApplication('Magento\Framework\App\Http') : null;
    }

    /**
     *  Launch app. and init. response object for futher using of getting the html content in the functions.php 
     * 
     *  @since 0.2
     */
    static protected function launch_app() {
        self::$error_handler = new ErrorHandler;
        set_error_handler(array(self::$error_handler, 'handler'));
        self::$response = self::$app->launch();
    }

    /** @return array Params adapted for Magento2 enviroment */
    static protected function get_converted_params() {
        $params = $_SERVER;
        
        $params['REQUEST_URI'] = $params['REDIRECT_URL'] = '/';
        
        /** 
         * TODO: study more about Magento2 behavior for REQUEST_METHOD 
         * @since 1.0.4
         */
        $params['REQUEST_METHOD'] = 'POST';
        
        return $params;
    }

    /** Generate error when file for Bootstrap is not founded */
    static protected function autoload_file_error() {
        self::generate_error(1, 'm2i_admin_autoload_file__error');
    }

    /** Generate error when Bootstrap class is not founded */
    static protected function bootstrap_class_error() {
        self::generate_error(2, 'm2i_admin_bootstrap_class__error');
    }
    
    /** Generate error when store is not founded */
    static protected function store_code_error(){
        self::generate_error(3, 'm2i_admin_store_code__error');
    } 

    /**
     *  @param int|string $ajax_code Code of error for futher transfering to script
     *  @param string $notice_callback Name of the notice callback (will be used in the action)
     */
    static protected function generate_error($ajax_code, $notice_callback) {
        if (self::$is_ajax) {
            echo $ajax_code;
            wp_die();
        } else {
            add_action('admin_notices', $notice_callback);
        }
    }
    
    /**
     *  Safety way to modify <b>$_SERVER</b> for Magento2 using 
     * 
     *  @since 1.0.0
     */
    static protected function modify_server_env(){
        $callback = function($n){
            return is_array($n) ? serialize($n) : $n;
        };
        if(count(array_diff_assoc( array_map($callback, $_SERVER), array_map($callback, self::$params['mage']) ))){
            self::$params['wp'] = $_SERVER;
            $_SERVER = self::$params['mage'];
        }
    }
    
     /**
     *  Safety way to restore <b>$_SERVER</b> for WordPress using 
     * 
     *  @since 1.0.0
     */
    static protected function restore_server_env(){
        if(!empty(self::$params['wp'])){
            $_SERVER = self::$params['wp'];
        }
    }

    /** @return Bootstrap|null */
    static public function get_bootstrap() {
        return self::$bootstrap;
    }

    /** @return \Magento\Framework\App\Http|null */
    static public function get_app() {
        return self::$app;
    }

    /** @return \Magento\Framework\View\LayoutInterface|null */
    static public function get_layout() {
        return self::$layout;
    }

}
