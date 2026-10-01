<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\DependencyInjection\CompilerPass;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\EndFileNewlineSniff as GenericEndFileNewlineSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\EndFileNoNewlineSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseConstantSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\UpperCaseConstantSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowSpaceIndentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowTabIndentSniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\FileHeaderSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\EndFileNewlineSniff;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLineConstructorParamFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Exception\Configuration\ConflictingCheckersLoadedException;
final class ConflictingCheckersCompilerPass
{
    /**
     * These groups do the opposite of each other, e.g. Yoda vs NoYoda.
     *
     * @var string[][]
     */
    private const CONFLICTING_CHECKER_GROUPS = [[StandaloneLineConstructorParamFixer::class, StandaloneLinePromotedPropertyFixer::class], ['ECSPrefix202610\SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff', YodaStyleFixer::class], [LowerCaseConstantSniff::class, UpperCaseConstantSniff::class], [ConstantCaseFixer::class, UpperCaseConstantSniff::class], ['ECSPrefix202610\SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff', DeclareEqualNormalizeFixer::class], ['ECSPrefix202610\SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff', BlankLineAfterOpeningTagFixer::class], [FileHeaderSniff::class, NoBlankLinesAfterPhpdocFixer::class], [EndFileNewlineSniff::class, EndFileNoNewlineSniff::class], [GenericEndFileNewlineSniff::class, EndFileNoNewlineSniff::class], [DisallowTabIndentSniff::class, DisallowSpaceIndentSniff::class]];
    public function process(ECSConfig $ecsConfig): void
    {
        $checkerTypes = \Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\CompilerPassHelper::resolveCheckerClasses($ecsConfig);
        if ($checkerTypes === []) {
            return;
        }
        foreach (self::CONFLICTING_CHECKER_GROUPS as $viceVersaMatchingCheckerGroup) {
            if (!$this->isMatch($checkerTypes, $viceVersaMatchingCheckerGroup)) {
                continue;
            }
            throw new ConflictingCheckersLoadedException(sprintf('Checkers "%s" mutually exclude each other. Use only one of them or exclude the unwanted one in "$ecsConfig->skip(...)" in your config.', implode('" and "', $viceVersaMatchingCheckerGroup)));
        }
    }
    /**
     * @param mixed[] $checkers
     * @param string[] $matchingCheckerGroup
     */
    private function isMatch(array $checkers, array $matchingCheckerGroup): bool
    {
        $checkers = array_flip($checkers);
        $matchingCheckerGroup = array_flip($matchingCheckerGroup);
        $foundCheckers = array_intersect_key($matchingCheckerGroup, $checkers);
        return count($foundCheckers) === count($matchingCheckerGroup);
    }
}
