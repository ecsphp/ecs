<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use Symplify\EasyCodingStandard\Blink\Exception\EcsGoBinaryNotFoundException;
/**
 * Decides whether blink can fully handle a resolved ECS config: every configured
 * rule must map to a blink fixer. A custom sniff/fixer or any not-yet-ported rule
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
    public function __construct(\Symplify\EasyCodingStandard\Blink\EcsGoBinaryLocator $ecsGoBinaryLocator)
    {
        $this->ecsGoBinaryLocator = $ecsGoBinaryLocator;
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
        $supportedCheckerClasses = $this->resolveSupportedCheckerClasses();
        if ($supportedCheckerClasses === []) {
            return \false;
        }
        return array_diff($ruleClasses, $supportedCheckerClasses) === [];
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
