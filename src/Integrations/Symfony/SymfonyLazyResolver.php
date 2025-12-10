<?php

declare(strict_types=1);

namespace Troum\Pipeline\Integrations\Symfony;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Troum\Pipeline\Contracts\PipeInterface;
use Closure;

final readonly class SymfonyLazyResolver implements PipeInterface
{
    /**
     * @param ContainerInterface $container
     * @param string|PipeInterface $target
     */
    public function __construct(
        private ContainerInterface   $container,
        private string|PipeInterface $target
    ) {}

    /**
     * @param mixed $payload
     * @param Closure $next
     * @return mixed
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        $pipe = $this->target instanceof PipeInterface
            ? $this->target
            : $this->container->get($this->target);

        return $pipe->handle($payload, $next);
    }
}
