<?php

namespace Impack\WP\REST;

class Router
{
    protected $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * 注册一个 GET 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function get($uri, $args = [], $override = false)
    {
        return $this->addRoute('GET', $uri, $args, $override);
    }

    /**
     * 注册一个 POST 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function post($uri, $args = [], $override = false)
    {
        return $this->addRoute('POST', $uri, $args, $override);
    }

    /**
     * 注册一个 PUT 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function put($uri, $args = [], $override = false)
    {
        return $this->addRoute('PUT', $uri, $args, $override);
    }

    /**
     * 注册一个 PATCH 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function patch($uri, $args = [], $override = false)
    {
        return $this->addRoute('PATCH', $uri, $args, $override);
    }

    /**
     * 注册一个 DELETE 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function delete($uri, $args = [], $override = false)
    {
        return $this->addRoute('DELETE', $uri, $args, $override);
    }

    /**
     * 注册一个 OPTIONS 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function options($uri, $args = [], $override = false)
    {
        return $this->addRoute('OPTIONS', $uri, $args, $override);
    }

    /**
     * 注册一个 `\WP_REST_Server::ALLMETHODS` 路由
     *
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function all($uri, $args = [], $override = false)
    {
        return $this->addRoute(\WP_REST_Server::ALLMETHODS, $uri, $args, $override);
    }

    /**
     * 注册路由
     *
     * @param string|array $methods
     * @param string $uri
     * @param array|callable $args
     * @param bool $override
     * @return $this
     */
    public function addRoute($methods, $uri, $args = [], $override = false)
    {
        if (is_callable($args)) {
            $args = ['callback' => $args, 'permission_callback' => '__return_true'];
        }

        $args['methods'] = $methods;

        \register_rest_route($this->namespace, $uri, $args, $override);

        return $this;
    }
}