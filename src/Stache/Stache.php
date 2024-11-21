<?php

namespace Statamic\Stache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Statamic\Events\StacheCleared;
use Statamic\Events\StacheClearing;
use Statamic\Events\StacheRefreshed;
use Statamic\Events\StacheRefreshing;
use Statamic\Events\StacheWarmed;
use Statamic\Events\StacheWarming;
use Statamic\Extensions\FileStore;
use Statamic\Facades\File;
use Statamic\Stache\Stores\Store;
use Statamic\Support\Str;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Wilderborn\Partyline\Facade as Partyline;

class Stache
{
    protected $sites;
    protected $stores;
    protected $startTime;
    protected $updateIndexes = true;
    protected $lockFactory;
    protected $locks = [];
    protected $duplicates;

    public function __construct()
    {
        $this->stores = collect();
    }

    public function sites($sites = null)
    {
        if (! $sites) {
            return $this->sites;
        }

        $this->sites = collect($sites);

        return $this;
    }

    public function defaultSite()
    {
        return $this->sites->first();
    }

    public function registerStore(Store $store)
    {
        $this->stores->put($store->key(), $store);

        return $this;
    }

    public function registerStores($stores)
    {
        foreach ($stores as $store) {
            $this->registerStore($store);
        }

        return $this;
    }

    public function stores()
    {
        return $this->stores;
    }

    public function store($key)
    {
        if (Str::contains($key, '::')) {
            [$parent, $child] = explode('::', $key);

            return $this->stores()->get($parent)->store($child);
        }

        return $this->stores()->get($key);
    }

    public function generateId()
    {
        return (string) Str::uuid();
    }

    public function clear()
    {
        Partyline::comment('Clearing Stache...');

        StacheClearing::dispatch($this);

        $this->stores()->reverse()->each(function ($store) {
            $start = microtime(true);
            $store->clear();
            $time = microtime(true) - $start;
            Partyline::comment("Cleared store {$store->key()} in " . round($time * 1000, 2) . "ms");
        });
        $start = microtime(true);

        $this->duplicates()->clear();
        
        $time = microtime(true) - $start;

        Partyline::comment("Duplicates measured: " . round($time * 1000, 2) . "ms");

        Cache::forget('stache::timing');

        StacheCleared::dispatch($this);

        return $this;
    }

    public function refresh()
    {
        Partyline::comment('Refreshing Stache...');

        $lock = tap($this->lock('stache-updating'))->acquire(true);

        StacheRefreshing::dispatch($this);

        $this->startTimer();

        $this->stores()->reverse()->each->clear();

        $this->duplicates()->clear();

        $this->stores()->each->warm();

        $this->stopTimer();

        StacheRefreshed::dispatch($this);

        $lock->release();
    }

    public function warm()
    {
        Partyline::comment('Warming Stache...');

        $lock = tap($this->lock('stache-updating'))->acquire(true);

        StacheWarming::dispatch($this);

        $this->startTimer();

        $this->stores()->each->warm();

        $this->stopTimer();

        StacheWarmed::dispatch($this);

        $lock->release();
    }

    public function instance()
    {
        return $this;
    }

    public function fileCount()
    {
        return $this->stores()->reduce(function ($carry, $store) {
            return $store->paths()->count() + $carry;
        }, 0);
    }

    public function fileSize()
    {
        if (! ($cache = app('cache')->store()->getStore()) instanceof FileStore) {
            return null;
        }

        $files = File::getFiles($cache->getDirectory().'/stache', true);

        return collect($files)->reduce(function ($size, $path) {
            return $size + File::size($path);
        }, 0);
    }

    public function startTimer()
    {
        $this->startTime = microtime(true);

        return $this;
    }

    public function stopTimer()
    {
        if (! $this->startTime) {
            return $this;
        }

        Cache::forever('stache::timing', [
            'time' => floor((microtime(true) - $this->startTime) * 1000),
            'date' => Carbon::now()->timestamp,
        ]);

        return $this;
    }

    public function buildTime()
    {
        return Cache::get('stache::timing')['time'] ?? null;
    }

    public function buildDate()
    {
        if (! $cache = Cache::get('stache::timing')) {
            return null;
        }

        return Carbon::createFromTimestamp($cache['date']);
    }

    public function disableUpdatingIndexes()
    {
        $this->updateIndexes = false;

        return $this;
    }

    public function shouldUpdateIndexes()
    {
        return $this->updateIndexes;
    }

    public function setLockFactory(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;

        return $this;
    }

    public function lock($name): LockInterface
    {
        if (isset($this->locks[$name])) {
            return $this->locks[$name];
        }

        return $this->locks[$name] = $this->lockFactory->createLock($name);
    }

    public function duplicates()
    {
        if ($this->duplicates) {
            return $this->duplicates;
        }

        return $this->duplicates = (new Duplicates($this))->load();
    }
}
