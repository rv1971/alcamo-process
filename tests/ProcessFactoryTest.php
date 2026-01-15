<?php

namespace alcamo\process;

use alcamo\exception\{
    Closed,
    DirectoryNotFound,
    Opened,
    Unsupported
};
use PHPUnit\Framework\TestCase;

class ProcessFactoryTest extends TestCase
{
    public function testBasics(): void
    {
        $factory1 = new ProcessFactory();

        $this->assertNull($factory1->getDir());

        if (PHP_VERSION_ID >= 70400) {
            $this->assertSame('false', $factory1->getCmd());
        }

        $this->assertNull($factory1->getEnv());
        $this->assertSame(Process::class, $factory1->getProcessClass());

        $cmd = [ 'php', '-d', 'foo=bar', '-r' ];
        $dir = dirname(__DIR__);
        $env = [ 'BAZ' => 'qux', 'BAZ2' => 'qux-qux' ];

        $factory2 = new ProcessFactory(
            $cmd,
            $dir,
            $env,
            null,
            null,
            OutputProcess::class
        );

        if (PHP_VERSION_ID >= 70400) {
            $this->assertSame($cmd, $factory2->getCmd());
        }

        $this->assertSame($env, $factory2->getEnv());
        $this->assertSame(OutputProcess::class, $factory2->getProcessClass());

        $process1 = $factory2->create([ 'echo __DIR__ . PHP_EOL;' ]);

        $this->assertSame($dir . PHP_EOL, fgets($process1->getStdout()));

        $process2 = $factory2->create('\'echo getenv("BAZ") . PHP_EOL;\'', true);

        $process2->open();

        $this->assertSame('qux' . PHP_EOL, fgets($process2->getStdout()));

        $factory3 = new ProcessFactory(
            'php -r',
            $dir,
            $env,
            null,
            null,
            OutputProcess::class
        );

        $process3 = $factory3->create([ 'echo __DIR__ . PHP_EOL;' ]);

        $this->assertSame($dir . PHP_EOL, fgets($process3->getStdout()));

        $process4 = $factory3->create('"echo __DIR__ . PHP_EOL;"');

        $this->assertSame($dir . PHP_EOL, fgets($process4->getStdout()));
    }

    public function testDirectoryNotFound(): void
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'foo';

        $this->expectException(DirectoryNotFound::class);
        $this->expectExceptionMessage(
            'Directory \"' . strstr(__DIR__, DIRECTORY_SEPARATOR)
        );
        $this->expectExceptionMessage(' not found');

        new ProcessFactory('cmd', $dir);
    }
}
