<?php

/**
 * AHtml Class
 * This provides a set of static methods for generating any HTML tag and attributes. 
 * Imported and modified some methods from yii\helpers\Html & yii\helpers\BaseHtml classes. 
 * Source Code	https://github.com/yiisoft/yii2/blob/master/framework/helpers/BaseHtml.php
 * 
 * @author   Yii
 * @version  1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AHtml')) {

    class AHtml {

        /**
         * @var array list of void elements (element name => 1)
         * @see http://www.w3.org/TR/html-markup/syntax.html#void-element
         */
        public static $voidElements = [
            'area' => 1,
            'base' => 1,
            'br' => 1,
            'col' => 1,
            'command' => 1,
            'embed' => 1,
            'hr' => 1,
            'img' => 1,
            'input' => 1,
            'keygen' => 1,
            'link' => 1,
            'meta' => 1,
            'param' => 1,
            'source' => 1,
            'track' => 1,
            'wbr' => 1,
        ];

        /**
         * @var array the preferred order of attributes in a tag. This mainly affects the order of the attributes
         * that are rendered by [[renderTagAttributes()]].
         */
        public static $attributeOrder = [
            'type',
            'id',
            'class',
            'name',
            'value',
            'href',
            'src',
            'srcset',
            'form',
            'action',
            'method',
            'selected',
            'checked',
            'readonly',
            'disabled',
            'multiple',
            'size',
            'maxlength',
            'width',
            'height',
            'rows',
            'cols',
            'alt',
            'title',
            'rel',
            'media',
        ];

        /**
         * Encodes special characters into HTML entities.
         * 
         * @param string $content the content to be encoded
         * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
         * HTML entities in `$content` will not be further encoded.
         * @return string the encoded content
         * @see decode()
         * @see http://www.php.net/manual/en/function.htmlspecialchars.php
         */
        public static function encode($content, $doubleEncode = true) {
            return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
        }

        /**
         * Decodes special HTML entities back to the corresponding characters.
         * This is the opposite of [[encode()]].
         * @param string $content the content to be decoded
         * @return string the decoded content
         * @see encode()
         * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
         */
        public static function decode($content) {
            return htmlspecialchars_decode($content, ENT_QUOTES);
        }

        /**
         * Generates a complete HTML tag.
         * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
         * @param string $content the content to be enclosed between the start and end tags. It will not be HTML-encoded.
         * If this is coming from end users, you should consider [[encode()]] it to prevent XSS attacks.
         * @param array $options the HTML tag attributes (HTML options) in terms of name-value pairs.
         * These will be rendered as the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
         * If a value is null, the corresponding attribute will not be rendered.
         *
         * For example when using `['class' => 'my-class', 'target' => '_blank', 'value' => null]` it will result in the
         * html attributes rendered like this: `class="my-class" target="_blank"`.
         *
         * See [[renderTagAttributes()]] for details on how attributes are being rendered.
         *
         * @return string the generated HTML tag
         * @see beginTag()
         * @see endTag()
         */
        public static function tag($name, $content = '', $options = []) {
            if ($name === null || $name === false) {
                return $content;
            }
            $html = "<$name" . static::renderTagAttributes($options) . '>';
            return isset(static::$voidElements[strtolower($name)]) ? $html : "$html $content</$name>";
        }

        /**
         * Generates a start tag.
         * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
         * @param array $options the tag options in terms of name-value pairs. These will be rendered as
         * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
         * If a value is null, the corresponding attribute will not be rendered.
         * See [[renderTagAttributes()]] for details on how attributes are being rendered.
         * @return string the generated start tag
         * @see endTag()
         * @see tag()
         */
        public static function beginTag($name, $options = []) {
            return $name === null || $name === false ? "" : "<$name" . static::renderTagAttributes($options) . '>';
        }

        /**
         * Generates an end tag.
         * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
         * @return string the generated end tag
         * @see beginTag()
         * @see tag()
         */
        public static function endTag($name) {
            return $name === null || $name === false ? "" : "</$name>";
        }

        /**
         * Renders the HTML tag attributes.
         *
         * Attributes whose values are of boolean type will be treated as
         * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
         *
         * Attributes whose values are null will not be rendered.
         *
         * The values of attributes will be HTML-encoded using [[encode()]].
         *
         * The "data" attribute is specially handled when it is receiving an array value. In this case,
         * the array will be "expanded" and a list data attributes will be rendered. For example,
         * if `'data' => ['id' => 1, 'name' => 'name']`, then this will be rendered:
         * `data-id="1" data-name="name"`.
         * Additionally `'data' => ['params' => ['id' => 1, 'name' => 'name'], 'status' => 'ok']` will be rendered as:
         * `data-params='{"id":1,"name":""}' data-status="ok"`.
         *
         * @param array $attributes attributes to be rendered. The attribute values will be HTML-encoded using [[encode()]].
         * @return string the rendering result. If the attributes are not empty, they will be rendered
         * into a string with a leading white space (so that it can be directly appended to the tag name
         * in a tag. If there is no attribute, an empty string will be returned.
         */
        public static function renderTagAttributes($attributes) {
            if (count($attributes) > 1) {
                $sorted = [];
                foreach (static::$attributeOrder as $name) {
                    if (isset($attributes[$name])) {
                        $sorted[$name] = $attributes[$name];
                    } else {
                        
                    }
                }
                $attributes = array_merge($sorted, $attributes);
            }

            $html = '';
            foreach ($attributes as $name => $value) {
                if (is_bool($value)) {
                    if ($value) {
                        $html .= " $name";
                    }
                } elseif (is_array($value)) {
                    if ($name === 'class') {
                        if (empty($value)) {
                            continue;
                        }
                        $html .= " $name=\"" . static::encode(implode(' ', $value)) . '"';
                    } elseif ($name === 'style') {
                        if (empty($value)) {
                            continue;
                        }
                        $html .= " $name=\"" . static::encode($value) . '"';
                    } else {
                        $html .= " $name='" . json_encode($value) . "'";
                    }
                } elseif ($value !== null) {
                    $html .= " $name=\"" . static::encode($value) . '"';
                }
            }

            return $html;
        }

    }

}