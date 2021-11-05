<?php

namespace Statamic\Events;


class StacheCleared extends Event
{

    public $stache;

    public function __construct($stache)
    {
        $this->stache = $stache;
    }
}
