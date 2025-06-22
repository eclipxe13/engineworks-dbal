<?php

declare(strict_types=1);

namespace EngineWorks\DBAL;

interface CommonTypes
{
    /** @var string */
    public const TDATE = 'DATE';

    /** @var string */
    public const TTIME = 'TIME';

    /** @var string */
    public const TDATETIME = 'DATETIME';

    /** @var string */
    public const TTEXT = 'TEXT';

    /** @var string */
    public const TNUMBER = 'NUMBER';

    /** @var string */
    public const TINT = 'INT';

    /** @var string */
    public const TBOOL = 'BOOL';
}
