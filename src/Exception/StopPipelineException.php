<?php

declare(strict_types=1);

namespace Troum\Pipeline\Exception;

use RuntimeException;

class StopPipelineException extends RuntimeException
{
    /**
     * @param mixed $payload
     */
    public function __construct(
        private readonly mixed $payload
    ) {
        parent::__construct('Пайплайн остановлен');
    }

    /**
     * @return mixed
     */
    public function getPayload(): mixed
    {
        return $this->payload;
    }
}
