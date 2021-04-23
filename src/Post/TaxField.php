<?php

namespace Impack\WP\Post;

abstract class TaxField
{
    /** 新建分类法的表单字段 */
    abstract public function addField();

    /**
     * 编辑分类法的表单字段
     *
     * @param \WP_Term $term
     */
    abstract public function editField($term);

    /**
     * 保存数据
     *
     * @param int $termid
     */
    abstract public function save($termid);

    /**
     * 添加分类法表单字段
     *
     * @param string $taxonomy
     */
    public static function add($taxonomy)
    {
        \add_action("{$taxonomy}_add_form_fields", [new static , 'addField']);
        \add_action("{$taxonomy}_edit_form_fields", [new static , 'editField']);
        \add_action("created_{$taxonomy}", [static::class, 'saveField']);
        \add_action("edited_{$taxonomy}", [static::class, 'saveField']);
    }

    /**
     * 保存表单时执行的回调
     *
     * @param int $termid
     */
    public static function saveField($termid)
    {
        if (isset($_POST['action']) && in_array($_POST['action'], ['add-tag', 'editedtag'])) {
            (new static )->save($termid);
        }
    }
}