<?php

namespace alcamo\process;

use alcamo\exception\Unsupported;

/**
 * @brief Output process opened by proc_open()
 *
 * Process that received output from the current PHP process. The functions
 * listed in @ref MAGIC_METHODS can be called as methods to this class.
 *
 * @sa [proc_open()](https://www.php.net/manual/en/function.proc-open)
 *
 * @date Last reviewed 2021-06-15
 */
class OutputProcess extends Process
{
    /**
     * Each of these can be called as a method and will call the php function
     * with this name and the process' stdin as its first parameter.
     */
    public const MAGIC_METHODS = [
        'fputcsv',
        'fputs',
        'fstat',
        'fwrite',
        'stream_get_meta_data'
    ];

    /// Call the corresponding function, if supported
    public function __call($name, $params)
    {
        if (!in_array($name, static::MAGIC_METHODS)) {
            /** @throw alcamo::exception::Unsupported is $name is not a
             *  supported method, i.e. not listed in @ref MAGIC_METHODS. */
            throw new Unsupported("$name()");
        }

        return $name($this->pipes_[0], ...$params);
    }
}
