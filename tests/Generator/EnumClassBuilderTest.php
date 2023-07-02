<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Generator;

use Composite\Sync\Generator\EnumClassBuilder;

final class EnumClassBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider enumClassBuilder_dataProvider
     */
    public function test_getFileContent($enumClassName, $cases, $expectedOutput)
    {
        $enumClassBuilder = new EnumClassBuilder($enumClassName, $cases);

        $actualOutput = $enumClassBuilder->getFileContent();
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public static function enumClassBuilder_dataProvider(): array
    {
        return [
            'valid_cases' => [
                'TestEnum',
                ['ONE', 'TWO', 'THREE'],
                <<<EOT
<?php

declare(strict_types=1);

enum TestEnum
{
	case ONE;
	case TWO;
	case THREE;
}

EOT
            ],
            'empty_cases' => [
                'TestEnum',
                [],
                <<<EOT
<?php

declare(strict_types=1);

enum TestEnum
{
}

EOT
            ],
        ];
    }

    public function test_getClassContentThrowsExceptionOnInvalidCaseName()
    {
        // Arrange
        $enumClassName = 'TestEnum';
        $cases = ['ONE', 'INVALID CASE'];
        $enumClassBuilder = new EnumClassBuilder($enumClassName, $cases);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("INVALID CASE");
        $enumClassBuilder->getFileContent();
    }
}