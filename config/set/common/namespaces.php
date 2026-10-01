<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use PhpCsFixer\Fixer\Import\NoUnneededImportAliasFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\SingleBlankLineBeforeNamespaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withRules([NoUnusedImportsFixer::class, OrderedImportsFixer::class, SingleBlankLineBeforeNamespaceFixer::class, NoUnneededImportAliasFixer::class]);
