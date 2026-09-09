# The Easiest way to use Coding Standard

[![Downloads total](https://img.shields.io/packagist/dt/symplify/easy-coding-standard.svg?style=flat-square)](https://packagist.org/packages/symplify/easy-coding-standard/stats)

Easy Coding Standard (ECS) is a single tool that runs [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) and [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) rules together, configured through one simple PHP file.

> [!NOTE]
> This is the **deployed** package. Development happens in the [ecs-src](https://github.com/easy-coding-standard/ecs-src) repository - report issues and send pull requests there.

<br>

## Killer Features

- Install on **any PHP 7.2-PHP 8.5** project with any dependencies
- Blazing fast with parallel run out of the box
- Use [PHP_CodeSniffer or PHP-CS-Fixer](https://tomasvotruba.com/blog/2017/05/03/combine-power-of-php-code-sniffer-and-php-cs-fixer-in-3-lines/) - anything you like
- Use **prepared sets** and [PHP CS Fixer sets](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/blob/master/doc/ruleSets/index.rst) to save time

<br>

## Install

```bash
composer require symplify/easy-coding-standard --dev
```

<br>

## Usage

```bash
vendor/bin/ecs
```

On the first run, ECS creates `ecs.php` config file with directories and first rule to kick off.

Then you can run again to see the suggested diffs:

```bash
vendor/bin/ecs
```

Want to check specific paths instead of the configured ones? Pass them as arguments:

```bash
vendor/bin/ecs src tests
```

To actually **fix your code**, add `--fix`:

```bash
vendor/bin/ecs --fix
```

That's it!

<br>

## Configure

Most of the time, you'll be happy with the default configuration. The most relevant part is configuring paths, checkers and sets:

```php
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])

    // start slow with sole rules
    ->withRules([
        ListSyntaxFixer::class,
    ])
    ->withConfiguredRule(
        ArraySyntaxFixer::class,
        ['syntax' => 'long']
    )

    // apply full set
    ->withPreparedSets(psr12: true);
```

<br>

Do you want to check all `*.php` files in your root (`ecs.php`, `rector.php` etc.)? Instead of listing them one by one, use `->withRootFiles()` method:

```php
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withRootFiles();
```

<br>

### Prepared Sets

`->withPreparedSets()` bundles curated rule sets. Enable the whole `common` set, or pick single topics:

```php
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPreparedSets(
        arrays: true,
        spaces: true,
        namespaces: true,
        docblocks: true,
        controlStructures: true,
        comments: true,
        casing: true,
        cleanup: true,
    );
```

| Set | What it covers |
| --- | --- |
| `arrays` | array syntax, spacing, trailing commas, indentation |
| `spaces` | whitespace, operator/type spacing, blank lines |
| `namespaces` | imports ordering, unused/needless-alias imports |
| `docblocks` | phpdoc tags, types, alignment, cleanup |
| `controlStructures` | control flow, casing, operators, class structure |
| `comments` | comment style, spacing, empty-comment cleanup |
| `casing` | native function/type, magic method, literal casing |
| `cleanup` | dead statements, useless returns/casts, unused closure imports |

Or enable everything at once with `->withPreparedSets(common: true)`.

Standalone presets are also available: `psr12: true`, `perCs: true`, and `laravel: true` (the [Laravel Pint](https://github.com/laravel/pint/blob/main/resources/presets/laravel.php) ruleset):

```php
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPreparedSets(laravel: true);
```

<br>

### Gradual Adoption with Levels

Want to adopt a coding standard step by step instead of all at once? Use `with*Level()` methods to start from the safest rules and raise the level as your codebase catches up:

```php
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSpacesLevel(0)
    ->withArrayLevel(0)
    ->withControlStructuresLevel(0)
    ->withDocblockLevel(0);
```

Each level enables the first N+1 rules from a curated list, ordered from safest to most invasive. Bump the number once your codebase is clean on the current level.

<br>

### How to Skip Files/Rules?

Love the sets of rules, but want to skip single rule or some files?

```php
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSkip([
        // skip single rule
        ArraySyntaxFixer::class,

        // skip single rule in specific paths
        ArraySyntaxFixer::class => [
            __DIR__ . '/src/ValueObject/',
        ],

        // skip directory by absolute or * mask
        __DIR__ . '/src/Migrations',

        // skip directories by mask
        __DIR__ . '/src/*/Legacy',
    ]);
```

<br>

### Less Common Options

You probably won't use these, but they can give you more control over the internal process:

```php
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    // file extensions to scan
    ->withFileExtensions(['php'])

    // configure cache paths and namespace - useful e.g. Gitlab CI caching, where getcwd() produces always different path
    ->withCache(
        directory: sys_get_temp_dir() . '/_changed_files_detector_tests',
        namespace: getcwd() // normalized to directory separator
    )

    // print contents with specific indent rules
    ->withSpacing(indentation: Option::INDENTATION_SPACES, lineEnding: PHP_EOL)

    // modify parallel run
    ->withParallel(timeoutSeconds: 120, maxNumberOfProcess: 32, jobSize: 20);
```

Mentioned values are default ones.

<br>

### Controlling Output Format

You may want to use ECS to generate reports for third-party tooling.

We currently provide formatters for:

- `console`:  Human-oriented printing à la PHP CS Fixer.
- `json`:  A custom JSON blob for arbitrary tooling.

You can use the output format option as below

```bash
vendor/bin/ecs --output-format=json
```

<br>

## FAQ

### How do I clear cache?

```bash
vendor/bin/ecs --clear-cache
```

### How can I see all used rules?

```bash
vendor/bin/ecs list-checkers
```

Do you look for json format?

```bash
vendor/bin/ecs list-checkers --output-format json
```

<br>

## How to Migrate from another coding standard tool?

Do you use another tool and want to migrate? It's pretty straightforward - here is "how to":

* for [PHP_CodeSniffer](https://tomasvotruba.com/blog/2018/06/04/how-to-migrate-from-php-code-sniffer-to-easy-coding-standard)
* and [PHP CS Fixer](https://tomasvotruba.com/blog/2018/06/07/how-to-migrate-from-php-cs-fixer-to-easy-coding-standard).
