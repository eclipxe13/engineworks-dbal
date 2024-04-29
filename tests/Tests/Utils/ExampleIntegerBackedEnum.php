<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\Utils;

enum ExampleIntegerBackedEnum: int
{
    case Foo = 111;
    case Bar = 222;
}
