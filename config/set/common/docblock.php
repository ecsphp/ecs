<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Config\Level\DocblockLevel;
return static function (ECSConfig $ecsConfig): void {
    // the rule order matters, as it's used in withDocblockLevel() method
    // place the safest rules first, follow by more complex ones
    $ecsConfig->rules(DocblockLevel::RULES);
    $ecsConfig->rulesWithConfiguration(DocblockLevel::RULE_CONFIGURATIONS);
};
