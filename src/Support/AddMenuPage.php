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
     * 添加单个菜单页
     *
     * @param array $menu
     * @param string $parent
     */
    public static function add(array $menu, $parent = '')
    {
        \add_action('admin_enqueue_scripts', [static::class, 'enqueueScripts']);

        if ($parent) {
            static::resolveSubMenuMethod($parent, $method);
            static::addMenuPage($method, $menu, $parent);
        } else {
            static::addMenuPage('add_menu_page', $menu);
        }
    }

    /**
     * 添加多个菜单页面
     *
     * @param array $menus
     */
    public static function addMany(array $menus)
    {
        global $admin_page_hooks;

        \add_action('admin_enqueue_scripts', [static::class, 'enqueueScripts']);

        foreach ($menus as $menu) {
            $parent = $slug = $menu['slug'] ?? '';

            static::resolveSubMenuMethod($parent, $method);

            // 仅不存在顶级菜单时添加
            if (!isset($admin_page_hooks[$slug]) && $parent !== true) {
                static::addMenuPage('add_menu_page', $menu);
            }

            if (!empty($menu['children'])) {
                foreach ($menu['children'] as $submenu) {
                    static::addMenuPage($method, $submenu, $slug);
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
     * 解析添加子菜单的方法，存在默认顶级菜单时 $parent 改为true
     *
     * @param string $parent
     * @param string $method
     */
    protected static function resolveSubMenuMethod(&$parent, &$method)
    {
        $method = "add_{$parent}_page";
        if (function_exists($method)) {
            $parent = true;
        } else {
            $method = 'add_submenu_page';
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