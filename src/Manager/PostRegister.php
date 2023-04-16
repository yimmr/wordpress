<?php

namespace Impack\WP\Manager;

class PostRegister
{
    /**
     * 注册类型及分类法等相关功能
     *
     * @param array $posttypes
     * @param array $taxonomies
     */
    public static function posttype(array $posttypes, array $taxonomies = [])
    {
        foreach ($posttypes as $posttype => $params) {
            if (isset($params['taxonomies'])) {
                self::taxonomy($posttype, is_array(current($params['taxonomies']))
                        ? $params['taxonomies']
                        : array_filter($taxonomies, function ($tax) use (&$params) {
                        return in_array($tax, $params['taxonomies']);
                    }, \ARRAY_FILTER_USE_KEY)
                );

                unset($params['taxonomies']);
            }

            if (isset($params['meta_boxes'])) {
                self::metaBoxes($posttype, $params['meta_boxes']);
                unset($params['meta_boxes']);
            }

            if (isset($params['meta'])) {
                foreach ($params['meta'] as $key => $args) {
                    \register_post_meta($posttype, $key, $args);
                }
                unset($params['meta']);
            }

            isset($GLOBALS['wp_post_types'][$posttype]) || \register_post_type($posttype, $params);
        }
    }

    /**
     * 注册分类法和相关功能
     *
     * @param string $posttype
     * @param array  $taxonomies
     */
    public static function taxonomy($posttype, array $taxonomies)
    {
        foreach ($taxonomies as $taxonomy => $params) {
            if (isset($params['fields'])) {
                \is_admin() && self::addTaxField($taxonomy, $params['fields']);
                unset($params['fields']);
            }

            if (isset($params['meta'])) {
                foreach ($params['meta'] as $key => $args) {
                    \register_term_meta($taxonomy, $key, $args);
                }
                unset($params['meta']);
            }

            isset($GLOBALS['wp_taxonomies'][$taxonomy]) || \register_taxonomy($taxonomy, $posttype, $params);
        }
    }

    /**
     * 注册元框
     *
     * @param string $posttype
     * @param array  $metaBoxes
     */
    public static function metaBoxes($posttype, array $metaBoxes)
    {
        if (!\is_admin() || empty($metaBoxes)) {
            return;
        }

        \add_action("save_post_{$posttype}", function ($postid) use ($metaBoxes) {
            foreach ($metaBoxes as $box) {
                (new $box[2])->save($postid);
            }
        });

        \add_action("add_meta_boxes_{$posttype}", function () use ($metaBoxes) {
            array_map(function ($box) {
                $box[2] = [new $box[2], 'render'];
                \add_meta_box(...$box);
            }, $metaBoxes);
        });
    }

    /**
     * 分类法添加自定义字段
     *
     * @param string $taxonomy
     * @param array  $fields
     */
    public static function addTaxField($taxonomy, array $fields = [])
    {
        array_map(function ($field) use ($taxonomy) {
            $object = new $field($taxonomy);

            \add_action("{$taxonomy}_add_form_fields", [$object, 'addFields']);
            \add_action("{$taxonomy}_edit_form_fields", [$object, 'editFields']);

            if (method_exists($object, 'save')) {
                \add_action("saved_{$taxonomy}", function ($termid) use ($object) {
                    if (isset($_POST['action']) && in_array($_POST['action'], ['add-tag', 'editedtag'])) {
                        $object->save($termid);
                    }
                });
            }
        }, $fields);
    }

    /**
     * 全部分类法添加自定义字段
     *
     * @param array $fields
     * @param array $exclude  排除的分类法
     */
    public static function addGlobalTaxField(array $fields, array $exclude = [])
    {
        array_map(function ($taxonomy) use ($fields, $exclude) {
            in_array($taxonomy, $exclude) || self::addTaxField($taxonomy, $fields);
        }, \get_taxonomies(['show_ui' => true, '_builtin' => false]), ['category', 'post_tag']);
    }
}