<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Console\Command;

use ECSPrefix202610\Entropy\Console\Contract\CommandInterface;
use ECSPrefix202610\Nette\Utils\Json;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;
use Symplify\EasyCodingStandard\Console\Reporter\CheckerListReporter;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedCriteriaResolver;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
final class ListCheckersCommand implements CommandInterface
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor
     */
    private $sniffFileProcessor;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor
     */
    private $fixerFileProcessor;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Reporter\CheckerListReporter
     */
    private $checkerListReporter;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedCriteriaResolver
     */
    private $skippedCriteriaResolver;
    public function __construct(SniffFileProcessor $sniffFileProcessor, FixerFileProcessor $fixerFileProcessor, CheckerListReporter $checkerListReporter, SkippedCriteriaResolver $skippedCriteriaResolver)
    {
        $this->sniffFileProcessor = $sniffFileProcessor;
        $this->fixerFileProcessor = $fixerFileProcessor;
        $this->checkerListReporter = $checkerListReporter;
        $this->skippedCriteriaResolver = $skippedCriteriaResolver;
    }
    public function getName(): string
    {
        return 'list-checkers';
    }
    public function getDescription(): string
    {
        return 'Shows loaded checkers';
    }
    /**
     * @param string $outputFormat Select output format
     * @param string $config       Path to config file
     *
     * @option $outputFormat
     * @option $config
     *
     * @api invoked via reflection by the Entropy console application
     *
     * @return ExitCode::*
     */
    public function run(string $outputFormat = ConsoleOutputFormatter::NAME, string $config = ''): int
    {
        // include skipped rules to avoid adding those too
        $skippedCheckers = $this->getSkippedCheckers();
        if ($outputFormat === 'json') {
            $data = ['sniffs' => $this->getSniffClasses(), 'fixers' => $this->getFixerClasses(), 'skipped-checkers' => $skippedCheckers];
            echo Json::encode($data, Json::PRETTY) . \PHP_EOL;
            return ExitCode::SUCCESS;
        }
        $this->checkerListReporter->report($this->getSniffClasses(), 'from PHP_CodeSniffer');
        $this->checkerListReporter->report($this->getFixerClasses(), 'from PHP-CS-Fixer');
        $this->checkerListReporter->report($skippedCheckers, 'are skipped');
        return ExitCode::SUCCESS;
    }
    /**
     * @return array<class-string<FixerInterface>>
     */
    private function getFixerClasses(): array
    {
        $fixers = $this->fixerFileProcessor->getCheckers();
        return $this->getObjectClasses($fixers);
    }
    /**
     * @return array<class-string<Sniff>>
     */
    private function getSniffClasses(): array
    {
        $sniffs = $this->sniffFileProcessor->getCheckers();
        return $this->getObjectClasses($sniffs);
    }
    /**
     * @template TObject as Sniff|FixerInterface
     * @param TObject[] $checkers
     * @return array<class-string<TObject>>
     */
    private function getObjectClasses(array $checkers): array
    {
        $objectClasses = array_map(static function (object $fixer): string {
            return get_class($fixer);
        }, $checkers);
        sort($objectClasses);
        return $objectClasses;
    }
    /**
     * @return string[]
     */
    private function getSkippedCheckers(): array
    {
        $skippedCheckers = [];
        foreach ($this->skippedCriteriaResolver->resolveClasses() as $checkerClass => $fileList) {
            // ignore specific skips
            if ($fileList !== null) {
                continue;
            }
            $skippedCheckers[] = $checkerClass;
        }
        return $skippedCheckers;
    }
}
