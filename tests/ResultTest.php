<?php
use Oleksii\ComposerPreloader\Composer\Generator\Writer\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $r = new Result();
        $this->assertFalse($r->isSuccess());
        $this->assertSame(0, $r->getWritedFiles());
        $this->assertSame(0, $r->getEnumFiles());
    }

    public function testSettersAndGetters(): void
    {
        $r = new Result();
        $r->setSuccess(true);
        $r->setWritedFiles(5);
        $r->setEnumFiles(2);

        $this->assertTrue($r->isSuccess());
        $this->assertSame(5, $r->getWritedFiles());
        $this->assertSame(2, $r->getEnumFiles());
    }
}
