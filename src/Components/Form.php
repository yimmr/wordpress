<?php

namespace Impack\WP\Components;

class Form
{
    public static function addEnqueueAssetsIf($hook, $baseURL, $basePath = '')
    {
        \add_action('admin_enqueue_scripts', fn($target) => $target === $hook ? static::enqueueAssets($baseURL, $basePath) : null);
    }

    /**
     * 页内加载所需的脚本和样式
     */
    public static function enqueueAssets()
    {
        $assets = static::getAssets();
        \wp_enqueue_media();
        \wp_enqueue_style(...$assets['style']);
        \wp_enqueue_script(...$assets['script']);
    }

    public static function registerAssets()
    {
        $assets = static::getAssets();
        \wp_register_style(...$assets['style']);
        \wp_register_script(...$assets['script']);
    }

    public static function getAssets()
    {
        $name    = 'impack-wpform';
        $baseURL = str_replace(rtrim(\get_home_path(), '\/'), \home_url(), __DIR__);
        return [
            'style'  => [$name, $baseURL . "/assets/{$name}.min.css", ['mediaelement'], '1.1'],
            'script' => [$name, $baseURL . "/assets/{$name}.min.js", ['mediaelement'], '1.1', true],
        ];
    }

    public static function settingsFieldJS(&$field, $depth = 0)
    {
        $field['depth'] = $depth;
        return '<div data-impack-wpform-field="' . htmlspecialchars(json_encode($field)) . '"></div>';
    }

    public static function settingsField($field, $depth = 0)
    {
        if ($field['server_render'] ?? ($depth > 0 && isset($field['children']))) {
            echo static::settingsFieldJS($field, $depth);
            return;
        }

        $description = null;

        if (isset($field['description'])) {
            $description = $field['description'];
            unset($field['description']);
        }

        if (!isset($field['fields'])) {
            $type  = $field['type']??='text';
            $label = $field['label'] ?? null;
            $title = $label ?? $field['title'] ?? null;
            $field['id']??=$field['name'];

            unset($field['default']);

            if (isset($title) && (isset($field['options']) || !static::hasOwnLabel($type))) {
                echo $depth > 0 ? '<p style="margin-top:.5em">' . $title . '</p>' : '';
                unset($field['label'], $field['title']);
            }

            switch ($type) {
                case 'radio':
                case 'checkbox':
                    if (isset($field['options'])) {
                        echo static::checkControlGroup($field);
                    } else {
                        echo '<fieldset>' . static::checkControl($field) . '</fieldset>';
                    }
                    break;
                case 'select':
                    echo static::select($field);
                    break;
                case 'textarea':
                    echo static::textarea($field);
                    break;
                case 'number':
                case 'text':
                case 'search':
                case 'email':
                case 'password':
                case 'tel':
                case 'url':
                    echo static::text($field);
                    break;
                default:
                    echo static::settingsFieldJS($field, $depth);
                    break;
            }

            echo static::description($description);

            return;
        }

        if ($depth > 0) {
            echo '<fieldset style="' . ($depth === 1 ? 'display:inline-block;' : '') . 'box-shadow:inset 0 0 4px #dddddd;padding:10px;margin:.7em 0 .25em;">';

            if (isset($field['label'])) {
                echo '<legend>' . $field['label'] . '</legend>';
            }
        }

        foreach ($field['fields'] as $subfield) {
            $subfield['name'] = "{$field['name']}[{$subfield['name']}]";
            static::settingsField($subfield, $depth + 1);
        }

        echo static::description($description);

        if ($depth > 0) {
            echo '</fieldset>';
        }
    }

    public static function hasOwnLabel($type)
    {
        return in_array($type, ['checkbox', 'radio', 'video', 'image', 'audio', 'items', 'list']);
    }

