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
    public function testBasics()
    {
        $factory1 = new ProcessFactory();

        $this->assertNull($factory1->getDir());
        $this->assertSame('false', $factory1->getProgram());
        $this->assertSame([], $factory1->getArgs());
        $this->assertNull($factory1->getEnv());
        $this->assertSame(Process::class, $factory1->getProcessClass());

        $dir = dirname(__DIR__);
        $args = [ '-d', 'foo=bar', '-r' ];
        $env = [ 'BAZ' => 'qux', 'BAZ2' => 'qux-qux' ];

        $factory2 =
            new ProcessFactory($dir, 'php', $args, $env, OutputProcess::class);

        $this->assertSame($dir, $factory2->getDir());
        $this->assertSame('php', $factory2->getProgram());
        $this->assertSame($args, $factory2->getArgs());
        $this->assertSame($env, $factory2->getEnv());
        $this->assertSame(OutputProcess::class, $factory2->getProcessClass());

        $process1 = $factory2->create('echo __DIR__ . PHP_EOL;');

        $this->assertSame($dir . PHP_EOL, fgets($process1->getStdout()));

        $process2 = $factory2->create('echo getenv("BAZ") . PHP_EOL;', true);

        $process2->open();

        $this->assertSame('qux' . PHP_EOL, fgets($process2->getStdout()));
    }

    public function testDirectoryNotFound()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'foo';

        $this->expectException(DirectoryNotFound::class);
        $this->expectExceptionMessage(
            'Directory \"' . strstr(__DIR__, DIRECTORY_SEPARATOR)
        );
        $this->expectExceptionMessage(' not found');

        new ProcessFactory($dir);
    }
}
