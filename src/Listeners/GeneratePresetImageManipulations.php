<?php

namespace Statamic\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetUploaded;
use Statamic\Facades\Config;
use Statamic\Facades\Folder;
use Statamic\Facades\Path;
use Statamic\Imaging\GlideManager;
use Statamic\Imaging\PresetGenerator;

class GeneratePresetImageManipulations implements ShouldQueue
{
    /**
     * @var PresetGenerator
     */
    private $generator;

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
        //Replacement code for GlideServer::cachePath(). Glide server is now turned into a GlideManager, and cachePath is turned private
        // Code below does exactly the same as previous. It checks statamic.assets.image_manipulation.cache and returns path
        $gm = new GlideManager();
        $cachePath = $gm->shouldServeDirectly()
            ?Config::get('statamic.assets.image_manipulation.cache_path')
            : storage_path('statamic/glide');

        $folder = Path::tidy($cachePath.'/containers/'.$asset->containerId().'/'.$asset->path());

        if (\File::exists($folder)) {
            \File::deleteDirectory($folder);
        }

        $this->generator->generate($asset);
    }
}
