<?php

namespace Impack\WP\Components;

abstract class MenuPage
{
    protected $props = [];

    protected $notices = [];

    /**
     * 引入页面脚本样式
     */
    abstract public static function enqueue();

    /**
     * 渲染页面
     */
    abstract public function view();

    /**
     * 创建页面实例并输出页面
     *
     * @param array $props
     */
    public static function render(array $props = [])
    {
        $instance = new static;
        $instance->fill($props);
        \add_action('imwp_option_form_before', [$instance, 'notice']);
        $instance->view();
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
     * 显示已设置的消息
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