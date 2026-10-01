<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard;

use ECSPrefix202610\Nette\Utils\Strings;
use Symplify\EasyCodingStandard\Exception\Configuration\InitializationException;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
/**
 * @inspiration https://github.com/phpstan/phpstan-src/commit/ccc046ca473dcdb5ce9225cc05d7808f2e327f40
 */
final class MemoryLimitter
{
    /**
     * @see https://regex101.com/r/pmiGUM/1
     * @var string
     */
    private const VALID_MEMORY_LIMIT_REGEX = '#^-?\d+[kMG]?$#i';
    public function adjust(Configuration $configuration): void
    {
        $memoryLimit = $configuration->getMemoryLimit();
        if ($memoryLimit === null) {
            return;
        }
        $this->validateMemoryLimitFormat($memoryLimit);
        $memorySetResult = ini_set('memory_limit', $memoryLimit);
        if ($memorySetResult === \false) {
            $errorMessage = sprintf('Memory limit "%s" cannot be set.', $memoryLimit);
            throw new InitializationException($errorMessage);
        }
    }
    private function validateMemoryLimitFormat(string $memoryLimit): void
    {
        $memoryLimitFormatMatch = Strings::match($memoryLimit, self::VALID_MEMORY_LIMIT_REGEX);
        if ($memoryLimitFormatMatch !== null) {
            return;
        }
        $errorMessage = sprintf('Invalid memory limit format "%s".', $memoryLimit);
        throw new InitializationException($errorMessage);
    }
}
