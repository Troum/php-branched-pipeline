<?php

declare(strict_types=1);

namespace Troum\Pipeline;

use Closure;

readonly class SwitchPipe implements PipeInterface
{
    /**
     * @param array<string|int, list<PipeInterface>> $cases
     * @param list<PipeInterface> $default
     */
    public function __construct(
        private string $field,
        private array  $cases = [],
        private array  $default = [],
    ) {}

    /**
     * @inheritDoc
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        $value = $payload[$this->field] ?? null;

        $pipes = $this->cases[$value] ?? $this->default;

        if (!empty($pipes)) {
            $payload = new Pipeline()
                ->via($pipes)
                ->process($payload);
        }

        return $next($payload);
    }
}
