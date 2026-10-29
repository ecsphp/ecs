<?php

declare (strict_types=1);
/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer;

use PhpCsFixer\Config\RuleCustomisationPolicyAwareConfigInterface;
use PhpCsFixer\Config\RuleCustomisationPolicyInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\RuleSet\RuleSetDefinitionInterface;
use PhpCsFixer\Runner\Parallel\ParallelConfig;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Katsuhiro Ogawa <ko.fivestar@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise.
 *
 * @api-extendable
 */
class Config implements \PhpCsFixer\ConfigInterface, \PhpCsFixer\ParallelAwareConfigInterface, \PhpCsFixer\UnsupportedPhpVersionAllowedConfigInterface, \PhpCsFixer\CustomRulesetsAwareConfigInterface, RuleCustomisationPolicyAwareConfigInterface
{
    /**
     * @var non-empty-string
     */
    private $cacheFile = '.php-cs-fixer.cache';
    /**
     * @var list<FixerInterface>
     */
    private $customFixers = [];
    /**
     * @var array<string, RuleSetDefinitionInterface>
     */
    private $customRuleSets = [];
    /**
     * @var null|iterable<\SplFileInfo>
     */
    private $finder;
    /**
     * @var string
     */
    private $format;
    /**
     * @var bool
     */
    private $hideProgress = \false;
    /**
     * @var non-empty-string
     */
    private $indent = '    ';
    /**
     * @var bool
     */
    private $isRiskyAllowed = \false;
    /**
     * @var non-empty-string
     */
    private $lineEnding = "\n";
    /**
     * @var string
     */
    private $name;
    /**
     * @var \PhpCsFixer\Runner\Parallel\ParallelConfig
     */
    private $parallelConfig;
    /**
     * @var string|null
     */
    private $phpExecutable;
    /**
     * @TODO: 4.0 - update to @PER
     *
     * @var array<string, array<string, mixed>|bool>
     */
    private $rules;
    /**
     * @var bool
     */
    private $usingCache = \true;
    /**
     * @var bool
     */
    private $isUnsupportedPhpVersionAllowed = \false;
    /**
     * @var \PhpCsFixer\Config\RuleCustomisationPolicyInterface|null
     */
    private $ruleCustomisationPolicy;
    public function __construct(string $name = 'default')
    {
        $this->name = $name . (\PhpCsFixer\Future::isFutureModeEnabled() ? ' (future mode)' : '');
        $this->rules = \PhpCsFixer\Future::getV4OrV3(['@PER-CS' => \true], ['@PSR12' => \true]);
        // @TODO 4.0 | 3.x switch to '@auto' for v4
        $this->format = \PhpCsFixer\Future::getV4OrV3('@auto', 'txt');
        $this->parallelConfig = ParallelConfigFactory::detect();
        // @TODO 4.0 cleanup
        if (\false !== getenv('PHP_CS_FIXER_IGNORE_ENV')) {
            $this->isUnsupportedPhpVersionAllowed = filter_var(getenv('PHP_CS_FIXER_IGNORE_ENV'), \FILTER_VALIDATE_BOOL);
        }
    }
    /**
     * @return non-empty-string
     */
    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }
    public function getCustomFixers(): array
    {
        return $this->customFixers;
    }
    public function getCustomRuleSets(): array
    {
        return array_values($this->customRuleSets);
    }
    /**
     * @return iterable<\SplFileInfo>
     */
    public function getFinder(): iterable
    {
        $this->finder = $this->finder ?? new \PhpCsFixer\Finder();
        return $this->finder;
    }
    public function getFormat(): string
    {
        return $this->format;
    }
    public function getHideProgress(): bool
    {
        return $this->hideProgress;
    }
    public function getIndent(): string
    {
        return $this->indent;
    }
    public function getLineEnding(): string
    {
        return $this->lineEnding;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getParallelConfig(): ParallelConfig
    {
        return $this->parallelConfig;
    }
    public function getPhpExecutable(): ?string
    {
        return $this->phpExecutable;
    }
    public function getRiskyAllowed(): bool
    {
        return $this->isRiskyAllowed;
    }
    public function getRules(): array
    {
        return $this->rules;
    }
    public function getUsingCache(): bool
    {
        return $this->usingCache;
    }
    public function getUnsupportedPhpVersionAllowed(): bool
    {
        return $this->isUnsupportedPhpVersionAllowed;
    }
    public function getRuleCustomisationPolicy(): ?RuleCustomisationPolicyInterface
    {
        return $this->ruleCustomisationPolicy;
    }
    public function registerCustomFixers(iterable $fixers): \PhpCsFixer\ConfigInterface
    {
        foreach ($fixers as $fixer) {
            $this->addCustomFixer($fixer);
        }
        return $this;
    }
    /**
     * @param list<RuleSetDefinitionInterface> $ruleSets
     */
    public function registerCustomRuleSets(array $ruleSets): \PhpCsFixer\ConfigInterface
    {
        foreach ($ruleSets as $ruleset) {
            $this->customRuleSets[$ruleset->getName()] = $ruleset;
        }
        return $this;
    }
    /**
     * @param non-empty-string $cacheFile
     */
    public function setCacheFile(string $cacheFile): \PhpCsFixer\ConfigInterface
    {
        $this->cacheFile = $cacheFile;
        return $this;
    }
    public function setFinder(iterable $finder): \PhpCsFixer\ConfigInterface
    {
        $this->finder = $finder;
        return $this;
    }
    public function setFormat(string $format): \PhpCsFixer\ConfigInterface
    {
        $this->format = $format;
        return $this;
    }
    public function setHideProgress(bool $hideProgress): \PhpCsFixer\ConfigInterface
    {
        $this->hideProgress = $hideProgress;
        return $this;
    }
    /**
     * @param non-empty-string $indent
     */
    public function setIndent(string $indent): \PhpCsFixer\ConfigInterface
    {
        $this->indent = $indent;
        return $this;
    }
    /**
     * @param non-empty-string $lineEnding
     */
    public function setLineEnding(string $lineEnding): \PhpCsFixer\ConfigInterface
    {
        $this->lineEnding = $lineEnding;
        return $this;
    }
    public function setParallelConfig(ParallelConfig $config): \PhpCsFixer\ConfigInterface
    {
        $this->parallelConfig = $config;
        return $this;
    }
    public function setPhpExecutable(?string $phpExecutable): \PhpCsFixer\ConfigInterface
    {
        $this->phpExecutable = $phpExecutable;
        return $this;
    }
    public function setRiskyAllowed(bool $isRiskyAllowed): \PhpCsFixer\ConfigInterface
    {
        $this->isRiskyAllowed = $isRiskyAllowed;
        return $this;
    }
    public function setRules(array $rules): \PhpCsFixer\ConfigInterface
    {
        $this->rules = $rules;
        return $this;
    }
    public function setUsingCache(bool $usingCache): \PhpCsFixer\ConfigInterface
    {
        $this->usingCache = $usingCache;
        return $this;
    }
    public function setUnsupportedPhpVersionAllowed(bool $isUnsupportedPhpVersionAllowed): \PhpCsFixer\ConfigInterface
    {
        $this->isUnsupportedPhpVersionAllowed = $isUnsupportedPhpVersionAllowed;
        return $this;
    }
    public function setRuleCustomisationPolicy(?RuleCustomisationPolicyInterface $ruleCustomisationPolicy): \PhpCsFixer\ConfigInterface
    {
        // explicitly prevent policy with no proper version defined
        if (null !== $ruleCustomisationPolicy && '' === $ruleCustomisationPolicy->getPolicyVersionForCache()) {
            throw new \InvalidArgumentException('The Rule Customisation Policy version cannot be an empty string.');
        }
        $this->ruleCustomisationPolicy = $ruleCustomisationPolicy;
        return $this;
    }
    private function addCustomFixer(FixerInterface $fixer): void
    {
        $this->customFixers[] = $fixer;
    }
}
