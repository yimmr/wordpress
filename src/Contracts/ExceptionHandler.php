<?php

namespace Impack\WP\Contracts;

use Throwable;

interface ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     *
     * @throws \Throwable
     */
    public function report(Throwable $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Throwable  $e
     *
     * @throws \Throwable
     */
    public function render(Throwable $e);
}