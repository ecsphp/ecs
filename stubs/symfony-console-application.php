<?php

declare (strict_types=1);
namespace ECSPrefix202610\Symfony\Component\Console;

/**
 * ECS replaces "symfony/console" with its own console, so this class is never installed.
 * Yet php-cs-fixer's Application extends it, and PHP must resolve the parent class before
 * PhpCsFixer\Console\Application::getMajorVersion() can be called - which ConfigurableFixerTrait
 * does for every fixer configured with a deprecated option.
 *
 * Only the class declaration is needed, as ECS never runs the php-cs-fixer console.
 *
 * @see https://github.com/ecsphp/ecs-src/issues/44
 */
class Application
{
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
    }
}
