<?php
namespace Psalm\Tests;

use Psalm\Type;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;

class TestFrameworkMemoryLeakTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new \Psalm\Internal\Provider\Providers(
            $this->file_provider,
            new \Psalm\Tests\Internal\Provider\FakeParserCacheProvider()
        );

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            $providers,
            false,
            true,
            \Psalm\Internal\Analyzer\ProjectAnalyzer::TYPE_CONSOLE,
            1,
            false
        );
    }

    /**
     * @return void
     */
    public function testOnce()
    {
        $file_path = 'somefile.php';
        $context = new Context();

        $this->addFile(
            $file_path,
            '<?php
                class A {}'
        );

        $this->analyzeFile($file_path, $context);

    }

    /**
     * @return void
     */
    public function testExtends()
    {
        $file_path = 'somefile.php';
        $context = new Context();

        $this->addFile(
            $file_path,
            '<?php
                class A {}
                class B extends A {}'
        );

        $this->analyzeFile($file_path, $context);
    }

    /**
     * @return void
     */
    public function testBadReturn()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $file_path = 'somefile.php';
        $context = new Context();

        $this->addFile(
            $file_path,
            '<?php
                class C {
                    /**
                     * @return $thus
                     */
                    public function barBar() {
                        return $this;
                    }
                }'
        );

        $this->analyzeFile($file_path, $context);
    }
}
