<?php
/**
 *
 * @date 04.09.2025
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Generator\Writer;

class Result
{
    private bool $success = false;

    private int $writedFiles = 0;

    private int $enumFiles = 0;

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return int
     */
    public function getWritedFiles(): int
    {
        return $this->writedFiles;
    }

    /**
     * @param int $writedFiles
     */
    public function setWritedFiles(int $writedFiles): void
    {
        $this->writedFiles = $writedFiles;
    }

    /**
     * @return int
     */
    public function getEnumFiles(): int
    {
        return $this->enumFiles;
    }

    /**
     * @param int $enumFiles
     */
    public function setEnumFiles(int $enumFiles): void
    {
        $this->enumFiles = $enumFiles;
    }
}