<?php
/**
 *
 * @date
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Command;

use Composer\Command\BaseCommand;
use Oleksii\ComposerPreloader\Composer\Generator\Config;
use Oleksii\ComposerPreloader\Composer\Generator\Generator;
use Oleksii\ComposerPreloader\Composer\Generator\Help;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreloaderCommand extends BaseCommand
{
    private Config $config;

    public function configure(): void
    {
        $this->setName('preloader');
        $this->setDescription('Preloader command');
        $this->setHelp(Help::getPluginHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->buildConfig();

        $status = $this->generatePreloaderFile();

        return $status ? 0 : 1;
    }

    private function buildConfig(): void
    {
        $composer = $this->getComposer();
        $extra = $composer->getPackage()->getExtra();

        if (!isset($extra['preloader'])) {
            throw new \RuntimeException('Preloader is not configured');
        }

        if (!is_array($extra['preloader'])) {
            throw new \RuntimeException('Preloader config must be array');
        }

        $vendorDir = $composer->getConfig()->has('vendor-dir') ? $composer->getConfig()->get('vendor-dir') : 'vendor';
        $rootDir = realpath(dirname(\Composer\Factory::getComposerFile()));

        $config = new Config();
        $config->setPaths($extra['preloader']['paths']);

        if (isset($extra['preloader']['include-files'])) {
            $config->setFiles($extra['preloader']['include-files']);
        }

        if (isset($extra['preloader']['exclude-paths'])) {
            $config->setExcludePaths($extra['preloader']['exclude-paths']);
        }

        if (isset($extra['preloader']['exclude-files'])) {
            $config->setExcludeFiles($extra['preloader']['exclude-files']);
        }

        if (isset($extra['preloader']['extensions'])) {
            $config->setExtensions($extra['preloader']['extensions']);
        }

        if (isset($extra['preloader']['output-file'])) {
            $config->setOutputFile($extra['preloader']['output-file']);
        }

        if (isset($extra['preloader']['exclude-regex'])) {
            $config->setExcludeFilesRegex($extra['preloader']['exclude-regex']);
        }

        if (isset($extra['preloader']['use-include-for-enum-files'])) {
            $config->setUseIncludeForEnumFiles($extra['preloader']['use-include-for-enum-files']);
        }

        if (isset($extra['preloader']['list-output-file'])) {
            $config->setListOutputFile($extra['preloader']['list-output-file']);
        }

        $config->setVendorDir($vendorDir);
        $config->setRootDir($rootDir);

        $this->config = $config;
    }

    private function generatePreloaderFile(): bool
    {
        $generator = new Generator($this->config, $this->getIO());
        $status = $generator->generate();
        return $status;
    }
}