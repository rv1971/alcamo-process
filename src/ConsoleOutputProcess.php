<?php

namespace alcamo\process;

/**
 * @brief Output process opened by proc_open()
 *
 * Process that receives output from the current PHP process and can write to
 * stdout and stderr.
 */
class ConsoleOutputProcess extends OutputProcess
{
    protected function createDescriptorSpec(): array
    {
        return [
            0 => [ 'pipe', 'r' ]
        ];
    }
}
