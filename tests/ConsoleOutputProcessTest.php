<?php

namespace alcamo\process;

use PHPUnit\Framework\TestCase;

class ConsoleOutputProcessTest extends TestCase
{
    public function testBasics()
    {
        $cmd = "sed 's/fxx/foo/'";

        $process = new ConsoleOutputProcess($cmd);

        $this->assertSame($cmd, $process->getCmd());

        $data = "fxx bar baz\n";

        $process->fwrite($data);
    }
}
