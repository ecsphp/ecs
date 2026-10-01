<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
return ECSConfig::configure()->withSets([SetList::ARRAY, SetList::COMMENTS, SetList::CONTROL_STRUCTURES, SetList::DOCBLOCK, SetList::NAMESPACES, SetList::SPACES, SetList::CASING, SetList::CLEANUP, SetList::CLEAN_CODE]);
