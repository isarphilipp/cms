<?php

namespace Statamic\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetUploaded;
use Statamic\Facades\Folder;
use Statamic\Facades\Path;
use Facades\Statamic\Imaging\GlideServer;
use Statamic\Imaging\PresetGenerator;

class GeneratePresetImageManipulations implements ShouldQueue
{
    /**
     * @var PresetGenerator
     */
    private $generator;

    /**
     * @param  PresetGenerator  $generator
     */
    public function __construct(PresetGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        if (! config('statamic.assets.image_manipulation.generate_presets_on_upload', true)) {
            return;
        }

        $events->listen(AssetReuploaded::class, self::class.'@handle');
        $events->listen(AssetUploaded::class, self::class.'@handle');
    }

    /**
     * Handle the events.
     *
     * @param  AssetUploaded  $event
     */
    public function handle($event)
    {
        $asset = $event->asset;

        if (! $asset->isImage()) {
            return;
        }

        $folder = Path::tidy(GlideServer::cachePath().'/containers/'.$asset->containerId().'/'.$asset->path());

        if (\File::exists($folder)) {
            \File::deleteDirectory($folder);
        }

        $this->generator->generate($asset);
    }
}
