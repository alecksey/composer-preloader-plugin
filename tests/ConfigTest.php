<?php
use Oleksii\ComposerPreloader\Composer\Generator\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testSetPathsRejectsNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setPaths(['ok', 123]);
    }

    public function testSetFilesRejectsNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setFiles(['ok.php', new stdClass()]);
    }

    public function testSetExtensionsRejectsDot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setExtensions(['php', 'p.hp']);
    }

    public function testSetExcludeFilesFluent(): void
    {
        $config = new Config();
        $ret = $config->setExcludeFiles(['a.php']);
        $this->assertSame($config, $ret);
        $this->assertSame(['a.php'], $config->getExcludeFiles());
    }

    public function testSetRootDirEmptyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = new Config();
        $config->setRootDir('');
    }

    public function testDefaultsAndSetters(): void
    {
        $config = new Config();
        $this->assertSame([], $config->getExtensions());
        $this->assertSame([], $config->getFiles());
        $this->assertSame([], $config->getExcludePaths());
        $this->assertSame([], $config->getExcludeFiles());
        $this->assertNull($config->getExcludeFilesRegex());
        $this->assertSame('', $config->getVendorDir());
        $this->assertSame('', $config->getOutputFile());
        $this->assertSame('', $config->getListOutputFile());
        $this->assertTrue($config->isUseIncludeForEnumFiles());

        $config->setVendorDir('v');
        $config->setOutputFile('out.php');
        $config->setListOutputFile('list.php');
        $config->setExcludeFilesRegex('/foo/');
        $config->setUseIncludeForEnumFiles(false);
        $config->setPaths(['src']);
        $config->setFiles(['a.php']);
        $config->setExtensions(['php']);
        $config->setExcludePaths(['vendor']);
        $config->setExcludeFiles(['b.php']);

        $this->assertSame('v', $config->getVendorDir());
        $this->assertSame('out.php', $config->getOutputFile());
        $this->assertSame('list.php', $config->getListOutputFile());
        $this->assertSame('/foo/', $config->getExcludeFilesRegex());
        $this->assertFalse($config->isUseIncludeForEnumFiles());
        $this->assertSame(['src'], $config->getPaths());
        $this->assertSame(['a.php'], $config->getFiles());
        $this->assertSame(['php'], $config->getExtensions());
        $this->assertSame(['vendor'], $config->getExcludePaths());
        $this->assertSame(['b.php'], $config->getExcludeFiles());
    }
}
