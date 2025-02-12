<?php

declare(strict_types=1);

namespace Bgeneto\Secrets;

use Bgeneto\Secrets\Models\BaseSecretModel;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use Config\Services;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Secrets
{
    private EncrypterInterface $encrypter;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    /**
     * @var Config\Secrets
     */
    private $config;

    private $secretModel;

    public function __construct(?EncrypterInterface $encrypter = null, ?LoggerInterface $logger = null)
    {
        $this->encrypter = $encrypter ?? Services::encrypter();
        $this->logger    = $logger ?? Services::logger();
        $this->cache     = Services::cache();
        $this->config    = \config('secrets');

        // Instantiate the configured model
        $modelClass = $this->config->modelClass;

        try {
            $this->secretModel = new $modelClass();
            if (! $this->secretModel instanceof BaseSecretModel) {
                throw new RuntimeException('The configured model must extend ' . BaseSecretModel::class);
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to instantiate the configured model: ' . $modelClass . '. Error: ' . $e->getMessage());
        }

        // Verify encryption is properly configured
        /** @phpstan-ignore-next-line */
        if (! $this->encrypter->key) {
            throw new EncryptionException('Encryption key is not set. Check your encryption configuration.');
        }
    }

    /**
     * Encrypts sensitive data using CI4's encryption service
     *
     * @throws EncryptionException
     */
    private function encrypt(string $data): string
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
    private function decrypt(string $encryptedData): string
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

            $result = $this->secretModel->addSecret($key, $encrypted);

            // Store in cache
            if ($result && $this->config->useCache) {
                $this->cache->save($this->config->cachePrefix . $key, $encrypted, $this->config->cacheTTL);
            }
        } catch (EncryptionException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Error storing encrypted value: ' . $e->getMessage());

            throw new DatabaseException('Failed to store the encrypted value! Maybe the key already exists?');
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

            $result = $this->secretModel->updateSecret($key, $encrypted);

            // Update cache
            if ($result && $this->config->useCache) {
                $this->cache->save($this->config->cachePrefix . $key, $encrypted, $this->config->cacheTTL);
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
            $encrypted = $this->cache->get($this->config->cachePrefix . $key);

            if ($encrypted === null) {
                // If not in cache, get from database
                $encrypted = $this->secretModel->retrieve($key);

                if (! $encrypted) {
                    return null;
                }

                // Store in cache for future requests
                if ($this->config->useCache) {
                    $this->cache->save($this->config->cachePrefix . $key, $encrypted, $this->config->cacheTTL);
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
            $result = $this->secretModel->deleteSecret($key);

            // Delete from cache
            if ($result && $this->config->useCache) {
                $this->cache->delete($this->config->cachePrefix . $key);
            }
        } catch (Exception $e) {
            $this->logger->error('Error deleting encrypted value: ' . $e->getMessage());

            return false;
        }

        return true;
    }
}
