<?php

namespace Troum\BranchedPipeline;

use BackedEnum;
use Closure;
use InvalidArgumentException;

readonly class EnumSwitchPipe implements PipeInterface
{
    /**
     * @param string $field
     * @param array<BackedEnum, list<PipeInterface>> $cases
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
        if (!array_key_exists($this->field, $payload)) {
            return $this->runDefault($payload, $next);
        }

        $value = $payload[$this->field];

        if (!$value instanceof BackedEnum) {
            throw new InvalidArgumentException(
                sprintf(
                    'Поле "%s" должно содержать резервное перечисление, а задано %s',
                    $this->field,
                    get_debug_type($value)
                )
            );
        }

        $key = $value->value;
        $pipes = $this->cases[$key] ?? $this->default;

        if (!empty($pipes)) {
            $payload = new Pipeline()
                ->via($pipes)
                ->process($payload);
        }

        return $next($payload);
    }

    /**
     * @param mixed $payload
     * @param Closure $next
     * @return mixed
     */
    private function runDefault(mixed $payload, Closure $next): mixed
    {
        if (!empty($this->default)) {
            $payload = new Pipeline()
                ->via($this->default)
                ->process($payload);
        }

        return $next($payload);
    }
}
