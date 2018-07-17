<?php
/**
 * WPAdmin Class 
 * 
 * @package  Plug-in/Core
 * @category Controller
 * @author   Amal Ranganath
 * @version  1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WPAdmin')) {

    /**
     * This is the parent helper class to create an admin portal in your Plug-in.<br/>When creating child for this<br/>
     * You need to call the "parent::__construct()" in the child constructor and define the actions() method where you can code controller actions<br/>
     * Can also be overridden main configuration parameters,  
     * @param array $mainMenu The main menu item attributes
     * @param array $pages All the pages and tabs going to be included
     * @param string $capability WP user access level
     */
    class WPAdmin extends BaseController {

        /**
         * The instance of this class object.
         * @static
         * @var object
         */
        private static $instance;

        /**
         * Main menu item info
         * @var string
         */
        protected static $mainMenu;

        /**
         * Allowed pages and tabs info
         * @var array
         */
        protected static $pages;

        /**
         * Menu item capability
         * @var string 
         */
        protected static $capability = 'manage_options';

        /**
         * Requested page
         * @var type 
         */
        protected $page;

        /**
         * Active template name
         * @var string
         */
        public $current;

        /**
         * Admin panel tabs with related model class
         * @var array
         */
        public $tabs;

        public function __construct() {
            static::$mainMenu = A::$config->admin['mainMenu'];
            static::$pages = A::$config->admin['pages'];
            if (!isset(static::$mainMenu)) {
                ANotify::flash('error', 'Please defin the static::$mainMenu array in your chiled ' . get_called_class() . ' class.');
                exit();
            }
            $this->page = isset($_REQUEST['page']) ? $_REQUEST['page'] : static::$mainMenu['slug'];
            $this->current = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : ($this->page != static::$mainMenu['slug'] ? $this->page : '');
            $this->action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'form';

            if (isset(static::$pages[$this->page]['tabs'])) {
                $this->tabs = static::$pages[$this->page]['tabs'];
                if ($this->current == '')
                    $this->current = key($this->tabs);
            }
            //init
            add_action('wp_loaded', array($this, 'init'));
            //add admin menu items
            add_action('admin_menu', array($this, 'menuItems'), 50);
        }

        /**
         * Format text
         * @since    1.0.0
         * @param string $text
         * @return string
         */
        public static function sanitize_text($text) {
            $str = str_replace(array('_', '-'), ' ', $text);
            return ucfirst($str);
        }

        /**
         * Return an instance of this class.
         * @since    1.0.0
         * @return  object  A single instance of this class.
         */
        public static function getInstance() {
            $class = get_called_class();
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new $class();
            }

            return self::$instance;
        }

        /**
         * Initial call
         */
        public function init() {
            //call page actions
            if (isset(static::$pages[$this->page])) {
                $action = "action" . ucfirst($this->page);
                if (method_exists($this, $action))
                    $this->$action();
                else
                    $this->actions();
            }
        }

        /**
         * Create menu items
         * @since    1.0.0
         */
        public function menuItems() {
            //main menu item
            add_menu_page(__(static::$mainMenu['pageTitle'], A::$config->i18n), __(static::$mainMenu['title'], A::$config->i18n), static::$capability, static::$mainMenu['slug'], array($this, 'renderPage'), static::$mainMenu['icon'], static::$mainMenu['position']);
            //sub menu items
            foreach (static::$pages as $page) {
                add_submenu_page(static::$mainMenu['slug'], __($page['pageTitle'], A::$config->i18n), __($page['title'], A::$config->i18n), static::$capability, $page['slug'], array($this, 'renderPage'));
            }
        }

        /**
         * Display current settings page
         * @since    1.0.0
         */
        public function renderPage() {

            //enqueue scripts
            wp_enqueue_media();
            ?>
            <div class="wrap">
                <?php if ($this->tabs != null): ?>
                    <?php
                    //hooked the panel tabs
                    $tabs = apply_filters("admin_{$this->page}_tabs", $this->tabs);
                    //validate for a existing tab
                    if (!array_key_exists($this->current, $tabs))
                        ANotify::flash('error', "Undefined tab '$this->current' !");
                    ?>
                    <h2 class="nav-tab-wrapper">
                        <?php
                        foreach ($tabs as $tab => $title)
                            echo '<a href="' . admin_url("admin.php?page=" . $this->page . "&tab=" . $tab) . '" class="nav-tab ' . ( $this->current == $tab ? 'nav-tab-active' : '' ) . '">' . __($title, A::$config->i18n) . '</a>';
                        ?>
                    </h2>
                <?php endif; ?>
                <?php
                //load template
                if (isset($_REQUEST['action']))
                    $this->render("$this->current-$this->action", ['options' => A::$plugin->options]);
                else
                    $this->render("$this->current", ['options' => A::$plugin->options]);
                ?>
            </div>
            <?php
        }

        /**
         * Save the settings.
         */
        public static function save() {

            if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'pc-settings')) {
                die(__('Action failed. Please refresh the page and retry.', A::$config->i18n));
            }
        }

    }

}
