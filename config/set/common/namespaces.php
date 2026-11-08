<?php

declare (strict_types=1);
namespace ECSPrefix202611;

use PhpCsFixer\Fixer\Import\NoUnneededImportAliasFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLinesBeforeNamespaceFixer;
use Symplify\CodingStandard\Fixer\ConstantNotation\RemoveSlashFromNativeConstantFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withConfiguredRule(BlankLinesBeforeNamespaceFixer::class, ['min_line_breaks' => 2, 'max_line_breaks' => 2])->withRules([NoUnusedImportsFixer::class, OrderedImportsFixer::class, NoUnneededImportAliasFixer::class, RemoveSlashFromNativeConstantFixer::class]);
