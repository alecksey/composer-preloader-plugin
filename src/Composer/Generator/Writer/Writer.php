<?php
/**
 *
 * @date 04.09.2025
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Generator\Writer;

use Composer\IO\IOInterface;
use Oleksii\ComposerPreloader\Composer\Generator\Config;

class Writer
{
    protected Config $config;
    protected IOInterface $io;

    public function __construct(Config $config, IOInterface $io)
    {
        $this->config = $config;
        $this->io = $io;
    }

    public function writePreloaderFile(array $fileList, array $enumFiles = []): Result
    {
        $preloaderPathname = $this->config->getRootDir() . DIRECTORY_SEPARATOR . $this->config->getOutputFile();

        $preloadFile = \fopen($preloaderPathname, 'w');

        if ($preloadFile === false) {
            throw new \RuntimeException('Can\'t open file: ' . $preloaderPathname);
        }

        $writedFiles = [];

        \fwrite($preloadFile, Render::renderPreloaderHeader());

        foreach ($enumFiles as $enumFile) {
            if ($this->config->isUseIncludeForEnumFiles()) {
                \fwrite($preloadFile, Render::renderPreloadIncludeItem($enumFile));
            } else {
                \fwrite($preloadFile, Render::renderPreloadCompileFile($enumFile));
            }

            $writedFiles[] = $enumFile;
        }

        foreach ($fileList as $filePath) {
            if (!\in_array($filePath, $enumFiles, true)) {
                \fwrite($preloadFile, Render::renderPreloadCompileFile($filePath));
                $writedFiles[] = $this->config->getRootDir() . DIRECTORY_SEPARATOR . $filePath;
            }
        }

        \fwrite($preloadFile, Render::renderPreloaderFooter());

        $status = \fclose($preloadFile);

        if ($status === false) {
            throw new \RuntimeException('Can\'t close file: ' . $preloaderPathname);
        }

        if ($this->config->getListOutputFile() !== '') {
            $outputListFilePathname = $this->config->getRootDir() . DIRECTORY_SEPARATOR . $this->config->getListOutputFile();
            $outputListFile = \fopen($outputListFilePathname, 'w');

            if ($outputListFile === false) {
                throw new \RuntimeException('Can\'t open file: ' . $outputListFilePathname);
            }

            \fwrite($outputListFile, Render::renderPreloadListHeader(''));

            \fwrite($outputListFile, Render::renderPreloadListBody($writedFiles));

            \fclose($outputListFile);
        }

        $result = new Result();
        $result->setSuccess(true);
        $result->setWritedFiles(count($writedFiles));
        $result->setEnumFiles(count($enumFiles));

        return $result;
    }
}