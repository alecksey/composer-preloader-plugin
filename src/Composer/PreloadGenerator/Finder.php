<?php
/**
 *
 * @date
 * @author Oleksii Bogatchenko
 * @package composer-preloader-plugin
 * @version 1.0
 */

namespace Oleksii\ComposerPreloader\Composer\PreloadGenerator;

use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use BadMethodCallException;

class Finder {

    private Config $config;

    private SymfonyFinder $finder;

    private \PhpParser\Parser $parserFactory;

    private string $rootDir;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->finder = new SymfonyFinder();
        $this->parserFactory = (new ParserFactory())->createForHostVersion();
        $this->rootDir = realpath(dirname(\Composer\Factory::getComposerFile()));
    }

    /**
     * @return array<int, array{'path': string, 'deps': array<int, string>}>
     */
    public function findFiles() : array
    {
        if(count($this->config->getPaths()) == 0) {
            throw new BadMethodCallException('Include folders must be set');
        }

        $paths = $this->config->getPaths();

        foreach ($paths as &$path) {
            $path = $this->rootDir . DIRECTORY_SEPARATOR . $path;
        }

        $this->finder->in($paths);
        foreach ($this->config->getExtensions() as $extension) {
            $this->finder->files()->name('*.' . $extension);
        }

        //$this->finder->exclude($this->config->getExcludePaths());
        $this->finder->ignoreDotFiles(true);
        $this->finder->ignoreVCS(true);

        if(count($this->config->getFiles())) {
            $this->finder->append($this->config->getFiles());
        }

        $filterCallback = $this->getFilterCallback();

        if (null !== $filterCallback ) {
            $this->finder->filter($filterCallback);
        }


        $filesIterator = $this->finder->getIterator();

        $fileList = $this->processFileList($filesIterator);



        return $fileList;
    }

    private function getFilterCallback() : ?callable
    {
        $directoryRegexp = null;
        $filesRegexp = $this->config->getExcludeFilesRegex();

        if(count($this->config->getExcludePaths()) > 0) {
            $directoryRegexp = '/^(';
            $pathList = [];
            foreach ($this->config->getExcludePaths() as $excludePath) {
                $excludePath = str_replace('\\', '/', $this->rootDir . DIRECTORY_SEPARATOR . $excludePath);
                if (substr($excludePath, -1) !== '/') {
                    $excludePath .= '/'; // Force all directives to be full direcory paths with "/" suffix.
                }
                $excludePath = preg_quote($excludePath, '/');
                $pathList[] = $excludePath;
            }

            $directoryRegexp .= implode('|', $pathList);
            $directoryRegexp .= ')/i';
        }

        if(null === $filesRegexp && null === $directoryRegexp) {
            return null;
        }

        return function (SplFileInfo $file) use ($filesRegexp, $directoryRegexp) {
            $path = str_replace('\\', '/', $file->getPathname());
            $exclude_match = false;


            if(null !== $directoryRegexp) {
                $exclude_match = preg_match($directoryRegexp, $path);
            }

            if (!$exclude_match && $filesRegexp !== null) {
                $exclude_match = preg_match($filesRegexp, $path);
            }

            return !$exclude_match;
        };
    }


    private function processFileList(\Iterator $filesIterator) : array
    {
        $fileList = [];

        $loader = require 'vendor/autoload.php';


        foreach ($filesIterator as $file) {

            if($file->isDir()) continue;

            $this->processFile($file, $loader, $fileList);
        }

        return $fileList;
    }

    private function processFile(SplFileInfo $file, $loader, array &$fileList) : void
    {
        gc_enable();
        $depsAdded = [];


        try {
            $ast = $this->parserFactory->parse($file->getContents());
        } catch (\Throwable $e) {
            echo $e->getMessage() . " in file: " . $file->getPathname();
            return;
        }



        $useStatements = [];

        $traverser = new NodeTraverser();
        $visitor = new class extends NodeVisitorAbstract {
            private array $foundUseStatements = [];
            private bool $foundEnum = false;

            public function enterNode(Node $node) {
                if ($node instanceof Use_) {
                    foreach ($node->uses as $use) {
                        if ($use instanceof UseUse) {
                            $this->foundUseStatements[] = [
                                'name' => $use->name->toString(),
                                'alias' => $use->alias ? $use->alias->name : null,
                                'type' => $node->type // Use_::TYPE_NORMAL, TYPE_FUNCTION, TYPE_CONSTANT
                            ];
                        }
                    }
                }

                if($node instanceof Enum_) {
                    $this->foundEnum = true;
                }
            }

            public function getFoundUseStatements(): array
            {
                return $this->foundUseStatements;
            }

            public function isFoundEnum() : bool
            {
                return $this->foundEnum;
            }
        };

        $traverser->addVisitor($visitor);

        $useStatements = $traverser->traverse($ast);

        $deps = [];

        if(count($visitor->getFoundUseStatements()) > 0) {
            foreach ($visitor->getFoundUseStatements() as $useStatement) {


                $filePath = $loader->findFile($useStatement['name']);

                if(false === $filePath) {
                    continue;
                }

                if(is_dir($filePath)) {
                    throw new \RuntimeException('Directory is not supported');
                }

                $filePath = str_replace($this->rootDir . DIRECTORY_SEPARATOR , '', realpath($filePath));

                if(!isset($fileList[$filePath])) {
                    $depsAdded[$filePath] = $filePath;
                }

                $deps[] = $filePath;
            }
        }

        $basePathName = str_replace($this->rootDir . DIRECTORY_SEPARATOR, '', $file->getPathname());

        $fileList[$basePathName] = [
            'path' => $basePathName,
            'isEnum' => $visitor->isFoundEnum(),
            'deps' => $deps
        ];

        foreach ($depsAdded as $dep) {
            $depFile = new SplFileInfo($this->rootDir . DIRECTORY_SEPARATOR . $dep, dirname($dep), $dep);
            $this->processFile($depFile, $loader, $fileList);
        }

        gc_collect_cycles();
    }
}