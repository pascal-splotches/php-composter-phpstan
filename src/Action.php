<?php

namespace PHPComposter\PHPComposter\PHPStan;

use Eloquent\Pathogen\FileSystem\FileSystemPath;
use Exception;
use PHPComposter\PHPComposter\BaseAction;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class Action
 *
 * @since 0.1.0
 *
 * @package PHPComposter\PHPComposter\PHPStan
 *
 * @author Pascal Scheepers <pascal@splotch.es>
 */
class Action extends BaseAction
{
    const EXIT_ERRORS_FOUND = 1;
    const EXIT_WITH_EXCEPTIONS = 2;

    const OS_WINDOWS = 'Windows';
    const OS_BSD = 'BSD';
    const OS_DARWIN = 'Darwin';
    const OS_SOLARIS = 'Solaris';
    const OS_LINUX = 'Linux';
    const OS_UNKNOWN = 'Unknown';

    /**
     * Statically analyse files
     *
     * @since 0.1.0
     */
    public function runPhpStan()
    {
        try {
            $this->checkPhpStanConfiguration();

            $process = new Process([$this->getPhpStanPath(), 'analyse']);
            $process->run();

            $this->write($process->getOutput());

            if ($process->isSuccessful()) {
                $this->success('PHPStan detected no errors, allowing commit to proceed.');
            }

            $this->error('PHPStan detected errors, aborting commit!', self::EXIT_ERRORS_FOUND);
        } catch (Exception $e) {
            $this->error('An error occurred trying to run PHPStan: ' . PHP_EOL . $e->getMessage(), self::EXIT_WITH_EXCEPTIONS);
        }
    }

    /**
     * Build the path to the PHPStan binary
     *
     * @return string
     */
    protected function getPhpStanPath()
    {
        $root = FileSystemPath::fromString($this->root);

        $phpStanPath = $root->joinAtomSequence(
            [
                "vendor",
                "bin",
                $this->getPhpStanBinary(),
            ]
        );

        return $phpStanPath->string();
    }

    /**
     * Build the path to the PHPStan configuration
     *
     * @return string
     */
    protected function getPhpStanConfigurationPath()
    {
        $root = FileSystemPath::fromString($this->root);

        $phpStanConfigurationPath = $root->joinAtomSequence(
            [
                "phpstan.neon",
            ]
        );

        return $phpStanConfigurationPath->string();
    }

    /**
     * Return the correct binary for the current OS
     *
     * @return string
     */
    protected function getPhpStanBinary()
    {
        switch (PHP_OS_FAMILY) {
            case self::OS_WINDOWS:
                return "phpstan.bat";
                break;
            default:
                return "phpstan";
                break;
        }
    }

    /**
     * Check whether PHPStan Configuration is available
     *
     * @throws RuntimeException
     */
    protected function checkPhpStanConfiguration()
    {
        if (!file_exists($this->getPhpStanConfigurationPath())) {
            throw new RuntimeException("PHPStan Configuration file missing");
        }
    }
}
