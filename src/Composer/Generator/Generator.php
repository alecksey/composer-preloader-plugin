<?php
/**
 *
 * @date
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Generator;

use Composer\IO\IOInterface;
use MJS\TopSort\Implementations\StringSort;
use Oleksii\ComposerPreloader\Composer\Generator\Writer\Writer;

final class Generator
{
    private Config $config;

    private IOInterface $io;

    /** @var callable */
    private $finderFactory;

    /** @var callable */
    private $writerFactory;

    public function __construct(Config $config, IOInterface $io, $finderFactory = null, $writerFactory = null)
    {
        $this->config = $config;
        $this->io = $io;
        $this->finderFactory = $finderFactory ?? function (Config $config) {
            return new Finder($config);
        };
        $this->writerFactory = $writerFactory ?? function (Config $config, IOInterface $io) {
            return new Writer($config, $io);
        };
    }

    public function generate(): bool
    {
        $timeStart = time();
        $finder = ($this->finderFactory)($this->config);
        $fileList = $finder->findFiles();
        $this->io->write('<info>Found ' . count($fileList) . ' files</info>');

        $enumFiles = [];

        $sorter = new StringSort();
        $sorter->setThrowCircularDependency(false);

        foreach ($fileList as $fileInfo) {
            if ($this->config->isUseIncludeForEnumFiles() && $fileInfo['isEnum']) {
                $enumFiles[] = $fileInfo['path'];
            }

            $sorter->add($fileInfo['path'], $fileInfo['deps']);
        }

        $sorted = $sorter->sort();

        $writer = ($this->writerFactory)($this->config, $this->io);

        $result = $writer->writePreloaderFile($sorted, $enumFiles);

        $this->io->write('<info>Preloader file was generated</info>');
        $timeEnd = time();
        $this->io->write('<info>Time: ' . ($timeEnd - $timeStart) . ' sec</info>');

        return $result->isSuccess();
    }
}