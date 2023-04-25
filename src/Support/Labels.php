<?php

class Labels
{
    public static function getPostTypeLabels()
    {
        return [
            'name'                     => _x('Pages', 'post type general name', 'textdomain'),
            'singular_name'            => _x('Page', 'post type singular name', 'textdomain'),
            'add_new'                  => _x('Add New', 'page', 'textdomain'),
            'add_new_item'             => __('Add New Page', 'textdomain'),
            'edit_item'                => __('Edit Page', 'textdomain'),
            'new_item'                 => __('New Page', 'textdomain'),
            'view_item'                => __('View Page', 'textdomain'),
            'view_items'               => __('View Pages', 'textdomain'),
            'search_items'             => __('Search Pages', 'textdomain'),
            'not_found'                => __('No pages found.', 'textdomain'),
            'not_found_in_trash'       => __('No pages found in Trash.', 'textdomain'),
            // 'parent_item_colon'        =>  __('Parent Page:', 'textdomain'),
            'all_items'                => __('All Pages', 'textdomain'),
            'archives'                 => __('Page Archives', 'textdomain'),
            'attributes'               => __('Page Attributes', 'textdomain'),
            'insert_into_item'         => __('Insert into page', 'textdomain'),
            'uploaded_to_this_item'    => __('Uploaded to this page', 'textdomain'),
            // 'featured_image'           => _x('Featured image', 'page', 'textdomain'),
            // 'set_featured_image'       => _x('Set featured image', 'page', 'textdomain'),
            // 'remove_featured_image'    => _x('Remove featured image', 'page', 'textdomain'),
            // 'use_featured_image'       => _x('Use as featured image', 'page', 'textdomain'),
            // 'filter_by_date'           => __('Filter by date', 'textdomain'),
            'filter_items_list'        => __('Filter pages list', 'textdomain'),
            'items_list_navigation'    => __('Pages list navigation', 'textdomain'),
            'items_list'               => __('Pages list', 'textdomain'),
            'item_published'           => __('Page published.', 'textdomain'),
            'item_published_privately' => __('Page published privately.', 'textdomain'),
            'item_reverted_to_draft'   => __('Page reverted to draft.', 'textdomain'),
            // 'item_scheduled'           => __('Page scheduled.', 'textdomain'),
            'item_updated'             => __('Page updated.', 'textdomain'),
            // 'item_link'                => _x('Page Link', 'navigation link block title', 'textdomain'),
            // 'item_link_description'    => _x('A link to a page.', 'navigation link block description', 'textdomain'),
        ];
    }

    public static function getTaxonomyLabels()
    {

        return [
            'name'                  => _x('Categories', 'taxonomy general name', 'textdomain'),
            'singular_name'         => _x('Category', 'taxonomy singular name', 'textdomain'),
            'search_items'          => __('Search Categories', 'textdomain'),
            'all_items'             => __('All Categories', 'textdomain'),
            'parent_item'           => __('Parent Category', 'textdomain'),
            'parent_item_colon'     => __('Parent Category:', 'textdomain'),
            'edit_item'             => __('Edit Category', 'textdomain'),
            'view_item'             => __('View Category', 'textdomain'),
            'update_item'           => __('Update Category', 'textdomain'),
            'add_new_item'          => __('Add New Category', 'textdomain'),
            'new_item_name'         => __('New Category Name', 'textdomain'),
            'not_found'             => __('No categories found.', 'textdomain'),
            'no_terms'              => __('No categories', 'textdomain'),
            'filter_by_item'        => __('Filter by category', 'textdomain'),
            'items_list_navigation' => __('Categories list navigation', 'textdomain'),
            'items_list'            => __('Categories list', 'textdomain'),
            'item_link'             => _x('Category Link', 'navigation link block title', 'textdomain'),
            'item_link_description' => _x('A link to a category.', 'navigation link block description', 'textdomain'),

            // 'popular_items'         => null,
            // 'name_field_description'     => '',
            // 'slug_field_description'     => '',
            // 'parent_field_description'   => '',
            // 'desc_field_description'     => '',

            // 'separate_items_with_commas' => null,
            // 'add_or_remove_items'        => null,
            // 'choose_from_most_used'      => null,

            /* translators: Tab heading when selecting from the most used terms. */
            // 'most_used'                  => _x('Most Used', 'categories', 'textdomain'),
            // 'back_to_items'              => __('&larr; Go to Categories', 'textdomain'),
        ];
    }
}