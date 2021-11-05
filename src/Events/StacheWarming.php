<?php

namespace Statamic\Events;


class StacheWarming extends Event
{

    public $stache;

    public function __construct($stache)
    {
        $this->stache = $stache;
    }
}
