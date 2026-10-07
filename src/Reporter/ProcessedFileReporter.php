<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Reporter;

use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\Console\Output\OutputFormatterCollector;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\ErrorAndDiffResult;
use Symplify\EasyCodingStandard\ValueObject\Error\FileDiff;
use Symplify\EasyCodingStandard\ValueObject\Error\SystemError;
final class ProcessedFileReporter
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Output\OutputFormatterCollector
     */
    private $outputFormatterCollector;
    public function __construct(OutputFormatterCollector $outputFormatterCollector)
    {
        $this->outputFormatterCollector = $outputFormatterCollector;
    }
    /**
     * @param array{system_errors?: SystemError[]|string[], file_diffs?: FileDiff[], coding_standard_errors?: CodingStandardError[], system_errors_count?: int, files_count?: int} $errorsAndDiffs
     * @return ExitCode::*
     */
    public function report(array $errorsAndDiffs, Configuration $configuration): int
    {
        $outputFormat = $configuration->getOutputFormat();
        $outputFormatter = $this->outputFormatterCollector->getByName($outputFormat);
        /** @var SystemError[]|string[] $systemErrors */
        $systemErrors = $errorsAndDiffs[Bridge::SYSTEM_ERRORS] ?? [];
        /** @var FileDiff[] $fileDiffs */
        $fileDiffs = $errorsAndDiffs[Bridge::FILE_DIFFS] ?? [];
        /** @var CodingStandardError[] $codingStandardErrors */
        $codingStandardErrors = $errorsAndDiffs[Bridge::CODING_STANDARD_ERRORS] ?? [];
        $errorAndDiffResult = new ErrorAndDiffResult($codingStandardErrors, $fileDiffs, $systemErrors);
        return $outputFormatter->report($errorAndDiffResult, $configuration);
    }
}
