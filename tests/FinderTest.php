<?php
use Oleksii\ComposerPreloader\Composer\Generator\Config;
use Oleksii\ComposerPreloader\Composer\Generator\Finder;
use PHPUnit\Framework\TestCase;

final class FinderTest extends TestCase
{
    public function testThrowsWhenNoPathsProvided(): void
    {
        $this->expectException(BadMethodCallException::class);
        $config = new Config();
        $finder = new Finder($config);
        $finder->findFiles();
    }

    public function testFindsFilesInFixtureDirectory(): void
    {
        $config = new Config();
        $config->setPaths(['tests\\fixtures\\basic']);
        $finder = new Finder($config);
        $files = $finder->findFiles();

        $this->assertIsArray($files);
        $paths = array_column($files, 'path');
        // Expect at least the two fixture files
        $this->assertContains('tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'basic' . DIRECTORY_SEPARATOR . 'SimpleClass.php', $paths);
        $this->assertContains('tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'basic' . DIRECTORY_SEPARATOR . 'SimpleEnum.php', $paths);

        foreach ($files as $fi) {
            $this->assertArrayHasKey('path', $fi);
            $this->assertArrayHasKey('deps', $fi);
            $this->assertArrayHasKey('isEnum', $fi);
            $this->assertIsArray($fi['deps']);
        }

        // Check enum detection
        $map = [];
        foreach ($files as $fi) { $map[$fi['path']] = $fi; }
        $this->assertFalse($map['tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'basic' . DIRECTORY_SEPARATOR . 'SimpleClass.php']['isEnum']);
        $this->assertTrue($map['tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'basic' . DIRECTORY_SEPARATOR . 'SimpleEnum.php']['isEnum']);
    }
}
