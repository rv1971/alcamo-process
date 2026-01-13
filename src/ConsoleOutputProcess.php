<?php

namespace alcamo\process;

/**
 * @brief Console output process opened by proc_open()
 *
 * Process that receives output from PHP process and can write to stdout and
 * stderr.
 *
 * @date Last reviewed 2026-01-13
 */
class ConsoleOutputProcess extends OutputProcess
{
    public const DESCRIPTOR_SPEC = [ 0 => [ 'pipe', 'r' ] ];
}
