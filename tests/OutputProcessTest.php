<?php

namespace alcamo\process;

use PHPUnit\Framework\TestCase;

class OutputProcessTest extends TestCase
{
    public function testBasics()
    {
        $cmd = "php -r 'echo fgets(STDIN);'";

        $process = new OutputProcess($cmd);

        $this->assertSame($cmd, $process->getCmd());

        $data = "Lorem ipsum\n";

        $process->fwrite($data);

        $this->assertSame($data, fgets($process->getStdout()));
    }
}
