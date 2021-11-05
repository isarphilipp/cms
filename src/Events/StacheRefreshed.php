<?php

namespace Statamic\Events;


class StacheRefreshed extends Event
{

    public $stache;

    public function __construct($stache)
    {
        $this->stache = $stache;
    }
}
