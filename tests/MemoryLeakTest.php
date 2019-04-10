<?php
namespace Psalm\Tests;

class MemoryLeakTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'SKIPPED-classNoExtends' => [
                '<?php
                    class A {}
                    class B {}',
            ],
            'classWithExtends' => [
                '<?php
                    class A {}
                    class B extends A {}',
            ],
            'classWithProperty' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }',
            ],
            'classWithPropertyAndExtended' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    class B extends A {}',
            ],
        ];
    }
}