    /**
     * 合并属性值
     *
     * @param array|string $value
     * @param array|string $append
     * @param string       $delimiter
     */
    public static function mergeAttr($value, $append, $delimiter = ' ')
    {
        if ($value === $append) {
            return $value;
        }

        $value  = is_array($value) ? $value : explode($delimiter, $value);
        $append = is_array($append) ? $append : explode($delimiter, $append);

        return implode($delimiter, array_unique(array_merge($value, $append)));
    }

    public static function toAttributes(&$attributes)
    {
        $result = '';

        foreach ($attributes as $key => $value) {
            if ($value !== null && $value !== false) {
                $result .= ' ' . ($value === true ? $key : $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"');
            }
        }

        return $result;
    }

    /**
     * 多行文本域
     *
     * @param  array    $attrs
     * @return string
     */
    public static function textarea(array $attrs = [])
    {
        $value = $attrs['value'] ?? '';
        $value = is_array($value) ? implode("\n", $attrs['value']) : $value;
        unset($attrs['value']);
        $attrs += ['rows' => 10, 'cols' => 50];
        return '<textarea' . self::toAttributes($attrs) . '>' . $value . '</textarea>';
    }

    /**
     * input 控件
     *
     * @param  array    $attrs
     * @return string
     */
    public static function input(array $attrs = [])
    {
        return '<input' . static::toAttributes($attrs) . '>';
    }

    /**
     * 文本输入框
     *
     * @param  array    $attrs
     * @return string
     */
    public static function text(array $attrs = [])
    {
        if (isset($attrs['value']) && is_array($attrs['value'])) {
            $attrs['value'] = implode(',', $attrs['value']);
        }

        $className      = $attrs['type'] === 'number' ? 'small-text' : 'regular-text';
        $attrs['class'] = empty($attrs['class']) ? $className : static::mergeAttr($className, $attrs['class']);
        return '<input' . static::toAttributes($attrs) . '>';
    }

    /**
     * 下拉框
     *
     * @param  array    $attrs
     * @return string
     */
    public static function select(array $attrs = [])
    {
        if (isset($attrs['multiple']) && $attrs['multiple'] !== false) {
            $attrs['name'] .= '[]';
        }

        $options = $attrs['options'];
        $checked = (array) ($attrs['value'] ?? []);
        unset($attrs['value'], $attrs['options']);
        $html = '<select' . static::toAttributes($attrs) . '>';
        foreach ($options as $option) {
            $selected = in_array($option['value'], $checked) ? ' selected' : '';
            $html .= '<option value="' . $option['value'] . '"' . $selected . '>' . $option['label'] . '</option>';
        }
        return $html .= '</select>';
    }

    public static function checkControlGroup(&$attrs)
    {
        $attrs['name'] .= '[]';
        $options = $attrs['options'];
        $checked = (array) ($attrs['value'] ?? []);
        $space   = isset($attrs['direction']) && $attrs['direction'] == 'vertical' ? '</br>' : '&nbsp;&nbsp;';

        unset($attrs['options'], $attrs['id'], $attrs['direction']);

        $html = '<fieldset>';
        foreach ($options as $option) {
            $option += $attrs;
            $option['checked'] = in_array($option['value'], $checked);
            $html .= static::checkControl($option) . $space;
        }
        return $html . '</fieldset>';
    }

    protected static function checkControl(&$attrs)
    {
        $label = $attrs['label'] ?? '';
        unset($attrs['label'], $attrs['title']);
        $attributes = static::toAttributes($attrs);
        return "<label><input{$attributes}>{$label}</label>";
    }

    /**
     * 复选框
     *
     * @param  array    $attrs
     * @return string
     */
    public static function checkbox(array $attrs = [])
    {
        $attrs['type'] = 'checkbox';
        return static::checkControl($attrs);
    }

    /**
     * 单选框
     *
     * @param  array    $attrs
     * @return string
     */
    public static function radio(array $attrs = [])
    {
        $attrs['type'] = 'radio';
        return static::checkControl($attrs);
    }

    public static function description($content)
    {
        echo '<p class="description">' . $content . '</p>';
    }
}