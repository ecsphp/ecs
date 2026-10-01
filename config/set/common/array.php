<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Config\Level\ArrayLevel;
return static function (ECSConfig $ecsConfig): void {
    // the rule order matters, as it's used in withArrayLevel() method
    // place the safest rules first, follow by more complex ones
    $ecsConfig->rules(ArrayLevel::RULES);
    $ecsConfig->rulesWithConfiguration(ArrayLevel::RULE_CONFIGURATIONS);
};
