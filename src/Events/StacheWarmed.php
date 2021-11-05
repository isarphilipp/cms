<?php

namespace Statamic\Events;


class StacheWarmed extends Event
{

    public $stache;

    public function __construct($stache)
    {
        $this->stache = $stache;
    }
}
