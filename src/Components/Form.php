<?php

namespace Impack\WP\Components;

class Form
{
    /**
     * 添加类名属性
     *
     * @param array $attrs
     * @param string $val
     */
    public static function addClass(&$attrs, $val)
    {
        $attrs['class'] = (isset($attrs['class']) ? "{$attrs['class']} " : '') . $val;
    }

    /**
     * 添加样式属性
     *
     * @param array $attrs
     * @param string $val
     */
    public static function css(&$attrs, $val)
    {
        $attrs['style'] = (isset($attrs['style']) ? "{$attrs['style']};" : '') . $val;
    }

    /**
     * 属性数组转字符串
     *
     * @param array $attrs
     * @return string
     */
    protected static function attrsToString(array &$attrs)
    {
        $_attrs = [];
        foreach ($attrs as $name => $val) {
            if (!is_null($val) && $val !== false) {
                $_attrs[] = $val === true ? $name : "{$name}=\"{$val}\"";
            }
        }
        return implode(' ', $_attrs);
    }

    /**
     * 解析出选项的value和label
     *
     * @param string|array $option
     * @param string $value
     * @param string $label
     */
    protected static function parseOption($option, &$value, &$label)
    {
        if (is_array($option)) {
            extract($option);
        } else {
            $value = $label = $option;
        }
    }

    /**
     * 输入框
     *
     * @param string $name
     * @param mixed $value
     * @param string $type
     * @param array $attrs
     * @return string
     */
    public static function input($name = '', $value = null, $type = 'text', array $attrs = [])
    {
        $attrs['type'] = $type;
        $attrs['name'] = $name;

        if (!is_null($value)) {
            $attrs['value'] = $value;
        }

        return '<input ' . static::attrsToString($attrs) . '>';
    }

    /**
     * 多行文本域
     *
     * @param string $name
     * @param string $value
     * @param int|array $rows 数组值当做attrs
     * @param int $cols
     * @param array $attrs
     * @return string
     */
    public static function textarea($name, $value = '', $rows = 5, $cols = 50, array $attrs = [])
    {
        if (is_array($rows)) {
            $attrs = $rows;
            $rows  = $attrs['rows'] ?? 5;
            $cols  = $attrs['cols'] ?? 50;
            unset($attrs['rows'], $attrs['cols']);
        }

        $attrs = static::attrsToString($attrs);
        return "<textarea name=\"$name\" rows=\"$rows\" cols=\"$cols\" $attrs>$value</textarea>";
    }

    /**
     * 下拉框
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @param array $attrs
     * @return string
     */
    public static function select($name, $value = '', array $options = [], array $attrs = [])
    {
        $attrs = static::attrsToString($attrs);
        $html  = "<select name=\"$name\" $attrs>";
        foreach ($options as $option) {
            static::parseOption($option, $_val, $label);
            $html .= sprintf('<option value="%s"%s>%s</option>', $_val, ($value == $_val ? ' selected' : ''), $label);
        }
        return $html . '</select>';
    }

    /**
     * 复选框
     *
     * @param string $name
     * @param bool|string|array $checked  布尔或已选值
     * @param string|array $option  [label,value]
     * @param array $attrs
     * @return string
     */
    public static function checkbox($name, $checked = false, $option = '', array $attrs = [])
    {
        static::parseOption($option, $value, $label);

        if (is_array($checked)) {
            $checked = in_array($value, $checked);
        } elseif (!is_bool($checked)) {
            $checked = $value == $checked;
        }

        $attrs['checked'] = $checked;

        return sprintf(
            '<span class="checkbox-wrapper"><label%s%s</label></span>',
            isset($attrs['id']) ? "for=\"{$attrs['id']}\">" : '>',
            static::input($name, $value, 'checkbox', $attrs) . $label
        );
    }

    /**
     * 单选框
     *
     * @param string $name
     * @param bool|string $checked  布尔或已选值
     * @param string|array $option  [label,value]
     * @param array $attrs
     * @return string
     */
    public static function radio($name, $checked = false, $option = '', array $attrs = [])
    {
        static::parseOption($option, $value, $label);

        $attrs['checked'] = is_bool($checked) ? $checked : $value == $checked;

        return sprintf(
            '<span class="radio-wrapper"><label%s%s</label></span>',
            isset($attrs['id']) ? "for=\"{$attrs['id']}\">" : '>',
            static::input($name, $value, 'radio', $attrs) . $label
        );
    }

    /**
     * 复选框组
     *
     * @param string $name
     * @param array $values
     * @param array $options
     * @param array $attrs
     * @return string
     */
    public static function checkboxGroup($name, array $values, array $options, array $attrs = [])
    {
        $html = '';
        foreach ($options as $option) {
            $html .= static::checkbox("{$name}[]", $values, $option, $attrs);
        }
        return $html;
    }

    /**
     * 单选框组
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @param array $attrs
     * @return string
     */
    public static function radioGroup($name, $value, array $options, array $attrs = [])
    {
        $html = '';
        foreach ($options as $option) {
            $html .= static::radio($name, $value, $option, $attrs);
        }
        return $html;
    }

    /**
     * Number类型字段
     *
     * @param string $name
     * @param int $value
     * @param array $attrs
     * @return string
     */
    public static function number($name, $value = 0, array $attrs = [])
    {
        static::addClass($attrs, 'tiny-text');
        return static::input($name, $value, 'number', $attrs);
    }

    /**
     * 返回上传图片的字段
     *
     * @param string $name
     * @param int $value 图片ID
     * @param string $size  宽|宽:高占比 (px)
     * @param string $class
     * @param array $attr
     * @return string
     */
    public static function image($name, $value = 0, $size = '100', $attrs = [])
    {
        $imageUrl = \wp_get_attachment_image_url($value, 'full');
        $size     = explode(':', $size);
        static::addClass($attrs, 'image-field');
        static::css($attrs, "width:{$size[0]}px;height:" . ($size[0] * ($size[1] ?? 1)) . 'px');

        $html = sprintf('<span %s>', static::attrsToString($attrs));
        $html .= $imageUrl ? sprintf('<img src="%s">', $imageUrl) : '';
        $html .= '<span class="cancel" onclick="imwp.formImage.cancel(this)"></span>';
        $html .= '<span class="tips" onclick="imwp.formImage.upload(this)">上传</span>';
        $html .= static::input($name, $value, 'hidden');
        $html .= '</span>';

        return $html;
    }

    /**
     * 图片上传组
     *
     * @param string $name
     * @param array $values
     * @param string $size
     * @param array $attrs
     */
    public static function imageGroup($name, $values = [], $size = '100', $attrs = [])
    {
        static::addClass($attrs, 'image-field-group');
        $attrs['data-count'] = $attrs['count'] ?? $attrs['data-count'] ?? 3;
        unset($attrs['count']);
        $html = '<div ' . static::attrsToString($attrs) . '>';
        $html .= static::image("{$name}[]", 0, $size, ['style' => 'display:none', 'class' => 'empty']);
        foreach ($values as $value) {
            $html .= static::image("{$name}[]", $value, $size);
        }
        return $html . '</div>';
    }

    /**
     * 页内加载字段的脚本和样式
     */
    public static function enqueue()
    {
        \wp_enqueue_media();
        \wp_add_inline_style('mediaelement', static::script('css'));
        \wp_add_inline_script('mediaelement', static::script('js'));
    }

    /**
     * 读取JS/CSS代码
     *
     * @param string $type
     * @return string
     */
    public static function script($type = 'js')
    {
        return file_get_contents(__DIR__ . '/assets/form.' . $type);
    }
}