<?php
/**
 *
 * @date 28.08.2025
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Oleksii\ComposerPreloader\Composer\Command\PreloaderCommandProvider;

class Plugin implements PluginInterface, Capable
{
    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io): void {

    }

    public function getCapabilities(): array {
        return [
            CommandProvider::class => PreloaderCommandProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function deactivate(Composer $composer, IOInterface $io) {
    }

    /**
     * @inheritDoc
     */
    public function uninstall(Composer $composer, IOInterface $io) {
    }
}