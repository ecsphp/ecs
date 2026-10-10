<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Console\Output;

use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\Contract\Console\Output\OutputFormatterInterface;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\ErrorAndDiffResult;
use Symplify\EasyCodingStandard\ValueObject\Error\FileDiff;
use Symplify\EasyCodingStandard\ValueObject\Error\SystemError;
final class ConsoleOutputFormatter implements OutputFormatterInterface
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Output\ExitCodeResolver
     */
    private $exitCodeResolver;
    /**
     * @var string
     */
    public const NAME = 'console';
    public function __construct(EasyCodingStandardStyle $easyCodingStandardStyle, \Symplify\EasyCodingStandard\Console\Output\ExitCodeResolver $exitCodeResolver)
    {
        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
        $this->exitCodeResolver = $exitCodeResolver;
    }
    /**
     * @return ExitCode::*
     */
    public function report(ErrorAndDiffResult $errorAndDiffResult, Configuration $configuration): int
    {
        if ($configuration->shouldShowDiffs()) {
            $this->reportFileDiffs($errorAndDiffResult->getFileDiffs());
        }
        if ($errorAndDiffResult->getErrorCount() === 0 && $errorAndDiffResult->getFileDiffsCount() === 0) {
            $this->easyCodingStandardStyle->success('No errors found. Great job - your code is shiny in style!');
            return $this->exitCodeResolver->resolve($errorAndDiffResult, $configuration);
        }
        $this->easyCodingStandardStyle->newLine(2);
        if ($configuration->isFixer()) {
            $this->printAfterFixerStatus($errorAndDiffResult, $configuration);
        } else {
            $this->printNoFixerStatus($errorAndDiffResult, $configuration);
        }
        return $this->exitCodeResolver->resolve($errorAndDiffResult, $configuration);
    }
    public static function getName(): string
    {
        return self::NAME;
    }
    public static function hasSupportForProgressBars(): bool
    {
        return \true;
    }
    /**
     * @param FileDiff[] $fileDiffs
     */
    private function reportFileDiffs(array $fileDiffs, bool $absoluteFilePath = \false): void
    {
        if ($fileDiffs === []) {
            return;
        }
        $this->easyCodingStandardStyle->newLine(1);
        $i = 1;
        foreach ($fileDiffs as $fileDiff) {
            $this->easyCodingStandardStyle->newLine(2);
            $filePath = $absoluteFilePath ? $fileDiff->getAbsoluteFilePath() : $fileDiff->getRelativeFilePath();
            $boldNumberedMessage = sprintf('<options=bold>%d) %s</>', $i, $filePath);
            $this->easyCodingStandardStyle->writeln($boldNumberedMessage);
            ++$i;
            $this->easyCodingStandardStyle->newLine();
            $this->easyCodingStandardStyle->writeln($fileDiff->getDiffConsoleFormatted());
            $this->easyCodingStandardStyle->newLine();
            $this->easyCodingStandardStyle->writeln('<options=underscore>Applied checkers:</>');
            $this->easyCodingStandardStyle->newLine();
            $this->easyCodingStandardStyle->listing($fileDiff->getAppliedCheckers());
        }
    }
    private function printAfterFixerStatus(ErrorAndDiffResult $errorAndDiffResult, Configuration $configuration): void
    {
        if ($configuration->shouldShowErrorTable()) {
            $this->easyCodingStandardStyle->printErrors($errorAndDiffResult->getErrors());
        }
        if ($errorAndDiffResult->getErrorCount() === 0) {
            $successMessage = sprintf('%d error%s successfully fixed and no other errors found!', $errorAndDiffResult->getFileDiffsCount(), $errorAndDiffResult->getFileDiffsCount() === 1 ? '' : 's');
            $this->easyCodingStandardStyle->success($successMessage);
            return;
        }
        $this->printSystemErrors($errorAndDiffResult);
        $this->printErrorMessageFromErrorCounts($errorAndDiffResult->getCodingStandardErrorCount(), $errorAndDiffResult->getFileDiffsCount(), $configuration);
    }
    private function printNoFixerStatus(ErrorAndDiffResult $errorAndDiffResult, Configuration $configuration): void
    {
        if ($configuration->shouldShowErrorTable()) {
            $errors = $errorAndDiffResult->getErrors();
            if ($errors !== []) {
                $this->easyCodingStandardStyle->newLine();
                $this->easyCodingStandardStyle->printErrors($errors);
            }
        }
        $this->printSystemErrors($errorAndDiffResult);
        $this->printErrorMessageFromErrorCounts($errorAndDiffResult->getCodingStandardErrorCount(), $errorAndDiffResult->getFileDiffsCount(), $configuration);
    }
    private function printSystemErrors(ErrorAndDiffResult $errorAndDiffResult): void
    {
        $systemErrors = $errorAndDiffResult->getSystemErrors();
        foreach ($systemErrors as $systemError) {
            $this->easyCodingStandardStyle->newLine();
            if ($systemError instanceof SystemError) {
                $this->easyCodingStandardStyle->error($systemError->getMessage() . ' in ' . $systemError->getFileWithLine());
            } else {
                $this->easyCodingStandardStyle->error($systemError);
            }
        }
    }
    private function printErrorMessageFromErrorCounts(int $codingStandardErrorCount, int $fileDiffsCount, Configuration $configuration): void
    {
        if ($codingStandardErrorCount !== 0) {
            $errorMessage = sprintf('Found %d error%s that need%s to be fixed manually.', $codingStandardErrorCount, $codingStandardErrorCount === 1 ? '' : 's', $codingStandardErrorCount === 1 ? 's' : '');
            $this->easyCodingStandardStyle->error($errorMessage);
        }
        if ($fileDiffsCount === 0) {
            return;
        }
        if ($configuration->isFixer()) {
            return;
        }
        $fixableMessage = sprintf('%s%d %s fixable! Just add "--fix" to console command and rerun to apply.', $codingStandardErrorCount !== 0 ? 'Good news is that ' : '', $fileDiffsCount, $fileDiffsCount === 1 ? 'error is' : 'errors are');
        $this->easyCodingStandardStyle->warning($fixableMessage);
    }
}
