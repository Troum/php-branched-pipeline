<?php

declare(strict_types=1);

namespace Troum\Pipeline\Pipes;

use Closure;
use InvalidArgumentException;
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Core\AbstractFieldPipe;
use Troum\Pipeline\Core\Pipeline;

class SwitchPipe extends AbstractFieldPipe
{
    /**
     * @param string $field
     * @param array<string|int, list<PipeInterface>> $cases
     * @param list<PipeInterface> $default
     */
    public function __construct(
        private readonly string $field,
        private readonly array $cases = [],
        private readonly array $default = [],
    ) {}

    /**
     * @inheritDoc
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        $value = $this->getFieldValue($payload, $this->field);

        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Значение поля "%s" должно быть string|int для SwitchPipe, %s передан.',
                    $this->field,
                    get_debug_type($value)
                )
            );
        }

        $pipes = $this->cases[$value] ?? $this->default;

        if (!empty($pipes)) {
            $payload = new Pipeline()
                ->via($pipes)
                ->process($payload);
        }

        return $next($payload);
    }
}
