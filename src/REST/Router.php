<?php

namespace Impack\WP\REST;

use BadMethodCallException;

abstract class Router
{
    const VENDOR = 'imwp';

    const VERSION = 1;

    abstract public function routes();

    /**
     * 返回成功响应的格式
     *
     * @param mixed $data
     * @return array
     */
    public static function success($data)
    {
        return ['code' => 0, 'message' => 'Success', 'data' => $data];
    }

    /**
     * 返回\WP_Error实例
     *
     * @param string $code
     * @param string $message
     * @param int|array $data
     * @return \WP_Error
     */
    public static function error($code, $message = '', $data = '')
    {
        return new \WP_Error($code, $message, is_int($data) ? ['status' => $data] : $data);
    }

    /**
     * 注册路由
     *
     * @param string $route 动态参数如 ?P<id>\d+
     * @param array $args
     * @param bool $override 是否重写存在的路由
     * @return bool
     */
    protected static function route($route, $args = [], $override = false)
    {
        return \register_rest_route(static::getNamespace(), $route, $args, $override);
    }

    /**
     * 返回命名空间
     *
     * @return string
     */
    public static function getNamespace()
    {
        return static::VENDOR . '/v' . static::VERSION;
    }

    /**
     * 返回参数配置项
     *
     * @param mixed $default 默认值
     * @param callable $validate 验证值类型的回调
     * @param callable $sanitize 过滤参数值的回调
     * @param array
     */
    public static function createParamArgs($default, $validate = null, $sanitize = null)
    {
        return [
            'default'           => $default,
            'validate_callback' => $validate,
            'sanitize_callback' => $sanitize,
        ];
    }

    public static function __callStatic($name, $params)
    {
        if (in_array($name, ['get', 'post', 'put', 'patch', 'delete'])) {
            $params[1] = [
                'method'   => strtoupper($name),
                'callback' => $params[1],
            ];

            if (isset($params[2])) {
                $params[1] = array_merge($params[1], $params[2]);
                unset($params[2]);
            }

            return static::route(...$params);
        } else {
            throw new BadMethodCallException("Call to undefined method " . static::class . "::{$name}()");
        }
    }
}