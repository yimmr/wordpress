<?php

namespace Impack\WP\Support;

class AddMenuPage
{
    protected static $enqueue = [];

    /**
     * 添加多个菜单页面同时入队脚本
     *
     * @param array $menus
     */
    public static function addMany(array $menus)
    {
        \add_action('admin_enqueue_scripts', [static::class, 'enqueueScripts']);
        foreach ($menus as $menu) {
            static::add($menu);
        }
    }

    /**
     * 添加入队脚本样式的钩子
     *
     * @param string $hook
     */
    public static function enqueueScripts($hook)
    {
        if (isset(static::$enqueue[$hook])) {
            call_user_func([static::$enqueue[$hook], 'enqueue']);
        }
        static::$enqueue = [];
    }

    /**
     * 添加菜单页及子菜单页，不会重复添加顶级菜单
     *
     * @param array $menu
     * @param string $parent
     */
    public static function add(array $menu)
    {
        $parent   = $menu['slug'] ?? '';
        $method   = static::getMethodToAddChild($parent);
        $children = [];

        if (isset($menu['children'])) {
            $children = $menu['children'];
            unset($menu['children']);
        }

        // 已加顶级菜单或定义了添加子菜单的快捷方法时忽略
        if (!isset($GLOBALS['admin_page_hooks'][$menu['slug']]) && $parent !== true) {
            static::call('add_menu_page', $menu);
        }

        foreach ($children as $submenu) {
            static::call($method, $submenu, $parent);
        }
    }

    /**
     * 调用指定方法添加菜单页
     *
     * @param array $params
     * @param string|bool $parentSlug
     * @return string — page's hook_suffix
     */
    public static function call($method, array &$params, $parentSlug = false)
    {
        $args   = [];
        $render = 4;

        foreach ([
            'title'    => 'Menu',
            'name'     => 'Menu',
            'cap'      => 'manage_options',
            'slug'     => '',
            'render'   => '',
            'position' => null,
        ] as $key => $default) {
            $args[] = $params[$key] = $params[$key] ?? $default;
        }

        if (is_string($parentSlug)) {
            array_unshift($args, $params['parent'] = $parentSlug);
            $parentSlug = true;
            ++$render;
        }

        if ($parentSlug === false) {
            $args[]  = $args[5];
            $args[5] = $params['icon'] = $params['icon'] ?? '';
        }

        if (is_string($params['render']) && class_exists($params['render'])) {
            $args[$render] = function () use ($params) {
                call_user_func([$params['render'], 'render'], $params);
            };

            $hook                   = call_user_func_array($method, $args);
            static::$enqueue[$hook] = $params['render'];

            return $hook;
        }

        return call_user_func_array($method, $args);
    }

    /**
     * 获取添加子菜单的方法，存在快捷方法时 $parent=true
     *
     * @param string $parent
     * @return string
     */
    public static function getMethodToAddChild(&$parent = '')
    {
        if (function_exists($method = "add_{$parent}_page")) {
            $parent = true;
            return $method;
        }
        return 'add_submenu_page';
    }
}