<?php
/**
 *
 * @date
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Command;

use Composer\Plugin\Capability\CommandProvider;

class PreloaderCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new PreloaderCommand(),
        ];
    }
}