<?php

namespace Impack\WP\Bootstrap;

use ErrorException;
use Exception;
use Impack\WP\Contracts\ExceptionHandler;
use Throwable;

class HandleExceptions
{
    protected $app;

    public function bootstrap($app)
    {
        $this->app = $app;

        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);

        if (!$this->app->isDebugging()) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * 处理程序未捕获的异常
     *
     * @param \Throwable $e
     */
    public function handleException(Throwable $e)
    {
        try { $this->getExceptionHandler()->report($e);} catch (Exception $e) {}

        $this->getExceptionHandler()->render($e);
    }

    /**
     * 错误转为异常，简单错误不终止运行
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            $e = new ErrorException($message, 0, $level, $file, $line);

            if ($this->isFatal($level)) {
                throw $e;
            }

            $this->handleException($e);
        }
    }

    /**
     * 处理运行结束后的错误
     *
     * @throws \ErrorException
     */
    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    /**
     * 返回处理异常的实例
     *
     * @return \Impack\WP\Contracts\ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        return $this->app->make(ExceptionHandler::class);
    }

    /**
     * 是否是严重错误
     *
     * @param  int    $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
}