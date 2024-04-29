<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Internal;

use BackedEnum;
use InvalidArgumentException;
use Stringable;
use UnitEnum;

trait ConvertObjectToStringMethod
{
    /**
     * @param object $variable
     * @return string
     */
    private static function convertObjectToString(object $variable): string
    {
        if (PHP_VERSION_ID > 80100) { // PHP 8.1
            if ($variable instanceof UnitEnum) { // BackedEnum implements UnitEnum
                return ($variable instanceof BackedEnum) ? strval($variable->value) : $variable->name;
            }
        }
        if (class_exists(Stringable::class) && $variable instanceof Stringable) {
            return strval($variable);
        }
        if (is_callable([$variable, '__toString'])) {
            return $variable->__toString();
        }

        throw new InvalidArgumentException(
            sprintf('Value of type %s that cannot be parsed as string', get_class($variable))
        );
    }
}
