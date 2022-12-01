<?php

namespace Statamic\Fieldtypes\Bard;

use ProseMirrorToHtml\Marks\Link;
use Statamic\Entries\Entry;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class LinkMark extends Link
{
    public function tag()
    {
        $tag = parent::tag();

        $tag[0]['attrs']['href'] = $this->convertHref($tag[0]['attrs']['href']);

        return $tag;
    }

    protected function convertHref($href)
    {
        if (! Str::startsWith($href, 'statamic://')) {
            return $href;
        }

        $ref = Str::after($href, 'statamic://');

        if (! $item = Data::find($ref)) {
            return '';
        }

        if($item instanceof Entry){
            return $item->in(Site::current()->handle())?->url() ?? $item->url();
        }

        return $item->url();
    }
}
