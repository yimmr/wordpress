<?php

namespace Impack\WP\Components;

use Closure;

class Option
{
    /**
     * 加了样式的文本框
     *
     * @param  string  $name
     * @param  mixed   $value
     * @param  string  $type
     * @param  array  $attrs
     * @return string
     */
    public static function input($name, $value = null, $type = 'text', $attrs = [])
    {
        if ($type == 'text') {
            Form::addClass($attrs, 'regular-text');
        }
        return Form::input($name, $value, $type, $attrs);
    }

    /**
     * 输出选项主体
     *
     * @param string $title
     * @param array $fields
     * @param array $values
     * @param string $prefix 字段name前缀
     * @param string $fromAttr
     */
    public static function main($title, array $fields, array $values, $prefix = '', $fromAttr = '')
    {
        static::wrapTable($title, function () use (&$fields, &$values, &$prefix) {
            static::trGroup($fields, $values, $prefix);
        });
    }

    /**
     * 输出标题+表单+表格
     *
     * @param string $title
     * @param \Closure $slot
     * @param string $fromAttr
     */
    public static function wrapTable($title, Closure $slot, $fromAttr = '')
    {
        echo '<div class="wrap">';
        echo '<h1>' . $title . '</h1>';

        \do_action('imwp_option_form_before');
        echo '<form method="post" novalidate="novalidate" ' . $fromAttr . '>';

        echo '<table class="form-table" role="presentation"><tbody>';
        $slot();
        echo '</tbody></table>';
        \do_action('imwp_option_table');

        echo '<div class="submit">';
        echo \get_submit_button('', 'primary large', 'submit', false);
        \do_action('imwp_option_submit');
        echo '</div>';

        echo '</form>';
        \do_action('imwp_option_form_after');

        echo '</div>';
    }

    /**
     * 输出表格tr
     *
     * @param [type] $label
     * @param string $slot
     * @param string $tips
     */
    public static function tr($label, $slot = '', $tips = '')
    {
        echo '<tr><th scope="row">' . "\r\n";
        echo '<label>' . $label . '</label>';
        echo '</th><td>';
        echo $slot;
        echo $tips ? '<p class="description">' . $tips . '</p>' : '';
        echo '</td></tr>';
    }

    /**
     * 按照配置输出多个表格tr
     *
     * @param array $fields
     * @param array $values
     * @param string $prefix name前缀
     */
    public static function trGroup(array &$fields, array &$values, $prefix = '')
    {
        foreach ($fields as $key => $field) {
            $name    = "{$prefix}{$key}";
            $content = \apply_filters("imwp_trgroup_{$key}_field", '', [$field, $name, $values[$key]]);

            if (is_null($content)) {
                continue;
            }

            if (empty($content)) {
                if (empty($field['children'])) {
                    $content = static::getHtmlOfFieldType($field, $name, $values[$key] ?? null);
                } else {
                    $content = static::trGroupChildren($field['children'], $name, $values[$key] ?? []);
                }
            }

            static::tr($field['label'] ?? '', $content, $field['tips'] ?? '');
        }
    }

    /**
     * 输出子级字段
     *
     * @param array $fields
     * @param string $name
     * @param mixed $value
     */
    public static function trGroupChildren(array &$fields, $name, $value)
    {
        $content = '';
        foreach ($fields as $key => $field) {
            $field['type'] = $field['type'] ?? 'text';
            $content .= '<fieldset class="tr-child ' . $key . '">';
            if (isset($field['label'])) {
                $content .= sprintf('<label>%s</label>&nbsp;&nbsp;&nbsp;%s', $field['label'], in_array($field['type'], ['textarea', 'image']) ? '<br>' : '');
            }
            $content .= '<span class="tr-child-field" style="vertical-align:middle">';
            $content .= static::getHtmlOfFieldType($field, "{$name}[{$key}]", $value[$key], true);
            $content .= '</span>';
            $content .= '</fieldset><br>';
        }
        return preg_replace('/<br>$/', '', $content);
    }

    /**
     * 依据字段类型返回html
     *
     * @param array $field
     * @param string $name
     * @param mixed $value
     * @param bool $rawInput 是否用原始输入框
     * @return string
     */
    public static function getHtmlOfFieldType(array &$field, $name, $value = null, $rawInput = false)
    {
        $field['type']  = $field['type'] ?? 'text';
        $field['attrs'] = $field['attrs'] ?? [];
        switch ($field['type']) {
            case 'checkbox':
                $options = $field['options'] ?? [];
                if (count($options) == 1) {
                    return Form::checkbox($name, $value ?? false, $options[0], $field['attrs']);
                } else {
                    return Form::checkboxGroup($name, $value ?? [], $options, $field['attrs']);
                }
                break;
            case 'radio':
                $options = $field['options'] ?? [];
                if (count($options) == 1) {
                    return Form::radio($name, $value ?? false, $options[0], $field['attrs']);
                } else {
                    return Form::radioGroup($name, $value ?? '', $options, $field['attrs']);
                }
                break;
            case 'select':
                $options = $field['options'] ?? [];
                return Form::select($name, $value ?? '', $options, $field['attrs']);
                break;
            case 'number':
                return Form::number($name, $value ?? 0, $field['attrs']);
                break;
            case 'image':
                if (is_array($value)) {
                    return Form::imageGroup($name, $value, $field['size'] ?? 100, $field['attrs']);
                } else {
                    return Form::image($name, intval($value), $field['size'] ?? 100, $field['attrs']);
                }
                break;
            case 'textarea':
                return Form::textarea($name, $value ?? '', $field['attrs']);
                break;
            default:
                $class = $rawInput ? Form::class : static::class;
                return $class::input($name, $value ?? '', $field['type'], $field['attrs']);
                break;
        }
    }

    /**
     * 重复拼接一组子字段
     *
     * @param array $params — `[$field, $name, $value]`
     * @param int $count
     * @return string|null
     */
    public static function repeatGroupChildren($params, $count = 3)
    {
        list($field, $name, $value) = $params;

        $content = null;
        $a       = 0;

        while ($a < $count) {
            $subname = "{$name}[{$a}]";
            $content .= '<div>';
            $content .= $count > 1 ? '<p><strong>[' . ($a + 1) . ']</strong></p>' : '';
            $content .= Option::trGroupChildren($field['children'], $subname, $value);
            $content .= '</div>';
            ++$a;
        }

        return $content;
    }
}