<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Skipper\Skipper;

use Symplify\EasyCodingStandard\Skipper\Matcher\FileInfoMatcher;
use Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedClassAndCodesResolver;
use Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedClassResolver;
use Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedMessagesResolver;
use Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedPathsResolver;
/**
 * @api
 * @see \Symplify\EasyCodingStandard\Tests\Skipper\Skipper\Skipper\SkipperTest
 */
final class Skipper
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedClassAndCodesResolver
     */
    private $skippedClassAndCodesResolver;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedClassResolver
     */
    private $skippedClassResolver;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedMessagesResolver
     */
    private $skippedMessagesResolver;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedPathsResolver
     */
    private $skippedPathsResolver;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\Skipper\SkipSkipper
     */
    private $skipSkipper;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\Matcher\FileInfoMatcher
     */
    private $fileInfoMatcher;
    /**
     * @var string
     */
    private const FILE_ELEMENT = 'file_elements';
    public function __construct(SkippedClassAndCodesResolver $skippedClassAndCodesResolver, SkippedClassResolver $skippedClassResolver, SkippedMessagesResolver $skippedMessagesResolver, SkippedPathsResolver $skippedPathsResolver, \Symplify\EasyCodingStandard\Skipper\Skipper\SkipSkipper $skipSkipper, FileInfoMatcher $fileInfoMatcher)
    {
        $this->skippedClassAndCodesResolver = $skippedClassAndCodesResolver;
        $this->skippedClassResolver = $skippedClassResolver;
        $this->skippedMessagesResolver = $skippedMessagesResolver;
        $this->skippedPathsResolver = $skippedPathsResolver;
        $this->skipSkipper = $skipSkipper;
        $this->fileInfoMatcher = $fileInfoMatcher;
    }
    /**
     * @param string|object $element
     */
    public function shouldSkipElement($element): bool
    {
        return $this->shouldSkipElementAndFilePath($element, __FILE__);
    }
    public function shouldSkipFilePath(string $filePath): bool
    {
        return $this->shouldSkipElementAndFilePath(self::FILE_ELEMENT, $filePath);
    }
    /**
     * @param string|object $element
     */
    public function shouldSkipElementAndFilePath($element, string $filePath): bool
    {
        if ($this->shouldSkipClassAndCode($element, $filePath)) {
            return \true;
        }
        if ($this->shouldSkipClass($element, $filePath)) {
            return \true;
        }
        if ($this->shouldSkipMessage($element, $filePath)) {
            return \true;
        }
        return $this->shouldSkipPath($filePath);
    }
    /**
     * @param string|object $element
     */
    private function shouldSkipClassAndCode($element, string $filePath): bool
    {
        if (!is_string($element)) {
            return \false;
        }
        // e.g. App\Category\ArraySniff.SomeCode
        if (substr_count($element, '.') !== 1) {
            return \false;
        }
        $skippedClassAndCodes = $this->skippedClassAndCodesResolver->resolve();
        if (!array_key_exists($element, $skippedClassAndCodes)) {
            return \false;
        }
        $skippedPaths = $skippedClassAndCodes[$element];
        if ($skippedPaths === null) {
            return \true;
        }
        return $this->fileInfoMatcher->doesFileInfoMatchPatterns($filePath, $skippedPaths);
    }
    /**
     * @param string|object $element
     */
    private function shouldSkipClass($element, string $filePath): bool
    {
        if (is_string($element) && !class_exists($element) && !interface_exists($element)) {
            return \false;
        }
        $skippedClasses = $this->skippedClassResolver->resolve();
        return $this->skipSkipper->doesMatchSkip($element, $filePath, $skippedClasses);
    }
    /**
     * @param string|object $element
     */
    private function shouldSkipMessage($element, string $filePath): bool
    {
        if (!is_string($element)) {
            return \false;
        }
        if (substr_count($element, ' ') === 0) {
            return \false;
        }
        $skippedMessages = $this->skippedMessagesResolver->resolve();
        if (!array_key_exists($element, $skippedMessages)) {
            return \false;
        }
        $skippedPaths = $skippedMessages[$element];
        if ($skippedPaths === null) {
            return \true;
        }
        return $this->fileInfoMatcher->doesFileInfoMatchPatterns($filePath, $skippedPaths);
    }
    private function shouldSkipPath(string $filePath): bool
    {
        $skippedPaths = $this->skippedPathsResolver->resolve();
        return $this->fileInfoMatcher->doesFileInfoMatchPatterns($filePath, $skippedPaths);
    }
}
