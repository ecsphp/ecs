<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Config\Level;

use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\Comment\NoTrailingWhitespaceInCommentFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocInlineTagNormalizerFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAccessFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAliasTagFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoDuplicateTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderByValueFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocReturnSelfReferenceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTagCasingFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarWithoutNameFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemoveEventSubscriberDescriptionFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemoveMethodNameDuplicateDescriptionFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemovePHPStormAnnotationFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemovePropertyVariableNameDescriptionFixer;
use Symplify\CodingStandard\Fixer\Commenting\AddMissingParamNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\AddMissingVarNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\DoubleAsteriskInlineVarFixer;
use Symplify\CodingStandard\Fixer\Commenting\FixParamNameTypoFixer;
use Symplify\CodingStandard\Fixer\Commenting\FixTagTypoFixer;
use Symplify\CodingStandard\Fixer\Commenting\MergeDocBlockStartFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveDeadParamFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveDeadVarThisFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveParamDescriptionDuplicateNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveParamNameReferenceFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveSuperfluousReturnNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveSuperfluousVarNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\CodingStandard\Fixer\Commenting\SingleLineInlineVarDocBlockFixer;
use Symplify\CodingStandard\Fixer\Commenting\SwitchedTypeAndNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\TypeToVarTagFixer;
/**
 * Key 0 = level 0
 *
 * Start at 0, go slowly higher, one level per PR, and improve your docblock coverage.
 *
 * From the safest rules to more changing ones.
 */
final class DocblockLevel
{
    /**
     * @var array<class-string<Sniff|FixerInterface>>
     */
    public const RULES = [
        // tag name typos (@returns → @return), run early so later rules see correct tags
        FixTagTypoFixer::class,
        // inline @var
        TypeToVarTagFixer::class,
        DoubleAsteriskInlineVarFixer::class,
        RemoveDeadVarThisFixer::class,
        SingleLineInlineVarDocBlockFixer::class,
        AddMissingVarNameFixer::class,
        // @param
        AddMissingParamNameFixer::class,
        FixParamNameTypoFixer::class,
        RemoveParamNameReferenceFixer::class,
        RemoveParamDescriptionDuplicateNameFixer::class,
        RemoveDeadParamFixer::class,
        // superfluous names
        RemoveSuperfluousReturnNameFixer::class,
        RemoveSuperfluousVarNameFixer::class,
        // switched type/name order
        SwitchedTypeAndNameFixer::class,
        // pure whitespace cleanup
        MergeDocBlockStartFixer::class,
        NoTrailingWhitespaceInCommentFixer::class,
        PhpdocTrimFixer::class,
        PhpdocTrimConsecutiveBlankLineSeparationFixer::class,
        PhpdocIndentFixer::class,
        NoBlankLinesAfterPhpdocFixer::class,
        AlignMultilineCommentFixer::class,
        // type / formatting normalization
        PhpdocTagCasingFixer::class,
        PhpdocInlineTagNormalizerFixer::class,
        PhpdocNoDuplicateTypesFixer::class,
        PhpdocScalarFixer::class,
        PhpdocTypesFixer::class,
        PhpdocLineSpanFixer::class,
        PhpdocVarWithoutNameFixer::class,
        PhpdocReturnSelfReferenceFixer::class,
        PhpdocOrderByValueFixer::class,
        // dropping content
        PhpdocNoAliasTagFixer::class,
        PhpdocNoAccessFixer::class,
        PhpdocNoPackageFixer::class,
        NoEmptyPhpdocFixer::class,
        PhpdocNoEmptyReturnFixer::class,
        RemoveUselessDefaultCommentFixer::class,
        // annotation cleanup (from deprecated "symplify" set)
        RemovePHPStormAnnotationFixer::class,
        RemoveMethodNameDuplicateDescriptionFixer::class,
        RemovePropertyVariableNameDescriptionFixer::class,
        RemoveEventSubscriberDescriptionFixer::class,
    ];
    /**
     * @var array<class-string<Sniff|FixerInterface>, mixed[]>
     */
    public const RULE_CONFIGURATIONS = [NoSuperfluousPhpdocTagsFixer::class => ['remove_inheritdoc' => \true, 'allow_mixed' => \true], GeneralPhpdocAnnotationRemoveFixer::class => ['annotations' => ['author', 'package', 'group', 'category']]];
}
