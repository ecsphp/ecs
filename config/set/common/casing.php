<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use PhpCsFixer\Fixer\Casing\ClassReferenceNameCasingFixer;
use PhpCsFixer\Fixer\Casing\IntegerLiteralCaseFixer;
use PhpCsFixer\Fixer\Casing\MagicMethodCasingFixer;
use PhpCsFixer\Fixer\Casing\NativeFunctionCasingFixer;
use PhpCsFixer\Fixer\Casing\NativeTypeDeclarationCasingFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withRules([NativeFunctionCasingFixer::class, NativeTypeDeclarationCasingFixer::class, IntegerLiteralCaseFixer::class, MagicMethodCasingFixer::class, ClassReferenceNameCasingFixer::class]);
