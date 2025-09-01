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
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use BadMethodCallException;

class Finder {

    private Config $config;

    private SymfonyFinder $finder;

    private \PhpParser\Parser $parserFactory;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->finder = new SymfonyFinder();
        $this->parserFactory = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

    }

    /**
     * @return array<int, array{'path': string, 'deps': array<int, string>}>
     */
    public function findFiles() : array
    {
        if(count($this->config->getPaths()) == 0) {
            throw new BadMethodCallException('Include folders must be set');
        }

        $this->finder->in($this->config->getPaths());
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
                $excludePath = str_replace('\\', '/', $excludePath);
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
        $depsAdded = [];

        $loader = require 'vendor/autoload.php';
        $composerRootDir = realpath(dirname(\Composer\Factory::getComposerFile()));

        foreach ($filesIterator as $file) {

            if($file->isDir()) continue;

            $this->processFile($file, $fileList);
        }

        return $fileList;
    }

    private function processFile(\SplFileInfo $file, array &$fileList) : void
    {
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
            }

            public function getFoundUseStatements(): array
            {
                return $this->foundUseStatements;
            }
        };

        $traverser->addVisitor($visitor);

        $useStatements = $traverser->traverse($ast);

        $deps = [];

        if(count($visitor->getFoundUseStatements()) > 0) {
            foreach ($visitor->getFoundUseStatements() as $useStatement) {
                $filePath = $loader->findFile($useStatement['name']);

                $filePath = str_replace($composerRootDir . DIRECTORY_SEPARATOR , '', realpath($filePath));

                if(!isset($fileList[$filePath])) {
                    $depsAdded[$filePath] = $filePath;
                }

                $deps[] = $filePath;
            }
        }

        $fileList[$file->getPathname()] = [
            'path' => $file->getPathname(),
            'deps' => $deps
        ];


        foreach ($depsAdded as $dep) {
            $depFile = new \SplFileInfo($dep);
            $this->processFile($depFile, $fileList);
        }
    }
}