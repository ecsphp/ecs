<?php

declare (strict_types=1);
namespace ECSPrefix202610;

use Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;
$cacheDirectory = \sys_get_temp_dir() . '/changed_files_detector';
if (StaticVersionResolver::PACKAGE_VERSION !== '@package_version@') {
    $cacheDirectory .= '_' . StaticVersionResolver::PACKAGE_VERSION;
}
// make cache individual per project
$cacheNamespace = \str_replace(\DIRECTORY_SEPARATOR, '_', \getcwd());
return ECSConfig::configure()->withParallel()->withSpacing(Option::INDENTATION_SPACES, \PHP_EOL)->withCache($cacheDirectory, $cacheNamespace)->withFileExtensions(['php'])->withSkip([])->withPaths([]);
