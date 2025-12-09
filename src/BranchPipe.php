<?php

declare(strict_types=1);

namespace Troum\Pipeline;

use Closure;

readonly class BranchPipe implements PipeInterface
{
    /**
     * @param Closure $condition
     * @param list<PipeInterface> $isTrueConditionPipes
     * @param list<PipeInterface> $isFalseConditionPipes
     */
    public function __construct(
        private Closure $condition,
        private array   $isTrueConditionPipes,
        private array   $isFalseConditionPipes,
    ) {}

    /**
     * @inheritDoc
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        $subPipeline = new Pipeline();

        $pipes = ($this->condition)($payload)
        ? $this->isTrueConditionPipes
        : $this->isFalseConditionPipes;

        if (!empty($pipes)) {
            $payload = $subPipeline
                ->via($pipes)
                ->process($payload);
        }

        return $next($payload);
    }
}
