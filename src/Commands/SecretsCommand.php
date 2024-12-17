<?php

namespace Bgeneto\Secrets\Commands;

use Bgeneto\Secrets\Libraries\Secrets;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\Commands;
use Config\Database;
use Psr\Log\LoggerInterface;

/**
 * Command line interface for managing application secrets.
 *
 * This class provides functionality to manage secrets through CLI commands:
 * - Add new secrets
 * - Update existing secrets
 * - Delete secrets
 * - List all secret keys
 *
 * @package    Bgeneto\Secrets
 * @subpackage Commands
 * @category   CLI Command
 * @author     bgeneto
 * @link       https://github.com/bgeneto/secrets
 *
 * @method void run(array $params)
 * @method void showHelp()
 * @method void addSecret()
 * @method void listSecrets()
 * @method void updateSecret()
 * @method void deleteSecret()
 */
class SecretsCommand extends BaseCommand
{
	protected $group       = 'Secrets';
	protected $name        = 'secrets';
	protected $description = 'Manages encrypted secrets in the database';

	protected $usage = 'secrets [operation] [options]';
	protected $arguments = [
		'operation' => 'Operation to perform: add, get, update, delete, list'
	];

	protected $options = [
		'--key'   => 'Key name for the secret',
		'--value' => 'Value to store (for add/update operations)',
		'--force' => 'Force update if key exists',
	];

	private Secrets $secrets;

	public function __construct(LoggerInterface $logger, Commands $commands)
	{
		parent::__construct($logger, $commands);
		$this->secrets = new Secrets();
	}

	public function run(array $params)
	{
		if (!isset($params[0])) {
			$this->showHelp();
			return;
		}

		$operation = $params[0];

		switch ($operation) {
			case 'add':
				$this->addSecret();
				break;
			case 'update':
				$this->updateSecret();
				break;
			case 'delete':
				$this->deleteSecret();
				break;
			case 'list':
				$this->listSecrets();
				break;
			default:
				$this->showHelp();
		}
	}

	/**
	 * Display command help
	 */
	public function showHelp()
	{
		CLI::write('Available Operations:', 'yellow');
		CLI::write('  add    : Add a new secret');
		CLI::write('  update : Update an existing secret');
		CLI::write('  delete : Delete a secret');
		CLI::write('  list   : List all secret keys');
		CLI::newLine();
		CLI::write('Options:', 'yellow');
		CLI::write('  --key   : Key name for the secret');
		CLI::write('  --value : Value to store (for add/update operations)');
		CLI::write('  --force : Force update if key exists');
		CLI::newLine();
		CLI::write('Examples:', 'yellow');
		CLI::write('  php spark secrets add --key=api_key --value=secret123');
		CLI::write('  php spark secrets update --key=api_key --value=newSecret123');
		CLI::write('  php spark secrets delete --key=api_key');
		CLI::write('  php spark secrets list');
	}

	/**
	 * Adds a new secret
	 */
	private function addSecret(): void
	{
		$key = $this->getKey();
		if (empty($key) || $key === '1') {
			CLI::error('Key is required');
			return;
		}

		$value = $this->getValue();
		if (empty($value) || $value === '1') {
			CLI::error('Value is required');
			return;
		}

		try {
			$result = $this->secrets->store($key, $value, false);
			if ($result) {
				CLI::write("Secret '{$key}' stored successfully!", 'green');
			} else {
				CLI::error("Failed to store secret");
			}
		} catch (\Throwable $th) {
			if (CLI::getOption('force')) {
				try {
					$result = $this->secrets->update($key, $value, false);
					if ($result) {
						CLI::write("Secret '{$key}' updated successfully!", 'green');
					} else {
						CLI::error("Failed to update secret");
					}
				} catch (\Throwable $th) {
					CLI::error($th->getMessage());
				}
			} else {
				CLI::error($th->getMessage());
				CLI::write("Use --force to update existing key", 'yellow');
			}
		}
	}

	/**
	 * Updates an existing secret
	 */
	private function updateSecret(): void
	{
		$key = $this->getKey();
		if (empty($key)) return;

		$value = $this->getValue();
		if (empty($value)) return;

		try {
			$result = $this->secrets->update($key, $value, false);
			if ($result) {
				CLI::write("Secret '{$key}' updated successfully!", 'green');
			} else {
				CLI::error("Secret not found or update failed");
			}
		} catch (\Throwable $th) {
			CLI::error($th->getMessage());
		}
	}

	/**
	 * Deletes a secret
	 */
	private function deleteSecret(): void
	{
		$key = $this->getKey();
		if (empty($key)) return;

		if (!CLI::prompt('Are you sure you want to delete this secret?', ['y', 'n']) === 'y') {
			CLI::write('Operation cancelled', 'yellow');
			return;
		}

		try {
			$result = $this->secrets->delete($key, false);
			if ($result) {
				CLI::write("Secret '{$key}' deleted successfully!", 'green');
			} else {
				CLI::error("Secret not found or delete failed");
			}
		} catch (\Throwable $th) {
			CLI::error($th->getMessage());
		}
	}

	/**
	 * Lists all secret keys (not values)
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
				'Key' => 'key_name',
				'Created' => 'created_at',
				'Updated' => 'updated_at'
			]);
		} catch (\Throwable $th) {
			CLI::error($th->getMessage());
		}
	}

	/**
	 * Gets key from command line
	 */
	private function getKey(): ?string
	{
		$key = CLI::getOption('key');
		if (empty($key)) {
			$key = CLI::prompt('Enter key name');
		}

		if (empty($key)) {
			CLI::error('Key is required');
			return null;
		}

		return $key;
	}

	/**
	 * Gets value from command line
	 */
	private function getValue(): ?string
	{
		$value = CLI::getOption('value');
		if (empty($value)) {
			$value = CLI::prompt('Enter value', null, true); // true for hidden input
		}

		if (empty($value)) {
			CLI::error('Value is required');
			return null;
		}

		return $value;
	}
}
