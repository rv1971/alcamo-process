<?php

namespace alcamo\process;

use alcamo\exception\{Closed, Opened, PopenFailed, Unsupported};

/**
 * @namespace alcamo::process
 *
 * @brief Classes for processes opened by proc_open()
 */

/**
 * @brief Process opened by proc_open()
 *
 * Provides some convenience by modelling a process as an object. In
 * particular, alcamo::process::Process::MAGIC_METHODS provides a list of PHP
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
    use ContextTrait;

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

    protected static $stdin_;  ///< resource
    protected static $stdout_; ///< resource
    protected static $stderr_; ///< resource

    protected $pipes_;   ///< ?array
    protected $process_; ///< ?resource

    /**
     * @brief Open the process, unless $deferOpen is `true`
     *
     * @param $cmd string|array command to execute.
     *
     * @param $dir initial working directory, defaults to the working
     * directory of the current PHP process. If given, realpath() will be
     * applied to it.
     *
     * @param $env array of the environment variables for the command that
     * will be run, defaults to the same environment as the current PHP
     * process.
     *
     * @paramm $descriptorSpec Descriptor spec passed to
     * proc_open(). alcamo::process::Process::DESCRIPTOR_SPEC will be added to
     * it. The descriptors 0, 1 and 2 will be assigned to their usual
     * destinations if not specified.
     *
     * @param $options Options passed to proc_open(). Defaults to
     * alcamo::process::Process::OPTIONS.
     *
     * @param $deferOpen if `true`, do not yet start the process
     */
    public function __construct(
        $cmd,
        ?string $dir = null,
        ?array $env = null,
        ?array $descriptorSpec = null,
        ?array $options = null,
        ?bool $deferOpen = null
    ) {
        if (!isset(self::$stdin_)) {
            self::$stdin_ = defined('STDIN')
                ? constant('STDIN')
                : fopen('php://stdin', 'r');

            self::$stdout_ = defined('STDOUT')
                ? constant('STDOUT')
                : fopen('php://stdout', 'w');

            self::$stderr_ = defined('STDERR')
                ? constant('STDERR')
                : fopen('php://stderr', 'w');
        }

        $this->initContext(
            $cmd,
            $dir,
            $env,
            (array)$descriptorSpec
                + static::DESCRIPTOR_SPEC
                + [ self::$stdin_, self::$stdout_, self::$stderr_ ],
            $options ?? static::OPTIONS
        );

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

    /// PHP's end of any pipes connected to the child process
    public function getPipes(): array
    {
        return $this->pipes_;
    }

    /// File pointer to standard input of child process, if any
    public function getStdin()
    {
        return $this->pipes_[0] ?? null;
    }

    /// File pointer to standard output of child process, if any
    public function getStdout()
    {
        return $this->pipes_[1] ?? null;
    }

    /// File pointer to standard error output of child process, if any
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
            $this->descriptorSpec_,
            $this->pipes_,
            $this->dir_,
            $this->env_,
            $this->options_
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
}
