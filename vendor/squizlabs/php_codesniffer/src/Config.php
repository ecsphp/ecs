<?php

/**
 * Stores the configuration used to run PHPCS and PHPCBF.
 *
 * Parses the command line to determine user supplied values
 * and provides functions to access data stored in config files.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */
namespace PHP_CodeSniffer;

use Exception;
use Phar;
use PHP_CodeSniffer\Exceptions\DeepExitException;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\ExitCode;
use PHP_CodeSniffer\Util\Help;
use PHP_CodeSniffer\Util\Standards;
/**
 * Stores the configuration used to run PHPCS and PHPCBF.
 *
 * @property string[]    $files           The files and directories to check.
 * @property string[]    $standards       The standards being used for checking.
 * @property int         $verbosity       How verbose the output should be.
 *                                        0: no unnecessary output
 *                                        1: basic output for files being checked
 *                                        2: ruleset and file parsing output
 *                                        3: sniff execution output
 * @property bool        $interactive     Enable interactive checking mode.
 * @property int         $parallel        Check files in parallel.
 * @property bool        $cache           Enable the use of the file cache.
 * @property string      $cacheFile       Path to the file where the cache data should be written
 * @property bool        $colors          Display colours in output.
 * @property bool        $explain         Explain the coding standards.
 * @property bool        $local           Process local files in directories only (no recursion).
 * @property bool        $showSources     Show sniff source codes in report output.
 * @property bool        $showProgress    Show basic progress information while running.
 * @property bool        $quiet           Quiet mode; disables progress and verbose output.
 * @property bool        $annotations     Process phpcs: annotations.
 * @property int         $tabWidth        How many spaces each tab is worth.
 * @property string      $encoding        The encoding of the files being checked.
 * @property string[]    $sniffs          The sniffs that should be used for checking.
 *                                        If empty, all sniffs in the supplied standards will be used.
 * @property string[]    $exclude         The sniffs that should be excluded from checking.
 *                                        If empty, all sniffs in the supplied standards will be used.
 * @property string[]    $ignored         Regular expressions used to ignore files and folders during checking.
 * @property string      $reportFile      A file where the report output should be written.
 * @property string      $generator       The documentation generator to use.
 * @property string      $filter          The filter to use for the run.
 * @property string[]    $bootstrap       One of more files to include before the run begins.
 * @property int|string  $reportWidth     The maximum number of columns that reports should use for output.
 *                                        Set to "auto" for have this value changed to the width of the terminal.
 * @property int         $errorSeverity   The minimum severity an error must have to be displayed.
 * @property int         $warningSeverity The minimum severity a warning must have to be displayed.
 * @property bool        $recordErrors    Record the content of error messages as well as error counts.
 * @property string      $suffix          A suffix to add to fixed files.
 * @property string|null $basepath        A file system location to strip from the paths of files shown in reports.
 * @property bool        $stdin           Read content from STDIN instead of supplied files.
 * @property string      $stdinContent    Content passed directly to PHPCS on STDIN.
 * @property string      $stdinPath       The path to use for content passed on STDIN.
 * @property bool        $trackTime       Whether or not to track sniff run time.
 *
 * @property array<string, string>      $extensions File extensions that should be checked, and what tokenizer is used.
 *                                                  E.g., array('inc' => 'PHP');
 *                                                  Note: since PHPCS 4.0.0, the tokenizer used will always be 'PHP',
 *                                                  but the array format of the property has not been changed to prevent
 *                                                  breaking integrations which may be accessing this property.
 * @property array<string, string|null> $reports    The reports to use for printing output after the run.
 *                                                  The format of the array is:
 *                                                      array(
 *                                                          'reportName1' => 'outputFile',
 *                                                          'reportName2' => null,
 *                                                      );
 *                                                  If the array value is NULL, the report will be written to the screen.
 *
 * @property string[] $unknown Any arguments gathered on the command line that are unknown to us.
 *                             E.g., using `phpcs -c` will give array('c');
 */
