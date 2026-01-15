<?php

namespace alcamo\process;

/**
 * @brief Factory for Process objects
 *
 * Simplifies creation of processes which have the same program, initial
 * working directory and/or environment. Useful as a base class for a wrapper
 * of an external program that offers many different uses.
 *
 * @date Last reviewed 2026-01-13
 */
class ProcessFactory
{
    use ContextTrait;

    /// Command to execute, to be overridden in derived classes
    public const DEFAULT_CMD = [ 'false' ];

    /// Default process class to create
    public const DEFAULT_PROCESS_CLASS = Process::class;

    private $processClass_; ///< string

    /**
     * @brief Set up the factory
     *
     * @param $cmd string|array command to execute, defaults to
     * alcamo::process::ProcessFactory::DEFAULT_CMD.
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
     * proc_open().
     *
     * @param $options Options passed to proc_open().
     *
     * @param $processClass Desired class to sue for the process to open,
     * defaults to alcamo::process::Process.
     */
    public function __construct(
        $cmd = null,
        ?string $dir = null,
        ?array $env = null,
        ?array $descriptorSpec = null,
        ?array $options = null,
        ?string $processClass = null
    ) {
        $this->initContext(
            $cmd ?? static::DEFAULT_CMD,
            $dir,
            $env,
            $descriptorSpec,
            $options
        );

        $this->processClass_ = $processClass ?? static::DEFAULT_PROCESS_CLASS;
    }

    public function getProcessClass(): string
    {
        return $this->processClass_;
    }

    /**
     * @brief Execute the program and return an object of the desired
     * process class
     *
     * @param $args string|array|null Command-line arguments to append
     *
     * @param $deferOpen if `true`, do not yet start the process
     */
    public function create(
        $args = null,
        ?bool $deferOpen = null
    ): Process {
        /** @warning If either $this->cmd_ or $args is a string, the other one
         *  is silently transformed to a string by wrapping each item into
         *  single quotes. There are cases where this will not lead to the
         *  desired result. */
        switch (true) {
            case !isset($args):
                $cmd = $this->cmd_;
                break;

            case is_array($this->cmd_) && is_array($args):
                $cmd = array_merge($this->cmd_, $args);
                break;

            case is_string($this->cmd_) && is_array($args):
                $cmd = "$this->cmd_ '" . implode("' '", $args) . "'";
                break;

            case is_array($this->cmd_) && is_string($args):
                $cmd = "'" . implode("' '", $this->cmd_) . "' $args";
                break;

            default:
                $cmd = "$this->cmd_ $args";
        }

        $class = $this->processClass_;

        return new $class(
            $cmd,
            $this->dir_,
            $this->env_,
            $this->descriptorSpec_,
            $this->options_,
            $deferOpen
        );
    }
}
