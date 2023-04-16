<?php

namespace Impack\WP\Base;

trait FilesystemTrait
{
    /**
     * 初始化WP文件系统
     */
    public function wpFilesystem()
    {
        global $wp_filesystem;

        if (!function_exists('WP_Filesystem')) {
            require_once rtrim(\ABSPATH, '\/') . implode(\DIRECTORY_SEPARATOR, ['', 'wp-admin', 'includes', 'file.php']);

            \WP_Filesystem();
        }

        if ($wp_filesystem instanceof \WP_Filesystem_FTPext) {
            \wp_die($wp_filesystem->errors);
        }
    }
}