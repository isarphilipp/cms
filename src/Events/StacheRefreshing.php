<?php

namespace Statamic\Events;


class StacheRefreshing extends Event
{

    public $stache;

    public function __construct($stache)
    {
        $this->stache = $stache;
    }
}
