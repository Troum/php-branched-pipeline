<?php

declare(strict_types=1);

namespace Troum\Pipeline\Integrations\Symfony;

use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Troum\Pipeline\Core\Pipeline;
use Troum\Pipeline\Contracts\PipeInterface;

final class SymfonyPipeline extends Pipeline
{
    public function __construct(
        private readonly ContainerInterface $container,
        array $pipes = [],
    ) {
        parent::__construct($pipes);
    }

    public function process(mixed $payload): mixed
    {
        $wrappedPipes = array_map(
            fn($pipe) => $this->wrap($pipe),
            $this->getPipes()
        );

        return (new Pipeline())->via($wrappedPipes)->process($payload);
    }

    private function wrap(PipeInterface|string $pipe): PipeInterface
    {
        return new SymfonyLazyResolver($this->container, $pipe);
    }

    private function getPipes(): array
    {
        $ref = new ReflectionClass(Pipeline::class);
        $prop = $ref->getProperty('pipes');
        $prop->setAccessible(true);
        return $prop->getValue($this);
    }
}
