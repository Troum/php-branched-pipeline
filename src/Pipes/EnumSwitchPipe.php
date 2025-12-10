<?php

declare(strict_types=1);

namespace Troum\Pipeline\Pipes;

use BackedEnum;
use Closure;
use InvalidArgumentException;
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Core\AbstractFieldPipe;
use Troum\Pipeline\Core\Pipeline;

class EnumSwitchPipe extends AbstractFieldPipe
{
    /**
     * @var array<string|int, list<PipeInterface>>
     */
    private array $normalizedCases = [];

    /**
     * @param string $field — поле payload с Enum
     * @param array<BackedEnum, list<PipeInterface>> $cases
     * @param list<PipeInterface> $default
     */
    public function __construct(
        private readonly string $field,
        private readonly array  $cases = [],
        private readonly array  $default = [],
    )
    {
        $normalized = [];

        foreach ($this->cases as $enum => $pipes) {
            if (!$enum instanceof BackedEnum) {
                throw new InvalidArgumentException(
                    'Ключи массива cases для EnumSwitchPipe должны быть BackedEnum.'
                );
            }

            $normalized[$enum->value] = $pipes;
        }

        $this->normalizedCases = $normalized;
    }

    public function handle(mixed $payload, Closure $next): mixed
    {
        $value = $this->getFieldValue($payload, $this->field);

        if (!$value instanceof BackedEnum) {
            throw new InvalidArgumentException(
                sprintf(
                    'Поле "%s" должно содержать BackedEnum для EnumSwitchPipe, %s передан.',
                    $this->field,
                    get_debug_type($value)
                )
            );
        }

        $key = $value->value;
        $pipes = $this->normalizedCases[$key] ?? $this->default;

        if (!empty($pipes)) {
            $payload = new Pipeline()
                ->via($pipes)
                ->process($payload);
        }

        return $next($payload);
    }
}
