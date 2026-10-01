<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Config\Level;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\LanguageConstructSpacingSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\SuperfluousWhitespaceSniff;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\NamespaceNotation\NoLeadingNamespaceWhitespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSingleLineVarSpacingFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\SpaceAfterSemicolonFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesAroundOffsetFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;
use PhpCsFixer\Fixer\Whitespace\TypeDeclarationSpacesFixer;
use PhpCsFixer\Fixer\Whitespace\TypesSpacesFixer;
use Symplify\CodingStandard\Fixer\Spacing\NoBlankLineBetweenImportsFixer;
use Symplify\CodingStandard\Fixer\Spacing\SpaceAfterCommaHereNowDocFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLineRequiredParamFixer;
use Symplify\CodingStandard\Fixer\Strict\BlankLineAfterStrictTypesFixer;
/**
 * Key 0 = level 0
 * Key 22 = level 22
 *
 * Start at 0, go slowly higher, one level per PR, and improve your spacing coverage.
 *
 * From the safest rules to more changing ones.
 *
 * This list can change in time, based on community feedback,
 * what rules are safer than other. The safest rules will be always in the top.
 */
final class SpacesLevel
{
    /**
     * @var array<class-string<Sniff|FixerInterface>>
     */
    public const RULES = [
        // easy picks - pure whitespace cleanup with no formatting opinion
        NoLeadingNamespaceWhitespaceFixer::class,
        NoSinglelineWhitespaceBeforeSemicolonsFixer::class,
        NoWhitespaceInBlankLineFixer::class,
        NoSpacesAroundOffsetFixer::class,
        SpaceAfterSemicolonFixer::class,
        NoBlankLinesAfterClassOpeningFixer::class,
        BlankLineAfterOpeningTagFixer::class,
        SingleTraitInsertPerStatementFixer::class,
        PhpdocSingleLineVarSpacingFixer::class,
        LanguageConstructSpacingSniff::class,
        NoBlankLineBetweenImportsFixer::class,
        // operator and type spacing
        CastSpacesFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        TernaryOperatorSpacesFixer::class,
        ReturnTypeDeclarationFixer::class,
        TypeDeclarationSpacesFixer::class,
        TypesSpacesFixer::class,
        // most invasive structural changes
        MethodChainingIndentationFixer::class,
        StandaloneLinePromotedPropertyFixer::class,
        StandaloneLineRequiredParamFixer::class,
        MethodArgumentSpaceFixer::class,
        // newline spacing (from deprecated "symplify" set)
        BlankLineAfterStrictTypesFixer::class,
        SpaceAfterCommaHereNowDocFixer::class,
    ];
    /**
     * Configurations matching the spaces set, applied when a configurable rule
     * is enabled via withSpacesLevel(). Rules absent from this map use defaults.
     *
     * @var array<class-string<Sniff|FixerInterface>, mixed[]>
     */
    public const RULE_CONFIGURATIONS = [ClassAttributesSeparationFixer::class => ['elements' => ['const' => 'one', 'property' => 'one', 'method' => 'one']], NoExtraBlankLinesFixer::class => ['tokens' => ['extra', 'throw', 'use']], ConcatSpaceFixer::class => ['spacing' => 'one'], SuperfluousWhitespaceSniff::class => ['ignoreBlankLines' => \false], BinaryOperatorSpacesFixer::class => ['operators' => ['=>' => 'single_space', '=' => 'single_space']]];
}
