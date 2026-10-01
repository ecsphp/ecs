<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePlainConstructorParamFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLineRequiredParamFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLineSymfonyAttributeParamFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->rules([StandaloneLineSymfonyAttributeParamFixer::class, StandaloneLinePromotedPropertyFixer::class, StandaloneLinePlainConstructorParamFixer::class, StandaloneLineRequiredParamFixer::class]);
};
