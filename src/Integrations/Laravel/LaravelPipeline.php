<?php

namespace Troum\Pipeline\Integrations\Laravel;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Troum\Pipeline\Contracts\PipeInterface;
use Troum\Pipeline\Core\Pipeline;

class LaravelPipeline extends Pipeline
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
            fn($pipe) => $this->resolvePipe($pipe),
            $this->getPipes()
        );

        return new Pipeline()->via($resolvedPipes)->process($payload);
    }

    /**
     * @return array
     */
    private function getPipes(): array
    {
        $ref = new ReflectionClass(Pipeline::class);
        $prop = $ref->getProperty('pipes');
        $prop->setAccessible(true);

        return $prop->getValue($this);
    }

    /**
     * @param PipeInterface|string $pipe
     * @return PipeInterface
     */
    private function resolvePipe(PipeInterface|string $pipe): PipeInterface
    {
        if (is_string($pipe)) {
            return $this->container->make($pipe);
        }

        return $pipe;
    }
}
