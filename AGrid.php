<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit; 
}
//admin includes
if (!is_admin()) {
    require_once ABSPATH . 'wp-admin/includes/template.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    /** WordPress Administration Screen API */
    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    wp_enqueue_style('list-tables');
    //wp_enqueue_script('wp-lists');
}

if (!class_exists('AGrid')) {

    /**
     * AGrid Class.
     *
     * @package  Plug-in/Core
     * @category Model
     * @author   Amal Ranganath
     * @version  1.0.0
     */
    class AGrid extends WP_List_Table {

        /**
         * The related model
         * @var object 
         */
        public $model;

        /**
         * Configurations
         * @var array 
         */
        public static $config = [];
        public static $pk;

        /**
         * create grid object
         */
        public function __construct() {
            global $wp_scripts;
            //ANotify::dupm($wp_scripts);
            $config = self::$config;
            if (!isset($config['class'])) {
                ANotify::flash('error', 'You must provide a model class to initiate the gridview');
                exit();
            }

            if (class_exists($config['class'])) {
                $this->model = new $config['class'];
            } else {
                ANotify::flash('error', 'Undefined model class "' . $config['class'] . '" ');
                exit();
            }
            self::$pk = $config['class']::PRIMARY_KEY;
            //call parent constructor
            parent::__construct([
                'singular' => A::t($config['class']::TABLE_NAME), //singular name of the listed records
                'plural' => A::t($config['class']::TABLE_NAME . "s"), //plural name of the listed records
                'ajax' => false, //should this table support ajax?
                'screen' => $config['class']::TABLE_NAME
            ]);

            //set columns
            $this->setColumns();
            add_action('wp_footer', [__CLASS__, 'footer_scripts']);
            add_action('admin_footer', [__CLASS__, 'footer_scripts']);
        }

        public static function footer_scripts() {
            ?>
            <script type="text/javascript">
                jQuery('.form-filter input').on('change', function () {
                    url = jQuery("input[name='_wp_http_referer']");
                    console.log(url.val())
                    if (url) {
                        jQuery('.form-filter').attr('action', url.val()).submit()
                    }
                    //console.log(this);
                    //jQuery('.form-filter').submit();
                });
            </script>
            <?php
        }

        /**
         * Display grid
         * @param array $config
         */
        public static function view($config = []) {
            self::$config = $config;
            $grid = new self();
            $grid->prepare_items();

            //add search box
            if (!isset($config['search']) && $config['search'])
                $grid->search_box('Search', 'search');

            $grid->views();
            $grid->display();
        }

        /**
         * To be implemented
         * @return array
         */
        protected function get_views() {
            //  return ['id' => 'link'];
        }

        /**
         * Overridden - Display the table
         *
         * @since 3.1.0
         */
        public function display() {
            $singular = $this->_args['singular'];
            $this->display_tablenav('top');
            $this->screen->render_screen_reader_content('heading_list');
            ?>
            <form action="" method="<?= is_admin() ? 'POST' : 'GET' ?>" class="form-filter">
                <table class="wp-list-table <?= implode(' ', $this->get_table_classes()); ?>">
                    <thead>
                        <tr>
                            <?php $this->print_column_headers(); ?>
                        </tr>           
                        <?php if (isset(self::$config['filter'])): ?>
                            <tr >
                                <?php $this->printFilters(); ?>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody id="the-list"<?php
                           if ($singular) {
                               echo " data-wp-lists='list:$singular'";
                           }
                           ?>>
                               <?php $this->display_rows_or_placeholder(); ?>
                    </tbody>
                    <?php if (isset(self::$config['footer']) ? self::$config['footer'] : true): ?>
                        <tfoot>
                            <tr>
                                <?php $this->print_column_headers(false); ?>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </form>
            <?php
            $this->display_tablenav('bottom');
        }

        /**
         * Render filtering fields
         */
        private function printFilters() {
            foreach (self::$config['columns'] as $attr => $parms) {
                echo "<td>";
                if (isset(self::$config['filter'][$attr])) {
                    echo self::$config['filter'][$attr] ?
                            AHtml::tag('input', '', ['type' => 'text', 'name' => $attr, 'value' => isset($_REQUEST[$attr]) ? $_REQUEST[$attr] : '']) :
                            self::$config['filter'][$attr];
                }
                echo "</td>";
            }
        }

        /**
         * Arrange columns to display
         */
        private function setColumns() {
            $model = self::$config['class'];
            $columns = $model::attributLabels();

            //has defined columns 
            if (isset(self::$config['columns'])) {
                foreach (self::$config['columns'] as $attr => $parms) {
                    if (is_int($attr)) {
                        $attr = $parms;
                    }
                    //iff model has attribute 
                    if (isset($columns[$attr])) {
                        $this->configColumn($attr, $parms);
                        $cols[$attr] = isset($parms['label']) ? $parms['label'] : $columns[$attr];
                    } elseif (isset($parms['label'])) { //iff user has configured                   
                        if (!isset($parms['value']))
                            $parms['value'] = "Error: Could not find any method or a value!";
                        $this->configColumn($attr, $parms);
                        $cols[$attr] = $parms['label'];
                    } else { //default
                        $cols[$attr] = $attr;
                    }
                }
                $columns = $cols;
            } else {
                self::$config['sortable'] = [];
            }
            //set columns
            self::$config['columns'] = $columns;
        }

        /**
         * setting column configurations
         * @param string $attr
         * @param array $val
         */
        private function configColumn($attr, $parms) {
            if (isset($parms['value'])) {
                self::$config['value'][$attr] = $parms['value'];
            }
            if (isset($parms['sortable']) && $parms['sortable']) {
                self::$config['sortable'][$attr] = [$attr, false];
            }
            if (isset($parms['actions'])) {
                self::$config['actions'][$attr] = $parms['actions'];
            }
            if (isset($parms['filter'])) {
                self::$config['filter'][$attr] = $parms['filter'];
            }
            if (isset($parms['hidden'])) {
                self::$config['hidden'][$attr] = $parms['hidden'];
            }
        }

        /**
         * if defined columns and  
         * @return array
         */
        public function get_columns() {
            return self::$config['columns'];
        }

        /**
         * Handles data query and filter, sorting, and pagination.
         */
        public function prepare_items() {
            //Process bulk action
            //$this->process_bulk_action();
            //column header
            $this->_column_headers = array(self::$config['columns'], [], self::$config['sortable']);

            $table = $this->model->table;

            //filter conditions
            $condition = "";
            foreach ($this->get_columns() as $attr => $label) {
                if (isset($_REQUEST[$attr]) && $_REQUEST[$attr] != "")
                    $condition .= ($condition == "" ? "" : " AND") . " $attr LIKE '%" . $_REQUEST[$attr] . "%'";
            }
            //order by
            $orderby = empty($_REQUEST['orderby']) ? self::$pk : $_REQUEST['orderby']; //If no sort, default to id
            $order = empty($_REQUEST['order']) ? 'desc' : $_REQUEST['order']; //If no order, default to desc
            //$condition .= " ORDER BY $orderby $order";

            $this->items = array();

            if ($items = $this->model->select()->where($condition)->orderby($orderby, $order)->all(ARRAY_A)) {
                //pagination
                $per_page = $this->get_items_per_page('_per_page');
                $this->set_pagination_args([
                    'total_items' => count($items), // the total number of items
                    'per_page' => $per_page //determine how many items to show on a page
                ]);
                $this->items = array_slice($items, (($this->get_pagenum() - 1) * $per_page), $per_page);
            }
        }

        /**
         * Overridden - Generates content for a single row of the table
         *
         * @since 3.1.0
         *
         * @param object $item The current item
         */
        public function single_row($item) {

            if (isset(self::$config['beforeSingleRow']))
                echo call_user_func(self::$config['beforeSingleRow'], $item, $this);

            parent::single_row($item);

            if (isset(self::$config['aftreSingleRow']))
                echo call_user_func(self::$config['aftreSingleRow'], $item, $this);
        }

        /**
         * Overridden - Generates the columns for a single row of the table
         *
         * @since 3.1.0
         * @access protected
         *
         * @param object $item The current item
         */
        protected function single_row_columns($item) {
            //list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
            $primary = $this->get_primary_column_name();
            //render columns
            foreach (self::$config['columns'] as $attr => $column_display_name) {
                $classes = "$attr column-$attr";
                if ($primary === $attr) {
                    $classes .= ' has-row-actions column-primary';
                }

                if (isset(self::$config['hidden'][$attr])) {//in_array($attr, $hidden)
                    $classes .= ' hidden';
                }

                // Comments column uses HTML in the display name with screen reader text.
                // Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
                $data = 'data-colname="' . wp_strip_all_tags($column_display_name) . '"';

                $attributes = "class='$classes' $data";

                if ('cb' === $attr) {
                    echo '<th scope="row" class="check-column">';
                    echo AHtml::tag('input', '', ['type' => 'checkbox', 'name' => 'a', 'value' => $item[self::$pk]]);
                    echo '</th>';
                } elseif (isset(self::$config['value'][$attr])) {
                    echo "<td $attributes>";
                    echo is_callable(self::$config['value'][$attr]) ?
                            call_user_func(self::$config['value'][$attr], $item) :
                            self::$config['value'][$attr];
                    echo $this->action($attr, $item);
                    echo $this->handle_row_actions($item, $attr, $primary);
                    echo "</td>";
                } else {
                    echo "<td $attributes>";
                    echo $item[$attr]; //$this->column_default($item, $attr);
                    echo $this->action($attr, $item);
                    echo $this->handle_row_actions($item, $attr, $primary);
                    echo "</td>";
                }
            }
        }

        /**
         * Render an action
         * @param string $col
         * @param array $item
         * @return string
         */
        protected function action($col, $item) {
            if (isset(self::$config['actions'][$col])) {
                return $this->row_actions(call_user_func(self::$config['actions'][$col], $item));
            }
        }

    }

}