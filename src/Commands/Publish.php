<?php

namespace bgeneto\Secrets\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;

/**
 * Publishes the secrets configuration
 */
class Publish extends BaseCommand
{
    /**
     * The Command's group
     *
     * @var string
     */
    protected $group = 'Secrets';
    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'secrets:publish';
    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Publish the secrets configuration.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'secrets:publish';

    /**
     * Source Path
     *
     * @var string
     */
    protected $sourcePath = '';

    /**
     * Enables task running
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $this->determineSourcePath();

        // Config
        if (CLI::prompt('Publish Config file?', ['y', 'n']) == 'y') {
            $this->publishConfig();
        }
    }

    protected function publishConfig()
    {
        $path = "{$this->sourcePath}/Config/Secrets.php";

        $content = file_get_contents($path);
        $content = str_replace('namespace Bgeneto\Secrets\Config', "namespace Config", $content);

        $this->writeFile("Config/Secrets.php", $content);
    }

    /**
     * Determines the current source path from which all other files are located.
     */
    protected function determineSourcePath()
    {
        $this->sourcePath = realpath(__DIR__ . '/../');

        if ($this->sourcePath == '/' || empty($this->sourcePath)) {
            CLI::error('Unable to determine the correct source directory. Bailing.');
            exit();
        }
    }

    /**
     * Write a file, catching any exceptions and showing a
     * nicely formatted error.
     *
     * @param string $path
     * @param string $content
     */
    protected function writeFile(string $path, string $content)
    {
        $config = new Autoload();
        $appPath = $config->psr4[APP_NAMESPACE];

        $directory = dirname($appPath . $path);

        if (!is_dir($directory)) {
            mkdir($directory);
        }

        try {
            write_file($appPath . $path, $content);
        } catch (\Exception $e) {
            $this->showError($e);
            exit();
        }

        $path = str_replace($appPath, '', $path);

        CLI::write(CLI::color('  created: ', 'green') . $path);
    }
}
