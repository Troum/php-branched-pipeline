<?php

declare(strict_types=1);

namespace Troum\Pipeline\Core;

use Closure;
use InvalidArgumentException;
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Exception\StopPipelineException;

class Pipeline
{
    /** @var list<PipeInterface> */
    private array $pipes = [];

    /**
     * @param array $pipes
     */
    public function __construct(array $pipes = [])
    {
        $this->via($this->pipes);
    }

    /**
     * @param list<PipeInterface> $pipes
     * @return self
     */
    public function via(array $pipes): self
    {
        $this->validatePipes($pipes);
        $this->pipes = array_values($pipes);

        return $this;
    }

    /**
     * @param PipeInterface ...$pipes
     * @return $this
     */
    public function append(PipeInterface ...$pipes): self
    {
        foreach ($pipes as $pipe) {
            $this->pipes[] = $pipe;
        }
        return $this;
    }

    /**
     * @param PipeInterface ...$pipes
     * @return $this
     */
    public function prepend(PipeInterface ...$pipes): self
    {
        array_unshift($this->pipes, ...$pipes);
        return $this;
    }

    /**
     * @param PipeInterface $before
     * @param PipeInterface ...$pipes
     * @return $this
     */
    public function insertBefore(PipeInterface $before, PipeInterface ...$pipes): self
    {
        $index = array_search($before, $this->pipes, true);

        if ($index === false) {
            throw new InvalidArgumentException('Указанный pipe не найден.');
        }

        array_splice($this->pipes, $index, 0, $pipes);

        return $this;
    }

    /**
     * @param PipeInterface $after
     * @param PipeInterface ...$pipes
     * @return $this
     */
    public function insertAfter(PipeInterface $after, PipeInterface ...$pipes): self
    {
        $index = array_search($after, $this->pipes, true);

        if ($index === false) {
            throw new InvalidArgumentException('Указанный pipe не найден.');
        }

        array_splice($this->pipes, $index + 1, 0, $pipes);

        return $this;
    }

    /**
     * @return $this
     */
    public function clear(): self
    {
        $this->pipes = [];
        return $this;
    }

    /**
     * @param array $pipes
     * @return void
     */
    private function validatePipes(array $pipes): void
    {
        foreach ($pipes as $pipe) {
            if (!$pipe instanceof PipeInterface) {
                throw new InvalidArgumentException(
                    'Каждый элемент массива должен быть экземпляром PipeInterface.'
                );
            }
        }
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    public function process(mixed $payload): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            fn(Closure $next, PipeInterface $pipe) => fn($payload) => $pipe->handle($payload, $next),
            fn($payload) => $payload
        );

        try {
            return $pipeline($payload);
        } catch (StopPipelineException $e) {
            return $e->getPayload();
        }
    }
}
