<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
/**
 * Drives --blink --commit: applies the resolved rules one at a time, each in its
 * own ecs-go fix run, and turns every rule that changed files into a single git
 * commit. One rule = one commit.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Blink\BlinkCommitRunnerTest
 */
final class BlinkCommitRunner
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Blink\BlinkRunner
     */
    private $blinkRunner;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;
    public function __construct(\Symplify\EasyCodingStandard\Blink\BlinkRunner $blinkRunner, EasyCodingStandardStyle $easyCodingStandardStyle)
    {
        $this->blinkRunner = $blinkRunner;
        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
    }
    /**
     * @param array{paths: string[], rules: array<array{class: string, config: object|array<string, mixed>}>, skips: array<mixed>} $configData
     * @return ExitCode::*
     */
    public function run(array $configData): int
    {
        if ($configData['rules'] === []) {
            $this->easyCodingStandardStyle->warning('No rules resolved, nothing to commit.');
            return ExitCode::SUCCESS;
        }
        $committedCount = 0;
        foreach ($configData['rules'] as $rule) {
            $singleRuleConfig = ['paths' => $configData['paths'], 'rules' => [$rule], 'skips' => $configData['skips']];
            $this->blinkRunner->run($singleRuleConfig, \true);
            if (!$this->hasChanges()) {
                continue;
            }
            $message = $this->createCommitMessage($rule['class']);
            $this->commit($message);
            $this->easyCodingStandardStyle->writeln('<fg=green>✓</> ' . $message);
            ++$committedCount;
        }
        $this->easyCodingStandardStyle->success(sprintf('%d rule(s) committed', $committedCount));
        return ExitCode::SUCCESS;
    }
    /**
     * Builds "[Group] ShortName" from the rule class, where the group is the
     * namespace segment just above the class (e.g. ...\Fixer\Import\OrderedImportsFixer
     * yields "[Import] OrderedImportsFixer"). Falls back to the bare short name.
     */
    public function createCommitMessage(string $ruleClass): string
    {
        $parts = explode('\\', $ruleClass);
        $shortName = array_pop($parts);
        $group = array_pop($parts);
        if ($group === null) {
            return $shortName;
        }
        return sprintf('[%s] %s', $group, $shortName);
    }
    private function hasChanges(): bool
    {
        exec('git status --porcelain --untracked-files=all 2>/dev/null', $statusLines, $exitCode);
        return $exitCode === 0 && array_filter($statusLines, static function (string $line): bool {
            return $line !== '';
        }) !== [];
    }
    private function commit(string $message): void
    {
        exec('git add --update 2>/dev/null');
        exec(sprintf('git commit --message %s 2>/dev/null', escapeshellarg($message)));
    }
}
