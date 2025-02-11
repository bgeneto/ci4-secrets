<?php

declare(strict_types=1);

namespace Bgeneto\Secrets;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use Config\Database;
use Config\Services;
use Exception;
use Psr\Log\LoggerInterface;

class Secrets
{
    // NOTE: Intelephense may incorrectly report undefined properties
    // for $useCache, $cachePrefix, and $cacheTTL, even though they are
    // correctly defined and typed. This is likely due to how CodeIgniter
    // handles configuration. The code is functionally correct.
    private BaseConnection $db;
    private EncrypterInterface $encrypter;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private $config;
    private bool $useCache;
    private int $cacheTTL;
    private string $cachePrefix;

    public function __construct()
    {
        $this->db        = Database::connect();
        $this->encrypter = Services::encrypter();
        $this->logger    = Services::logger();
        $this->cache     = Services::cache();
        $this->config    = \config('secrets');

        // Load config values with type checks
        $this->useCache    = (bool) $this->config->useCache;
        $this->cachePrefix = (string) $this->config->cachePrefix;
        $this->cacheTTL    = (int) $this->config->cacheTTL;

        // Verify encryption is properly configured
        if (! $this->encrypter->key) {
            throw new EncryptionException('Encryption key is not set. Check your encryption configuration.');
        }
    }

    /**
     * Encrypts sensitive data using CI4's encryption service
     *
     * @throws EncryptionException
     */
    public function encrypt(string $data): string
    {
        try {
            return \bin2hex($this->encrypter->encrypt($data));
        } catch (Exception $e) {
            $this->logger->error('Encryption failed: ' . $e->getMessage());

            throw new EncryptionException('Failed to encrypt data');
        }
    }

    /**
     * Decrypts encrypted data using CI4's encryption service
     *
     * @throws EncryptionException
     */
    public function decrypt(string $encryptedData): string
    {
        try {
            return $this->encrypter->decrypt(\hex2bin($encryptedData));
        } catch (Exception $e) {
            $this->logger->error('Decryption failed: ' . $e->getMessage());

            throw new EncryptionException('Failed to decrypt  ' . $e->getMessage());
        }
    }

    /**
     * Stores encrypted value in database
     *
     * @throws DatabaseException|EncryptionException
     */
    public function store(string $key, string $value): bool
    {
        try {
            $encrypted = $this->encrypt($value);

            $data = [
                'key_name'        => $key,
                'encrypted_value' => $encrypted,
                'created_at'      => \date('Y-m-d H:i:s'),
                'updated_at'      => \date('Y-m-d H:i:s'),
            ];

            $builder = $this->db->table('secrets');

            // Check if key already exists
            if ($builder->where('key_name', $key)->countAllResults() > 0) {
                throw new DatabaseException('Key already exists. Use update() or --force instead.');
            }

            $result = $builder->insert($data);

            if ($result) {
                // Store in cache
                if ($this->useCache) {
                    $this->cache->save($this->cachePrefix . $key, $encrypted, $this->cacheTTL);
                }
            }
        } catch (EncryptionException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error storing encrypted value: ' . $e->getMessage());

            throw new DatabaseException('Failed to store encrypted value');
        }

        return true;
    }

    /**
     * Updates existing encrypted value
     *
     * @throws DatabaseException|EncryptionException
     */
    public function update(string $key, string $value): bool
    {
        try {
            $encrypted = $this->encrypt($value);

            $data = [
                'encrypted_value' => $encrypted,
                'updated_at'      => \date('Y-m-d H:i:s'),
            ];

            $result = $this->db->table('secrets')
                ->where('key_name', $key)
                ->update($data);

            if ($result) {
                // Update cache
                if ($this->useCache) {
                    $this->cache->save($this->cachePrefix . $key, $encrypted, $this->cacheTTL);
                }
            }
        } catch (EncryptionException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error updating encrypted value: ' . $e->getMessage());

            throw new DatabaseException('Failed to update encrypted value');
        }

        return true;
    }

    /**
     * Retrieves and decrypts stored value
     *
     * @throws EncryptionException
     */
    public function retrieve(string $key): ?string
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

                if (! $result) {
                    return null;
                }

                $encrypted = $result['encrypted_value'];
                // Store in cache for future requests
                if ($this->useCache) {
                    $this->cache->save($this->cachePrefix . $key, $encrypted, $this->cacheTTL);
                }
            }

            return $this->decrypt($encrypted);
        } catch (EncryptionException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving encrypted value: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Deletes stored encrypted value
     */
    public function delete(string $key): bool
    {
        try {
            $result = $this->db->table('secrets')
                ->where('key_name', $key)
                ->delete();

            if ($result) {
                // Delete from cache
                if ($this->useCache) {
                    $this->cache->delete($this->cachePrefix . $key);
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error deleting encrypted value: ' . $e->getMessage());

            return false;
        }

        return true;
    }
}
