<?php

namespace alcamo\process;

use alcamo\exception\{Closed, DirectoryNotFound, Opened, PopenFailed, Unsupported};

/**
 * @namespace alcamo::process
 *
 * @brief Classes for processes opened by proc_open()
 */

/**
 * @brief Process opened by proc_open()
 *
 * Provides some convenience by modelling a process as an object. In
 * particulare, alcamo::process::Process::MAGIC_METHODS provides a list of PHP
 * functions for streams that can be invoked magically as methods on this
 * object and call the respective PHP function with the corresponding pipe of
 * the child process as its first argument.
 *
 * @warning A code fragment like
 * `$resource = (new Process('dir'))->getStdout()`
 * will not work as expected because the existence of the `$resource` variable
 * will not inhibit PHP from destroying the `Process` object. Hence the process
 * will be terminated before reading anything from its output. To avoid this,
 * the process must be stored in a variable which is destroyed only after all
 * interaction with the child process is done.
 *
 * @sa [proc_open()](https://www.php.net/manual/en/function.proc-open)
 *
 * @date Last reviewed 2026-01-13
 */
class Process
{
    /// Descripor spec passed to proc_open()
    public const DESCRIPTOR_SPEC = [
        0 => [ 'pipe', 'r' ],
        1 => [ 'pipe', 'w' ],
        2 => [ 'pipe', 'w' ]
    ];

    /// Options passed to proc_open()
    public const OPTIONS = [];

    /**
     * Each of these can be called as a method and will call the php function
     * with this name and the corresponding pipe as its first parameter.
     */
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
        'stream_get_line'     => 1,
        'fputcsv'             => 0,
        'fputs'               => 0,
        'fstat'               => 0,
        'fwrite'              => 0
    ];

    private $cmd_; ///< string|array
    private $dir_; ///< ?string
    private $env_; ///< ?array

    protected $pipes_;   ///< ?array
    protected $process_; ///< ?resource

    /**
     * @brief Open the process, unless $deferOpen is `true`
     *
     * @param $cmd string|array command to execute
     *
     * @param $dir initial working directory, defaults to the working
     * directory of the current PHP process
     *
     * @param $env array with the environment variables for the command that
     * will be run, defaults the same environment as the current PHP process
     *
     * @param $deferOpen if `true`, do not yet start the process
     */
    public function __construct(
        $cmd,
        ?string $dir = null,
        ?array $env = null,
        ?bool $deferOpen = null
    ) {
        /** @warning For PHP versions < 7.4, an array $cmd is simply
         *  transformed to a command line by wrapping each item into single
         *  quotes. There are cases where this will not work. */
        if (is_array($cmd) && PHP_VERSION_ID < 70400) {
            $cmd = "'" . implode("' '", $cmd) . "'";
        }

        $this->cmd_ = $cmd;

        if (isset($dir)) {
            $this->dir_ = realpath($dir);

            if ($this->dir_ === false) {
                /** @throw alcamo::exception::DirectoryNotFound if
                 *  `realpath($dir)` fails */
                throw (new DirectoryNotFound())
                    ->setMessageContext([ 'path' => $dir ]);
            }
        }

        $this->env_ = $env;

        if (!$deferOpen) {
            $this->open();
        }
    }

    /// Upon destruction, close the process
    public function __destruct()
    {
        if (isset($this->process_)) {
            $this->close();
        }
    }

    /// Call the corresponding function, if supported
    public function __call(string $name, array $params)
    {
        if (!isset(static::MAGIC_METHODS[$name])) {
            /** @throw alcamo::exception::Unsupported is $name is not a
             *  supported method, i.e. not listed in @ref MAGIC_METHODS. */
            throw (new Unsupported())
                ->setMessageContext([ 'feature' => $name ]);
        }

        return $name($this->pipes_[static::MAGIC_METHODS[$name]], ...$params);
    }

    /// @return array|string
    public function getCmd()
    {
        return $this->cmd_;
    }

    public function getDir(): ?string
    {
        return $this->dir_;
    }

    public function getEnv(): ?array
    {
        return $this->env_;
    }

    /// PHP's end of any pipes connected to the child process
    public function getPipes(): array
    {
        return $this->pipes_;
    }

    /// File pointer to standard input of child process
    public function getStdin()
    {
        return $this->pipes_[0] ?? null;
    }

    /// File pointer to standard output of child process
    public function getStdout()
    {
        return $this->pipes_[1] ?? null;
    }

    /// File pointer to standard error output of child process
    public function getStderr()
    {
        return $this->pipes_[2] ?? null;
    }

    /// Open the process
    public function open(): void
    {
        if (isset($this->process_)) {
            /** @throw alcamo::exception::Opened if process is already
             *  open. */
            throw (new Opened())
                ->setMessageContext(
                    [
                        'objectType' => 'process',
                        'object' => $this->cmd_
                    ]
                );
        }

        $this->process_ = proc_open(
            $this->cmd_,
            $this->createDescriptorSpec(),
            $this->pipes_,
            $this->dir_,
            $this->env_,
            $this->createOtherOptions()
        );

        if ($this->process_ === false) {
            $this->process_ = null;

            /** @throw alcamo::exception::PopenFailed if
             *  [proc_open()](https://www.php.net/manual/en/function.proc-open)
             *  fails */
            throw (new PopenFailed())
                ->setMessageContext([ 'command' => $this->cmd_ ]);
        }
    }

    /// Close the process and return its exit code
    public function close(): int
    {
        if (!isset($this->process_)) {
            /** @throw alcamo::exception::Closed if process is already
             *  closed. */
            throw (new Closed())
                ->setMessageContext(
                    [
                        'objectType' => 'process',
                        'object' => $this->process_
                    ]
                );
        }

        $exitcode = proc_close($this->process_);

        $this->process_ = null;
        $this->pipes_ = null;

        return $exitcode;
    }

    /**
     * @brief Create descriptor spec as used in proc_open()
     *
     * This implementation returns alcamo::process::Process::DESCRIPTOR_SPEC,
     * whch may be overridden in derived classes.
     */
    protected function createDescriptorSpec(): array
    {
        return static::DESCRIPTOR_SPEC;
    }

    /**
     * @brief Create other options as used in proc_open()
     *
     * This implementation returns alcamo::process::Process::OPTIONS, whch may
     * be overridden in derived classes.
     */
    protected function createOtherOptions(): ?array
    {
        return static::OPTIONS;
    }
}
