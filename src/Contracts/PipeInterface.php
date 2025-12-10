<?php

declare(strict_types=1);

namespace Troum\Pipeline\Contracts;

use Closure;

interface PipeInterface
{
    /**
     * @param mixed $payload Входные данные
     * @param Closure $next Следующий шаг
     * @return mixed
     */
    public function handle(mixed $payload, Closure $next): mixed;
}
