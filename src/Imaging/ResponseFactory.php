<?php

namespace Statamic\Imaging;

use Carbon\Carbon;
use League\Flysystem\FilesystemInterface;
use League\Glide\Responses\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Request object to check "is not modified".
     *
     * @var Request|null
     */
    protected $request;

    /**
     * Create SymfonyResponseFactory instance.
     *
     * @param  Request|null  $request  Request object to check "is not modified".
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Create the response.
     *
     * @param  FilesystemInterface  $cache  The cache file system.
     * @param  string  $path  The cached file path.
     * @return StreamedResponse The response object.
     */
    public function create($cache, $path)
    {
        $stream = $cache->readStream($path);

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $cache->mimeType($path));
        $response->headers->set('Content-Length', $cache->fileSize($path));
        $response->setPublic();

        $expiryAfterSeconds = config('statamic.assets.glide_http_expiry_after_seconds', 31536000);

        $response->setMaxAge($expiryAfterSeconds);
        $response->setExpires(Carbon::now()->addSeconds($expiryAfterSeconds));

        if ($this->request) {
            $response->setLastModified(date_create()->setTimestamp($cache->lastModified($path)));
            $response->isNotModified($this->request);
        }

        $response->setCallback(function () use ($stream) {
            if (ftell($stream) !== 0) {
                rewind($stream);
            }
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }
}
