<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Caching;

use ECSPrefix202610\Symfony\Component\Filesystem\Filesystem;
use Symplify\EasyCodingStandard\Caching\ValueObject\Storage\FileCacheStorage;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\ValueObject\Option;
/**
 * @api
 */
final class CacheFactory
{
    /**
     * @readonly
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $fileSystem;
    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }
    /**
     * @api
     */
    public function create(): \Symplify\EasyCodingStandard\Caching\Cache
    {
        $cacheDirectory = SimpleParameterProvider::getStringParameter(Option::CACHE_DIRECTORY);
        // ensure cache directory exists
        if (!$this->fileSystem->exists($cacheDirectory)) {
            $this->fileSystem->mkdir($cacheDirectory);
        }
        $fileCacheStorage = new FileCacheStorage($cacheDirectory, $this->fileSystem);
        return new \Symplify\EasyCodingStandard\Caching\Cache($fileCacheStorage);
    }
}
