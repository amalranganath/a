<?php
//namespace Core;

/**
 * ANotify class.
 * @package  Plug-in/Core
 * @author   Amal Ranganath
 * @version  1.0.1
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('ANotify')) {

    class ANotify {

        /**
         * Add a message.
         * @param string $text
         */
        public static function addMessage($text) {
            $_SESSION['messages'][] = $text;
        }

        /**
         * Add an error.
         * @param string $text
         */
        public static function addError($text) {
            $_SESSION['errors'][] = $text;
        }

        /**
         * Output messages + errors.
         * @return string
         */
        public static function show() {
            //var_dump($_SESSION);
            if (isset($_SESSION['errors'])) {
                foreach ($_SESSION['errors'] as $error) {
                    self::flash('error', esc_html($error));
                }
                unset($_SESSION['errors']);
            }
            if (isset($_SESSION['messages']) > 0) {
                foreach ($_SESSION['messages'] as $message) {
                    self::flash('updated', esc_html($message));
                }
                unset($_SESSION['messages']);
            }
        }

        /**
         * Notify massages
         * @since    1.0.0
         * @param string $class error|updated|info
         * @param string $message
         */
        public static function flash($class, $message = '') {
            ?>
            <div class="<?= $class ?> published notice inline is-dismissible">
                <p><strong><?= __($message, A::$config->i18n); ?></strong></p>
            </div>
            <?php
        }

        /**
         * Variable dumper
         * @param mixed $var Any variable
         * @param boolean $die Die here, default false
         */
        public static function dump($var, $die = false) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
            if ($die)
                die();
        }

    }

}