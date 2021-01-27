<?php

namespace Impack\WP\Register;

use Closure;
use Impack\Contracts\Foundation\Application;
use Impack\Support\Str;
use Impack\WP\Register\MetaBox;

class Manager
{
    protected $app;

    protected $isAdmin = false;

    public function __construct(Application $app)
    {
        $this->app     = $app;
        $this->isAdmin = \is_admin();
    }

    /**
     * 自动注册Post类型和相关功能
     */
    public function registerPostType()
    {
        global $wp_post_types;

        $taxonomies = [];

        foreach ($this->getConfig('post') as $postType => $params) {
            // 处理分类法
            if (isset($params['taxonomies']) && $params['taxonomies']) {
                $this->registerTaxonomy($taxonomies = $params['taxonomies'], $postType);
                unset($params['taxonomies']);
            }

            if ($this->isAdmin && isset($params['meta_boxes']) && is_array($params['meta_boxes'])) {
                $params['meta_boxes']           = $this->getMetaBoxes($params['meta_boxes']);
                $params['register_meta_box_cb'] = $this->getMetaBoxCallback($params['meta_boxes'], $postType,
                    $params['register_meta_box_cb'] ?? null);
            }

            if (isset($wp_post_types[$postType])) {
                $object = $wp_post_types[$postType];
                if ($this->isAdmin && isset($params['register_meta_box_cb'])) {
                    $object->register_meta_box_cb = $params['register_meta_box_cb'];
                    $object->register_meta_boxes();
                }
            } else {
                $object = \register_post_type($postType, $params);
            }

            if (isset($params['meta']) && is_array($params['meta'])) {
                foreach ($params['meta'] as $key => $args) {
                    \register_post_meta($postType, $key, (array) $args);
                }
            }

            if (!is_wp_error($object)) {
                $object->taxonomies = $taxonomies;
                if (isset($params['object_alias'])) {
                    $this->setAppInstance($params['object_alias'], $object, "PostType_$postType");
                }
            }
        }
    }

    /**
     * 自动注册分类法和相关功能
     *
     * @param  array|string  $taxonomies
     * @param  string  $postType
     */
    public function registerTaxonomy($taxonomies, $postType)
    {
        global $wp_taxonomies;

        if (is_object($taxonomies)) {
            return;
        }

        $config = $this->getConfig('taxonomy');

        foreach ((array) $taxonomies as $taxonomy) {
            if (!isset($config[$taxonomy]) || !is_array($config[$taxonomy])) {
                continue;
            }

            $object = $wp_taxonomies[$taxonomy] ?? \register_taxonomy($taxonomy, $postType, $config[$taxonomy]);

            if ($this->isAdmin && isset($config[$taxonomy]['fields'])) {
                $this->addTaxFromFields($taxonomy, $config[$taxonomy]['fields']);
            }

            if (!is_wp_error($object) && isset($config[$taxonomy]['object_alias'])) {
                $this->setAppInstance($config[$taxonomy]['object_alias'], $object, "Tax_$taxonomy");
            }
        }
    }

    /**
     * 在所有分类法添加表单字段
     *
     * @param  string|\Impack\WP\Register\TaxField[]  $className
     * @param  array  $exclude
     */
    public function addGlobalTaxField($className, array $exclude = [])
    {
        global $wp_taxonomies;

        if (!$this->isAdmin) {
            return;
        }

        $exclude = array_merge(['nav_menu', 'link_category', 'post_format'], $exclude);

        foreach (array_keys($wp_taxonomies) as $taxonomy) {
            if (!in_array($taxonomy, $exclude)) {
                $this->addTaxFromFields($taxonomy, $className);
            }
        }
    }

    /**
     * 读取配置
     *
     * @param  string  $key
     * @return array
     */
    protected function getConfig($key)
    {
        return $this->app['config']->get($key, []);
    }

    /**
     * 返回可注册MetaBox的回调函数
     *
     * @param  array  $boxes
     * @param  string  $postType
     * @param  callable  $callback
     * @return Closure
     */
    protected function getMetaBoxCallback(array $boxes, $postType, $callback = null)
    {
        if (empty($boxes)) {
            return;
        }

        \add_action("save_post_$postType", function ($postid) use ($boxes) {
            foreach (array_keys($boxes) as $className) {
                $className::instance()->save($postid);
            }
        });

        return function ($post) use ($boxes, $callback) {
            foreach ($boxes as $className => $array) {
                foreach ($array as $args) {
                    $className::add(array_shift($args), array_shift($args), null, ...$args);
                }
            }

            if (is_callable($callback)) {
                call_user_func($callback, $post);
            }
        };
    }

    /**
     * 返回符合约定的元框
     *
     * @param  array  $boxes
     * @return array
     */
    protected function getMetaBoxes(array &$boxes)
    {
        $metaBoxes = [];

        foreach ($boxes as $args) {
            if (!is_array($args)) {
                if ($this->isMetaBox($metaBox = array_shift($boxes))) {
                    $metaBoxes[$metaBox][] = $boxes;
                }
                break;
            }

            if ($this->isMetaBox($metaBox = array_shift($args))) {
                $metaBoxes[$metaBox][] = $args;
            }
        }

        return $metaBoxes;
    }

    /**
     * 是否是可调用的元框类
     *
     * @param  string  $metaBox
     * @return bool
     */
    protected function isMetaBox($metaBox)
    {
        return class_exists($metaBox) && is_callable($metaBox, 'instance') && $metaBox::instance() instanceof MetaBox;
    }

    /**
     * 分类法添加指定的表单字段
     *
     * @param  string  $taxonomy
     * @param  array  $fields
     */
    protected function addTaxFromFields($taxonomy, $fields = [])
    {
        if (empty($fields)) {
            return;
        }

        foreach ((array) $fields as $field) {
            if (class_exists($field) && is_callable($field, 'add')) {
                call_user_func([$field, 'add'], $taxonomy);
            }
        }
    }

    /**
     * 绑定注册后的对象至容器
     *
     * @param  string|mixed  $alias
     * @param  object  $object
     * @param  string  $default
     */
    protected function setAppInstance($alias, $object, $default = '')
    {
        $this->app->instance(is_string($alias) ? $alias : ucfirst(Str::camel($default)), $object);
    }
}