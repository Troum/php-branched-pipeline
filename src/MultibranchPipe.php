<?php

declare(strict_types=1);

namespace Troum\Pipeline;

use Closure;
use InvalidArgumentException;

class MultibranchPipe implements PipeInterface
{
    public const string MODE_FIRST_MATCH = 'first_match';
    public const string MODE_ALL_MATCHES = 'all_matches';

    /**
     * @var array<int, array{condition: Closure(mixed): bool, pipes: list<PipeInterface>}>
     */
    private array $branches = [];

    public function __construct(
        array $branches = [],
        private readonly string $mode = self::MODE_FIRST_MATCH,
    ) {
        foreach ($branches as $branch) {
            if (!isset($branch['condition'], $branch['pipes'])) {
                throw new InvalidArgumentException(
                    'Каждое ветвление должно иметь: condition => Closure, pipes => array'
                );
            }

            $this->branches[] = [
                'condition' => $branch['condition'],
                'pipes' => $branch['pipes'],
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        foreach ($this->branches as $branch) {
            if (($branch['condition'])($payload)) {

                $payload = new Pipeline()
                    ->via($branch['pipes'])
                    ->process($payload);

                if ($this->mode === self::MODE_FIRST_MATCH) {
                    break;
                }
            }
        }

        return $next($payload);
    }
}
