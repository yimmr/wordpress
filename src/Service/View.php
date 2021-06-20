<?php

namespace Impack\WP\Service;

use Impack\WP\Service\Base;

class View
{
    protected $service;

    public function __construct(Base $service)
    {
        $this->service = $service;
    }

    /**
     * 渲染模板
     *
     * @param string $filename
     * @param array $data
     * @param bool $name
     */
    public function render($filename, $data = [], $once = false)
    {
        \load_template($this->getTemplateFile($filename), $once, $data);
    }

    /**
     * 返回模板文件路径
     *
     * @param string $filename
     * @return string
     */
    public function getTemplateFile($filename)
    {
        $file = $this->service->path("{$filename}.view.php");

        return file_exists($file) ? $file : $this->service->viewPath("{$filename}.php");
    }
}