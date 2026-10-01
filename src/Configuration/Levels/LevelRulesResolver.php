<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Configuration\Levels;

use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use Symplify\EasyCodingStandard\Exception\ShouldNotHappenException;
use ECSPrefix202610\Webmozart\Assert\Assert;
final class LevelRulesResolver
{
    /**
     * @param array<class-string<Sniff|FixerInterface>> $availableRules
     * @return array<class-string<Sniff|FixerInterface>>
     */
    public static function resolve(int $level, array $availableRules, string $methodName): array
    {
        Assert::natural($level, sprintf('Level must be >= 0 on %s', $methodName));
        $rulesCount = count($availableRules);
        if ($availableRules === []) {
            throw new ShouldNotHappenException(sprintf('There are no available rules in "%s()", define the available rules first', $methodName));
        }
        $maxLevel = $rulesCount - 1;
        if ($level > $maxLevel) {
            $level = $maxLevel;
        }
        $levelRules = [];
        for ($i = 0; $i <= $level; ++$i) {
            $levelRules[] = $availableRules[$i];
        }
        return $levelRules;
    }
}
