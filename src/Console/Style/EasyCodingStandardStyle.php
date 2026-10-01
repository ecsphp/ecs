<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Console\Style;

use ECSPrefix202610\Entropy\Console\Output\OutputPrinter;
use ECSPrefix202610\Entropy\Console\Output\ProgressBar;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;
final class EasyCodingStandardStyle
{
    /**
     * @readonly
     * @var \Entropy\Console\Output\OutputPrinter
     */
    private $outputPrinter;
    /**
     * @readonly
     * @var \Entropy\Console\Output\ProgressBar
     */
    private $progressBar;
    /**
     * @readonly
     * @var bool
     */
    private $isDebug = \false;
    /**
     * To fit in Linux/Windows terminal windows to prevent overflow.
     * @var int
     */
    private const BULGARIAN_CONSTANT = 8;
    /**
     * @var int
     */
    private const DEFAULT_TERMINAL_WIDTH = 120;
    public function __construct(OutputPrinter $outputPrinter, ProgressBar $progressBar, bool $isDebug = \false)
    {
        $this->outputPrinter = $outputPrinter;
        $this->progressBar = $progressBar;
        $this->isDebug = $isDebug;
    }
    public function writeln(string $message): void
    {
        $this->outputPrinter->writeln($this->normalizeTags($message));
    }
    public function newLine(int $count = 1): void
    {
        $this->outputPrinter->newline($count);
    }
    public function success(string $message): void
    {
        $this->outputPrinter->success($message);
    }
    public function warning(string $message): void
    {
        $this->outputPrinter->warning($message);
    }
    public function error(string $message): void
    {
        $this->outputPrinter->error($message);
    }
    public function section(string $message): void
    {
        $this->outputPrinter->section($message);
    }
    /**
     * @param string[] $items
     */
    public function listing(array $items): void
    {
        $this->outputPrinter->listing($items);
    }
    public function ask(string $question, ?string $default = null): ?string
    {
        return $this->outputPrinter->ask($question, $default);
    }
    public function isDebug(): bool
    {
        return $this->isDebug;
    }
    public function progressStart(int $max): void
    {
        $this->progressBar->start($max);
    }
    public function progressAdvance(int $step = 1): void
    {
        $this->progressBar->advance($step);
    }
    /**
     * @param CodingStandardError[] $codingStandardErrors
     */
    public function printErrors(array $codingStandardErrors): void
    {
        foreach ($codingStandardErrors as $codingStandardError) {
            $this->separator();
            $this->writeln(' ' . $codingStandardError->getFileWithLine());
            $this->separator();
            $message = $this->createMessageFromFileError($codingStandardError);
            $this->writeln(' ' . $message);
            $this->separator();
            $this->newLine();
        }
    }
    /**
     * Translate the Symfony-style console tags still emitted by the reporters into
     * the smaller tag vocabulary understood by Entropy's OutputColorizer.
     */
    private function normalizeTags(string $text): string
    {
        // <comment> → yellow, <info> → green
        $text = (string) preg_replace('#<comment>(.*?)</comment>#su', '<fg=yellow>$1</>', $text);
        $text = (string) preg_replace('#<info>(.*?)</info>#su', '<fg=green>$1</>', $text);
        // normalize explicit closing tags (e.g. </fg=green>) to the generic closing tag
        return (string) preg_replace('#</fg=[a-z]+>#', '</>', $text);
    }
    private function separator(): void
    {
        $separator = str_repeat('-', $this->getTerminalWidth());
        $this->writeln(' ' . $separator);
    }
    private function createMessageFromFileError(CodingStandardError $codingStandardError): string
    {
        $message = sprintf('%s%s Reported by: "%s"', $codingStandardError->getMessage(), \PHP_EOL . \PHP_EOL, $codingStandardError->getCheckerClass());
        $message = $this->clearCrLfFromMessage($message);
        return $this->wrapMessageSoItFitsTheColumnWidth($message);
    }
    private function getTerminalWidth(): int
    {
        $columns = getenv('COLUMNS');
        if (is_numeric($columns)) {
            return (int) $columns - self::BULGARIAN_CONSTANT;
        }
        return self::DEFAULT_TERMINAL_WIDTH - self::BULGARIAN_CONSTANT;
    }
    /**
     * This prevents message override in Windows system.
     */
    private function clearCrLfFromMessage(string $message): string
    {
        return str_replace("\r", '', $message);
    }
    private function wrapMessageSoItFitsTheColumnWidth(string $message): string
    {
        return wordwrap($message, $this->getTerminalWidth(), \PHP_EOL);
    }
}
