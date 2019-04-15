<?php
namespace Psalm\Internal\Provider;

use Psalm\Config;

/**
 * @psalm-type  IssueData = array{
 *     severity: string,
 *     line_from: int,
 *     line_to: int,
 *     type: string,
 *     message: string,
 *     file_name: string,
 *     file_path: string,
 *     snippet: string,
 *     from: int,
 *     to: int,
 *     snippet_from: int,
 *     snippet_to: int,
 *     column_from: int,
 *     column_to: int
 * }
 *
 * @psalm-type  TaggedCodeType = array<int, array{0: int, 1: string}>
 */
/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FileReferenceCacheProvider
{
    const REFERENCE_CACHE_NAME = 'references';
    const ANALYZED_METHODS_CACHE_NAME = 'analyzed_methods';
    const CLASS_METHOD_CACHE_NAME = 'class_method_references';
    const FILE_CLASS_MEMBER_CACHE_NAME = 'file_class_member_references';
    const ISSUES_CACHE_NAME = 'issues';
    const FILE_MAPS_CACHE_NAME = 'file_maps';
    const TYPE_COVERAGE_CACHE_NAME = 'type_coverage';
    const CONFIG_HASH_CACHE_NAME = 'config';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var bool
     */
    public $config_changed;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->config_changed = $config->hash !== $this->getConfigHashCache();
        $this->setConfigHashCache($config->hash);
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedFileReferences()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        if (!is_readable($reference_cache_location)) {
            return null;
        }

        $reference_cache = unserialize((string) file_get_contents($reference_cache_location));

        if (!is_array($reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $reference_cache;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedMethodMemberReferences()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        if (!is_readable($class_member_cache_location)) {
            return null;
        }

        $class_member_reference_cache = unserialize((string) file_get_contents($class_member_cache_location));

        if (!is_array($class_member_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $class_member_reference_cache;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedFileMemberReferences()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $file_class_member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_MEMBER_CACHE_NAME;

        if (!is_readable($file_class_member_cache_location)) {
            return null;
        }

        $file_class_member_reference_cache = unserialize((string) file_get_contents($file_class_member_cache_location));

        if (!is_array($file_class_member_reference_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $file_class_member_reference_cache;
    }

    /**
     * @return ?array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedTypeCoercion
     */
    public function getCachedIssues()
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return null;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        if (!is_readable($issues_cache_location)) {
            return null;
        }

        $issues_cache = unserialize((string) file_get_contents($issues_cache_location));

        if (!is_array($issues_cache)) {
            throw new \UnexpectedValueException('The reference cache must be an array');
        }

        return $issues_cache;
    }

    /**
     * @return void
     */
    public function setCachedFileReferences(array $file_references)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $reference_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

        file_put_contents($reference_cache_location, serialize($file_references));
    }

    /**
     * @return void
     */
    public function setCachedMethodMemberReferences(array $member_references)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CLASS_METHOD_CACHE_NAME;

        file_put_contents($member_cache_location, serialize($member_references));
    }

    /**
     * @return void
     */
    public function setCachedFileMemberReferences(array $member_references)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $member_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_CLASS_MEMBER_CACHE_NAME;

        file_put_contents($member_cache_location, serialize($member_references));
    }

    /**
     * @return void
     */
    public function setCachedIssues(array $issues)
    {
        $cache_directory = $this->config->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $issues_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ISSUES_CACHE_NAME;

        file_put_contents($issues_cache_location, serialize($issues));
    }

    /**
     * @return array<string, array<string, int>>|false
     */
    public function getAnalyzedMethodCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $analyzed_methods_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::ANALYZED_METHODS_CACHE_NAME;

        if ($cache_directory
            && file_exists($analyzed_methods_cache_location)
            && !$this->config_changed
        ) {
            /** @var array<string, array<string, int>> */
            return unserialize(file_get_contents($analyzed_methods_cache_location));
        }

        return false;
    }

    /**
     * @param array<string, array<string, int>> $analyzed_methods
     * @return void
     */
    public function setAnalyzedMethodCache(array $analyzed_methods)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $analyzed_methods_cache_location = $cache_directory
                . DIRECTORY_SEPARATOR
                . self::ANALYZED_METHODS_CACHE_NAME;

            file_put_contents(
                $analyzed_methods_cache_location,
                serialize($analyzed_methods)
            );
        }
    }

    /**
     * @return array<string, array{0: TaggedCodeType, 1: TaggedCodeType}>|false
     */
    public function getFileMapCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $file_maps_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MAPS_CACHE_NAME;

        if ($cache_directory
            && file_exists($file_maps_cache_location)
            && !$this->config_changed
        ) {
            /** @var array<string, array{0: TaggedCodeType, 1: TaggedCodeType}> */
            $file_maps_cache = unserialize(file_get_contents($file_maps_cache_location));
            return $file_maps_cache;
        }

        return false;
    }

    /**
     * @param array<string, array{0: TaggedCodeType, 1: TaggedCodeType}> $file_maps
     * @return void
     */
    public function setFileMapCache(array $file_maps)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $file_maps_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::FILE_MAPS_CACHE_NAME;

            file_put_contents(
                $file_maps_cache_location,
                serialize($file_maps)
            );
        }
    }

    /**
     * @return array<string, array{int, int}>|false
     */
    public function getTypeCoverage()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        $type_coverage_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::TYPE_COVERAGE_CACHE_NAME;

        if ($cache_directory
            && file_exists($type_coverage_cache_location)
            && !$this->config_changed
        ) {
            /** @var array<string, array{int, int}> */
            $type_coverage_cache = unserialize(file_get_contents($type_coverage_cache_location));
            return $type_coverage_cache;
        }

        return false;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     * @return void
     */
    public function setTypeCoverage(array $mixed_counts)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $type_coverage_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::TYPE_COVERAGE_CACHE_NAME;

            file_put_contents(
                $type_coverage_cache_location,
                serialize($mixed_counts)
            );
        }
    }

    /**
     * @return string|false
     */
    public function getConfigHashCache()
    {
        $cache_directory = $this->config->getCacheDirectory();

        $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

        if ($cache_directory
            && file_exists($config_hash_cache_location)
        ) {
            /** @var string */
            $file_maps_cache = unserialize(file_get_contents($config_hash_cache_location));
            return $file_maps_cache;
        }

        return false;
    }

    /**
     * @return void
     */
    public function setConfigHashCache(string $hash)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $config_hash_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::CONFIG_HASH_CACHE_NAME;

            file_put_contents(
                $config_hash_cache_location,
                serialize($hash)
            );
        }
    }
}
