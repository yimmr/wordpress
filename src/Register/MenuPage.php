<?php

namespace Impack\WP\Register;

use Closure;
use Impack\Contracts\Foundation\Application;

abstract class MenuPage
{
    protected $app;

    protected $hook;

    protected $props = [];

    protected $notices = [];

    protected $async = false;

    private static $with;

    /** 引入脚本样式 */
    abstract public function scripts();

    /** 渲染页面 */
    abstract public function render();

    /** 保存数据 */
    abstract public function save();

    /**
     * 设置页面属性
     *
     * @param  array  $props
     * @return static
     */
    public static function props(array $props)
    {
        $instance        = new static;
        $instance->props = array_merge($instance->props, $props);
        $instance::$with = $instance;

        return $instance;
    }

    /**
     * 创建菜单页面
     *
     * @param  \Impack\Contracts\Foundation\Application  $app
     * @param  string|Closure  $callback
     * @return static
     */
    public static function create(Application $app, $callback = null)
    {
        $instance = static::createNewPage($app);

        if (is_null($callback)) {
            return $instance->setHook(\add_menu_page(...$instance->getParams(true)));
        }

        if (is_string($callback)) {
            return $instance->setHook(\add_submenu_page($callback, ...$instance->getParams()));
        }

        if (is_callable($callback)) {
            \add_menu_page(...$instance->getParams(true, false, false));
            $instance->setHook(\add_submenu_page($instance->getProp('slug'), ...$instance->getParams(false, true)));
            $callback($app, $instance->getProp('slug'));
        }

        return $instance;
    }

    /**
     * 创建主菜单的子页面
     *
     * @param  \Impack\Contracts\Foundation\Application  $app
     * @param  string  $parent
     * @return static
     */
    public static function subMain(Application $app, $parent)
    {
        $instance = static::createNewPage($app);

        return $instance->setHook(call_user_func_array("add_{$parent}_page", $instance->getParams()));
    }

    /**
     * 设置服务容器
     *
     * @param  \Impack\Contracts\Foundation\Application  $app
     * @param  $this
     */
    public function setApp(Application $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * 创建一个新的页面实例
     *
     * @param  \Impack\Contracts\Foundation\Application  $app
     * @return static
     */
    protected static function createNewPage($app)
    {
        $instance = static::$with ?: new static;

        static::$with = null;

        $instance->setApp($app);

        $instance->enqueueScripts();

        return $instance;
    }

    /**
     * 获取添加页面所需的参数
     *
     * @param  bool  $icon
     * @param  bool  $subname
     * @param  bool  $render
     * @return array
     */
    protected function getParams($icon = false, $subname = false, $render = true)
    {
        $params = [];

        foreach ([
            'title' => '',
            'name'  => __('Menu'),
            'cap'   => 'manage_options',
            'slug'  => 'custom-menu',
        ] as $key => $value) {
            $params[] = $this->props[$key] = $this->props[$key] ?? $value;
        }

        $params[] = $render ? $this->getRenderCallback() : null;

        if ($icon) {
            $params[] = $this->props['icon'] = $this->props['icon'] ?? '';
        }

        if (isset($this->props['position'])) {
            $params[] = $this->props['position'];
        }

        if ($subname && isset($this->props['subname'])) {
            $params[1] = $this->props['subname'];
        }

        return $params;
    }

    /**
     * 返回指定属性
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getProp($key, $default = null)
    {
        return $this->props[$key] ?? $default;
    }

    /**
     * 设置页面 hook_suffix
     *
     * @param  string  $hook
     * @return $this
     */
    protected function setHook($hook)
    {
        $this->hook = $hook;

        return $this;
    }

    /**
     * 返回渲染页面的回调
     *
     * @return Closure
     */
    protected function getRenderCallback()
    {
        return $this->async ? [$this, 'render'] : function () {
            if (isset($_POST['submit'])) {
                $this->save();
            }

            foreach ($this->notices as $setting) {
                \settings_errors($setting);
            }

            $this->render();
        };
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
     * 获取唯一的消息$setting
     *
     * @return string
     */
    protected function getSetting()
    {
        $this->notices[] = uniqid();

        return end($this->notices);
    }

    /**
     * 添加脚本队列
     */
    protected function enqueueScripts()
    {
        \add_action('admin_enqueue_scripts', function ($hook) {
            if ($this->hook == $hook) {$this->scripts();}
        });
    }
}