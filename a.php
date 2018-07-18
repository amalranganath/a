<?php

/**
 * A is the main Framework helper class
 */
if (!defined('ABSPATH')) {
    exit;
}

class A {

    /**
     * Main configurations
     * @var array 
     */
    public static $config;
    public static $plugin;
    public static $controller;

    public function __construct($config) {
        if (!isset($config['id'])) {
            //throw exception
            throw new Exception('The "id" configuration for the Plugin is required.');
            exit();
        }
        self::$config = (object) $config;
        self::$plugin = new plugin();

        //init plugin
        add_action('plugins_loaded', [$this, 'init']);
        //load classes
        spl_autoload_register([__CLASS__, 'loadClasses']);
    }

    /**
     * Initiate the plug-in
     */
    public function init() {
        //form template loader as a shortcode [form template="tpl-name"]
        if (!is_admin())
            add_shortcode('form', array(__CLASS__, 'form'));

        //find query arguments & run controller action
        add_filter('parse_request', function($wp_query) {
            $request = explode('/', esc_sql($wp_query->request));
            //if not set a page 
            if (!isset($wp_query->query_vars["pagename"]) && $request[0] != '') {
                $class_name = $request[0] ? ucfirst($request[0]) : null;
                //init controller
                if (class_exists($class_name)) {
                    $controller = self::$controller = new $class_name;
                    //set action
                    if (isset($request[1])) {
                        if (isset($request[2])) {
                            $params = $request[2];
                        }
                        $controller->action = $request[1];
                    }
                    //call a valid action
                    if ($controller != null && method_exists($controller, $controller->action)) {
                        //has parameters
                        $Method = new ReflectionMethod($controller, $controller->action);
                        if ($Method->getNumberOfParameters() > 0 && isset($params))
                            $controller->{$controller->action}($params);
                        else
                            $controller->{$controller->action}();
                    }
                }
            }
            return $wp_query;
        });

        //run admin controller
        if (is_admin() && isset(self::$config->admin['class'])) {
            $admin = self::$config->admin['class'];
            $admin::getInstance();
        }
    }

    /**
     * Form short code
     * @param array $atts
     * @return mixed
     */
    public static function form($atts) {
        //attributes
        $atts = shortcode_atts(['model' => '', 'template' => '', 'action' => ''], $atts);
        extract($atts);
        $file = A::$config->basePath . "views/$template.php";

        //start rendering
        ob_start();
        if ($model == '') {
            ANotify::flash("error", "Please provide a model class name to populate the AForm!");
        } else if ($template == '' || !file_exists($file)) {
            ANotify::flash("error", "Please provide a valid template name to load view");
        } else {
            $model = new $model;
            require_once $file;
        }
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Load class file.
     * @param string $class_name The called class name
     */
    public static function loadClasses($class_name) {
        $file = str_replace('_', '', ($class_name));
        //if a model class
        if (file_exists(A::$config->basePath . "models/$file.php")) {
            include_once A::$config->basePath . "models/$file.php";
        }
        //if a controllers class
        if (file_exists(A::$config->basePath . "controllers/" . $file . "Controller.php")) {
            include_once A::$config->basePath . "controllers/" . $file . "Controller.php";
        }
        //if a admin controllers class
        if (file_exists(A::$config->basePath . "controllers/admin/" . $file . "Controller.php")) {
            include_once A::$config->basePath . "controllers/admin/" . $file . "Controller.php";
        }
    }

}

/**
 * Plugin helper class
 */
class plugin {

    /**
     * Plugin options
     * @var array 
     */
    public $options;

    public function __construct() {
        //set opions
        $options = get_option(A::$config->id);
        $this->options = $options != '' ? $options : [];
    }

    /**
     * Get options
     * @param string $option
     */
    public function get($option) {
        return isset($this->options[$option]) ? $this->options[$option] : '';
    }

}
