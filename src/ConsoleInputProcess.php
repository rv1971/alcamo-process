<?php

namespace alcamo\process;

/**
 * @brief Console input process opened by proc_open()
 *
 * Process that receives input from stdin and can send output and error output
 * to PHP.
 *
 * @date Last reviewed 2026-01-13
 */
class ConsoleOutputProcess extends OutputProcess
{
    public const DESCRIPTOR_SPEC = [
        1 => [ 'pipe', 'w' ],
        2 => [ 'pipe', 'w' ]
    ];
}
