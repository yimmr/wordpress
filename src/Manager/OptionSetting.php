<?php

namespace Impack\WP\Manager;

use Impack\WP\Components\Form;
use Impack\WP\Components\Setting;

class OptionSetting
{
    protected $app;

    protected $key;

    protected $optionName;

    public $group;

    public $args;

    public $page;

    protected $settingInstance;

    public function __construct($app, $optionName, $group, $args = [])
    {
        $this->key = empty($args['key']) ? $optionName : $args['key'];
        unset($args['key']);

        $this->optionName = $optionName;
        $this->group      = $group;
        $this->args       = $args;

        $this->app = $app;
    }

    public function onAdminMenu(callable $callback)
    {
        \add_action('admin_menu', fn() => $callback($this));
        return $this;
    }

    /**
     * 注册 setting api
     *
     * @param null|bool|string|array $section 单个section的id或数组，其他真值会添加多个 section
     * @param string                 $page
     */
    public function initSetting($section = null, $page = '')
    {
        \add_action('admin_init', fn() => $this->addSetting($section, $page));
        return $this;
    }

    /**
     * 注册 settings api
     *
     * @param null|bool|string|array $section
     * @param string                 $page
     */
    public function addSetting($section = null, $page = '')
    {
        $config = $this->loadConfig();

        \register_setting($this->group, $this->getOptionName(), [
            'sanitize_callback' => [$this, 'sanitize_callback'],
            'default'           => static::loadDefaultValue($config),
            ...$this->args,
        ]);

        $this->page = $page = $page ?: $this->group;

        if (!isset($section)) {
            $section = 'default';
        } else if (is_string($section)) {
            $section = $section ?: 'default';
            \add_settings_section($section, '', null, $page);
        } else if (is_array($section)) {
            \add_settings_section(
                $section = $section['id'] ?? 'default',
                $section['title'] ?? '',
                $section['render'] ?? null,
                $page,
                $section['args'] ?? []
            );
        }

        if ($section) {
            $this->addFields($config, $this->getOptionName(), $page, $section);
            return;
        }

        foreach ($config as $section) {
            $key = $section['name'];
            \add_settings_section(
                $key,
                $field['title'] ?? $field['label'] ?? '',
                $section['render'] ?? null,
                $page,
                $section['args'] ?? []
            );

            $this->addFields($section['fields'], "{$this->getOptionName()}[{$key}]", $page, $key);
        }

        return $this;
    }

    public function addFields(array &$fields, $preName, $page, $section = 'default')
    {
        foreach ($fields as &$field) {
            $field['name'] = "{$preName}[{$field['name']}]";

            if (!isset($field['label_for'])) {
                if (!isset($field['type']) || !in_array($field['type'], ['checkbox', 'radio'])) {
                    $field['label_for'] = $field['name'];
                }
            } else if ($field['label_for'] === true) {
                $field['label_for'] = $field['name'];
            }

            \add_settings_field(
                $field['name'],
                $field['title'] ?? $field['label'] ?? '',
                [$this, 'renderField'],
                $page,
                $section,
                $field
            );
        }
    }

    public function sanitize_callback($value)
    {
        try {
            $value = $this->option()->sanitize($value);

            if (\is_wp_error($value)) {
                \add_settings_error($this->option()->getName(), $value->get_error_code(), $value->get_error_message());
                return $this->option()->get();
            }

            return $value;
        } catch (\Throwable $th) {
            \add_settings_error($this->option()->getName(), $th->getCode(), $th->getMessage());
            return $this->option()->get();
        }
    }

    public function renderField($field)
    {
        $option = $this->option();
        unset($field['label_for']);

        $this->setFieldValue($field, $option->get(trim(
            str_replace('][', '.', substr($field['name'], strlen($option->getName()) + 1)),
            '[]'
        )));

        return isset($field['render']) ? call_user_func($field['render'], $field) : Form::settingsField($field);
    }

    public function setFieldValue(&$field, $value)
    {
        if (!isset($field['fields'])) {
            if (!isset($field['options']) && isset($field['type']) && in_array($field['type'], ['checkbox', 'radio'])) {
                $field['checked'] = isset($field['value']) ? $field['value'] === $value : boolval($value);
            } else {
                $field['value'] = $value ?? '';
            }

            unset($field['default']);
            return;
        }

        foreach ($field['fields'] as &$subfield) {
            $this->setFieldValue($subfield, $value[$subfield['name']] ?? '');
        }
    }

    public function extractDefaultValue(&$arr, &$fields)
    {
        if (!is_array($arr)) {
            $arr = [];
        }

        foreach ($fields as &$field) {
            if (array_key_exists('default', $field)) {
                $arr[$field['name']] = $field['default'];
            } else if (isset($field['fields'])) {
                $arr[$field['name']] = [];
                $this->extractDefaultValue($arr[$field['name']], $field['fields']);
            } else {
                $arr[$field['name']] = null;
            }
        }

        return $arr;
    }

    public function getMenuPageParams($capability = null, ...$args)
    {
        return [$capability ?? 'manage_options', $this->getPageSlug(), fn() => $this->setting()->renderPage(), ...$args];
    }

    public function getPageSlug()
    {
        return $this->optionName . '_page';
    }

    public function getOptionName()
    {
        return $this->optionName;
    }

    public function loadConfig()
    {
        return $this->loadDataFile($this->optionName);
    }

    public function loadDefaultValue(&$config = null)
    {
        if ($value = $this->loadDataFile($this->optionName . '_default')) {
            return $value;
        }

        $config = $config ?? $this->loadDataFile($this->optionName);

        return $this->extractDefaultValue($_value, $config);
    }

    protected function loadDataFile($name)
    {
        if (is_file($file = $this->app->configPath($name . '.php'))) {
            return include $file;
        }
    }

    /**
     * @return \Impack\WP\Base\Option
     */
    public function option()
    {
        return $this->app->make($this->key);
    }

    /**
     * @return \Impack\WP\Components\Setting
     */
    public function setting()
    {
        return $this->settingInstance ?? ($this->settingInstance = new Setting($this->optionName, $this->group, $this->page));
    }
}