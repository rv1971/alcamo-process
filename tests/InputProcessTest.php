<?php

namespace alcamo\process;

use alcamo\exception\{
    Closed,
    DirectoryNotFound,
    Opened,
    Unsupported
};
use PHPUnit\Framework\TestCase;

class InputProcessTest extends TestCase
{
    public function testCmd()
    {
        $cmd = [ 'php', '-r', 'echo "Lorem ipsum";' ];

        $process = new InputProcess($cmd);

        if (PHP_VERSION_ID >= 70400) {
            $this->assertSame($cmd, $process->getCmd());
        }

        $this->assertSame('Lorem ipsum', $process->fgets());

        $this->assertSame(0, $process->close());
    }

    public function testDir()
    {
        $cmd = "php -r 'echo __DIR__;'";

        $dir = dirname(__DIR__);

        $process = new InputProcess($cmd, $dir);

        $this->assertSame($dir, $process->getDir());

        $this->assertSame($dir, $process->fgets());

        $this->assertSame(0, $process->close());
    }

    public function testEnv()
    {
        $cmd = [ 'php', '-r', 'echo getenv("foo");' ];

        $env = [ 'foo' => 'bar' ];

        $process = new InputProcess($cmd, null, $env);

        $this->assertSame($env, $process->getEnv());

        $this->assertSame('bar', $process->fgets());

        $this->assertSame(0, $process->close());
    }

    public function testStderr()
    {
        $cmd = "php -r 'fwrite(STDERR, \"Lorem ipsum\");'";

        $process = new InputProcess($cmd);

        $this->assertSame($cmd, $process->getCmd());

        $this->assertSame('Lorem ipsum', fgets($process->getStderr()));

        $this->assertSame(0, $process->close());
    }

    public function testClose()
    {
        $cmd = "php -r 'exit(42);'";

        $process = new InputProcess($cmd);

        $this->assertSame($cmd, $process->getCmd());

        $this->assertSame(42, $process->close());
    }

    public function testDirectoryNotFound()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'foo';

        $this->expectException(DirectoryNotFound::class);
        $this->expectExceptionMessage(
            "Directory \"$dir\" not found"
        );
        new InputProcess('echo', $dir);
    }

    public function testOpened()
    {
        $process = new InputProcess('echo');

        $this->expectException(Opened::class);
        $this->expectExceptionMessage(
            'Attempt to open already opened Resource'
        );

        $process->open();
    }

    public function testClosed()
    {
        $process = new InputProcess('echo');
        $process->close();

        $this->expectException(Closed::class);
        $this->expectExceptionMessage(
            'Attempt to use closed process'
        );

        $process->close();
    }

    public function testUnsupported()
    {
        $process = new InputProcess('echo');

        $this->expectException(Unsupported::class);
        $this->expectExceptionMessage('foo() not supported');

        $process->foo();
    }
}
