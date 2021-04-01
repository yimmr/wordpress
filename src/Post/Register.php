<?php

namespace Impack\WP\Post;

use Closure;

class Register
{
    /**
     * 注册Post类型及相关
     *
     * @param array $posttypes
     * @param array $taxonomies
     */
    public static function posttype(array &$posttypes, &$taxonomies = [])
    {
        global $wp_post_types;

        foreach ($posttypes as $posttype => $params) {
            // 分类法
            if (!empty($params['taxonomies']) && !empty($taxonomies)) {
                static::taxonomy($taxonomies, $posttype, $taxNames = $params['taxonomies']);
                unset($params['taxonomies']);
            }

            // 设置metabox回调
            if (\is_admin() && isset($params['meta_boxes']) && is_array($params['meta_boxes'])) {
                $params['register_meta_box_cb'] = static::getMetaBoxCallback($params['meta_boxes'], $posttype,
                    $params['register_meta_box_cb'] ?? null);
                unset($params['meta_boxes']);
            }

            // 已有的类型只注册元框
            if (isset($wp_post_types[$posttype])) {
                $object = $wp_post_types[$posttype];
                if (\is_admin() && isset($params['register_meta_box_cb'])) {
                    $object->register_meta_box_cb = $params['register_meta_box_cb'];
                    $object->register_meta_boxes();
                }
            } else {
                $object = \register_post_type($posttype, $params);
            }

            $object->taxonomies = array_merge($object->taxonomies, $taxNames);

            // meta
            if (isset($params['meta']) && is_array($params['meta'])) {
                foreach ($params['meta'] as $key => $args) {
                    \register_post_meta($posttype, $key, $args);
                }
                unset($object->meta);
            }
        }
    }

    /**
     * 注册分类法及相关
     *
     * @param array $taxonomies
     * @param string $posttype
     * @param array $taxNameWith
     */
    public static function taxonomy(array &$taxonomies, $posttype, $taxNameWith = [])
    {
        global $wp_taxonomies;

        foreach ((empty($taxNameWith) ? $taxonomies : $taxNameWith) as $taxonomy => $params) {
            if (is_string($params) && isset($taxonomies[$params])) {
                $taxonomy = $params;
                $params   = $taxonomies[$params];
            }

            if (!is_array($params)) {
                continue;
            }

            if (\is_admin() && isset($params['fields'])) {
                static::addTaxFromFields($taxonomy, $params['fields']);
                unset($params['fields']);
            }

            ($wp_taxonomies[$taxonomy] ?? \register_taxonomy($taxonomy, $posttype, $params));
        }
    }

    /**
     * 字段添加到所有分类法
     *
     * @param \Impack\WP\Post\TaxField|\Impack\WP\Post\TaxField[] $className
     * @param array $exclude 排除的分类法
     */
    public static function addGlobalTaxField($className, array $exclude = [])
    {
        global $wp_taxonomies;

        $exclude = array_merge(['nav_menu', 'link_category', 'post_format'], $exclude);

        foreach (array_keys($wp_taxonomies) as $taxonomy) {
            if (!in_array($taxonomy, $exclude)) {
                static::addTaxFromFields($taxonomy, $className);
            }
        }
    }

    /**
     * 给指定分类法添加表单字段
     *
     * @param string $taxonomy
     * @param \Impack\WP\Post\TaxField|\Impack\WP\Post\TaxField[] $fields
     */
    public static function addTaxFromFields($taxonomy, $fields = [])
    {
        foreach ((array) $fields as $field) {
            call_user_func([$field, 'add'], $taxonomy);
        }
    }

    /**
     * 返回注册metabox的回调函数
     *
     * @param array $boxes
     * @param string $posttype
     * @param callable $callback
     * @return Closure
     */
    public static function getMetaBoxCallback(array $boxes, $posttype, $callback = null)
    {
        if (empty($boxes)) {
            return;
        }

        \add_action("save_post_{$posttype}", function ($postid) use ($boxes) {
            foreach ($boxes as $box) {
                $className = array_shift($box);
                (new $className)->save($postid);
            }
        });

        return function ($post) use ($boxes, $callback) {
            foreach ($boxes as $box) {
                $className = array_shift($box);
                $className::add(array_shift($box), array_shift($box), null, ...$box);
            }

            if (is_callable($callback)) {
                call_user_func($callback, $post);
            }
        };
    }
}