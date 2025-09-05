<?php
namespace {
    use Composer\IO\NullIO;
    use Oleksii\ComposerPreloader\Composer\Generator\Config;
    use Oleksii\ComposerPreloader\Composer\Generator\Generator;
    use Oleksii\ComposerPreloader\Composer\Generator\Writer\Result;
    use PHPUnit\Framework\TestCase;

    class CollectIO extends NullIO {
        public array $messages = [];
        public function write($messages, bool $newline = true, int $verbosity = self::NORMAL): void
        {
            if (is_array($messages)) {
                $this->messages = array_merge($this->messages, $messages);
            } else {
                $this->messages[] = $messages;
            }
        }
    }

    final class GeneratorTest extends TestCase
    {
        public function testGenerateSortsAndPassesEnumFilesWhenIncludeEnabled(): void
        {
            $config = new Config();
            $config->setUseIncludeForEnumFiles(true);
            $config->setRootDir(__DIR__); // required by Config contract

            $io = new CollectIO();

            $capturedSorted = [];
            $capturedEnum = [];

            $finderFactory = function (Config $c) {
                return new class($c) {
                    public function __construct(private Config $config) {}
                    public function findFiles(): array
                    {
                        return [
                            ['path' => 'A.php', 'deps' => ['B.php'], 'isEnum' => false],
                            ['path' => 'B.php', 'deps' => [], 'isEnum' => true],
                            ['path' => 'C.php', 'deps' => [], 'isEnum' => false],
                        ];
                    }
                };
            };

            $writerFactory = function (Config $c, $io) use (&$capturedSorted, &$capturedEnum) {
                return new class($c, $io, $capturedSorted, $capturedEnum) {
                    public function __construct(private Config $config, private $io, private &$sorted, private &$enum) {}
                    public function writePreloaderFile(array $sorted, array $enumFiles = []): Result
                    {
                        $this->sorted = $sorted;
                        $this->enum = $enumFiles;
                        $r = new Result();
                        $r->setSuccess(true);
                        $r->setWritedFiles(count($sorted));
                        $r->setEnumFiles(count($enumFiles));
                        return $r;
                    }
                };
            };

            $gen = new Generator($config, $io, $finderFactory, $writerFactory);

            $result = $gen->generate();
            $this->assertTrue($result, 'Generator should return success state from Writer Result');

            $this->assertContains('A.php', $capturedSorted);
            $this->assertContains('B.php', $capturedSorted);
            $this->assertContains('C.php', $capturedSorted);
            $this->assertLessThan(array_search('A.php', $capturedSorted), array_search('B.php', $capturedSorted), 'B must come before A due to dependency');

            $this->assertSame(['B.php'], $capturedEnum);

            $this->assertTrue($this->hasMessageContaining($io->messages, 'Found 3 files'));
            $this->assertTrue($this->hasMessageContaining($io->messages, 'Preloader file was generated'));
            $this->assertTrue($this->hasMessageContaining($io->messages, 'Time: '));
        }

        public function testGenerateOmitsEnumFilesWhenIncludeDisabled(): void
        {
            $config = new Config();
            $config->setUseIncludeForEnumFiles(false);
            $config->setRootDir(__DIR__);

            $io = new CollectIO();

            $capturedEnum = null;

            $finderFactory = function (Config $c) {
                return new class($c) {
                    public function __construct(private Config $config) {}
                    public function findFiles(): array
                    {
                        return [
                            ['path' => 'A.php', 'deps' => ['B.php'], 'isEnum' => false],
                            ['path' => 'B.php', 'deps' => [], 'isEnum' => true],
                        ];
                    }
                };
            };

            $writerFactory = function (Config $c, $io) use (&$capturedEnum) {
                return new class($c, $io, $capturedEnum) {
                    public function __construct(private Config $config, private $io, private &$enum) {}
                    public function writePreloaderFile(array $sorted, array $enumFiles = []): Result
                    {
                        $this->enum = $enumFiles;
                        $r = new Result();
                        $r->setSuccess(true);
                        return $r;
                    }
                };
            };

            $gen = new Generator($config, $io, $finderFactory, $writerFactory);

            $result = $gen->generate();
            $this->assertTrue($result);

            $this->assertSame([], $capturedEnum);
        }

        private function hasMessageContaining(array $messages, string $needle): bool
        {
            foreach ($messages as $m) {
                if (strpos($m, $needle) !== false) {
                    return true;
                }
            }
            return false;
        }
    }
}
