<?php

declare(strict_types=1);

namespace Troum\BranchedPipeline;

use Closure;

class Pipeline
{
    /**
     * @param list<PipeInterface> $pipes
     */
    public function __construct(private array $pipes = [])
    {
    }

    /**
     * @param list<PipeInterface> $pipes
     * @return self
     */
    public function via(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    public function process(mixed $payload): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            function (Closure $next, PipeInterface $pipe): Closure {
                return function (mixed $payload) use ($next, $pipe) {
                    return $pipe->handle($payload, $next);
                };
            },
            fn (mixed $payload) => $payload
        );

        return $pipeline($payload);
    }
}