class Config
{
    /**
     * The current version.
     *
     * @var string
     */
    public const VERSION = '4.0.4';
    /**
     * Package stability; either stable, RC, beta or alpha.
     *
     * @var string
     */
    public const STABILITY = 'stable';
    /**
     * Default report width when no report width is provided and 'auto' does not yield a valid width.
     *
     * @var int
     */
    public const DEFAULT_REPORT_WIDTH = 80;
    /**
     * Translation table for config settings which can be changed via multiple CLI flags.
     *
     * If the flag name matches the setting name, there is no need to add it to this translation table.
     * Similarly, if there is only one flag which can change a setting, there is no need to include
     * it in this table, even if the flag name and the setting name don't match.
     *
     * @var array<string, string> Key is the CLI flag name, value the corresponding config setting name.
     */
    public const CLI_FLAGS_TO_SETTING_NAME = ['n' => 'warningSeverity', 'w' => 'warningSeverity', 'warning-severity' => 'warningSeverity', 'no-colors' => 'colors', 'no-cache' => 'cache'];
    /**
     * A list of valid generators.
     *
     * @var array<string, string> Keys are the lowercase version of the generator name, while values
     *                            are the name of the associated PHP generator class.
     */
    private const VALID_GENERATORS = ['text' => 'Text', 'html' => 'HTML', 'markdown' => 'Markdown'];
    /**
     * The default configuration file names supported by PHPCS.
     *
     * @var array<string> The supported file names in order of precedence (highest first).
     */
    private const CONFIG_FILENAMES = ['.phpcs.xml', 'phpcs.xml', '.phpcs.xml.dist', 'phpcs.xml.dist'];
    /**
     * An array of settings that PHPCS and PHPCBF accept.
     *
     * This array is not meant to be accessed directly. Instead, use the settings
     * as if they are class member vars so the __get() and __set() magic methods
     * can be used to validate the values. For example, to set the verbosity level to
     * level 2, use $this->verbosity = 2; instead of accessing this property directly.
     *
     * Each of these settings is described in the class comment property list.
     *
     * @var array<string, mixed>
     */
    private $settings = ['files' => null, 'standards' => null, 'verbosity' => null, 'interactive' => null, 'parallel' => null, 'cache' => null, 'cacheFile' => null, 'colors' => null, 'explain' => null, 'local' => null, 'showSources' => null, 'showProgress' => null, 'quiet' => null, 'annotations' => null, 'tabWidth' => null, 'encoding' => null, 'extensions' => null, 'sniffs' => null, 'exclude' => null, 'ignored' => null, 'reportFile' => null, 'generator' => null, 'filter' => null, 'bootstrap' => null, 'reports' => null, 'basepath' => null, 'reportWidth' => null, 'errorSeverity' => null, 'warningSeverity' => null, 'recordErrors' => null, 'suffix' => null, 'stdin' => null, 'stdinContent' => null, 'stdinPath' => null, 'trackTime' => null, 'unknown' => null];
    /**
     * Whether or not to kill the process when an unknown command line arg is found.
     *
     * If FALSE, arguments that are not command line options or file/directory paths
     * will be ignored and execution will continue. These values will be stored in
     * $this->unknown.
     *
     * @var boolean
     */
    public $dieOnUnknownArg;
    /**
     * The current command line arguments we are processing.
     *
     * @var string[]
     */
    private $cliArgs = [];
    /**
     * Command line values that the user has supplied directly.
     *
     * @var array<string, true|array<string, true>>
     */
    private $overriddenDefaults = [];
    /**
     * Config file data that has been loaded for the run.
     *
     * @var array<string, string>
     */
    private static $configData = null;
    /**
     * The full path to the config data file that has been loaded.
     *
     * @var string
     */
    private static $configDataFile = null;
    /**
     * Automatically discovered executable utility paths.
     *
     * @var array<string, string>
     */
    private static $executablePaths = [];
    /**
     * Get the value of an inaccessible property.
     *
     * @param string $name The name of the property.
     *
     * @return mixed
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the setting name is invalid.
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->settings) === \false) {
            throw new RuntimeException("ERROR: unable to get value of property \"{$name}\"");
        }
        // Figure out what the terminal width needs to be for "auto".
        if ($name === 'reportWidth' && $this->settings[$name] === 'auto') {
            if (function_exists('shell_exec') === \true) {
                $dimensions = shell_exec('stty size 2>&1');
                if (is_string($dimensions) === \true && preg_match('|\d+ (\d+)|', $dimensions, $matches) === 1) {
                    $this->settings[$name] = (int) $matches[1];
                }
            }
            if ($this->settings[$name] === 'auto') {
                // If shell_exec wasn't available or didn't yield a usable value, set to the default.
                // This will prevent subsequent retrievals of the reportWidth from making another call to stty.
                $this->settings[$name] = self::DEFAULT_REPORT_WIDTH;
            }
        }
        return $this->settings[$name];
    }
    /**
     * Set the value of an inaccessible property.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value of the property.
     *
     * @return void
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the setting name is invalid.
     */
    public function __set(string $name, $value)
    {
        if (array_key_exists($name, $this->settings) === \false) {
            throw new RuntimeException("Can't __set() {$name}; setting doesn't exist");
        }
        switch ($name) {
            case 'reportWidth':
                if (is_string($value) === \true && $value === 'auto') {
                    // Nothing to do. Leave at 'auto'.
                    break;
                }
                if (is_int($value) === \true) {
                    $value = abs($value);
                } elseif (is_string($value) === \true && preg_match('`^\d+$`', $value) === 1) {
                    $value = (int) $value;
                } else {
                    $value = self::DEFAULT_REPORT_WIDTH;
                }
                break;
            case 'standards':
                $cleaned = [];
                // Check if the standard name is valid, or if the case is invalid.
                $installedStandards = Standards::getInstalledStandards();
                foreach ($value as $standard) {
                    foreach ($installedStandards as $validStandard) {
                        if (strtolower($standard) === strtolower($validStandard)) {
                            $standard = $validStandard;
                            break;
                        }
                    }
                    $cleaned[] = $standard;
                }
                $value = $cleaned;
                break;
            // Only track time when explicitly needed.
            case 'verbosity':
                if ($value > 2) {
                    $this->settings['trackTime'] = \true;
                }
                break;
            case 'reports':
                $reports = array_change_key_case($value, \CASE_LOWER);
                if (array_key_exists('performance', $reports) === \true) {
                    $this->settings['trackTime'] = \true;
                }
                break;
            default:
                // No validation required.
                break;
        }
        $this->settings[$name] = $value;
    }
    /**
     * Check if the value of an inaccessible property is set.
     *
     * @param string $name The name of the property.
     *
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset($this->settings[$name]);
    }
    /**
     * Unset the value of an inaccessible property.
     *
     * @param string $name The name of the property.
     *
     * @return void
     */
    public function __unset(string $name)
    {
        $this->settings[$name] = null;
    }
    /**
     * Get the array of all config settings.
     *
     * @return array<string, mixed>
     */
    public function getSettings()
    {
        return $this->settings;
    }
    /**
     * Set the array of all config settings.
     *
     * @param array<string, mixed> $settings The array of config settings.
     *
     * @return void
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }
    /**
     * Creates a Config object and populates it with command line values.
     *
     * @param array $cliArgs         An array of values gathered from CLI args.
     * @param bool  $dieOnUnknownArg Whether or not to kill the process when an
     *                               unknown command line arg is found.
     *
     * @return void
     */
    public function __construct(array $cliArgs = [], bool $dieOnUnknownArg = \true)
    {
        if (defined('PHP_CODESNIFFER_IN_TESTS') === \true) {
            // Let everything through during testing so that we can
            // make use of PHPUnit command line arguments as well.
            $this->dieOnUnknownArg = \false;
        } else {
            $this->dieOnUnknownArg = $dieOnUnknownArg;
        }
        if (empty($cliArgs) === \true) {
            $cliArgs = $_SERVER['argv'];
            array_shift($cliArgs);
        }
        $this->restoreDefaults();
        $this->setCommandLineValues($cliArgs);
        if (isset($this->overriddenDefaults['standards']) === \false) {
            // They did not supply a standard to use.
            // Look for a default ruleset in the current directory or higher.
            $currentDir = getcwd();
            do {
                foreach (self::CONFIG_FILENAMES as $defaultFilename) {
                    $default = $currentDir . \DIRECTORY_SEPARATOR . $defaultFilename;
                    if (is_file($default) === \true) {
                        $this->standards = [$default];
                        break 2;
                    }
                }
                $lastDir = $currentDir;
                $currentDir = dirname($currentDir);
            } while ($currentDir !== '.' && $currentDir !== $lastDir && Common::isReadable($currentDir) === \true);
        }
        if (defined('STDIN') === \false || \PHP_OS_FAMILY === 'Windows') {
            return;
        }
        $handle = fopen('php://stdin', 'r');
        // Check for content on STDIN.
        if ($this->stdin === \true || Common::isStdinATTY() === \false && feof($handle) === \false) {
            $readStreams = [$handle];
            $writeSteams = null;
            $fileContents = '';
            while (is_resource($handle) === \true && feof($handle) === \false) {
                // Set a timeout of 200ms.
                if (stream_select($readStreams, $writeSteams, $writeSteams, 0, 200000) === 0) {
                    break;
                }
                $fileContents .= fgets($handle);
            }
            if (trim($fileContents) !== '') {
                $this->stdin = \true;
                $this->stdinContent = $fileContents;
                $this->overriddenDefaults['stdin'] = \true;
                $this->overriddenDefaults['stdinContent'] = \true;
            }
        }
        fclose($handle);
    }
    /**
     * Set the command line values.
     *
     * @param array $args An array of command line arguments to set.
     *
     * @return void
     */
    public function setCommandLineValues(array $args)
    {
        $this->cliArgs = $args;
        $numArgs = count($args);
        for ($i = 0; $i < $numArgs; $i++) {
            $arg = $this->cliArgs[$i];
            if ($arg === '') {
                continue;
            }
            if ($arg[0] === '-') {
                if ($arg === '-') {
                    // Asking to read from STDIN.
                    $this->stdin = \true;
                    $this->overriddenDefaults['stdin'] = \true;
                    continue;
                }
                if ($arg === '--') {
                    // Empty argument, ignore it.
                    continue;
                }
                if ($arg[1] === '-') {
                    $this->processLongArgument((string) substr($arg, 2), $i);
                } else {
                    $switches = str_split($arg);
                    foreach ($switches as $switch) {
                        if ($switch === '-') {
                            continue;
                        }
                        $this->processShortArgument($switch, $i);
                    }
                }
            } else {
                $this->processUnknownArgument($arg, $i);
            }
        }
    }
    /**
     * Restore default values for all possible command line arguments.
     *
     * @return void
     */
    public function restoreDefaults()
    {
        $this->files = [];
        $this->standards = ['PSR12'];
        $this->verbosity = 0;
        $this->interactive = \false;
        $this->cache = \false;
        $this->cacheFile = null;
        $this->colors = \false;
        $this->explain = \false;
        $this->local = \false;
        $this->showSources = \false;
        $this->showProgress = \false;
        $this->quiet = \false;
        $this->annotations = \true;
        $this->parallel = 1;
        $this->tabWidth = 0;
        $this->encoding = 'utf-8';
        $this->extensions = ['php' => 'PHP', 'inc' => 'PHP'];
        $this->sniffs = [];
        $this->exclude = [];
        $this->ignored = [];
        $this->reportFile = null;
        $this->generator = null;
        $this->filter = null;
        $this->bootstrap = [];
        $this->basepath = null;
        $this->reports = ['full' => null];
        $this->reportWidth = 'auto';
        $this->errorSeverity = 5;
        $this->warningSeverity = 5;
        $this->recordErrors = \true;
        $this->suffix = '';
        $this->stdin = \false;
        $this->stdinContent = null;
        $this->stdinPath = null;
        $this->trackTime = \false;
        $this->unknown = [];
        $standard = self::getConfigData('default_standard');
        if ($standard !== null) {
            $this->standards = explode(',', $standard);
        }
        $reportFormat = self::getConfigData('report_format');
        if ($reportFormat !== null) {
            $this->reports = [$reportFormat => null];
        }
        $tabWidth = self::getConfigData('tab_width');
        if ($tabWidth !== null) {
            $this->tabWidth = (int) $tabWidth;
        }
        $encoding = self::getConfigData('encoding');
        if ($encoding !== null) {
            $this->encoding = strtolower($encoding);
        }
        $severity = self::getConfigData('severity');
        if ($severity !== null) {
            $this->errorSeverity = (int) $severity;
            $this->warningSeverity = (int) $severity;
        }
        $severity = self::getConfigData('error_severity');
        if ($severity !== null) {
            $this->errorSeverity = (int) $severity;
        }
        $severity = self::getConfigData('warning_severity');
        if ($severity !== null) {
            $this->warningSeverity = (int) $severity;
        }
        $showWarnings = self::getConfigData('show_warnings');
        if ($showWarnings !== null) {
            $showWarnings = (bool) $showWarnings;
            if ($showWarnings === \false) {
                $this->warningSeverity = 0;
            }
        }
        $reportWidth = self::getConfigData('report_width');
        if ($reportWidth !== null) {
            $this->reportWidth = $reportWidth;
        }
        $showProgress = self::getConfigData('show_progress');
        if ($showProgress !== null) {
            $this->showProgress = (bool) $showProgress;
        }
        $quiet = self::getConfigData('quiet');
        if ($quiet !== null) {
            $this->quiet = (bool) $quiet;
        }
        $colors = self::getConfigData('colors');
        if ($colors !== null) {
            $this->colors = (bool) $colors;
        }
        $cache = self::getConfigData('cache');
        if ($cache !== null) {
            $this->cache = (bool) $cache;
        }
        $parallel = self::getConfigData('parallel');
        if ($parallel !== null) {
            $this->parallel = max((int) $parallel, 1);
        }
    }
    /**
     * Processes a short (-e) command line argument.
     *
     * @param string $arg The command line argument.
     * @param int    $pos The position of the argument on the command line.
     *
     * @return void
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    public function processShortArgument(string $arg, int $pos)
    {
        switch ($arg) {
            case 'h':
            case '?':
                $this->printUsage();
                throw new DeepExitException('', ExitCode::OKAY);
            case 'i':
                $output = Standards::prepareInstalledStandardsForDisplay() . \PHP_EOL;
                throw new DeepExitException($output, ExitCode::OKAY);
            case 'v':
                if ($this->quiet === \true) {
                    // Ignore when quiet mode is enabled.
                    break;
                }
                $this->verbosity++;
                $this->overriddenDefaults['verbosity'] = \true;
                break;
            case 'l':
                $this->local = \true;
                $this->overriddenDefaults['local'] = \true;
                break;
            case 's':
                $this->showSources = \true;
                $this->overriddenDefaults['showSources'] = \true;
                break;
            case 'a':
                $this->interactive = \true;
                $this->overriddenDefaults['interactive'] = \true;
                break;
            case 'e':
                $this->explain = \true;
                $this->overriddenDefaults['explain'] = \true;
                break;
            case 'p':
                if ($this->quiet === \true) {
                    // Ignore when quiet mode is enabled.
                    break;
                }
                $this->showProgress = \true;
                $this->overriddenDefaults['showProgress'] = \true;
                break;
            case 'q':
                // Quiet mode disables a few other settings as well.
                $this->quiet = \true;
                $this->showProgress = \false;
                $this->verbosity = 0;
                $this->overriddenDefaults['quiet'] = \true;
                break;
            case 'm':
                $this->recordErrors = \false;
                $this->overriddenDefaults['recordErrors'] = \true;
                break;
            case 'd':
                $ini = explode('=', $this->cliArgs[$pos + 1]);
                $this->cliArgs[$pos + 1] = '';
                if (isset($ini[1]) === \false) {
                    // Set to true.
                    $ini[1] = '1';
                }
                $current = ini_get($ini[0]);
                if ($current === \false) {
                    // Ini setting which doesn't exist, or is from an unavailable extension.
                    // Silently ignore it.
                    break;
                }
                $changed = ini_set($ini[0], $ini[1]);
                if ($changed === \false && ini_get($ini[0]) !== $ini[1]) {
                    $error = sprintf('ERROR: Ini option "%s" cannot be changed at runtime.', $ini[0]) . \PHP_EOL;
                    $error .= $this->printShortUsage(\true);
                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                }
                break;
            case 'n':
                if (isset($this->overriddenDefaults['warningSeverity']) === \false) {
                    $this->warningSeverity = 0;
                    $this->overriddenDefaults['warningSeverity'] = \true;
                }
                break;
            case 'w':
                if (isset($this->overriddenDefaults['warningSeverity']) === \false) {
                    $this->warningSeverity = $this->errorSeverity;
                    $this->overriddenDefaults['warningSeverity'] = \true;
                }
                break;
            default:
                if ($this->dieOnUnknownArg === \false) {
                    $unknown = $this->unknown;
                    $unknown[] = $arg;
                    $this->unknown = $unknown;
                } else {
                    $this->processUnknownArgument('-' . $arg, $pos);
                }
        }
    }
    /**
     * Processes a long (--example) command-line argument.
     *
     * @param string $arg The command line argument.
     * @param int    $pos The position of the argument on the command line.
     *
     * @return void
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    public function processLongArgument(string $arg, int $pos)
    {
        switch ($arg) {
            case 'help':
                $this->printUsage();
                throw new DeepExitException('', ExitCode::OKAY);
            case 'version':
                $output = 'PHP_CodeSniffer version ' . self::VERSION . ' (' . self::STABILITY . ') ';
                $output .= 'by Squiz and PHPCSStandards' . \PHP_EOL;
                throw new DeepExitException($output, ExitCode::OKAY);
            case 'colors':
                if (isset($this->overriddenDefaults['colors']) === \true) {
                    break;
                }
                $this->colors = \true;
                $this->overriddenDefaults['colors'] = \true;
                break;
            case 'no-colors':
                if (isset($this->overriddenDefaults['colors']) === \true) {
                    break;
                }
                $this->colors = \false;
                $this->overriddenDefaults['colors'] = \true;
                break;
            case 'cache':
                if (isset($this->overriddenDefaults['cache']) === \true) {
                    break;
                }
                $this->cache = \true;
                $this->overriddenDefaults['cache'] = \true;
                break;
            case 'no-cache':
                if (isset($this->overriddenDefaults['cache']) === \true) {
                    break;
                }
                $this->cache = \false;
                $this->overriddenDefaults['cache'] = \true;
                break;
            case 'ignore-annotations':
                if (isset($this->overriddenDefaults['annotations']) === \true) {
                    break;
                }
                $this->annotations = \false;
                $this->overriddenDefaults['annotations'] = \true;
                break;
            case 'config-set':
                if (isset($this->cliArgs[$pos + 1]) === \false || isset($this->cliArgs[$pos + 2]) === \false) {
                    $error = 'ERROR: Setting a config option requires a name and value' . \PHP_EOL . \PHP_EOL;
                    $error .= $this->printShortUsage(\true);
                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                }
                $key = $this->cliArgs[$pos + 1];
                $value = $this->cliArgs[$pos + 2];
                $current = self::getConfigData($key);
                try {
                    $this->setConfigData($key, $value);
                } catch (Exception $e) {
                    throw new DeepExitException($e->getMessage() . \PHP_EOL, ExitCode::PROCESS_ERROR);
                }
                $output = 'Using config file: ' . self::$configDataFile . \PHP_EOL . \PHP_EOL;
                if ($current === null) {
                    $output .= "Config value \"{$key}\" added successfully" . \PHP_EOL;
                } else {
                    $output .= "Config value \"{$key}\" updated successfully; old value was \"{$current}\"" . \PHP_EOL;
                }
                throw new DeepExitException($output, ExitCode::OKAY);
            case 'config-delete':
                if (isset($this->cliArgs[$pos + 1]) === \false) {
                    $error = 'ERROR: Deleting a config option requires the name of the option' . \PHP_EOL . \PHP_EOL;
                    $error .= $this->printShortUsage(\true);
                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                }
                $output = 'Using config file: ' . self::$configDataFile . \PHP_EOL . \PHP_EOL;
                $key = $this->cliArgs[$pos + 1];
                $current = self::getConfigData($key);
                if ($current === null) {
                    $output .= "Config value \"{$key}\" has not been set" . \PHP_EOL;
                } else {
                    try {
                        $this->setConfigData($key, null);
                    } catch (Exception $e) {
                        throw new DeepExitException($e->getMessage() . \PHP_EOL, ExitCode::PROCESS_ERROR);
                    }
                    $output .= "Config value \"{$key}\" removed successfully; old value was \"{$current}\"" . \PHP_EOL;
                }
                throw new DeepExitException($output, ExitCode::OKAY);
            case 'config-show':
                $data = self::getAllConfigData();
                $output = 'Using config file: ' . self::$configDataFile . \PHP_EOL . \PHP_EOL;
                $output .= $this->prepareConfigDataForDisplay($data);
                throw new DeepExitException($output, ExitCode::OKAY);
            case 'runtime-set':
                if (isset($this->cliArgs[$pos + 1]) === \false || isset($this->cliArgs[$pos + 2]) === \false) {
                    $error = 'ERROR: Setting a runtime config option requires a name and value' . \PHP_EOL . \PHP_EOL;
                    $error .= $this->printShortUsage(\true);
                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                }
                $key = $this->cliArgs[$pos + 1];
                $value = $this->cliArgs[$pos + 2];
                $this->cliArgs[$pos + 1] = '';
                $this->cliArgs[$pos + 2] = '';
                $this->setConfigData($key, $value, \true);
                if (isset($this->overriddenDefaults['runtime-set']) === \false) {
                    $this->overriddenDefaults['runtime-set'] = [];
                }
                $this->overriddenDefaults['runtime-set'][$key] = \true;
                break;
            default:
                if (substr($arg, 0, 7) === 'sniffs=') {
                    if (isset($this->overriddenDefaults['sniffs']) === \true) {
                        break;
                    }
                    $this->sniffs = $this->parseSniffCodes((string) substr($arg, 7), 'sniffs');
                    $this->overriddenDefaults['sniffs'] = \true;
                } elseif (substr($arg, 0, 8) === 'exclude=') {
                    if (isset($this->overriddenDefaults['exclude']) === \true) {
                        break;
                    }
                    $this->exclude = $this->parseSniffCodes((string) substr($arg, 8), 'exclude');
                    $this->overriddenDefaults['exclude'] = \true;
                } elseif (substr($arg, 0, 6) === 'cache=') {
                    if (isset($this->overriddenDefaults['cache']) === \true && $this->cache === \false || isset($this->overriddenDefaults['cacheFile']) === \true) {
                        break;
                    }
                    // Turn caching on.
                    $this->cache = \true;
                    $this->overriddenDefaults['cache'] = \true;
                    $this->cacheFile = Common::realpath((string) substr($arg, 6));
                    // It may not exist and return false instead.
                    if ($this->cacheFile === \false) {
                        $this->cacheFile = (string) substr($arg, 6);
                        $dir = dirname($this->cacheFile);
                        if (is_dir($dir) === \false) {
                            $error = 'ERROR: The specified cache file path "' . $this->cacheFile . '" points to a non-existent directory' . \PHP_EOL . \PHP_EOL;
                            $error .= $this->printShortUsage(\true);
                            throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                        }
                        if ($dir === '.') {
                            // Passed cache file is a file in the current directory.
                            $this->cacheFile = getcwd() . '/' . basename($this->cacheFile);
                        } else {
                            if ($dir[0] === '/') {
                                // An absolute path.
                                $dir = Common::realpath($dir);
                            } else {
                                $dir = Common::realpath(getcwd() . '/' . $dir);
                            }
                            if ($dir !== \false) {
                                // Cache file path is relative.
                                $this->cacheFile = $dir . '/' . basename($this->cacheFile);
                            }
                        }
                    }
                    $this->overriddenDefaults['cacheFile'] = \true;
                    if (is_dir($this->cacheFile) === \true) {
                        $error = 'ERROR: The specified cache file path "' . $this->cacheFile . '" is a directory' . \PHP_EOL . \PHP_EOL;
                        $error .= $this->printShortUsage(\true);
                        throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                    }
                } elseif (substr($arg, 0, 10) === 'bootstrap=') {
                    $files = explode(',', (string) substr($arg, 10));
                    $bootstrap = [];
                    foreach ($files as $file) {
                        $path = Common::realpath($file);
                        if ($path === \false) {
                            $error = 'ERROR: The specified bootstrap file "' . $file . '" does not exist' . \PHP_EOL . \PHP_EOL;
                            $error .= $this->printShortUsage(\true);
                            throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                        }
                        $bootstrap[] = $path;
                    }
                    $this->bootstrap = array_merge($this->bootstrap, $bootstrap);
                    $this->overriddenDefaults['bootstrap'] = \true;
                } elseif (substr($arg, 0, 10) === 'file-list=') {
                    $fileList = (string) substr($arg, 10);
                    $path = Common::realpath($fileList);
                    if ($path === \false) {
                        $error = 'ERROR: The specified file list "' . $fileList . '" does not exist' . \PHP_EOL . \PHP_EOL;
                        $error .= $this->printShortUsage(\true);
                        throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                    }
                    $files = file($path);
                    foreach ($files as $inputFile) {
                        $inputFile = trim($inputFile);
                        // Skip empty lines.
                        if ($inputFile === '') {
                            continue;
                        }
                        $this->processFilePath($inputFile);
                    }
                } elseif (substr($arg, 0, 11) === 'stdin-path=') {
                    if (isset($this->overriddenDefaults['stdinPath']) === \true) {
                        break;
                    }
                    $this->stdinPath = Common::realpath((string) substr($arg, 11));
                    // It may not exist and return false instead, so use whatever they gave us.
                    if ($this->stdinPath === \false) {
                        $this->stdinPath = trim((string) substr($arg, 11));
                    }
                    $this->overriddenDefaults['stdinPath'] = \true;
                } elseif (substr($arg, 0, 12) === 'report-file=') {
                    if (\PHP_CODESNIFFER_CBF === \true || isset($this->overriddenDefaults['reportFile']) === \true) {
                        break;
                    }
                    $this->reportFile = Common::realpath((string) substr($arg, 12));
                    // It may not exist and return false instead.
                    if ($this->reportFile === \false) {
                        $this->reportFile = (string) substr($arg, 12);
                        $dir = Common::realpath(dirname($this->reportFile));
                        if (is_dir($dir) === \false) {
                            $error = 'ERROR: The specified report file path "' . $this->reportFile . '" points to a non-existent directory' . \PHP_EOL . \PHP_EOL;
                            $error .= $this->printShortUsage(\true);
                            throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                        }
                        $this->reportFile = $dir . '/' . basename($this->reportFile);
                    }
                    $this->overriddenDefaults['reportFile'] = \true;
                    if (is_dir($this->reportFile) === \true) {
                        $error = 'ERROR: The specified report file path "' . $this->reportFile . '" is a directory' . \PHP_EOL . \PHP_EOL;
                        $error .= $this->printShortUsage(\true);
                        throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                    }
                } elseif (substr($arg, 0, 13) === 'report-width=') {
                    if (isset($this->overriddenDefaults['reportWidth']) === \true) {
                        break;
                    }
                    $this->reportWidth = (string) substr($arg, 13);
                    $this->overriddenDefaults['reportWidth'] = \true;
                } elseif (substr($arg, 0, 9) === 'basepath=') {
                    if (isset($this->overriddenDefaults['basepath']) === \true) {
                        break;
                    }
                    $this->overriddenDefaults['basepath'] = \true;
                    if ((string) substr($arg, 9) === '') {
                        $this->basepath = null;
                        break;
                    }
                    $basepath = Common::realpath((string) substr($arg, 9));
                    // It may not exist and return false instead.
                    if ($basepath === \false) {
                        $this->basepath = (string) substr($arg, 9);
                    } else {
                        $this->basepath = $basepath;
                    }
                    if (is_dir($this->basepath) === \false) {
                        $error = 'ERROR: The specified basepath "' . $this->basepath . '" points to a non-existent directory' . \PHP_EOL . \PHP_EOL;
                        $error .= $this->printShortUsage(\true);
                        throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                    }
                } elseif (substr($arg, 0, 7) === 'report=' || substr($arg, 0, 7) === 'report-') {
                    $reports = [];
                    if ($arg[6] === '-') {
                        // This is a report with file output.
                        $split = strpos($arg, '=');
                        if ($split === \false) {
                            $report = (string) substr($arg, 7);
                            $output = null;
                        } else {
                            $report = (string) substr($arg, 7, $split - 7);
                            $output = (string) substr($arg, $split + 1);
                            if ($output === \false) {
                                $output = null;
                            } else {
                                $dir = Common::realpath(dirname($output));
                                if (is_dir($dir) === \false) {
                                    $error = 'ERROR: The specified ' . $report . ' report file path "' . $output . '" points to a non-existent directory' . \PHP_EOL . \PHP_EOL;
                                    $error .= $this->printShortUsage(\true);
                                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                                }
                                $output = $dir . '/' . basename($output);
                                if (is_dir($output) === \true) {
                                    $error = 'ERROR: The specified ' . $report . ' report file path "' . $output . '" is a directory' . \PHP_EOL . \PHP_EOL;
                                    $error .= $this->printShortUsage(\true);
                                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                                }
                            }
                        }
                        $reports[$report] = $output;
                    } else {
                        // This is a single report.
                        if (isset($this->overriddenDefaults['reports']) === \true) {
                            break;
                        }
                        $reportNames = explode(',', (string) substr($arg, 7));
                        foreach ($reportNames as $report) {
                            $reports[$report] = null;
                        }
                    }
                    // Remove the default value so the CLI value overrides it.
                    if (isset($this->overriddenDefaults['reports']) === \false) {
                        $this->reports = $reports;
                    } else {
                        $this->reports = array_merge($this->reports, $reports);
                    }
                    $this->overriddenDefaults['reports'] = \true;
                } elseif (substr($arg, 0, 7) === 'filter=') {
                    if (isset($this->overriddenDefaults['filter']) === \true) {
                        break;
                    }
                    $this->filter = (string) substr($arg, 7);
                    $this->overriddenDefaults['filter'] = \true;
                } elseif (substr($arg, 0, 9) === 'standard=') {
                    $standards = trim((string) substr($arg, 9));
                    if ($standards !== '') {
                        $this->standards = explode(',', $standards);
                    }
                    $this->overriddenDefaults['standards'] = \true;
                } elseif (substr($arg, 0, 11) === 'extensions=') {
                    if (isset($this->overriddenDefaults['extensions']) === \true) {
                        break;
                    }
                    $extensionsString = (string) substr($arg, 11);
                    $newExtensions = [];
                    if (empty($extensionsString) === \false) {
                        $extensions = explode(',', $extensionsString);
                        foreach ($extensions as $ext) {
                            if (strpos($ext, '/') !== \false) {
                                // They specified the tokenizer too.
                                list($ext, $tokenizer) = explode('/', $ext);
                                if (strtoupper($tokenizer) !== 'PHP') {
                                    $error = 'ERROR: Specifying the tokenizer to use for an extension is no longer supported.' . \PHP_EOL;
                                    $error .= 'PHP_CodeSniffer >= 4.0 only supports scanning PHP files.' . \PHP_EOL;
                                    $error .= 'Received: ' . substr($arg, 11) . \PHP_EOL . \PHP_EOL;
                                    $error .= $this->printShortUsage(\true);
                                    throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                                }
                            }
                            $newExtensions[$ext] = 'PHP';
                        }
                    }
                    $this->extensions = $newExtensions;
                    $this->overriddenDefaults['extensions'] = \true;
                } elseif (substr($arg, 0, 7) === 'suffix=') {
                    if (isset($this->overriddenDefaults['suffix']) === \true) {
                        break;
                    }
                    $this->suffix = (string) substr($arg, 7);
                    $this->overriddenDefaults['suffix'] = \true;
                } elseif (substr($arg, 0, 9) === 'parallel=') {
                    if (isset($this->overriddenDefaults['parallel']) === \true) {
                        break;
                    }
                    $this->parallel = max((int) substr($arg, 9), 1);
                    $this->overriddenDefaults['parallel'] = \true;
                } elseif (substr($arg, 0, 9) === 'severity=') {
                    $this->errorSeverity = (int) substr($arg, 9);
                    $this->warningSeverity = $this->errorSeverity;
                    if (isset($this->overriddenDefaults['errorSeverity']) === \false) {
                        $this->overriddenDefaults['errorSeverity'] = \true;
                    }
                    if (isset($this->overriddenDefaults['warningSeverity']) === \false) {
                        $this->overriddenDefaults['warningSeverity'] = \true;
                    }
                } elseif (substr($arg, 0, 15) === 'error-severity=') {
                    if (isset($this->overriddenDefaults['errorSeverity']) === \true) {
                        break;
                    }
                    $this->errorSeverity = (int) substr($arg, 15);
                    $this->overriddenDefaults['errorSeverity'] = \true;
                } elseif (substr($arg, 0, 17) === 'warning-severity=') {
                    if (isset($this->overriddenDefaults['warningSeverity']) === \true) {
                        break;
                    }
                    $this->warningSeverity = (int) substr($arg, 17);
                    $this->overriddenDefaults['warningSeverity'] = \true;
                } elseif (substr($arg, 0, 7) === 'ignore=') {
                    if (isset($this->overriddenDefaults['ignored']) === \true) {
                        break;
                    }
                    // Split the ignore string on commas, unless the comma is escaped
                    // using 1 or 3 slashes (\, or \\\,).
                    $patterns = preg_split('/(?<=(?<!\\\\)\\\\\\\\),|(?<!\\\\),/', (string) substr($arg, 7));
                    $ignored = [];
                    foreach ($patterns as $pattern) {
                        $pattern = trim($pattern);
                        if ($pattern === '') {
                            continue;
                        }
                        $ignored[$pattern] = 'absolute';
                    }
                    $this->ignored = $ignored;
                    $this->overriddenDefaults['ignored'] = \true;
                } elseif (substr($arg, 0, 10) === 'generator=' && \PHP_CODESNIFFER_CBF === \false) {
                    if (isset($this->overriddenDefaults['generator']) === \true) {
                        break;
                    }
                    $generatorName = (string) substr($arg, 10);
                    $lowerCaseGeneratorName = strtolower($generatorName);
                    if (isset(self::VALID_GENERATORS[$lowerCaseGeneratorName]) === \false) {
                        $validOptions = implode(', ', self::VALID_GENERATORS);
                        $validOptions = substr_replace($validOptions, ' and', strrpos($validOptions, ','), 1);
                        $error = sprintf('ERROR: "%s" is not a valid generator. The following generators are supported: %s.' . \PHP_EOL . \PHP_EOL, $generatorName, $validOptions);
                        $error .= $this->printShortUsage(\true);
                        throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
                    }
                    $this->generator = self::VALID_GENERATORS[$lowerCaseGeneratorName];
                    $this->overriddenDefaults['generator'] = \true;
                } elseif (substr($arg, 0, 9) === 'encoding=') {
                    if (isset($this->overriddenDefaults['encoding']) === \true) {
                        break;
                    }
                    $this->encoding = strtolower((string) substr($arg, 9));
                    $this->overriddenDefaults['encoding'] = \true;
                } elseif (substr($arg, 0, 10) === 'tab-width=') {
                    if (isset($this->overriddenDefaults['tabWidth']) === \true) {
                        break;
                    }
                    $this->tabWidth = (int) substr($arg, 10);
                    $this->overriddenDefaults['tabWidth'] = \true;
                } else if ($this->dieOnUnknownArg === \false) {
                    $eqPos = strpos($arg, '=');
                    try {
                        $unknown = $this->unknown;
                        if ($eqPos === \false) {
                            $unknown[$arg] = $arg;
                        } else {
                            $value = (string) substr($arg, $eqPos + 1);
                            $arg = (string) substr($arg, 0, $eqPos);
                            $unknown[$arg] = $value;
                        }
                        $this->unknown = $unknown;
                    } catch (RuntimeException $e) {
                        // Value is not valid, so just ignore it.
                    }
                } else {
                    $this->processUnknownArgument('--' . $arg, $pos);
                }
                break;
        }
    }
    /**
     * Parse supplied string into a list of validated sniff codes.
     *
     * @param string $input    Comma-separated string of sniff codes.
     * @param string $argument The name of the argument which is being processed.
     *
     * @return array<string>
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException When any of the provided codes are not valid as sniff codes.
     */
    private function parseSniffCodes(string $input, string $argument)
    {
        $errors = [];
        $sniffs = [];
        $possibleSniffs = array_filter(explode(',', $input));
        if ($possibleSniffs === []) {
            $errors[] = 'No codes specified / empty argument';
        }
        foreach ($possibleSniffs as $sniff) {
            $sniff = trim($sniff);
            $partCount = substr_count($sniff, '.');
            if ($partCount === 2) {
                // Correct number of parts.
                $sniffs[] = $sniff;
                continue;
            }
            if ($partCount === 0) {
                $errors[] = 'Standard codes are not supported: ' . $sniff;
            } elseif ($partCount === 1) {
                $errors[] = 'Category codes are not supported: ' . $sniff;
            } elseif ($partCount === 3) {
                $errors[] = 'Message codes are not supported: ' . $sniff;
            } else {
                $errors[] = 'Too many parts: ' . $sniff;
            }
            if ($partCount > 2) {
                $parts = explode('.', $sniff, 4);
                $sniffs[] = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
            }
        }
        $sniffs = array_reduce($sniffs, static function ($carry, $item) {
            $lower = strtolower($item);
            foreach ($carry as $found) {
                if ($lower === strtolower($found)) {
                    // This sniff is already in our list.
                    return $carry;
                }
            }
            $carry[] = $item;
            return $carry;
        }, []);
        if ($errors !== []) {
            $error = 'ERROR: The --' . $argument . ' option only supports sniff codes.' . \PHP_EOL;
            $error .= 'Sniff codes are in the form "Standard.Category.Sniff".' . \PHP_EOL;
            $error .= \PHP_EOL;
            $error .= 'The following problems were detected:' . \PHP_EOL;
            $error .= '* ' . implode(\PHP_EOL . '* ', $errors) . \PHP_EOL;
            if ($sniffs !== []) {
                $error .= \PHP_EOL;
                $error .= 'Perhaps try --' . $argument . '="' . implode(',', $sniffs) . '" instead.' . \PHP_EOL;
            }
            $error .= \PHP_EOL;
            $error .= $this->printShortUsage(\true);
            throw new DeepExitException(ltrim($error), ExitCode::PROCESS_ERROR);
        }
        return $sniffs;
    }
    /**
     * Processes an unknown command line argument.
     *
     * Assumes all unknown arguments are files and folders to check.
     *
     * @param string $arg The command line argument.
     * @param int    $pos The position of the argument on the command line.
     *
     * @return void
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    public function processUnknownArgument(string $arg, int $pos)
    {
        // We don't know about any additional switches; just files.
        if ($arg[0] === '-') {
            if ($this->dieOnUnknownArg === \false) {
                return;
            }
            $error = "ERROR: option \"{$arg}\" not known" . \PHP_EOL . \PHP_EOL;
            $error .= $this->printShortUsage(\true);
            throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
        }
        $this->processFilePath($arg);
    }
    /**
     * Processes a file path and add it to the file list.
     *
     * @param string $path The path to the file to add.
     *
     * @return void
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    public function processFilePath(string $path)
    {
        // If we are processing STDIN, don't record any files to check.
        if ($this->stdin === \true) {
            return;
        }
        $file = Common::realpath($path);
        if (file_exists($file) === \false) {
            if ($this->dieOnUnknownArg === \false) {
                return;
            }
            $error = 'ERROR: The file "' . $path . '" does not exist.' . \PHP_EOL . \PHP_EOL;
            $error .= $this->printShortUsage(\true);
            throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
        } else {
            // Can't modify the files array directly because it's not a real
            // class member, so need to use this little get/modify/set trick.
            $files = $this->files;
            $files[] = $file;
            $this->files = $files;
            $this->overriddenDefaults['files'] = \true;
        }
    }
    /**
     * Prints out the usage information for this script.
     *
     * @return void
     */
    public function printUsage()
    {
        echo \PHP_EOL;
        if (\PHP_CODESNIFFER_CBF === \true) {
            $this->printPHPCBFUsage();
        } else {
            $this->printPHPCSUsage();
        }
        echo \PHP_EOL;
    }
    /**
     * Prints out the short usage information for this script.
     *
     * @param bool $returnOutput If TRUE, the usage string is returned
     *                           instead of output to screen.
     *
     * @return string|void
     */
    public function printShortUsage(bool $returnOutput = \false)
    {
        if (\PHP_CODESNIFFER_CBF === \true) {
            $usage = 'Run "phpcbf --help" for usage information';
        } else {
            $usage = 'Run "phpcs --help" for usage information';
        }
        $usage .= \PHP_EOL . \PHP_EOL;
        if ($returnOutput === \true) {
            return $usage;
        }
        echo $usage;
    }
    /**
     * Prints out the usage information for PHPCS.
     *
     * @return void
     */
    public function printPHPCSUsage()
    {
        $longOptions = Help::DEFAULT_LONG_OPTIONS;
        $longOptions[] = 'cache';
        $longOptions[] = 'no-cache';
        $longOptions[] = 'report';
        $longOptions[] = 'report-file';
        $longOptions[] = 'report-report';
        $longOptions[] = 'config-explain';
        $longOptions[] = 'config-set';
        $longOptions[] = 'config-delete';
        $longOptions[] = 'config-show';
        $longOptions[] = 'generator';
        $shortOptions = Help::DEFAULT_SHORT_OPTIONS . 'aems';
        (new Help($this, $longOptions, $shortOptions))->display();
    }
    /**
     * Prints out the usage information for PHPCBF.
     *
     * @return void
     */
    public function printPHPCBFUsage()
    {
        $longOptions = Help::DEFAULT_LONG_OPTIONS;
        $longOptions[] = 'suffix';
        $shortOptions = Help::DEFAULT_SHORT_OPTIONS;
        (new Help($this, $longOptions, $shortOptions))->display();
    }
    /**
     * Get a single config value.
     *
     * @param string $key The name of the config value.
     *
     * @return string|null
     * @see    setConfigData()
     * @see    getAllConfigData()
     */
    public static function getConfigData(string $key)
    {
        $phpCodeSnifferConfig = self::getAllConfigData();
        if ($phpCodeSnifferConfig === null) {
            return null;
        }
        if (isset($phpCodeSnifferConfig[$key]) === \false) {
            return null;
        }
        return $phpCodeSnifferConfig[$key];
    }
    /**
     * Get the path to an executable utility.
     *
     * @param string $name The name of the executable utility.
     *
     * @return string|null
     * @see    getConfigData()
     */
    public static function getExecutablePath(string $name)
    {
        $data = self::getConfigData($name . '_path');
        if ($data !== null) {
            return $data;
        }
        if ($name === 'php') {
            // For php, we know the executable path. There's no need to look it up.
            return \PHP_BINARY;
        }
        if (array_key_exists($name, self::$executablePaths) === \true) {
            return self::$executablePaths[$name];
        }
        if (\PHP_OS_FAMILY === 'Windows') {
            $cmd = 'where ' . escapeshellarg($name) . ' 2> nul';
        } else {
            $cmd = 'which ' . escapeshellarg($name) . ' 2> /dev/null';
        }
        $result = exec($cmd, $output, $retVal);
        if ($retVal !== 0) {
            $result = null;
        }
        self::$executablePaths[$name] = $result;
        return $result;
    }
    /**
     * Set a single config value.
     *
     * @param string      $key   The name of the config value.
     * @param string|null $value The value to set. If null, the config
     *                           entry is deleted, reverting it to the
     *                           default value.
     * @param boolean     $temp  Set this config data temporarily for this
     *                           script run. This will not write the config
     *                           data to the config file.
     *
     * @return bool
     * @see    getConfigData()
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException If the config file can not be written.
     */
    public function setConfigData(string $key, ?string $value, bool $temp = \false)
    {
        if (isset($this->overriddenDefaults['runtime-set']) === \true && isset($this->overriddenDefaults['runtime-set'][$key]) === \true) {
            return \false;
        }
        if ($temp === \false) {
            $path = '';
            if (is_callable('ECSPrefix202610\Phar::running') === \true) {
                $path = Phar::running(\false);
            }
            if ($path !== '') {
                $configFile = dirname($path) . \DIRECTORY_SEPARATOR . 'CodeSniffer.conf';
            } else {
                $configFile = dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'CodeSniffer.conf';
            }
            if (is_file($configFile) === \true && is_writable($configFile) === \false) {
                $error = 'ERROR: Config file ' . $configFile . ' is not writable' . \PHP_EOL . \PHP_EOL;
                throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
            }
        }
        $phpCodeSnifferConfig = self::getAllConfigData();
        if ($value === null) {
            if (isset($phpCodeSnifferConfig[$key]) === \true) {
                unset($phpCodeSnifferConfig[$key]);
            }
        } else {
            $phpCodeSnifferConfig[$key] = $value;
        }
        if ($temp === \false) {
            $output = '<' . '?php' . "\n" . ' $phpCodeSnifferConfig = ';
            $output .= var_export($phpCodeSnifferConfig, \true);
            $output .= ";\n?" . '>';
            if (file_put_contents($configFile, $output) === \false) {
                $error = 'ERROR: Config file ' . $configFile . ' could not be written' . \PHP_EOL . \PHP_EOL;
                throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
            }
            self::$configDataFile = $configFile;
        }
        self::$configData = $phpCodeSnifferConfig;
        // If the installed paths are being set, make sure all known
        // standards paths are added to the autoloader.
        if ($key === 'installed_paths') {
            $installedStandards = Standards::getInstalledStandardDetails();
            foreach ($installedStandards as $details) {
                \PHP_CodeSniffer\Autoload::addSearchPath($details['path'], $details['namespace']);
            }
        }
        return \true;
    }
    /**
     * Get all config data.
     *
     * @return array<string, string>
     * @see    getConfigData()
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException If the config file could not be read.
     */
    public static function getAllConfigData()
    {
        if (self::$configData !== null) {
            return self::$configData;
        }
        $path = '';
        if (is_callable('ECSPrefix202610\Phar::running') === \true) {
            $path = Phar::running(\false);
        }
        if ($path !== '') {
            $configFile = dirname($path) . \DIRECTORY_SEPARATOR . 'CodeSniffer.conf';
        } else {
            $configFile = dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'CodeSniffer.conf';
        }
        if (is_file($configFile) === \false) {
            self::$configData = [];
            return [];
        }
        if (Common::isReadable($configFile) === \false) {
            $error = 'ERROR: Config file ' . $configFile . ' is not readable' . \PHP_EOL . \PHP_EOL;
            throw new DeepExitException($error, ExitCode::PROCESS_ERROR);
        }
        include $configFile;
        self::$configDataFile = $configFile;
        self::$configData = $phpCodeSnifferConfig;
        return self::$configData;
    }
    /**
     * Prepares the gathered config data for display.
     *
     * @param array<string, string> $data The config data to format for display.
     *
     * @return string
     */
    public function prepareConfigDataForDisplay(array $data)
    {
        if (empty($data) === \true) {
            return '';
        }
        $max = 0;
        $keys = array_keys($data);
        foreach ($keys as $key) {
            $len = strlen($key);
            if ($len > $max) {
                $max = $len;
            }
        }
        $max += 2;
        ksort($data);
        $output = '';
        foreach ($data as $name => $value) {
            $output .= str_pad($name . ': ', $max) . $value . \PHP_EOL;
        }
        return $output;
    }
    /**
     * Prints out the gathered config data.
     *
     * @param array<string, string> $data The config data to print.
     *
     * @deprecated 4.0.0 Use `echo Config::prepareConfigDataForDisplay()` instead.
     *
     * @return void
     */
    public function printConfigData(array $data)
    {
        echo $this->prepareConfigDataForDisplay($data);
    }
}
