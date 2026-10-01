<?php

namespace ECSPrefix202610;

/**
 * Bootstrap file for PHP_CodeSniffer unit tests.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */
use PHP_CodeSniffer\Autoload;
use PHP_CodeSniffer\Util\Standards;
use PHP_CodeSniffer\Util\Tokens;
if (\defined('PHP_CODESNIFFER_IN_TESTS') === \false) {
    \define('PHP_CODESNIFFER_IN_TESTS', \true);
}
/*
 * Determine whether the test suite should be run in CBF mode.
 *
 * Use `<php><env name="PHP_CODESNIFFER_CBF" value="1"/></php>` in a `phpunit.xml` file
 * or set the ENV variable at an OS-level to enable CBF mode.
 *
 * To run the CBF specific tests, use the following command:
 * vendor/bin/phpunit --group CBF --exclude-group nothing
 *
 * If the ENV variable has not been set, or is set to "false", the tests will run in CS mode.
 */
if (\defined('PHP_CODESNIFFER_CBF') === \false) {
    $cbfMode = \getenv('PHP_CODESNIFFER_CBF');
    if ($cbfMode === '1') {
        \define('PHP_CODESNIFFER_CBF', \true);
        echo 'Note: Tests are running in "CBF" mode' . \PHP_EOL . \PHP_EOL;
    } else {
        \define('PHP_CODESNIFFER_CBF', \false);
        echo 'Note: Tests are running in "CS" mode' . \PHP_EOL . \PHP_EOL;
    }
}
if (\defined('PHP_CODESNIFFER_VERBOSITY') === \false) {
    \define('PHP_CODESNIFFER_VERBOSITY', 0);
}
require_once __DIR__ . '/../autoload.php';
// Make sure all installed standards are autoloadable.
$installedStandards = Standards::getInstalledStandardDetails();
foreach ($installedStandards as $standardDetails) {
    Autoload::addSearchPath($standardDetails['path'], $standardDetails['namespace']);
}
$tokens = new Tokens();
