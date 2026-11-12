<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use Symplify\EasyCodingStandard\Blink\Exception\EcsGoBinaryNotFoundException;
use Symplify\EasyCodingStandard\FixerRunner\ValueObject\Spacing;
use Symplify\EasyCodingStandard\FixerRunner\WhitespacesFixerConfigFactory;
/**
 * Decides whether blink can fully handle a resolved ECS config: every configured
 * rule must map to a blink fixer. A custom sniff/fixer, any not-yet-ported rule
 * or a non-default indentation/line ending (blink only knows 4 spaces and "\n")
 * makes it unsupported, so the caller falls back to the PHP engine.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Blink\BlinkSupportResolverTest
 */
final class BlinkSupportResolver
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Blink\EcsGoBinaryLocator
     */
    private $ecsGoBinaryLocator;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\FixerRunner\WhitespacesFixerConfigFactory
     */
    private $whitespacesFixerConfigFactory;
    public function __construct(\Symplify\EasyCodingStandard\Blink\EcsGoBinaryLocator $ecsGoBinaryLocator, WhitespacesFixerConfigFactory $whitespacesFixerConfigFactory)
    {
        $this->ecsGoBinaryLocator = $ecsGoBinaryLocator;
        $this->whitespacesFixerConfigFactory = $whitespacesFixerConfigFactory;
    }
    /**
     * @param array{rules: array<array{class: string, config: object|array<string, mixed>}>} $configData
     */
    public function isFullySupported(array $configData): bool
    {
        $ruleClasses = array_column($configData['rules'], 'class');
        if ($ruleClasses === []) {
            return \false;
        }
        if (!$this->hasDefaultWhitespaces()) {
            return \false;
        }
        $supportedCheckerClasses = $this->resolveSupportedCheckerClasses();
        if ($supportedCheckerClasses === []) {
            return \false;
        }
        return array_diff($ruleClasses, $supportedCheckerClasses) === [];
    }
    private function hasDefaultWhitespaces(): bool
    {
        $whitespacesFixerConfig = $this->whitespacesFixerConfigFactory->create();
        if ($whitespacesFixerConfig->getIndent() !== Spacing::FOUR_SPACES) {
            return \false;
        }
        return $whitespacesFixerConfig->getLineEnding() === "\n";
    }
    /**
     * @return string[]
     */
    private function resolveSupportedCheckerClasses(): array
    {
        try {
            $binary = $this->ecsGoBinaryLocator->locate();
        } catch (EcsGoBinaryNotFoundException $exception) {
            return [];
        }
        $command = escapeshellarg($binary) . ' list-checkers';
        exec($command, $outputLines, $exitCode);
        if ($exitCode !== 0) {
            return [];
        }
        return $this->parseCheckerClasses($outputLines);
    }
    /**
     * Lines of "list-checkers" look like "  - PhpCsFixer\Fixer\...\FooFixer".
     *
     * @param string[] $outputLines
     * @return string[]
     */
    private function parseCheckerClasses(array $outputLines): array
    {
        $checkerClasses = [];
        foreach ($outputLines as $outputLine) {
            if (preg_match('#^\s*-\s*(?<class>\S+)$#', $outputLine, $matches) !== 1) {
                continue;
            }
            $checkerClasses[] = $matches['class'];
        }
        return $checkerClasses;
    }
}
