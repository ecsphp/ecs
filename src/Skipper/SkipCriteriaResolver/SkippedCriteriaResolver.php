<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver;

use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\FileSystem\PathNormalizer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use ECSPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Symplify\EasyCodingStandard\Tests\Skipper\SkipCriteriaResolver\SkippedPathsResolver\SkippedPathsResolverTest
 */
final class SkippedCriteriaResolver
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\FileSystem\PathNormalizer
     */
    private $pathNormalizer;
    /**
     * @var string[]
     */
    private $skippedPaths = [];
    /**
     * @var array<string, string[]|null>
     */
    private $skippedClasses = [];
    /**
     * @var array<string, string[]|null>
     */
    private $skippedClassAndCodes = [];
    /**
     * @var array<string, string[]|null>
     */
    private $skippedMessages = [];
    /**
     * @var bool
     */
    private $isResolved = \false;
    public function __construct(PathNormalizer $pathNormalizer)
    {
        $this->pathNormalizer = $pathNormalizer;
    }
    /**
     * @return string[]
     */
    public function resolvePaths(): array
    {
        $this->resolve();
        return $this->skippedPaths;
    }
    /**
     * @return array<string, string[]|null>
     */
    public function resolveClasses(): array
    {
        $this->resolve();
        return $this->skippedClasses;
    }
    /**
     * @return array<string, string[]|null>
     */
    public function resolveClassAndCodes(): array
    {
        $this->resolve();
        return $this->skippedClassAndCodes;
    }
    /**
     * @return array<string, string[]|null>
     */
    public function resolveMessages(): array
    {
        $this->resolve();
        return $this->skippedMessages;
    }
    private function resolve(): void
    {
        if ($this->isResolved) {
            return;
        }
        $skip = SimpleParameterProvider::getArrayParameter(Option::SKIP);
        foreach ($skip as $key => $value) {
            // e.g. [SomeClass::class] → shift values to [SomeClass::class => null]
            if (is_int($key)) {
                if (strpos((string) $value, '*') !== \false) {
                    $this->skippedPaths[] = $this->pathNormalizer->normalizePath($value);
                } elseif (file_exists($value)) {
                    $this->skippedPaths[] = $this->pathNormalizer->normalizePath($value);
                }
                $key = $value;
                $value = null;
            }
            if (is_string($key) && (class_exists($key) || interface_exists($key))) {
                $this->skippedClasses[$key] = $value;
            }
            if (substr_count((string) $key, '.') === 1) {
                Assert::string($key);
                $this->skippedClassAndCodes[$key] = $value;
            }
            if (is_string($key) && substr_count($key, ' ') > 0) {
                $this->skippedMessages[$key] = $value;
            }
        }
        $this->isResolved = \true;
    }
}
