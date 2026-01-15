<?php

namespace alcamo\process;

use alcamo\exception\DirectoryNotFound;

/**
 * @brief Common context elements used in Process and ProcessFactory
 *
 * @date Last reviewed 2026-01-13
 */
trait ContextTrait
{
    private $cmd_;            ///< string|array
    private $dir_;            ///< ?string
    private $env_;            ///< ?array
    private $descriptorSpec_; ///< array
    private $options_;        ///< ?array

    /** @return array|string */
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

    public function getDescriptorSpec(): array
    {
        return $this->descriptorSpec_;
    }

    public function getOptions(): ?array
    {
        return $this->options_;
    }

    /**
     * @brief Store the arguments in class properties.
     *
     * @param $cmd string|array command to execute.
     *
     * @param $dir initial working directory, defaults to the working
     * directory of the current PHP process. If given, realpath() will be
     * applied to it.
     *
     * @param $env array of the environment variables for the command that will
     * be run, defaults to the same environment as the current PHP process.
     *
     * @param $descriptorSpec Descriptor spec passed to proc_open().
     *
     * @param $options Options passed to proc_open().
     */
    protected function initContext(
        $cmd,
        ?string $dir,
        ?array $env,
        ?array $descriptorSpec,
        ?array $options
    ): void {
        /** @warning For PHP versions < 7.4, an array $cmd is silently
         *  transformed to a command line string by wrapping each item into
         *  single quotes. There are cases where this will not lead to the
         *  desired result. */
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

        $this->descriptorSpec_ = (array)$descriptorSpec;

        $this->options_ = $options;
    }
}
