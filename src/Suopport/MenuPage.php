<?php

namespace Impack\WP\Support;

abstract class MenuPage
{
    protected $props = [];

    protected $notices = [];

    /**
     * 入队页面脚本样式
     */
    abstract public static function enqueue();

    /**
     * 渲染页面
     */
    abstract public function render();

    /**
     * 创建页面实例并输出页面
     *
     * @param array $props
     */
    public static function renderPage(array $props = [])
    {
        $instance = new static;
        $instance->setProps($props);
        \add_action('imwp_option_form_before', [$instance, 'notice']);
        $instance->render();
    }

    /**
     * 重置属性
     *
     * @param array $props
     */
    public function setProps(array $props)
    {
        $this->props = $props;
    }

    /**
     * 读取所有属性
     *
     * @return array
     */
    public function getProps()
    {
        return $this->props;
    }

    /**
     * 显示内置的通知消息
     */
    public function notice()
    {
        foreach ($this->notices as $setting) {
            \settings_errors($setting);
        }
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
     * 执行钩子
     *
     * @param string $name
     */
    public function doAction($name = '')
    {
        if (!$name) {
            $name = explode('\\', static::class);
            $name = end($name);
        }
        \do_action("imwp_menu_page_{$name}", $this);
    }

    /**
     * 设置应用实例
     *
     * @param Object $app
     */
    public function setApp($app)
    {
        $this->app = $app;
    }

    /**
     * 读取单个属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->props[$name] ?? null;
    }

    /**
     * 设置单个属性
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->props[$name] = $value;
    }
}