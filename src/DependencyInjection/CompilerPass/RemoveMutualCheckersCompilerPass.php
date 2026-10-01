<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\DependencyInjection\CompilerPass;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowShortArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\EndFileNewlineSniff as GenericEndFileNewlineSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineEndingsSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\DisallowMultipleStatementsSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseConstantSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseKeywordSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowSpaceIndentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowTabIndentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\LanguageConstructSpacingSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\ScopeIndentSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ScopeClosingBraceSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\ClassDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\ClosingTagSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Files\EndFileNewlineSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\NamespaceDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Namespaces\UseDeclarationSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\LowercaseClassKeywordsSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\FunctionDeclarationArgumentSpacingSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Strings\DoubleQuoteUsageSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\SuperfluousWhitespaceSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\Casing\LowercaseKeywordsFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ControlStructure\IncludeFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer;
use PhpCsFixer\Fixer\Import\SingleLineAfterImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\PhpTag\NoClosingTagFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer;
use PhpCsFixer\Fixer\Whitespace\LineEndingFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
final class RemoveMutualCheckersCompilerPass
{
    /**
     * List of checkers with the same functionality. If found, only the first one is used.
     *
     * @var string[][]
     */
    private const DUPLICATED_CHECKER_GROUPS = [
        [IndentationTypeFixer::class, ScopeIndentSniff::class],
        [IndentationTypeFixer::class, DisallowTabIndentSniff::class],
        [IndentationTypeFixer::class, DisallowSpaceIndentSniff::class],
        [StrictComparisonFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Operators\DisallowEqualOperatorsSniff'],
        [VisibilityRequiredFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Classes\ClassConstantVisibilitySniff'],
        [ArraySyntaxFixer::class, DisallowShortArraySyntaxSniff::class],
        [ArraySyntaxFixer::class, DisallowLongArraySyntaxSniff::class],
        [LowercaseKeywordsFixer::class, LowercaseClassKeywordsSniff::class],
        [LowercaseKeywordsFixer::class, LowerCaseKeywordSniff::class],
        [SingleImportPerStatementFixer::class, UseDeclarationSniff::class],
        [SingleImportPerStatementFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Namespaces\DisallowGroupUseSniff'],
        [SingleImportPerStatementFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Namespaces\MultipleUsesPerLineSniff'],
        [PhpdocScalarFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\TypeHints\LongTypeHintsSniff'],
        [OrderedImportsFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff'],
        [NoUnusedImportsFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff'],
        [TrailingCommaInMultilineFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Arrays\TrailingArrayCommaSniff'],
        [NoUnneededControlParenthesesFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\ControlStructures\LanguageConstructWithParenthesesSniff'],
        [ReturnTypeDeclarationFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSpacingSniff'],
        [FunctionTypehintSpaceFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSpacingSniff'],
        [FunctionTypehintSpaceFixer::class, FunctionDeclarationArgumentSpacingSniff::class],
        [GeneralPhpdocAnnotationRemoveFixer::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\Commenting\ForbiddenAnnotationsSniff'],
        [NoExtraBlankLinesFixer::class, SuperfluousWhitespaceSniff::class],
        [NoTrailingWhitespaceFixer::class, SuperfluousWhitespaceSniff::class],
        [IncludeFixer::class, LanguageConstructSpacingSniff::class],
        [AssignmentInConditionSniff::class, 'ECSPrefix202610\SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff'],
        [SingleQuoteFixer::class, DoubleQuoteUsageSniff::class],
        // PSR2
        [BracesFixer::class, ScopeClosingBraceSniff::class],
        [BlankLineAfterNamespaceFixer::class, NamespaceDeclarationSniff::class],
        [SingleLineAfterImportsFixer::class, DisallowMultipleStatementsSniff::class],
        [LineEndingFixer::class, LineEndingsSniff::class],
        [ConstantCaseFixer::class, LowerCaseConstantSniff::class],
        [LowercaseKeywordsFixer::class, LowerCaseKeywordSniff::class],
        [SingleBlankLineAtEofFixer::class, EndFileNewlineSniff::class],
        [SingleBlankLineAtEofFixer::class, GenericEndFileNewlineSniff::class],
        [EndFileNewlineSniff::class, GenericEndFileNewlineSniff::class],
        [BracesFixer::class, ScopeIndentSniff::class],
        [BracesFixer::class, ScopeClosingBraceSniff::class],
        [ClassDefinitionFixer::class, ClassDeclarationSniff::class],
        [NoClosingTagFixer::class, ClosingTagSniff::class],
        [SingleClassElementPerStatementFixer::class, PropertyDeclarationSniff::class],
        [LineLengthFixer::class, LineLengthSniff::class],
    ];
    public function process(ECSConfig $ecsConfig): void
    {
        $checkerTypes = \Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\CompilerPassHelper::resolveCheckerClasses($ecsConfig);
        if ($checkerTypes === []) {
            return;
        }
        $checkersToRemove = $this->resolveCheckersToRemove($checkerTypes);
        if ($checkersToRemove === []) {
            return;
        }
        foreach ($ecsConfig->getCheckerClasses() as $type) {
            if (!in_array($type, $checkersToRemove, \true)) {
                continue;
            }
            \Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\CompilerPassHelper::removeCheckerFromContainer($ecsConfig, $type);
        }
    }
    /**
     * @param string[] $checkers
     * @return string[]
     */
    private function resolveCheckersToRemove(array $checkers): array
    {
        $checkers = array_flip($checkers);
        $checkersToRemove = [];
        foreach (self::DUPLICATED_CHECKER_GROUPS as $matchingCheckerGroup) {
            if (!$this->isMatch($checkers, $matchingCheckerGroup)) {
                continue;
            }
            array_shift($matchingCheckerGroup);
            $checkersToRemove = array_merge($checkersToRemove, $matchingCheckerGroup);
        }
        return $checkersToRemove;
    }
    /**
     * @param string[] $checkers
     * @param string[] $matchingCheckerGroup
     */
    private function isMatch(array $checkers, array $matchingCheckerGroup): bool
    {
        $matchingCheckerGroupKeys = array_flip($matchingCheckerGroup);
        $matchingCheckers = array_intersect_key($matchingCheckerGroupKeys, $checkers);
        return count($matchingCheckers) === count($matchingCheckerGroup);
    }
}
