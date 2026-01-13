<?php

namespace alcamo\process;

use alcamo\exception\Unsupported;

/**
 * @brief Process opened by proc_open() that can give input to PHP
 *
 * @date Last reviewed 2026-01-13
 */
class InputProcess extends Process
{
    public const MAGIC_METHODS = [
        'feof'                => 1,
        'fgetc'               => 1,
        'fgetcsv'             => 1,
        'fgets'               => 1,
        'fgetss'              => 1,
        'fpassthru'           => 1,
        'fread'               => 1,
        'fscanf'              => 1,
        'fstat'               => 1,
        'stream_get_contents' => 1,
        'stream_get_line'     => 1
    ];
}
