<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationIndentationFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationSpacesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return ECSConfig::configure()->withRules([DoctrineAnnotationArrayAssignmentFixer::class])->withConfiguredRule(DoctrineAnnotationIndentationFixer::class, ['indent_mixed_lines' => \true])->withConfiguredRule(DoctrineAnnotationSpacesFixer::class, ['after_array_assignments_equals' => \false, 'before_array_assignments_equals' => \false]);
