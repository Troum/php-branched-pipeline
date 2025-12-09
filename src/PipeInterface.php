<?php

declare(strict_types=1);

namespace Troum\BranchedPipeline;

use Closure;
interface PipeInterface
{
    /**
     * @param mixed $payload Входные данные
     * @param Closure $next
     * @return mixed
     */
    public function handle(mixed $payload, Closure $next): mixed;
}
