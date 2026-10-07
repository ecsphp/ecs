<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Contract\Application;

use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\FileDiff;
interface FileProcessorInterface
{
    /**
     * @return array{file_diffs?: FileDiff[], coding_standard_errors?: CodingStandardError[]}
     */
    public function processFile(string $filePath, Configuration $configuration): array;
    /**
     * @return object[]
     */
    public function getCheckers(): array;
}
