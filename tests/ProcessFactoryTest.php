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
        $this->assertSame([], $factory1->getOptions());
        $this->assertNull($factory1->getEnv());

        $dir = dirname(__DIR__);
        $options = [ '-d', 'foo=bar', '-r' ];
        $env = [ 'BAZ' => 'qux', 'BAZ2' => 'qux-qux' ];

        $factory2 = new ProcessFactory($dir, 'php', $options, $env);

        $this->assertSame($dir, $factory2->getDir());
        $this->assertSame('php', $factory2->getProgram());
        $this->assertSame($options, $factory2->getOptions());
        $this->assertSame($env, $factory2->getEnv());

        $process1 = $factory2->exec('echo __DIR__ . "\n";');

        $this->assertSame("$dir\n", fgets($process1->getStdout()));

        $process2 = $factory2->exec('echo getenv("BAZ") . "\n";');

        $this->assertSame("qux\n", fgets($process2->getStdout()));
    }

    public function testDirectoryNotFound()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'foo';

        $this->expectException(DirectoryNotFound::class);
        $this->expectExceptionMessage(
            "Directory \"$dir\" not found"
        );

        new ProcessFactory($dir);
    }
}
