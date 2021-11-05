<?php

namespace Statamic\Events;


class StacheClearing extends Event
{

    public $stache;

    public function __construct($stache)
    {
        $this->stache = $stache;
    }
}
