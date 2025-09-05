<?php
use Oleksii\ComposerPreloader\Composer\Generator\CacheInfo;
use PHPUnit\Framework\TestCase;

final class CacheInfoTest extends TestCase
{
    public function testThrowsWhenListFileMissing(): void
    {
        $ci = new CacheInfo(__DIR__ . DIRECTORY_SEPARATOR . 'not-exists-file.php');
        $this->expectException(InvalidArgumentException::class);
        $ci->getMissedList();
    }
}
