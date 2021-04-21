<?php

namespace Impack\WP\Service;

trait ViewTrait
{
    /**
     * 引入模板
     *
     * @param string $filename
     * @param array $data
     */
    public static function template($filename, $data = [])
    {
        static::getInstance()->render($filename, $data, false);
    }

    /**
     * 引入模板
     *
     * @param string $filename
     * @param array $data
     * @param bool $name
     */
    public function render($filename, $data = [], $once = true)
    {
        \load_template($this->getTemplateFile($filename), $once, $data);
    }

    /**
     * 返回视图路径
     *
     * @param string $path
     * @return string
     */
    public function viewPath($path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'views' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回模板文件路径
     *
     * @param string $filename
     * @return string
     */
    protected function getTemplateFile($filename)
    {
        $file = $this->viewPath("{$filename}.view.php");

        return file_exists($file) ? $file : $this->viewPath("{$filename}.php");
    }
}