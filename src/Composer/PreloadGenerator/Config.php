<?php
/**
 *
 * @date 29.08.2025
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\PreloadGenerator;

class Config
{
    private array $paths;

    private array $files = [];

    private array $extensions = ['php'];

    private array $exclude_paths = [];

    private array $exclude_files = [];

    private ?string $exclude_files_regex = null;

    private string $vendor_dir = 'vendor';

    private string $output_file = 'vendor/preload.php';

    /*
     * Call enums cached by op_cache_compile_file() raises seg fault of php, so we need to use include instead
     * @var bool
     */
    private bool $useIncludeForEnumFiles = true;

    public function __construct(array $paths = [], array $files = [], array $exclude_paths = [], array $exclude_files = [], array $extensions = [], $vendor_dir = '')
    {
        $this->setPaths($paths);
        $this->setFiles($files);
        $this->setExcludePaths($exclude_paths);
        $this->setExcludeFiles($exclude_files);
        $this->setExtensions($extensions);

    }

    /**
     * @return array<int,string>
     */
    public function getPaths() : array
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     */
    public function setPaths(array $paths)
    {
        foreach($paths as $path) {
            if(!is_string($path)) {
                throw new \InvalidArgumentException(
                    'Path must be a string'
                );
            }
        }

        $this->paths = $paths;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param array $files
     */
    public function setFiles($files)
    {
        foreach($files as $file) {
            if(!is_string($file)) {
                throw new \InvalidArgumentException(
                    'File must be a string'
                );
            }
        }
        $this->files = $files;
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param array $extensions
     */
    public function setExtensions($extensions)
    {
        foreach($extensions as $extension) {
            if(!is_string($extension)) {
                throw new \InvalidArgumentException(
                    'Extension must be a string'
                );
            }

            if(strpos($extension, '.') !== false) {
                throw new \InvalidArgumentException(
                    'Extension can not contain dot'
                );
            }
        }
        $this->extensions = $extensions;
    }

    /**
     * @return array
     */
    public function getExcludePaths()
    {
        return $this->exclude_paths;
    }

    /**
     * @param array $exclude_paths
     */
    public function setExcludePaths($exclude_paths)
    {
        foreach($exclude_paths as $exclude_path) {
            if(!is_string($exclude_path)) {
                throw new \InvalidArgumentException(
                    'Exclude path must be a string'
                );
            }
        }
        $this->exclude_paths = $exclude_paths;
    }

    /**
     * @return array
     */
    public function getExcludeFiles() : array
    {
        return $this->exclude_files;
    }

    /**
     * @param array $exclude_files
     */
    public function setExcludeFiles(array $exclude_files) : Config
    {
        foreach($exclude_files as $exclude_file) {
            if(!is_string($exclude_file)) {
                throw new \InvalidArgumentException(
                    'Exclude file must be a string'
                );
            }
        }
        $this->exclude_files = $exclude_files;

        return $this;
    }

    public function getVendorDir(): string
    {
        return $this->vendor_dir;
    }

    public function setVendorDir(string $vendor_dir): void
    {
        $this->vendor_dir = $vendor_dir;
    }

    public function getOutputFile(): string
    {
        return $this->output_file;
    }

    public function setOutputFile(string $output_file): void
    {
        if(!is_string($output_file)) {
            throw new \InvalidArgumentException(
                'Output file must be a string'
            );
        }

        $this->output_file = $output_file;
    }

    public function getExcludeFilesRegex(): ?string
    {
        return $this->exclude_files_regex;
    }

    public function setExcludeFilesRegex(?string $exclude_files_regex): void
    {
        $this->exclude_files_regex = $exclude_files_regex;
    }

    public function isUseIncludeForEnumFiles(): bool
    {
        return $this->useIncludeForEnumFiles;
    }

    public function setUseIncludeForEnumFiles(bool $useIncludeForEnumFiles): void
    {
        $this->useIncludeForEnumFiles = $useIncludeForEnumFiles;
    }


}