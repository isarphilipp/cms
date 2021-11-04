<?php

namespace Statamic\Events;


class StacheUpdating extends Event
{
    public const OPERATION_WARM = 1;
    public const OPERATION_REFRESH = 2;
    public const OPERATION_CLEAR = 3;

    public $stache;

    public $operation;

    public function __construct($stache, $operation)
    {
        $this->stache = $stache;
        $this->operation = $operation;
    }
}
