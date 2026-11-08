<?php

declare (strict_types=1);
namespace ECSPrefix202611;

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededBracesFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer;
use Symplify\CodingStandard\Fixer\Commenting\AddMissingParamNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\AddMissingVarNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\DoubleAsteriskInlineVarFixer;
use Symplify\CodingStandard\Fixer\Commenting\FixParamNameTypoFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveDeadParamFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveParamNameReferenceFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveSuperfluousReturnNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveSuperfluousVarNameFixer;
use Symplify\CodingStandard\Fixer\Commenting\SingleLineInlineVarDocBlockFixer;
use Symplify\CodingStandard\Fixer\Commenting\SwitchedTypeAndNameFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withConfiguredRule(ArraySyntaxFixer::class, ['syntax' => 'short'])->withRules([AddMissingParamNameFixer::class, AddMissingVarNameFixer::class, DoubleAsteriskInlineVarFixer::class, FixParamNameTypoFixer::class, RemoveDeadParamFixer::class, RemoveParamNameReferenceFixer::class, RemoveSuperfluousReturnNameFixer::class, RemoveSuperfluousVarNameFixer::class, SingleLineInlineVarDocBlockFixer::class, SwitchedTypeAndNameFixer::class, NoUnusedImportsFixer::class, NoEmptyStatementFixer::class, ProtectedToPrivateFixer::class, NoUnneededControlParenthesesFixer::class, NoUnneededBracesFixer::class])->withConfiguredRule(OrderedImportsFixer::class, ['imports_order' => ['class', 'function', 'const']]);
