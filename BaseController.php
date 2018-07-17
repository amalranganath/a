<?php

/**
 * Base Controller class.
 * the base controller class
 * @package  Plug-in/Core
 * @author   Amal Ranganath
 * @version  1.0.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Base_Controller')) {

    /**
     * This class handles underground controlling parts like rendering views.
     */
    class BaseController {

        /**
         * The controller identity
         * @var string 
         */
        protected $id;

        /**
         * Requested controller action
         * @var string
         */
        public $action = 'index';
        public $template;
        public $title;
        public $content;

        /**
         * The child controller related model
         * @var object 
         */
        protected $model;

        public function __construct() {
            $this->id = strtolower(get_called_class());

            //has a valid controller action
            add_filter('template_include', function($template) {
                if ($this->id != null && method_exists($this, $this->action)) {
                    global $wp_query;
                    $wp_query->is_404 = false;
                    //$wp_query->query_vars['page'] = 2;
                    return $this->template;
                } else {
                    return $template;
                }
            });

            //title filter
            add_filter('the_title', [$this, 'getTitle'], 10, 2);
            add_filter('wp_title', [$this, 'headTitle'], 1, 3);
        }

        public function headTitle($title, $sep, $seplocation) {
            var_dump($title);
            return $this->title;
        }

        /**
         * Get the title
         * @param string $title
         * @param integer $id
         * @return type
         */
        public function getTitle($title, $id) {
            return $id == 0 ? $this->title : $title;
        }

        
        /**
         * Render view by given template
         * @param string $template Template file name (required)
         * @param array $atts Attributes to pass (optional)
         */
        protected function render($template, $atts = []) {
            //set path
            $path = A::$config->basePath . "views/";
            $layout = $path . "layout.php";
            if (is_admin())
                $path .= "admin/";
            else if (isset($this->id))
                $path .= $this->id . "/";
            $file = $path . "$template.php";

            //render view
            if (file_exists($file)) {
                if (!empty($atts))
                    extract($atts);
                if (is_admin())
                    require_once $file;
                else {
                    ob_start();
                    //hooked before load the template
                    do_action('before_load_template', $template);
                    require_once $file;
                    //hooked after loaded the template
                    do_action('after_template_loaded', $template);
                    $this->content = ob_get_contents();
                    ob_end_clean();
                }
                $this->template = file_exists($layout) ? $layout : $file;
            } else {
                ANotify::flash("error", "Could not locate the template \"$template\" in path \"$path\" ");
            }
        }

    }

}