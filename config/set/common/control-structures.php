<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Config\Level\ControlStructuresLevel;
return static function (ECSConfig $ecsConfig): void {
    // the rule order matters, as it's used in withControlStructuresLevel() method
    // place the safest rules first, follow by more complex ones
    $ecsConfig->rules(ControlStructuresLevel::RULES);
    $ecsConfig->rulesWithConfiguration(ControlStructuresLevel::RULE_CONFIGURATIONS);
};
