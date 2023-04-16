<?php

namespace Impack\WP\Components;

class TaxForm
{
    /**
     * 新建标签时的字段
     *
     * @param string $label
     * @param string $content
     * @param string $tips
     */
    public static function addField($label, $content = '', $tips = '')
    {
        echo '<div class="form-field">';
        echo '<label>' . $label . '</label>';
        echo $content;
        echo '</div>';
        echo $tips ? "<p>{$tips}</p>" : '';
    }

    /**
     * 编辑标签时的字段
     *
     * @param string $label
     * @param string $content
     * @param string $tips
     */
    public static function editField($label, $content = '', $tips = '')
    {
        echo '<tr class="form-field">';
        echo '<th scope="row">' . $label . '</th>';
        echo '<td>' . $content . '</td>';
        echo '</tr>';
        echo $tips ? '<p class="description">' . $tips . '</p>' : '';
    }
}