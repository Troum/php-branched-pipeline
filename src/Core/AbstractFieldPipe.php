<?php

declare(strict_types=1);

namespace Troum\Pipeline\Core;

use ArrayAccess;
use InvalidArgumentException;
use Troum\Pipeline\Contracts\PipeInterface;

abstract class AbstractFieldPipe implements PipeInterface
{
    /**
     * @throws InvalidArgumentException
     */
    protected function getFieldValue(mixed $payload, string $field): mixed
    {
        if (is_array($payload)) {
            if (array_key_exists($field, $payload)) {
                return $payload[$field];
            }

            throw new InvalidArgumentException(
                sprintf('Поле "%s" отсутствует в payload (массив).', $field)
            );
        }

        if ($payload instanceof ArrayAccess) {
            if ($payload->offsetExists($field)) {
                return $payload[$field];
            }

            throw new InvalidArgumentException(
                sprintf('Поле "%s" отсутствует в payload (ArrayAccess).', $field)
            );
        }

        if (is_object($payload)) {
            if (property_exists($payload, $field)) {
                return $payload->$field;
            }

            $uc = ucfirst($field);

            $candidates = [
                'get' . $uc,
                'is' . $uc,
                'has' . $uc,
            ];

            foreach ($candidates as $method) {
                if (method_exists($payload, $method)) {
                    return $payload->$method();
                }
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Поле "%s" не найдено в payload-объекте: ни свойство, ни геттер.',
                    $field
                )
            );
        }

        throw new InvalidArgumentException(
            sprintf(
                'Невозможно извлечь поле "%s" из payload типа %s.',
                $field,
                get_debug_type($payload)
            )
        );
    }
}
