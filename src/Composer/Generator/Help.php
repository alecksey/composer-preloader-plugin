<?php
/**
 *
 * @date 04.09.2025
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\Generator;

class Help
{
    public static function getPluginHelp(): string
    {
        return <<<HELP
Run `composer preloader` to generate preload file.

Example config for `composer.json`:

-----------------------------------------------------------

"extra": {
    "preloader" : {
        "paths" : [
            "vendor/psr",
            "library
        ],
        "exclude-paths" : [
            "tests",
            "test"
        ],
        "exclude-files" : [
           "app/config.php"
        ],
        "extensions" : ["php", "inc"],
        "exclude-regex": "/[A-Za-z0-9_]test\\.php$/i",
        "files": [
            "app/bootstrap.php"
        ],
        "use-include-for-enum-files" : true,
        "output-file" : "vendor/preload.php",
        "list-output-file" : "vendor/preload.list.php"
    }
}

-----------------------------------------------------------

Config options:

 - paths: array of paths to scan
 - exclude-paths: array of paths to exclude from scan
 - exclude-files: array of files to exclude from scan
 - extensions: array of extensions to scan
 - exclude-regex: regex to exclude files from scan
 - files: array of files to include in preload
 - use-include-for-enum-files: use include for enum files
 - output-file: path to output file
 - list-output-file: path to output file with list of files
HELP;
    }
}