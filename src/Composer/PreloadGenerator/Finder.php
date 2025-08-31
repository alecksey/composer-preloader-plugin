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

        $this->finder->exclude($this->config->getExcludePaths());
        $this->finder->ignoreDotFiles(true);
        $this->finder->ignoreVCS(true);
        $this->finder->filter(static function (SplFileInfo $file) {
            return !$file->isDir();
        });

        $filesIterator = $this->finder->getIterator();

        $fileList = [];

        $loader = require 'vendor/autoload.php';
        $composerRootDir = realpath(dirname(\Composer\Factory::getComposerFile()));

        foreach ($filesIterator as $file) {

            try {
                $ast = $this->parserFactory->parse($file->getContents());
            } catch (\Throwable $e) {
                echo $e->getMessage() . " in file: " . $file->getPathname();
                continue;
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
                    $deps[] = $filePath;
                }
            }

            $fileList[$file->getPathname()] = [
                'path' => $file->getPathname(),
                'deps' => $deps
            ];
        }

        return $fileList;
    }
}