<?php

namespace Bgeneto\Secrets\Commands;

use Bgeneto\Secrets\Secrets;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\Commands;
use Config\Database;
use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Command line interface for managing application secrets.
 *
 * This class provides functionality to manage encrypted secrets through CLI commands:
 * - Add new secrets with optional force update
 * - Retrieve existing secrets
 * - Update existing secrets
 * - Delete secrets
 * - List all secret keys
 *
 * Usage:
 * ```bash
 * php spark secrets [operation] [--key=keyname] [--value=secretvalue] [--force=yes|no]
 * ```
 *
 * @category    CLI Command
 * @license     MIT
 * @see        https://github.com/bgeneto/ci4-secrets
 * @version     1.0.1
 * @since       1.0.0
 * @modified    2025-02-11
 */
class SecretsCommand extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Secrets';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'secrets';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Manages encrypted secrets in the database';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'secrets [operation] [--key=keyname] [--value=secretvalue] [--force=yes|no]';

    /**
     * The Command's Arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'operation' => 'Operation to perform: add, get, update, delete, list',
    ];

    /**
     * The Command's Options
     *
     * @var array<string, string>
     */
    protected $options = [
        '--key'   => 'Key name for the secret (required for add/get/update/delete)',
        '--value' => 'Value to store (required for add/update operations)',
        '--force' => 'Force update if key exists (yes/no)',
    ];

    /**
     * The Secrets service instance
     */
    private Secrets $secrets;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger   Instance of the logger
     * @param Commands        $commands Instance of the commands service
     */
    public function __construct(LoggerInterface $logger, Commands $commands)
    {
        parent::__construct($logger, $commands);
        $this->secrets = new Secrets();
    }

    /**
     * Execute the console command.
     *
     * This method is the entry point for the command execution and handles
     * the routing to specific operations based on the provided arguments.
     *
     * @param array<int, string> $params Command line parameters
     *
     * @return void
     */
    public function run(array $params)
    {
        // Check if operation is provided
        if (! isset($params[0])) {
            $this->showHelp();

            return;
        }

        // Get operation and options
        $operation = $params[0];
        $key       = CLI::getOption('key');
        $value     = CLI::getOption('value');
        $force     = \strtolower(CLI::getOption('force')) === 'yes';

        // Route to appropriate operation
        switch ($operation) {
            case 'add':
                $this->addSecret($key, $value, $force);
                break;

            case 'get':
                $this->getSecret($key);
                break;

            case 'update':
                $this->updateSecret($key, $value);
                break;

            case 'delete':
                $this->deleteSecret($key);
                break;

            case 'list':
                $this->listSecrets();
                break;

            default:
                $this->showHelp();
        }
    }

    /**
     * Display command help information.
     *
     * Shows detailed usage instructions, available operations,
     * options, and examples for using the secrets command.
     */
    public function showHelp(): void
    {
        CLI::write('Available Operations:', 'yellow');
        CLI::write('  add    : Add a new secret');
        CLI::write('  get    : Retrieve a secret by key');
        CLI::write('  update : Update an existing secret');
        CLI::write('  delete : Delete a secret');
        CLI::write('  list   : List all secret keys');
        CLI::newLine();
        CLI::write('Options:', 'yellow');
        CLI::write('  --key=keyname     : Key name for the secret');
        CLI::write('  --value=secret    : Value to store (for add/update operations)');
        CLI::write('  --force=yes|no    : Force update if key exists');
        CLI::newLine();
        CLI::write('Examples:', 'yellow');
        CLI::write('  php spark secrets add --key=api_key --value=sk-1234');
        CLI::write('  php spark secrets add --key=api_key --value=sk-1234 --force=yes');
        CLI::write('  php spark secrets get --key=api_key');
        CLI::write('  php spark secrets update --key=master_password --value=MyNewPasswd123');
        CLI::write('  php spark secrets delete --key=api_key');
        CLI::write('  php spark secrets list');
    }

    /**
     * Add a new secret to the storage.
     *
     * If the key already exists and force option is not enabled,
     * the operation will fail. Interactive mode is supported when
     * key or value are not provided.
     *
     * @param string|null $key   The key name for the secret
     * @param string|null $value The secret value to store
     * @param bool        $force Whether to force update if key exists
     */
    private function addSecret(?string $key, ?string $value, bool $force = false): void
    {
        // Get key from CLI option if not passed as parameter
        $key ??= CLI::getOption('key');

        // Get value from CLI option if not passed as parameter
        $value ??= CLI::getOption('value');

        // Only prompt if still empty after checking CLI options
        if (empty($key)) {
            $key = CLI::prompt('Enter key name');
            if (empty($key)) {
                CLI::error('Key is required');

                return;
            }
        }

        if (empty($value)) {
            $value = CLI::prompt('Enter value');
            if (empty($value)) {
                CLI::error('Value is required');

                return;
            }
        }

        try {
            $result = $this->secrets->store($key, $value);
            if ($result) {
                CLI::write("Secret '{$key}' stored successfully!", 'green');
            } else {
                CLI::error('Failed to store secret');
            }
        } catch (Throwable $th) {
            if ($force) {
                try {
                    $result = $this->secrets->update($key, $value);
                    if ($result) {
                        CLI::write("Secret '{$key}' updated successfully!", 'green');
                    } else {
                        CLI::error('Failed to update secret');
                    }
                } catch (Throwable $th) {
                    CLI::error($th->getMessage());
                }
            } else {
                CLI::error($th->getMessage());
                CLI::write('Use --force=yes to update existing key', 'yellow');
            }
        }
    }

    /**
     * Retrieve a secret by its key.
     *
     * @param string|null $key The key name to retrieve
     */
    private function getSecret(?string $key): void
    {
        // Get key from CLI option if not passed as parameter
        $key ??= CLI::getOption('key');

        if (empty($key)) {
            $key = CLI::prompt('Enter key name');
            if (empty($key)) {
                CLI::error('Key is required');

                return;
            }
        }

        try {
            $value = $this->secrets->retrieve($key);
            if ($value !== null) {
                CLI::write("Current value for '{$key}' is:", 'green');
                CLI::write($value);
            } else {
                CLI::error('Secret not found');
            }
        } catch (Exception $e) {
            CLI::error($e->getMessage());
        }
    }

    /**
     * Update an existing secret.
     *
     * @param string|null $key   The key name to update
     * @param string|null $value The new secret value
     */
    private function updateSecret(?string $key, ?string $value): void
    {
        // Get key and value from CLI options if not passed as parameters
        $key ??= CLI::getOption('key');
        $value ??= CLI::getOption('value');

        if (empty($key)) {
            $key = CLI::prompt('Enter key name');
            if (empty($key)) {
                CLI::error('Key is required');

                return;
            }
        }

        if (empty($value)) {
            $value = CLI::prompt('Enter new value');
            if (empty($value)) {
                CLI::error('Value is required');

                return;
            }
        }

        try {
            $result = $this->secrets->update($key, $value);
            if ($result) {
                CLI::write("Secret '{$key}' updated successfully!", 'green');
            } else {
                CLI::error('Secret not found or update failed');
            }
        } catch (Throwable $th) {
            CLI::error($th->getMessage());
        }
    }

    /**
     * Delete a secret by its key.
     *
     * @param string|null $key The key name to delete
     */
    private function deleteSecret(?string $key): void
    {
        // Get key from CLI option if not passed as parameter
        $key ??= CLI::getOption('key');

        if (empty($key)) {
            $key = CLI::prompt('Enter key name');
            if (empty($key)) {
                CLI::error('Key is required');

                return;
            }
        }

        if (! CLI::prompt('Are you sure you want to delete this secret?', ['y', 'n']) === 'y') {
            CLI::write('Operation cancelled', 'yellow');

            return;
        }

        try {
            $result = $this->secrets->delete($key);
            if ($result) {
                CLI::write("Secret '{$key}' deleted successfully!", 'green');
            } else {
                CLI::error('Secret not found or delete failed');
            }
        } catch (Throwable $th) {
            CLI::error($th->getMessage());
        }
    }

    /**
     * List all available secret keys.
     *
     * Displays a table with key names and their creation/update timestamps.
     * Note: Secret values are not displayed for security reasons.
     */
    private function listSecrets(): void
    {
        try {
            $secrets = Database::connect()
                ->table('secrets')
                ->select('key_name, created_at, updated_at')
                ->get()
                ->getResultArray();

            if (empty($secrets)) {
                CLI::write('No secrets found', 'yellow');

                return;
            }

            CLI::write('Available secrets:', 'green');
            CLI::table($secrets, [
                'Key'     => 'key_name',
                'Created' => 'created_at',
                'Updated' => 'updated_at',
            ]);
        } catch (Throwable $th) {
            CLI::error($th->getMessage());
        }
    }
}
