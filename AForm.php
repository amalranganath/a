<?php
/**
 * WP Form Class
 *
 * @package  Plug-in/Form
 * @category Form
 * @author   Amal Ranganath
 * @version  1.0.1
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AForm')) {

    class AForm {

        public static $name;
        public static $model;

        /**
         * 
         */
        public function __construct() {
            
        }

        /**
         * Begin the form
         * @param string $name Form name, default 'form'
         * @param string $method Request method, default 'POST'
         * @param array $options 
         */
        public static function begin($name = 'form', $method = 'POST', $options = []) {
            static::$name = $name;
            $options['action'] = isset($options['action']) ? $options['action'] : '';
            $options['method'] = $method;
            //display noticess
            ANotify::show();
            echo AHtml::beginTag('form', $options);
            wp_nonce_field(-1, $name);
        }

        /**
         * End form
         */
        public static function end() {
            echo '</form>';
        }

        /**
         * Output section start
         * @param array $option
         */
        public static function section($option) {
            if (!empty($option['title'])) {
                echo '<h2>' . esc_html($option['title']) . '</h2>';
            }
            if (!empty($option['desc'])) {
                echo wpautop(wptexturize(wp_kses_post($option['desc'])));
            }

            if (!empty($id)) {
                do_action('before_' . sanitize_title($id) . '_section');
            }

            if (is_admin())
                echo '<table class="form-table' . ( isset($option['class']) ? $option['class'] : '' ) . '">' . "\n\n";

            if (!empty($option['id'])) {
                do_action('section_' . sanitize_title($option['id']));
            }
        }

        /**
         * Output section end.
         * @param string $id
         */
        public static function sectionEnd($id) {
            if (is_admin())
                echo '</table>';
            if (!empty($id)) {
                do_action('after_' . sanitize_title($id) . '_section');
            }
        }

        /**
         * Render label
         * @param type $option
         */
        public static function renderLabel($option) {
            if (is_admin())
                echo AHtml::beginTag('th', ['scope' => 'row', 'class' => 'titledesc']);
            if ($option['type'] == 'checkbox') {
                echo esc_html($option['label']);
            } else {
                echo AHtml::tag('label', esc_html($option['label']), ['for' => $option['id']]);
                echo $option['desc_tip'];
            }
            if (is_admin())
                echo '</th>';
        }

        /**
         * Output a form field by given type and options.
         * @param object $model required
         * @param array $option Please define the field attributes and values as array key value pairs. The 'name' and 'type' are required.<br/> Ex : ['name'=>'email', 'type'=>'email', 'class'=>'form-control', ...]
         */
        public static function field($model, $option) {
            //check for defaults
            if (!isset($option['type'])) {
                ANotify::flash('error', "The field doesn't have a type");
            }
            if (!isset($option['name'])) {
                ANotify::flash('error', "The field doesn't have a name");
            }
            if ($option['name'] == self::$name) {
                ANotify::flash('error', "The form name must be unique, should not use it for a field name!");
            }
            if (!isset($option['id'])) {
                $option['id'] = $option['name'];
            }
            if (!isset($option['class'])) {
                $option['class'] = '';
            }
            if (!isset($option['desc'])) {
                $option['desc'] = '';
            }
            if (!isset($option['desc_tip'])) {
                $option['desc_tip'] = false;
            }
            //get field value
            $option['value'] = self::getValue($model, $option['id'], isset($option['value']) ? $option['value'] : '');

            // Description handling
            $option = self::get_field_description($option);
            $description = $option['desc'];
            unset($option['desc']);

            //start render
            if (is_admin())
                echo AHtml::beginTag('tr', ['valign' => 'top', 'class' => '']);
            else
                echo AHtml::beginTag('div', ['class' => isset($option['wrap_class']) ? isset($option['wrap_class']) : 'field-wrap']);

            //field label
            if (!isset($option['label']) && $model instanceof BaseModel) {
                //get attribute label
                $option['label'] = $model->attrLabel($option['id']);
            }
            if (isset($option['label'])) {
                self::renderLabel($option);
                unset($option['label']);
            }
            unset($option['desc_tip']);

            //render field
            if (is_admin())
                echo AHtml::beginTag('td', ['valign' => 'top', 'class' => 'forminp forminp-' . sanitize_title($option['type'])]);

            // Switch based on type
            switch ($option['type']) {
                // Standard text inputs and subtypes like 'number'
                case 'text':
                case 'email':
                case 'number':
                case 'color' :
                case 'password' :
                case 'upload':
                case 'file':
                    if ($option['type'] == 'color') {
                        $option['type'] = 'text';
                        $option['class'] .= ' colorpick';
                        $description .= '<div id="colorPickerDiv_' . esc_attr($option['id']) . '" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>';
                        echo '<span class="colorpickpreview" style="background: ' . esc_attr($option['value']) . ';"></span>';
                    }
                    if ($option['type'] == 'upload') {
                        $option['type'] = 'text';
                        $option['class'] .= ' upload-img-url';
                    }

                    echo AHtml::tag('input', '', $option);
                    echo $description;

                    break;

                // Textarea
                case 'textarea':
                    unset($option['type']);
                    echo $description;
                    echo AHtml::tag('textarea', $option['value'], $option);

                    break;

                // Select boxes
                case 'select' :
                case 'multiselect' :
                    $options = $option['options'];
                    unset($option['options']);
                    ?>
                    <?php
                    if (isset($option['multiple']))
                        $option['name'] .= '[]';

                    echo AHtml::beginTag('select', $option);
                    foreach ($options as $key => $val):
                        ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php
                        is_array($option['value']) ?
                                        selected(in_array($key, $option['value']), true) :
                                        selected($option['value'], $key);
                        ?>><?= $val ?></option>
                                <?php
                            endforeach;
                            echo '</select> ' . $description;

                            break;

                        // Radio inputs
                        case 'radio' :
                            ?>
                    <fieldset>
                        <?php echo $description; ?>
                        <ul>
                            <?php foreach ($option['options'] as $key => $val) { ?>
                                <li>
                                    <label>
                                        <?php
                                        $option['checked'] = $key == $option['value'];
                                        echo AHtml::tag('input', $val, $option);
                                        ?>
                                    </label>
                                </li>
                            <?php } ?>
                        </ul>
                    </fieldset>
                    <?php
                    break;

                // Checkbox input
                case 'checkbox' :
                    $visbility_class = array();
                    if (!isset($option['hide_if_checked'])) {
                        $option['hide_if_checked'] = false;
                    }
                    if (!isset($option['show_if_checked'])) {
                        $option['show_if_checked'] = false;
                    }
                    if ('yes' == $option['hide_if_checked'] || 'yes' == $option['show_if_checked']) {
                        $visbility_class[] = 'hidden_option';
                    }
                    if ('option' == $option['hide_if_checked']) {
                        $visbility_class[] = 'hide_options_if_checked';
                    }
                    if ('option' == $option['show_if_checked']) {
                        $visbility_class[] = 'show_options_if_checked';
                    }

                    if (!isset($option['checkboxgroup']) || 'start' == $option['checkboxgroup']) {
                        ?>
                        <fieldset>
                        <?php } else { ?>
                            <fieldset class="<?php echo esc_attr(implode(' ', $visbility_class)); ?>">
                            <?php } if (!empty($option['title'])) { ?>
                                <legend class="screen-reader-text"><span><?php echo esc_html($option['title']) ?></span></legend>
                                    <?php } ?>
                            <label for="<?php echo $option['id'] ?>">
                                <?php
                                $option['checked'] = $option['value'];
                                echo AHtml::tag('input', '', $option);
                                echo $description
                                ?>
                            </label> <?php if (isset($option['desc_tip'])) echo $option['desc_tip']; ?>
                            <?php if (!isset($option['checkboxgroup']) || 'end' == $option['checkboxgroup']) { ?>
                            </fieldset>
                        <?php } else { ?>
                        </fieldset>
                        <?php
                    }
                    break;

                // Single page selects
                case 'single_select_page' :
                    $args = array(
                        'name' => $option['id'],
                        'id' => $option['id'],
                        'sort_column' => 'menu_order',
                        'sort_order' => 'ASC',
                        'show_option_none' => ' ',
                        'class' => $option['class'],
                        'echo' => false,
                        'selected' => absint(self::getValue($model, $option['id']))
                    );

                    if (isset($option['args'])) {
                        $args = wp_parse_args($option['args'], $args);
                    }
                    echo str_replace(' id=', " data-placeholder='" . esc_attr__('Select a page&hellip;', A::$config->i18n) . "' style='" . $option['style'] . "' class='" . $option['class'] . "' id=", wp_dropdown_pages($args));
                    echo $description;

                    break;

                // Default: run an action
                default:
                    do_action('render_form_field_' . $option['type'], $option, $model);
                    break;
            }

            echo is_admin() ? '</td></tr>' : '</div>';
        }

        /**
         * Get field value by given model object.
         * @param object|array $model Model or data array
         * @param string $attr Attribute name
         * @param mixed $default Default value
         * @return mixed
         */
        public static function getValue($model, $attr, $default = '') {
            if ($model == null) {
                return $default;
            }
            if (is_array($model)) {
                return isset($model[$attr]) ? $model[$attr] : $default;
            }
            return property_exists($model, $attr) ? $model->$attr : $default;
        }

        /**
         * Helper function to get the formated description and tip HTML for a
         * given form field. Plug-ins can call this when implementing their own custom
         * settings types.
         *
         * @param  array $option The form field value array
         * @return array The description and tip as a 2 element array
         * @deprecated since version 1.0.1
         */
        public static function get_field_description($option) {
            $description = '';
            $tooltip_html = '';

            if (true === $option['desc_tip']) {
                $tooltip_html = $option['desc'];
            } elseif (!empty($option['desc_tip'])) {
                $description = $option['desc'];
                $tooltip_html = $option['desc_tip'];
            } elseif (!empty($option['desc'])) {
                $description = $option['desc'];
            }

            if ($description && in_array($option['type'], array('textarea', 'radio'))) {
                $description = '<p style="margin-top:0">' . wp_kses_post($description) . '</p>';
            } elseif ($description && in_array($option['type'], array('checkbox'))) {
                $description = wp_kses_post($description);
            } elseif ($description) {
                $description = '<span class="description">' . wp_kses_post($description) . '</span>';
            }

            if ($tooltip_html && in_array($option['type'], array('checkbox'))) {
                $tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
            } elseif ($tooltip_html) {
                $tooltip_html = '<span class="help-tip" data-tip="' . esc_attr($tooltip_html) . '"></span>';
            }
            $option['desc'] = $description;
            $option['desc_tip'] = $tooltip_html;
            return $option;
        }

    }

}
    