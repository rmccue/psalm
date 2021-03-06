<?php
require_once('command_functions.php');

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Config;
use Psalm\IssueBuffer;

// show all errors
error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('memory_limit', '4096M');

// get options from command line
$options = getopt(
    'f:mhr:',
    [
        'help', 'debug', 'debug-by-line', 'config:', 'file:', 'root:',
        'plugin:', 'issues:', 'php-version:', 'dry-run', 'safe-types',
        'find-unused-code', 'threads:',
    ]
);

if (array_key_exists('help', $options)) {
    $options['h'] = false;
}

if (array_key_exists('monochrome', $options)) {
    $options['m'] = false;
}

if (isset($options['config'])) {
    $options['c'] = $options['config'];
}

if (isset($options['c']) && is_array($options['c'])) {
    die('Too many config files provided' . PHP_EOL);
}

if (array_key_exists('h', $options)) {
    echo <<< HELP
Usage:
    psalter [options] [file...]

Options:
    -h, --help
        Display this help message

    --debug, --debug-by-line
        Debug information

    -c, --config=psalm.xml
        Path to a psalm.xml configuration file. Run psalm --init to create one.

    -m, --monochrome
        Enable monochrome output

    -r, --root
        If running Psalm globally you'll need to specify a project root. Defaults to cwd

    --plugin=PATH
        Executes a plugin, an alternative to using the Psalm config

    --dry-run
        Shows a diff of all the changes, without making them

    --safe-types
        Only update PHP types when the new type information comes from other PHP types,
        as opposed to type information that just comes from docblocks

    --php-version=PHP_MAJOR_VERSION.PHP_MINOR_VERSION

    --issues=IssueType1,IssueType2
        If any issues can be fixed automatically, Psalm will update the codebase

    --find-dead-code
        Include unused code as a candidate for removal

    --threads=INT
        If greater than one, Psalm will run analysis on multiple threads, speeding things up.
HELP;

    exit;
}

if (!isset($options['issues']) && (!isset($options['plugin']) || $options['plugin'] === false)) {
    die('Please specify the issues you want to fix with --issues=IssueOne,IssueTwo '
        . 'or provide a plugin that has its own manipulations with --plugin=path/to/plugin.php' . PHP_EOL);
}

if (isset($options['root'])) {
    $options['r'] = $options['root'];
}

$current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;

if (isset($options['r']) && is_string($options['r'])) {
    $root_path = realpath($options['r']);

    if (!$root_path) {
        die('Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL);
    }

    $current_dir = $root_path . DIRECTORY_SEPARATOR;
}

$vendor_dir = getVendorDir($current_dir);

$first_autoloader = requireAutoloaders($current_dir, isset($options['r']), $vendor_dir);

// If XDebug is enabled, restart without it
(new \Composer\XdebugHandler\XdebugHandler('PSALTER'))->check();

$paths_to_check = getPathsToCheck(isset($options['f']) ? $options['f'] : null);

if ($paths_to_check && count($paths_to_check) > 1) {
    die('Psalter can currently only be run on one path at a time' . PHP_EOL);
}

$path_to_config = isset($options['c']) && is_string($options['c']) ? realpath($options['c']) : null;

if ($path_to_config === false) {
    /** @psalm-suppress InvalidCast */
    die('Could not resolve path to config ' . (string)$options['c'] . PHP_EOL);
}

// initialise custom config, if passed
if ($path_to_config) {
    $config = Config::loadFromXMLFile($path_to_config, $current_dir);
} else {
    $config = Config::getConfigForPath($current_dir, $current_dir, ProjectAnalyzer::TYPE_CONSOLE);
}

$config->setComposerClassLoader($first_autoloader);

$threads = isset($options['threads']) ? (int)$options['threads'] : 1;

$project_analyzer = new ProjectAnalyzer(
    $config,
    new Psalm\Internal\Provider\Providers(
        new Psalm\Internal\Provider\FileProvider(),
        new Psalm\Internal\Provider\ParserCacheProvider($config),
        new Psalm\Internal\Provider\FileStorageCacheProvider($config),
        new Psalm\Internal\Provider\ClassLikeStorageCacheProvider($config)
    ),
    !array_key_exists('m', $options),
    false,
    ProjectAnalyzer::TYPE_CONSOLE,
    $threads,
    array_key_exists('debug', $options)
);

if (array_key_exists('debug-by-line', $options)) {
    $project_analyzer->debug_lines = true;
}

$config->visitComposerAutoloadFiles($project_analyzer);

if (array_key_exists('issues', $options)) {
    if (!is_string($options['issues']) || !$options['issues']) {
        die('Expecting a comma-separated list of issues' . PHP_EOL);
    }

    $issues = explode(',', $options['issues']);

    $keyed_issues = [];

    foreach ($issues as $issue) {
        $keyed_issues[$issue] = true;
    }
} else {
    $keyed_issues = [];
}

if (isset($options['php-version'])) {
    if (!is_string($options['php-version'])) {
        die('Expecting a version number in the format x.y' . PHP_EOL);
    }

    $project_analyzer->setPhpVersion($options['php-version']);
}

$plugins = [];

if (isset($options['plugin'])) {
    $plugins = $options['plugin'];

    if (!is_array($plugins)) {
        $plugins = [$plugins];
    }
}

/** @var string $plugin_path */
foreach ($plugins as $plugin_path) {
    Config::getInstance()->addPluginPath($current_dir . $plugin_path);
}

$find_unused_code = array_key_exists('find-unused-code', $options);

if ($config->find_unused_code) {
    $find_unused_code = true;
}

if ($find_unused_code) {
    $project_analyzer->getCodebase()->reportUnusedCode();
}

$project_analyzer->alterCodeAfterCompletion(
    array_key_exists('dry-run', $options),
    array_key_exists('safe-types', $options)
);
$project_analyzer->setIssuesToFix($keyed_issues);

$start_time = microtime(true);

if ($paths_to_check === null) {
    $project_analyzer->check($current_dir);
} elseif ($paths_to_check) {
    foreach ($paths_to_check as $path_to_check) {
        if (is_dir($path_to_check)) {
            $project_analyzer->checkDir($path_to_check);
        } else {
            $project_analyzer->checkFile($path_to_check);
        }
    }
}

IssueBuffer::finish($project_analyzer, false, $start_time);
