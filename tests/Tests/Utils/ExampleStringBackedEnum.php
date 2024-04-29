<?php

declare(strict_types=1);

namespace EngineWorks\DBAL\Tests\Utils;

enum ExampleStringBackedEnum: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}
