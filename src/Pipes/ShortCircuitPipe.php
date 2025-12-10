<?php

namespace Troum\Pipeline\Pipes;

use Closure;
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Exception\StopPipelineException;

readonly class ShortCircuitPipe implements PipeInterface
{
    /**
     * @param Closure|null $transform
     */
    public function __construct(
        private ?Closure $transform = null
    ) {}

    /**
     * @inheritDoc
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        if ($this->transform) {
            $payload = ($this->transform)($payload);
        }

        throw new StopPipelineException($payload);
    }
}
