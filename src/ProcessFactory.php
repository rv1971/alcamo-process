<?php

namespace alcamo\process;

use alcamo\exception\DirectoryNotFound;

/**
 * @brief Factory for Process objects
 *
 * Simplifies creation of processes which have the same program, initial
 * working directory and environment. Useful as a base class for a wrapper of
 * an external program that offers many different uses.
 *
 * @date Last reviewed 2021-06-15
 */
class ProcessFactory
{
    /// Program to start, to be overridden in derived classes
    public const DEFAULT_PROGRAM = 'false';

    /// Process class to create
    public const PROCESS_CLASS = Process::class;

    private $dir_;     ///< ?string
    private $program_; ///< string
    private $options_; ///< array
    private $env_;     ///< ?array

    /**
     * @brief Set up the factory
     *
     * @param $dir initial working directory, defaults to the working dir of
     * the current PHP process
     *
     * @brief $program Program to start, defaults to @ref DEFAULT_PROGRAM
     *
     * @brief $options Options to always pass to the program
     *
     * @param $env array with the environment variables for the command that
     * will be run, defaults the same environment as the current PHP process
     */
    public function __construct(
        ?string $dir = null,
        ?string $program = null,
        ?array $options = null,
        ?array $env = null
    ) {
        if (isset($dir)) {
            $this->dir_ = realpath($dir);

            if ($this->dir_ === false) {
                /** @throw alcamo::exception::DirectoryNotFound if
                 *  `realpath($dir)` fails */
                throw new DirectoryNotFound($dir);
            }
        }

        $this->program_ = $program ?? static::DEFAULT_PROGRAM;
        $this->options_ = (array)$options;
        $this->env_ = $env;
    }

    public function getDir(): ?string
    {
        return $this->dir_;
    }

    public function getProgram(): string
    {
        return $this->program_;
    }

    public function getOptions(): array
    {
        return $this->options_;
    }

    public function getEnv(): ?array
    {
        return $this->env_;
    }

    /// Execute the program and return an obejct of class @ref PROCESS_CLASS
    public function exec($args = null): Process
    {
        $cmd = array_merge([ $this->program_ ], $this->options_, (array)$args);

        $class = static::PROCESS_CLASS;

        return new $class($cmd, $this->dir_, $this->env_);
    }
}
