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
 * @date Last reviewed 2026-01-13
 */
class ProcessFactory
{
    /// Program to start, to be overridden in derived classes
    public const DEFAULT_PROGRAM = 'false';

    /// Default arguments to pass to the program
    public const DEFAULT_ARGS = [];

    /// Default process class to create
    public const DEFAULT_PROCESS_CLASS = Process::class;

    private $dir_;          ///< ?string
    private $program_;      ///< string
    private $args_;         ///< array
    private $env_;          ///< ?array
    private $processClass_; ///< string

    /**
     * @brief Set up the factory
     *
     * @param $dir initial working directory, defaults to the working
     * directory of the current PHP process
     *
     * @brief $program Program to start, defaults to
     * alcamo::process::ProcessFactory::DEFAULT_PROGRAM
     *
     * @brief $args Arguments to always pass to the program, defaults to
     * alcamo::process::ProcessFactory::DEFAULT_ARGS
     *
     * @param $env array with the environment variables for the command that
     * will be run, defaults the same environment as the current PHP process
     */
    public function __construct(
        ?string $dir = null,
        ?string $program = null,
        ?array $args = null,
        ?array $env = null,
        ?string $processClass = null
    ) {
        if (isset($dir)) {
            $this->dir_ = realpath($dir);

            if ($this->dir_ === false) {
                /** @throw alcamo::exception::DirectoryNotFound if
                 *  `realpath($dir)` fails */
                throw (new DirectoryNotFound())
                    ->setMessageContext([ 'path' => $dir ]);
            }
        }

        $this->program_ = $program ?? static::DEFAULT_PROGRAM;
        $this->args_ = $args ?? static::DEFAULT_ARGS;
        $this->env_ = $env;
        $this->processClass_ = $processClass ?? static::DEFAULT_PROCESS_CLASS;
    }

    public function getDir(): ?string
    {
        return $this->dir_;
    }

    public function getProgram(): string
    {
        return $this->program_;
    }

    public function getArgs(): array
    {
        return $this->args_;
    }

    public function getEnv(): ?array
    {
        return $this->env_;
    }

    public function getProcessClass(): string
    {
        return $this->processClass_;
    }

    /**
     * @brief Execute the program and return an object of the desired
     * process class
     *
     * @param $args ?string|?array Command-line arguments to append
     *
     * @param $deferOpen if `true`, do not yet start the process
     */
    public function create(
        $args = null,
        ?bool $deferOpen = null
    ): Process {
        $class = $this->processClass_;

        return new $class(
            array_merge([ $this->program_ ], $this->args_, (array)$args),
            $this->dir_,
            $this->env_,
            $deferOpen
        );
    }
}
