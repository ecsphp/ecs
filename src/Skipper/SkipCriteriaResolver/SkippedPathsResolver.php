<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver;

use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\FileSystem\PathNormalizer;
use Symplify\EasyCodingStandard\ValueObject\Option;
/**
 * @see \Symplify\EasyCodingStandard\Tests\Skipper\SkipCriteriaResolver\SkippedPathsResolver\SkippedPathsResolverTest
 */
final class SkippedPathsResolver
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
    public function __construct(PathNormalizer $pathNormalizer)
    {
        $this->pathNormalizer = $pathNormalizer;
    }
    /**
     * @return string[]
     */
    public function resolve(): array
    {
        if ($this->skippedPaths !== []) {
            return $this->skippedPaths;
        }
        $skip = SimpleParameterProvider::getArrayParameter(Option::SKIP);
        foreach ($skip as $key => $value) {
            if (!is_int($key)) {
                continue;
            }
            if (strpos((string) $value, '*') !== \false) {
                $this->skippedPaths[] = $this->pathNormalizer->normalizePath($value);
                continue;
            }
            if (file_exists($value)) {
                $this->skippedPaths[] = $this->pathNormalizer->normalizePath($value);
            }
        }
        return $this->skippedPaths;
    }
}
