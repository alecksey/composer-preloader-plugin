<?php
/**
 *
 * @date
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Generator;

class CacheInfo
{
    protected string $fileListPathName;

    public function __construct(string $fileListPathName)
    {
        $this->fileListPathName = $fileListPathName;
    }

    public function getMissedList(): array
    {
        if (!\file_exists($this->fileListPathName)) {
            throw new \InvalidArgumentException(
                'File list not found'
            );
        }

        $fileList = include $this->fileListPathName;

        $status = \opcache_get_status();

        $missedList = [];
        foreach ($status['scripts'] as $script) {
            if (!\in_array($script['full_path'], $fileList)) {
                $missedList[] = $script['full_path'];
            }
        }

        return $missedList;
    }
}