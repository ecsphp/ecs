<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededCurlyBracesFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer;
use Symplify\CodingStandard\Fixer\Commenting\ParamReturnAndVarTagMalformsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withConfiguredRule(ArraySyntaxFixer::class, ['syntax' => 'short'])->withRules([ParamReturnAndVarTagMalformsFixer::class, NoUnusedImportsFixer::class, NoEmptyStatementFixer::class, ProtectedToPrivateFixer::class, NoUnneededControlParenthesesFixer::class, NoUnneededCurlyBracesFixer::class])->withConfiguredRule(OrderedImportsFixer::class, ['imports_order' => ['class', 'function', 'const']]);
