<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use ReflectionProperty;
use stdClass;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedCriteriaResolver;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
/**
 * Turns the resolved ecs.php configuration into the JSON shape the ecs-go blink
 * runner consumes: paths, rules (each a class and its config) and skips. See
 * docs/blink.md for the schema.
 */
final class BlinkConfigDumper
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
     * @var \Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver\SkippedCriteriaResolver
     */
    private $skippedCriteriaResolver;
    public function __construct(SniffFileProcessor $sniffFileProcessor, FixerFileProcessor $fixerFileProcessor, SkippedCriteriaResolver $skippedCriteriaResolver)
    {
        $this->sniffFileProcessor = $sniffFileProcessor;
        $this->fixerFileProcessor = $fixerFileProcessor;
        $this->skippedCriteriaResolver = $skippedCriteriaResolver;
    }
    /**
     * @param string[] $paths
     * @return array{paths: string[], rules: array<array{class: string, config: object|array<string, mixed>}>, skips: array<array{path?: string, class?: string, paths?: string[]}>}
     */
    public function dump(array $paths): array
    {
        return ['paths' => array_values($paths), 'rules' => $this->dumpRules(), 'skips' => $this->dumpSkips()];
    }
    /**
     * @return array<array{class: string, config: object|array<string, mixed>}>
     */
    private function dumpRules(): array
    {
        $rules = [];
        foreach ($this->fixerFileProcessor->getCheckers() as $fixer) {
            $rules[] = ['class' => get_class($fixer), 'config' => $this->extractFixerConfiguration($fixer)];
        }
        foreach ($this->sniffFileProcessor->getCheckers() as $sniff) {
            $rules[] = [
                'class' => get_class($sniff),
                // sniff properties are not extracted yet; ecs-go maps only config-less sniffs
                'config' => new stdClass(),
            ];
        }
        return $rules;
    }
    /**
     * @return array<array{path?: string, class?: string, paths?: string[]}>
     */
    private function dumpSkips(): array
    {
        $skips = [];
        foreach ($this->skippedCriteriaResolver->resolvePaths() as $path) {
            $skips[] = ['path' => $path];
        }
        foreach ($this->skippedCriteriaResolver->resolveClasses() as $checkerClass => $paths) {
            if ($paths === null) {
                $skips[] = ['class' => $checkerClass];
                continue;
            }
            $skips[] = ['class' => $checkerClass, 'paths' => array_values($paths)];
        }
        return $skips;
    }
    /**
     * Reads a configured fixer's options off the ConfigurableFixerTrait's
     * `configuration` property. An unconfigured or non-configurable fixer yields
     * an empty object, which ecs-go reads as the config-less form.
     *
     * @return object|array<string, mixed>
     */
    private function extractFixerConfiguration(FixerInterface $fixer)
    {
        if (!$fixer instanceof ConfigurableFixerInterface) {
            return new stdClass();
        }
        if (!property_exists($fixer, 'configuration')) {
            return new stdClass();
        }
        $reflectionProperty = new ReflectionProperty($fixer, 'configuration');
        if (\PHP_VERSION_ID < 80100) {
            $reflectionProperty->setAccessible(\true);
        }
        $configuration = $reflectionProperty->getValue($fixer);
        if (!is_array($configuration) || $configuration === []) {
            return new stdClass();
        }
        return $configuration;
    }
}
