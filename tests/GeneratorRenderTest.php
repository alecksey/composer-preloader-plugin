<?php
use Oleksii\ComposerPreloader\Composer\Generator\Writer\Render;
use PHPUnit\Framework\TestCase;

final class GeneratorRenderTest extends TestCase
{
    public function testRenderPreloaderHeader(): void
    {
        $code = Render::renderPreloaderHeader();
        $this->assertIsString($code);
        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('composer-preloader-plugin', $code);
        $this->assertStringContainsString('$rootDir', $code);
    }

    public function testRenderPreloadIncludeItem(): void
    {
        $path = 'src/Foo.php';
        $code = Render::renderPreloadIncludeItem($path);
        $this->assertIsString($code);
        $this->assertStringContainsString('include', $code);
        $this->assertStringContainsString($path, $code);
        $this->assertStringContainsString('$rootDir', $code);
    }

    public function testRenderPreloadCompileFile(): void
    {
        $path = 'src/Bar.php';
        $code = Render::renderPreloadCompileFile($path);
        $this->assertIsString($code);
        $this->assertStringContainsString('opcache_compile_file', $code);
        $this->assertStringContainsString($path, $code);
    }

    public function testRenderPreloadListHeader(): void
    {
        $code = Render::renderPreloadListHeader('ignored');
        $this->assertIsString($code);
        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('composer-preloader-plugin', $code);
    }

    public function testRenderPreloadListBody(): void
    {
        $files = ['a.php', 'b.php'];
        $code = Render::renderPreloadListBody($files);
        $this->assertIsString($code);
        $this->assertStringContainsString('return [', $code);
        foreach ($files as $f) {
            $this->assertStringContainsString("'$f'", $code);
        }
        $this->assertStringContainsString('];', $code);
    }
}
