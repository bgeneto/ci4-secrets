<?php

namespace Bgeneto\Secrets\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use Config\Database;
use Config\Services;

class Secrets
{
	private BaseConnection $db;
	private EncrypterInterface $encrypter;
	private $logger;
	private $cache;
	private string $cachePrefix = 'secure_storage_';
	private int $cacheTTL = 7200;

	public function __construct()
	{
		$this->db = Database::connect();
		$this->encrypter = service('encrypter');
		$this->logger = Services::logger();
		$this->cache = Services::cache();

		// Verify encryption is properly configured
		if (!$this->encrypter->key) {
			throw new EncryptionException('Encryption key is not set. Check your encryption configuration.');
		}
	}

	/**
	 * Encrypts sensitive data using CI4's encryption service
	 *
	 * @param string $data
	 * @return string
	 * @throws EncryptionException
	 */
	public function encrypt(string $data): string
	{
		try {
			return bin2hex($this->encrypter->encrypt($data));
		} catch (\Exception $e) {
			$this->logger->error('Encryption failed: ' . $e->getMessage());
			throw new EncryptionException('Failed to encrypt data');
		}
	}

	/**
	 * Decrypts encrypted data using CI4's encryption service
	 *
	 * @param string $encryptedData
	 * @return string
	 * @throws EncryptionException
	 */
	public function decrypt(string $encryptedData): string
	{
		try {
			return $this->encrypter->decrypt(hex2bin($encryptedData));
		} catch (\Exception $e) {
			$this->logger->error('Decryption failed: ' . $e->getMessage());
			throw new EncryptionException('Failed to decrypt data: ' . $e->getMessage());
		}
	}

	/**
	 * Stores encrypted value in database
	 *
	 * @param string $key
	 * @param string $value
	 * @param bool $log
	 * @return bool
	 * @throws DatabaseException|EncryptionException
	 */
	public function store(string $key, string $value, bool $log = true): bool
	{
		try {
			$encrypted = $this->encrypt($value);

			$data = [
				'key_name' => $key,
				'encrypted_value' => $encrypted,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];

			$builder = $this->db->table('secrets');

			// Check if key already exists
			if ($builder->where('key_name', $key)->countAllResults() > 0) {
				throw new DatabaseException('Key already exists. Use update() instead.');
			}

			$result = $builder->insert($data);

			if ($result) {
				// Store in cache
				$this->cache->save($this->cachePrefix . $key, $encrypted, $this->cacheTTL);

				if ($log) {
					$this->logAccess('store', $key);
				}
			}
		} catch (EncryptionException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error('Error storing encrypted value: ' . $e->getMessage());
			throw new DatabaseException('Failed to store encrypted value');
		}

		return true;
	}

	/**
	 * Updates existing encrypted value
	 *
	 * @param string $key
	 * @param string $value
	 * @param bool $log
	 * @return bool
	 * @throws DatabaseException|EncryptionException
	 */
	public function update(string $key, string $value, bool $log = true): bool
	{
		try {
			$encrypted = $this->encrypt($value);

			$data = [
				'encrypted_value' => $encrypted,
				'updated_at' => date('Y-m-d H:i:s')
			];

			$result = $this->db->table('secrets')
				->where('key_name', $key)
				->update($data);

			if ($result) {
				// Update cache
				$this->cache->save($this->cachePrefix . $key, $encrypted, $this->cacheTTL);

				if ($log) {
					$this->logAccess('update', $key);
				}
			}
		} catch (EncryptionException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error('Error updating encrypted value: ' . $e->getMessage());
			throw new DatabaseException('Failed to update encrypted value');
		}

		return true;
	}

	/**
	 * Retrieves and decrypts stored value
	 *
	 * @param string $key
	 * @param bool $log
	 * @return string|null
	 * @throws EncryptionException
	 */
	public function retrieve(string $key, bool $log = true): ?string
	{
		try {
			// Try to get from cache first
			$encrypted = $this->cache->get($this->cachePrefix . $key);

			if ($encrypted === null) {
				// If not in cache, get from database
				$result = $this->db->table('secrets')
					->select('encrypted_value')
					->where('key_name', $key)
					->get()
					->getRowArray();

				if (!$result) {
					return null;
				}

				$encrypted = $result['encrypted_value'];
				// Store in cache for future requests
				$this->cache->save($this->cachePrefix . $key, $encrypted, $this->cacheTTL);
			}

			if ($log) {
				$this->logAccess('retrieve', $key);
			}

			return $this->decrypt($encrypted);
		} catch (EncryptionException $e) {
			throw $e;
		} catch (\Exception $e) {
			$this->logger->error('Error retrieving encrypted value: ' . $e->getMessage());
			return null;
		}
	}

	/**
	 * Deletes stored encrypted value
	 *
	 * @param string $key
	 * @param bool $log
	 * @return bool
	 */
	public function delete(string $key, bool $log = true): bool
	{
		try {
			$result = $this->db->table('secrets')
				->where('key_name', $key)
				->delete();

			if ($result) {
				// Delete from cache
				$this->cache->delete($this->cachePrefix . $key);

				if ($log) {
					$this->logAccess('delete', $key);
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('Error deleting encrypted value: ' . $e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Logs access to sensitive data
	 *
	 * @param string $action
	 * @param string $key
	 * @return void
	 */
	private function logAccess(string $action, string $key): void
	{
		try {
			$data = [
				'action' => $action,
				'key_name' => $key,
				'user_id' => user_id() ?? 0,
				'ip_address' => Services::request()->getIPAddress(),
				'created_at' => date('Y-m-d H:i:s')
			];

			$this->db->table('access_logs')->insert($data);
		} catch (\Exception $e) {
			$this->logger->error('Error logging access: ' . $e->getMessage());
		}
	}
}
