<?php

namespace Impack\WP\Base;

use Exception;
use Impack\WP\Base\ApplicationTrait;
use ReflectionObject;

class Filesystem
{
    protected $wpFilesystem;

    public function __construct()
    {
        global $wp_filesystem;

        $this->loadWPFilesystem();

        if ($wp_filesystem instanceof \WP_Filesystem_FTPext) {
            throw new Exception($wp_filesystem->errors->get_error_message(), 0);
        }

        $this->wpFilesystem = $wp_filesystem;
    }

    /**
     * WP_Filesystem_Direct所有可用方法
     *
     * @return \ReflectionObject
     */
    public function getMethods()
    {
        return new ReflectionObject($this->wpFilesystem);
    }

    /**
     * 复制目录
     *
     * @param string $from
     * @param string $to
     * @param string $skipList 忽略的文件/文件夹数组。
     * @return true|\WP_Error
     */
    public function copyDir($from, $to, $skipList = [])
    {
        return \copy_dir($from, $to, $skipList);
    }

    /**
     * 解压文件至指定路径
     *
     * @param string $file
     * @param string $to
     * @return true|\WP_Error
     */
    public function unzip($file, $to)
    {
        return \unzip_file($file, $to);
    }

    /**
     * 加载Wordpress文件系统
     */
    protected function loadWPFilesystem()
    {
        if (!function_exists('WP_Filesystem')) {
            require_once rtrim(\ABSPATH, '\/') . sprintf('%1$swp-admin%1$sincludes%1$sfile.php', \DIRECTORY_SEPARATOR);

            \WP_Filesystem();
        }
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->wpFilesystem, ApplicationTrait::methodToHook($name)], $arguments);
    }
}