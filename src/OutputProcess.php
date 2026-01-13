<?php

namespace alcamo\process;

/**
 * @brief Process opened by proc_open() that can receive output from PHP
 *
 * @date Last reviewed 2026-01-13
 */
class OutputProcess extends Process
{
    public const MAGIC_METHODS = [
        'fputcsv' => 0,
        'fputs'   => 0,
        'fstat'   => 0,
        'fwrite'  => 0
    ];
}
