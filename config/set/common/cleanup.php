<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use PhpCsFixer\Fixer\CastNotation\NoShortBoolCastFixer;
use PhpCsFixer\Fixer\ClassNotation\NoNullPropertyInitializationFixer;
use PhpCsFixer\Fixer\FunctionNotation\LambdaNotUsedImportFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withRules([NoEmptyStatementFixer::class, NoUselessReturnFixer::class, LambdaNotUsedImportFixer::class, NoNullPropertyInitializationFixer::class, NoShortBoolCastFixer::class]);
