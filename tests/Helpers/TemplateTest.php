<?php declare(strict_types=1);

namespace Composite\Sync\Tests\Helpers;

use Composite\Sync\Helpers\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private string $templateDir;

    protected function setUp(): void
    {
        // Assuming templates are stored in a 'templates' subdirectory of the test directory
        $this->templateDir = __DIR__ . '/templates/';
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir);
        }
    }

    protected function tearDown(): void
    {
        // Clean up created templates
        array_map('unlink', glob("{$this->templateDir}*.php"));
        rmdir($this->templateDir);
    }

    public function testRender(): void
    {
        $templatePath = $this->templateDir . 'template1';
        $templateContent = "<?php echo 'Hello, ' . \$name . '!'; ?>";
        file_put_contents($templatePath . '.php', $templateContent);

        $vars = ['name' => 'World'];
        $expected = 'Hello, World!';
        $result = Template::render($templatePath, $vars);
        $this->assertEquals($expected, $result);
    }

    public function testRenderWithMissingFile(): void
    {
        $this->expectException(\Exception::class);
        Template::render($this->templateDir . 'missing');
    }

    // Add more test cases as necessary
}
