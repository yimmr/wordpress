<?php

namespace Impack\WP\Post;

class Register
{
    protected static $taxonomies = [];

    /**
     * 注册Post类型及分类法等相关功能
     *
     * @param array $posttypes
     * @param array $taxonomies
     */
    public static function posttype(array $posttypes, $taxonomies = [])
    {
        static::$taxonomies = &$taxonomies;

        foreach ($posttypes as $posttype => $params) {
            if (isset($params['taxonomies'])) {
                static::taxonomy($taxNames = $params['taxonomies'], $posttype);
                unset($params['taxonomies']);
            }

            if (isset($params['meta_boxes'])) {
                static::setRegisterMetaBoxCB($params, $posttype);
            }

            // 注册过的类型只重新注册元框
            if (isset($GLOBALS['wp_post_types'][$posttype])) {
                $object = $GLOBALS['wp_post_types'][$posttype];
                if (isset($params['register_meta_box_cb'])) {
                    \add_action('add_meta_boxes_' . $object->name, $params['register_meta_box_cb']);
                }
            } else {
                $object = \register_post_type($posttype, $params);
            }

            array_push($object->taxonomies, ...$taxNames);

            if (isset($params['meta'])) {
                foreach ($params['meta'] as $key => $args) {
                    \register_post_meta($posttype, $key, $args);
                }
                unset($object->meta);
            }
        }

        static::$taxonomies = [];
    }

    /**
     * 注册分类法及相关功能
     *
     * @param array $taxonomies
     * @param string $posttype
     */
    public static function taxonomy(array $taxonomies, $posttype)
    {
        foreach ($taxonomies as $taxonomy => $params) {
            if (is_string($params)) {
                if (!isset(static::$taxonomies[$params])) {
                    continue;
                }
                $taxonomy = $params;
                $params   = static::$taxonomies[$params];
            }

            if (isset($params['fields'])) {
                \is_admin() && static::addTaxField($taxonomy, $params['fields']);
                unset($params['fields']);
            }

            $GLOBALS['wp_taxonomies'][$taxonomy] ?? \register_taxonomy($taxonomy, $posttype, $params);
        }
    }

    /**
     * 添加分类法的自定义字段
     *
     * @param string $taxonomy
     * @param string|array $fields
     */
    public static function addTaxField($taxonomy, $fields = [])
    {
        foreach ((array) $fields as $field) {
            $field::add($taxonomy);
        }
    }

    /**
     * 字段添加到所有分类法
     *
     * @param string|array $className
     * @param array $exclude 排除的分类法
     */
    public static function addGlobalTaxField($className, array $exclude = [])
    {
        $exclude = array_merge(['nav_menu', 'link_category', 'post_format'], $exclude);

        foreach (array_keys($GLOBALS['wp_taxonomies']) as $taxonomy) {
            if (!in_array($taxonomy, $exclude)) {
                static::addTaxField($taxonomy, $className);
            }
        }
    }

    /**
     * 设置注册元框的回调
     *
     * @param array $params
     * @param string $posttype
     */
    public static function setRegisterMetaBoxCB(array &$params, $posttype)
    {
        $boxes = $params['meta_boxes'];
        unset($params['meta_boxes']);

        if (!\is_admin() || empty($boxes)) {
            return;
        }

        \add_action("save_post_{$posttype}", function ($postid) use ($boxes) {
            foreach ($boxes as $box) {
                $className = array_shift($box);
                (new $className)->save($postid);
            }
        });

        $callback = $params['register_meta_box_cb'] ?? null;

        $params['register_meta_box_cb'] = function ($post) use ($boxes, $callback) {
            foreach ($boxes as $box) {
                $className = array_shift($box);
                $className::add(array_shift($box), array_shift($box), null, ...$box);
            }

            is_callable($callback) && call_user_func($callback, $post);
        };
    }
}