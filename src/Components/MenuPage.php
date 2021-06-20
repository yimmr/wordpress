<?php

namespace Impack\WP\Components;

use Closure;

class MenuPage
{
    protected $props = [];

    protected $notices = [];

    protected static $enqueue = [];

    /**
     * 页面视图
     */
    public function view()
    {}

    /**
     * 引入页面脚本样式
     */
    public static function enqueue()
    {}

    /**
     * 添加菜单页面
     *
     * @param array $props
     * @return string — The resulting page's hook_suffix.
     */
    public static function add(array $props = [])
    {
        $args       = self::resloveMenuArgs($props, $method, $render);
        $hookSuffix = call_user_func_array($method, $args);

        if ($render) {
            self::$enqueue[$hookSuffix] = $render;
        }

        return $hookSuffix;
    }

    /**
     * 键值对数组解析为添加菜单的参数数组
     *
     * @param array $props
     * @param string|null $method
     * @param string|null $render
     * @return array
     */
    public static function resloveMenuArgs(array $props, &$method = null, &$render = null)
    {
        $args        = [];
        $renderIndex = 4;

        foreach ([
            'title'    => 'Menu',
            'name'     => 'Menu',
            'cap'      => 'manage_options',
            'slug'     => '',
            'render'   => static::class == self::class ? '' : static::class,
            'position' => null,
        ] as $key => $default) {
            $args[] = $props[$key] = array_key_exists($key, $props) ? $props[$key] : $default;
        }

        if (isset($props['parent'])) {
            if (!function_exists($method = "add_{$props['parent']}_page")) {
                array_unshift($args, $props['parent']);
                $method = "add_submenu_page";
                ++$renderIndex;
            }
        } else {
            $args[]  = $args[5];
            $args[5] = $props['icon'] ?? '';
            $method  = 'add_menu_page';
        }

        // 类都当做页面组件处理
        if (is_string($props['render']) && class_exists($props['render'])) {
            $render             = $props['render'];
            $args[$renderIndex] = static::renderCallback($props);
        }

        return $args;
    }

    /**
     * 返回渲染页面闭包
     *
     * @param array $props
     * @return Closure
     */
    public static function renderCallback(array $props = [])
    {
        return function () use ($props) {
            $instance = new $props['render'];
            $instance->fill($props);
            \add_action('imwp_option_form_before', [$instance, 'notice']);
            $instance->view();
        };
    }

    /**
     * 批量添加菜单页面
     *
     * @param array $menus
     */
    public static function addMany(array $menus)
    {
        foreach ($menus as $menu) {
            $children = [];
            $parent   = $menu['slug'] ?? '';

            if (isset($menu['children'])) {
                $children = $menu['children'];
                unset($menu['children']);
            }

            // 存在顶级或定义了添加子菜单的快捷方法时忽略
            if (!isset($GLOBALS['admin_page_hooks'][$parent]) && !function_exists("add_{$parent}_page")) {
                self::add($menu);
            }

            foreach ($children as $submenu) {
                $submenu['parent'] = $parent;
                self::add($submenu);
            }
        }
    }

    /**
     * 引入当前页面脚本，用于 `admin_enqueue_scripts` 钩子回调
     *
     * @param string $hookSuffix
     */
    public static function enqueueScripts($hookSuffix)
    {
        if (isset(self::$enqueue[$hookSuffix])) {
            call_user_func([self::$enqueue[$hookSuffix], 'enqueue']);
        }
        self::$enqueue = [];
    }

    /**
     * 设置成功消息
     *
     * @param  string  $message
     * @param  string  $code
     */
    protected function success($message, $code = 'success')
    {
        \add_settings_error($this->getSetting(), $code, $message, 'success');
    }

    /**
     * 设置错误消息
     *
     * @param  string  $message
     * @param  string  $code
     */
    protected function error($message, $code = 'error')
    {
        \add_settings_error($this->getSetting(), $code, $message, 'error');
    }

    /**
     * 设置警告消息
     *
     * @param  string  $message
     * @param  string  $code
     */
    protected function warning($message, $code = 'warning')
    {
        \add_settings_error($this->getSetting(), $code, $message, 'warning');
    }

    /**
     * 设置提示消息
     *
     * @param  string  $message
     * @param  string  $code
     */
    protected function info($message, $code = 'info')
    {
        \add_settings_error($this->getSetting(), $code, $message, 'info');
    }

    /**
     * 获取消息的唯一ID
     *
     * @return string
     */
    protected function getSetting()
    {
        $this->notices[] = uniqid();
        return end($this->notices);
    }

    /**
     * 显示已设置的消息
     */
    public function notice()
    {
        foreach ($this->notices as $setting) {
            \settings_errors($setting);
        }
    }

    /**
     * 填充页面属性
     *
     * @param array $props
     */
    public function fill(array $props)
    {
        $this->props = $props;
    }

    /**
     * 读取全部属性
     *
     * @return array
     */
    public function getProps()
    {
        return $this->props;
    }

    /**
     * 读取属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->props[$name] ?? null;
    }

    /**
     * 设置属性
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->props[$name] = $value;
    }
}