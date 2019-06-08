<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('BaseModel')) {

    /**
     * BaseModel Class.
     *
     * @package  Plug-in/Core
     * @category Model
     * @author   Amal Ranganath
     * @version  1.0.6
     */
    class BaseModel {

        /**
         * post attributes
         * @var array 
         */
        public $attributes = array();

        /**
         * error message
         * @var string 
         */
        public $error = '';

        /**
         * $wpdb global object
         * @var object 
         */
        public static $wpdb;

        /**
         * Table identity
         * @var string 
         */
        public $table;

        /**
         * Fields to select
         * @var string 
         * @since 1.0.6
         */
        private $fields = '*';

        /**
         * Query to run
         * @var string 
         * @since 1.0.6
         */
        private $query;

        /**
         * Join query
         * @var string 
         */
        public $join;

        /**
         * Set $wpdb object and table name
         * @global type $wpdb
         */
        public function __construct() {
            global $wpdb;
            self::$wpdb = $wpdb;
            $this->table = $wpdb->prefix . static::TABLE_NAME;
        }

        /**
         * Return property value or "get_name" method if exits
         * @param string $name
         */
        public function __get($name) {
            if (property_exists($this, $name)) {
                return $this->$name;
            }
            $method = 'get_' . $name;
            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }

        /**
         * Get label by attribute
         * @param string $attr
         * @return string
         */
        public function attrLabel($attr) {
            $lebels = static::attributLabels();
            return isset($lebels[$attr]) ? $lebels[$attr] : ucfirst($attr);
        }

        /**
         * Set post attributes before insert
         * @param void $data
         */
        public function setAttributes($data = []) {
            if (empty($data)) {
                //init
                $data = array_fill_keys(array_keys(static::attributLabels()), null);
            }
            if (is_array($data)) {
                $this->attributes = self::$wpdb->_escape($data);
            } else {
                parse_str($data, $this->attributes);
            }
            foreach ($this->attributes as $name => $value)
                if (property_exists($this, $name)) {
                    $this->$name = $this->attributes[$name] = stripslashes_deep($value);
                } else {
                    unset($this->attributes[$name]);
                }
        }

        /**
         * Insert into db
         * @return boolean
         */
        public function insert() {
            //call before insert data
            if (method_exists($this, 'beforeInsert'))
                $this->beforeInsert();
            //if inserted
            if (self::$wpdb->insert($this->table, $this->attributes)) {
                $this->{static::PRIMARY_KEY} = self::$wpdb->insert_id;
                return true;
            }

            return $this->error();
        }

        /**
         * Update the db
         * @param string $attr
         * @return boolean
         */
        public function update($attr = null) {
            //call before update data
            if (method_exists($this, 'beforeUpdate')) {
                $this->beforeUpdate();
            }
            $attr = ($attr == null ? static::PRIMARY_KEY : $attr);
            if (self::$wpdb->update($this->table, $this->attributes, [$attr => $this->$attr])) {
                return true;
            }

            return $this->error();
        }

        /**
         * Delete from db
         * @return boolean
         */
        public function delete() {
            $pk = static::PRIMARY_KEY;
            if (self::$wpdb->delete($this->table, [$pk => (int) $this->$pk])) {
                return true;
            }

            return $this->error();
        }

        /**
         * Check for duplicates
         * @param string $attr
         * @param mixed $value
         * @return boolean
         */
        public function isExists($attr, $value = null) {
            //$where = (is_array($attr)) ? http_build_query($attr, null, ' AND ') : "$this->table.$attr = '$value'";
            return $this->select($attr)->where("$attr='$value'")->single() || false;
        }

        /**
         * Set fields
         * @param string $fields
         * @since 1.0.6
         */
        public function select($fields = '*') {
            $this->fields = $fields;
            $this->query = "SELECT $this->fields FROM $this->table";
            return $this;
        }

        /**
         * Set conditions
         * @param string|array $condition mysql WHERE conditions to apply
         * @since 1.0.6
         */
        public function where($condition, $operator = 'AND') {
            //has no condition
            if ($condition == '') {
                return $this;
            }
            $where = (is_array($condition)) ? http_build_query($condition, $this->table, " $operator ") : "$condition";
            $this->query .= $this->join != '' ? "$this->join WHERE $where" : " WHERE $where";
            return $this;
        }

        /**
         * Set Order by
         * @param string $field Order by field, required
         * @param string $order Order default DESC
         * @return $this
         */
        public function orderby($field, $order = 'DESC') {
            $this->query .= " ORDER BY $field $order";
            return $this;
        }

        /**
         * Find a single record
         * @param string $output The return type. One of OBJECT, ARRAY_A, or ARRAY_N. default OBJECT
         * @return array|object|boolean
         * @since 1.0.6
         */
        public function single($output = OBJECT) {
            //$where = (is_array($attr)) ? http_build_query($attr, $this->table, ' AND ') : "$this->table.$attr = '$value'";
            //$row = self::$wpdb->get_row($this->join != '' ? "$this->join WHERE $where" : "SELECT * FROM $this->table WHERE $where");
            if ($row = self::$wpdb->get_row($this->query, $output)) {
                $this->setAttributes((array) $row);
                return $row;
            }

            return $this->error();
        }

        /**
         * Find all records 
         * @param string $output The return type. One of OBJECT, ARRAY_A, or ARRAY_N. default OBJECT
         * @return array|object|boolean
         * @since 1.0.6
         */
        public function all($output = OBJECT) {
            //var_dump($this->query);
            //has records
            if ($results = self::$wpdb->get_results($this->query, $output)) {
                return $results;
            }
            $this->error = __("No results found", A::$config->i18n);
            return false;
        }

        /**
         * call error
         * @return boolean
         * @since 1.0.6
         */
        public function error() {
            $this->error = self::$wpdb->last_error;
            return false;
        }

    }

}