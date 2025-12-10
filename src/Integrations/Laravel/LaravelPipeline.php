<?php

declare(strict_types=1);

namespace Troum\Pipeline\Integrations\Laravel;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Core\Pipeline;

final class LaravelPipeline extends Pipeline
{
    /**
     * @param Container $container
     * @param array $pipes
     */
    public function __construct(
        private readonly Container $container,
        array $pipes = []
    ) {
        parent::__construct($pipes);
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    public function process(mixed $payload): mixed
    {
        $resolvedPipes = array_map(
            fn($pipe) => $this->resolve($pipe),
            $this->getPipes()
        );

        return (new Pipeline())->via($resolvedPipes)->process($payload);
    }

    /**
     * @param PipeInterface|string $pipe
     * @return PipeInterface
     */
    private function resolve(PipeInterface|string $pipe): PipeInterface
    {
        if (is_string($pipe)) {
            return $this->container->make($pipe);
        }

        return $pipe;
    }

    /**
     * @return array
     */
    private function getPipes(): array
    {
        $ref = new ReflectionClass(Pipeline::class);
        $property = $ref->getProperty('pipes');
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
