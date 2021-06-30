<?php

namespace alcamo\process;

use alcamo\exception\{Closed, DirectoryNotFound, Opened, PopenFailed};

/**
 * @brief Process opened by proc_open()
 *
 * @sa [proc_open()](https://www.php.net/manual/en/function.proc-open)
 *
 * @date Last reviewed 2021-06-15
 */
class Process
{
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
     * @param $dir initial working directory, defaults to the working dir of
     * the current PHP process
     *
     * @param $env array with the environment variables for the command that
     * will be run, defaults the same environment as the current PHP process
     *
     * @param $deferOpen if `true`, do not yet open the pipe
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
                throw new DirectoryNotFound($dir);
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

    /// Open the process
    public function open(): void
    {
        if (isset($this->process_)) {
            /** @throw alcamo::exception::Opened if process is already
             *  open. */
            throw new Opened($this->process_);
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
            throw new PopenFailed($this->cmd_);
        }
    }

    /// Close the process and return its exit code
    public function close(): int
    {
        if (!isset($this->process_)) {
            /** @throw alcamo::exception::Closed if process is already
             *  closed. */
            throw new Closed('process');
        }

        $exitcode = proc_close($this->process_);

        $this->process_ = null;
        $this->pipes_ = null;

        return $exitcode;
    }

    /// Return file pointer to standard input of process
    public function getStdin()
    {
        return $this->pipes_[0];
    }

    /// Return file pointer to standard output of process
    public function getStdout()
    {
        return $this->pipes_[1];
    }

    /// Return file pointer to standard error output of process
    public function getStderr()
    {
        return $this->pipes_[2];
    }

    /// Create descriptorspec as used in proc_open()
    protected function createDescriptorSpec(): array
    {
        /** The default implementation creates the three standard file
         *  descriptors. May be overridden in derived classes. */

        return [
            0 => [ 'pipe', 'r' ],
            1 => [ 'pipe', 'w' ],
            2 => [ 'pipe', 'w' ]
        ];
    }

    /// Create other options as used in proc_open()
    protected function createOtherOptions(): ?array
    {
        /** The default implementation creates no other options. May be
         *  overridden in derived classes. */
        return null;
    }
}
