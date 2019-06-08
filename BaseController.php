<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Base_Controller')) {

    /**
     * Base Controller class.
     * This class handles underground controlling parts like rendering titles, views.
     * 
     * @package  Plug-in/Core
     * @author   Amal Ranganath
     * @version  1.0.1
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
            add_filter('document_title_parts', [$this, 'docTitle']); //document_title_parts pre_get_document_title
        }

        /**
         * Page title
         * @param array $title
         * @return string
         */
        public function docTitle($parts) {
            //set title
            $parts['title'] = $this->title;
            return $parts;
        }

        /**
         * Get the title
         * @param string $title Post Title
         * @param integer $id Post ID
         * @return string
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
            $path = A::$plugin->basePath . "views/";
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