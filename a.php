<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A Class
 * This is the framework main helper class
 * 
 * @package  Plug-in/Core
 * @author   Amal Ranganath
 * @version  1.0.1
 */
class A {

    /**
     * Main configurations
     * @var array 
     */
    public static $config;

    /**
     * Plug-in instance
     * @var object 
     */
    public static $plugin;

    /**
     * Current controller instance
     * @var object 
     */
    public static $controller;

    public function __construct($config) {
        if (!isset($config['id'])) {
            //throw exception
            throw new Exception('The "id" configuration for the Plugin is required.');
            exit();
        }

        self::$config = (object) $config;

        spl_autoload_register([__CLASS__, 'loadClasses']);

        self::$plugin = new plugin();

        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Initiate the plug-in
     */
    public function init() {

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
                        if (isset($request[2]))
                            $params = $request[2];
                        $controller->action = $request[1];
                    }

                    //call a valid action
                    if (isset($controller->action) && method_exists($controller, $controller->action)) {

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
     * Retrieve the translation of $text
     * @param string $text
     * @return string
     */
    public static function t($text) {
        return translate($text, self::$config->i18n);
    }

    /**
     * Load class file.
     * @param string $class_name The called class name
     */
    public static function loadClasses($class_name) {

        $file = str_replace('_', '', ($class_name));

        //if a model class
        self::locate("models/$file");

        //if a controllers class
        self::locate("controllers/" . $file . "Controller");

        //if a admin controllers class
        self::locate("controllers/admin/" . $file . "Controller");

        //if a component class
        self::locate("components/$file");
    }

    /**
     * Load template 
     * @since 1.0.1
     * @param string $path
     * @param boolean $include
     */
    public static function locate($path, $include = true) {

        $template = A::$config->basePath . "$path.php";

        if (file_exists($template)) {
            if ($include)
                include_once $template;
            else
                return $template;
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

        /**
         * Load components
         * @since 1.0.1
         */
        if (is_array($this->components))
            foreach ($this->components as $comp) {
                if (class_exists($comp))
                    new $comp();
            }
    }

    /**
     * Getter
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        //has a propert
        if (isset(A::$config->$name))
            return A::$config->$name;
    }

    /**
     * Get options
     * @param string $option
     */
    public function get($option) {
        return isset($this->options[$option]) ? $this->options[$option] : '';
    }

}
