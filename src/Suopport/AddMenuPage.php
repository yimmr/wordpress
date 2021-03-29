<?php

namespace Impack\WP\Support;

class AddMenuPage
{
    protected static $enqueue = [];

    protected static $default = [
        'title'    => 'Menu',
        'name'     => 'Menu',
        'cap'      => 'manage_options',
        'slug'     => null,
        'render'   => null,
        'icon'     => 'dashicons-admin-generic',
        'position' => null,
    ];

    /**
     * 添加多个菜单页面
     *
     * @param array $menus
     */
    public static function add(array $menus)
    {
        global $admin_page_hooks;

        \add_action('admin_enqueue_scripts', [static::class, 'enqueueScripts']);

        foreach ($menus as $menu) {
            $slug = $menu['slug'] ?? '';
            $func = "add_{$slug}_page";

            // 仅不存在顶级菜单时添加，若已定义添加子菜单的函数也视为已存在顶级
            if (!isset($admin_page_hooks[$slug]) && !function_exists($func)) {
                static::addMenuPage('add_menu_page', $menu);
            }

            if (!empty($menu['children'])) {
                if (function_exists($func)) {
                    $slug = true;
                } else {
                    $func = 'add_submenu_page';
                }
                foreach ($menu['children'] as $submenu) {
                    static::addMenuPage($func, $submenu, $slug);
                }
            }
        }
    }

    /**
     * 引入页面脚本样式
     *
     * @param string $hook
     */
    public static function enqueueScripts($hook)
    {
        if (isset(static::$enqueue[$hook])) {
            call_user_func([static::$enqueue[$hook], 'enqueue']);
            unset(static::$enqueue[$hook]);
        }
    }

    /**
     * 添加单个菜单页面
     *
     * @param string $method
     * @param array $menu
     * @param string|bool $parentSlug
     */
    protected static function addMenuPage($method, $menu, $parentSlug = false)
    {
        $hook = call_user_func_array($method, static::toMenuParams($menu, $parentSlug, $render));
        if ($render) {
            static::$enqueue[$hook] = $render;
        }
    }

    /**
     * 键值数组转换添加菜单页参数数组
     *
     * @param array $params
     * @param string|bool $parentSlug
     * @param string $render 提取菜单页面类的类名
     * @return array
     */
    protected static function toMenuParams(array &$params, $parentSlug = false, &$render = '')
    {
        $result = is_string($parentSlug) ? ['parent' => $parentSlug] : [];

        foreach (static::$default as $key => $val) {
            if (!($parentSlug && $key == 'icon')) {
                $result[$key] = $params[$key] ?? $val;
            }
        }

        // 渲染回调是类名时，视为使用菜单页面类且只在闭包中创建实例
        if (is_string($result['render']) && class_exists($result['render'])) {
            $render           = $result['render'];
            $result['render'] = function () use ($render, &$result) {
                $render::renderPage($result);
            };
        }

        return $result;
    }
}