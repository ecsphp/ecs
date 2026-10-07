<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Skipper\Matcher;

use SplFileInfo;
use Symplify\EasyCodingStandard\Skipper\FileSystem\FnMatchPathNormalizer;
final class FileInfoMatcher
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\FileSystem\FnMatchPathNormalizer
     */
    private $fnMatchPathNormalizer;
    public function __construct(FnMatchPathNormalizer $fnMatchPathNormalizer)
    {
        $this->fnMatchPathNormalizer = $fnMatchPathNormalizer;
    }
    /**
     * @param string[] $filePatterns
     * @param \SplFileInfo|string $fileInfo
     */
    public function doesFileInfoMatchPatterns($fileInfo, array $filePatterns): bool
    {
        $found = \false;
        foreach ($filePatterns as $filePattern) {
            if ($this->doesFileInfoMatchPattern($fileInfo, $filePattern)) {
                $found = \true;
                break;
            }
        }
        return $found;
    }
    /**
     * Supports both relative and absolute $file path. They differ for PHP-CS-Fixer and PHP_CodeSniffer.
     * @param \SplFileInfo|string $file
     */
    private function doesFileInfoMatchPattern($file, string $ignoredPath): bool
    {
        $filePath = $file instanceof SplFileInfo ? $file->getRealPath() : $file;
        // in ecs.php, the path can be absolute
        if ($filePath === $ignoredPath) {
            return \true;
        }
        $ignoredPath = $this->fnMatchPathNormalizer->normalizeForFnmatch($ignoredPath);
        if ($ignoredPath === '') {
            return \false;
        }
        if (strncmp($filePath, $ignoredPath, strlen($ignoredPath)) === 0) {
            return \true;
        }
        if (substr_compare($filePath, $ignoredPath, -strlen($ignoredPath)) === 0) {
            return \true;
        }
        if ($this->matchesFnmatch($ignoredPath, $filePath)) {
            return \true;
        }
        return $this->matchesRealpath($ignoredPath, $filePath);
    }
    private function matchesFnmatch(string $matchingPath, string $filePath): bool
    {
        $normalizedMatchingPath = $this->normalizePath($matchingPath);
        $normalizedFilePath = $this->normalizePath($filePath);
        if (fnmatch($normalizedMatchingPath, $normalizedFilePath)) {
            return \true;
        }
        // in case of relative compare
        return fnmatch('*/' . $normalizedMatchingPath, $normalizedFilePath);
    }
    private function matchesRealpath(string $matchingPath, string $filePath): bool
    {
        /** @var non-empty-string|false $realPathMatchingPath */
        $realPathMatchingPath = realpath($matchingPath);
        if ($realPathMatchingPath === \false) {
            return \false;
        }
        $realpathFilePath = realpath($filePath);
        if ($realpathFilePath === \false) {
            return \false;
        }
        $normalizedMatchingPath = $this->normalizePath($realPathMatchingPath);
        $normalizedFilePath = $this->normalizePath($realpathFilePath);
        // skip define direct path
        if (is_file($normalizedMatchingPath)) {
            return $normalizedMatchingPath === $normalizedFilePath;
        }
        // ensure add / suffix to ensure no same prefix directory
        if (is_dir($normalizedMatchingPath)) {
            $normalizedMatchingPath = rtrim($normalizedMatchingPath, '/') . '/';
        }
        return strncmp($normalizedFilePath, $normalizedMatchingPath, strlen($normalizedMatchingPath)) === 0;
    }
    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
