<?php

declare (strict_types=1);
namespace ECSPrefix202609;

use PhpCsFixer\Fixer\Comment\MultilineCommentOpeningClosingFixer;
use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Comment\SingleLineCommentSpacingFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withRules([NoEmptyCommentFixer::class, SingleLineCommentSpacingFixer::class, MultilineCommentOpeningClosingFixer::class]);
