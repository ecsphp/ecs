<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Caching\ValueObject\Storage;

use ECSPrefix202610\Nette\Utils\FileSystem as UtilsFileSystem;
use ECSPrefix202610\Symfony\Component\Filesystem\Filesystem;
use Symplify\EasyCodingStandard\Caching\ValueObject\CacheFilePaths;
use Symplify\EasyCodingStandard\Caching\ValueObject\CacheItem;
use Symplify\EasyCodingStandard\Exception\ShouldNotHappenException;
/**
 * Inspired by
 * https://github.com/phpstan/phpstan-src/commit/4df7342f3a0aaef4bcd85456dd20ca88d38dd90d#diff-6dc14f6222bf150e6840ca44a7126653052a1cedc6a149b4e5c1e1a2c80eacdc
 */
final class FileCacheStorage
{
    /**
     * @readonly
     * @var string
     */
    private $directory;
    /**
     * @readonly
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $fileSystem;
    public function __construct(string $directory, Filesystem $fileSystem)
    {
        $this->directory = $directory;
        $this->fileSystem = $fileSystem;
    }
    public function load(string $key, string $variableKey): ?string
    {
        $cacheFilePaths = $this->getCacheFilePaths($key);
        $filePath = $cacheFilePaths->getFilePath();
        if (!is_file($filePath)) {
            return null;
        }
        $cacheItem = require $filePath;
        if (!$cacheItem instanceof CacheItem) {
            return null;
        }
        if (!$cacheItem->isVariableKeyValid($variableKey)) {
            return null;
        }
        return $cacheItem->getData();
    }
    public function save(string $key, string $variableKey, string $data): void
    {
        $cacheFilePaths = $this->getCacheFilePaths($key);
        $this->fileSystem->mkdir($cacheFilePaths->getFirstDirectory());
        $this->fileSystem->mkdir($cacheFilePaths->getSecondDirectory());
        $errorBefore = error_get_last();
        $exported = var_export(new CacheItem($variableKey, $data), \true);
        $errorAfter = error_get_last();
        if ($errorAfter !== null && $errorBefore !== $errorAfter) {
            $errorMessage = sprintf('Error occurred while saving item "%s" ("%s") to cache: "%s"', $key, $variableKey, $errorAfter['message']);
            throw new ShouldNotHappenException($errorMessage);
        }
        $variableFileContent = sprintf("<?php declare(strict_types = 1);\n\nreturn %s;", $exported);
        UtilsFileSystem::write($cacheFilePaths->getFilePath(), $variableFileContent, null);
    }
    public function clean(string $cacheKey): void
    {
        $cacheFilePaths = $this->getCacheFilePaths($cacheKey);
        UtilsFileSystem::delete($cacheFilePaths->getFilePath());
    }
    public function clear(): void
    {
        UtilsFileSystem::delete($this->directory);
    }
    private function getCacheFilePaths(string $key): CacheFilePaths
    {
        $keyHash = sha1($key);
        $firstDirectory = sprintf('%s/%s', $this->directory, substr($keyHash, 0, 2));
        $secondDirectory = sprintf('%s/%s', $firstDirectory, (string) substr($keyHash, 2, 2));
        $filePath = sprintf('%s/%s.php', $secondDirectory, $keyHash);
        return new CacheFilePaths($firstDirectory, $secondDirectory, $filePath);
    }
}
