<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Helpers;

use Composite\Sync\Helpers\ClassHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClassHelperTest extends TestCase
{
    #[DataProvider('extractNamespace_dataProvider')]
    public function test_extractNamespace(string $name, string $expected): void
    {
        $result = ClassHelper::extractNamespace($name);
        $this->assertSame($expected, $result);
    }

    public static function extractNamespace_dataProvider(): array
    {
        return [
            ['Composite\\Sync\\Helpers\\ClassHelper', 'Composite\\Sync\\Helpers'],
            ['Composite\\ClassHelper', 'Composite'],
            ['ClassHelper', '']
        ];
    }

    #[DataProvider('extractShortName_dataProvider')]
    public function test_extractShortName(string $name, string $expected): void
    {
        $result = ClassHelper::extractShortName($name);
        $this->assertSame($expected, $result);
    }

    public static function extractShortName_dataProvider(): array
    {
        return [
            ['Composite\\Sync\\Helpers\\ClassHelper', 'ClassHelper'],
            ['Composite\\ClassHelper', 'ClassHelper'],
            ['ClassHelper', 'ClassHelper']
        ];
    }

    #[DataProvider('normalizeString_dataProvider')]
    public function test_normalizeString(string $input, string $expected): void
    {
        $result = ClassHelper::normalizeString($input);
        $this->assertSame($expected, $result);
    }

    public static function normalizeString_dataProvider(): array
    {
        return [
            ['test', 'test'],
            ['Test String', 'test_string'],
            ['Test String 123', 'test_string_123'],
            ['test_string', 'test_string']
        ];
    }
}
