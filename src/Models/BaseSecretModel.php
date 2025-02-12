<?php

namespace Bgeneto\Secrets\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;

class BaseSecretModel extends Model
{
    protected $table            = 'secrets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['key_name', 'encrypted_value', 'created_at', 'updated_at'];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Stores encrypted value in database
     *
     * @throws DatabaseException
     */
    public function addSecret(string $key, string $encrypted): bool
    {
        $data = [
            'key_name'        => $key,
            'encrypted_value' => $encrypted,
        ];

        // Check if key already exists
        if ($this->where('key_name', $key)->countAllResults() > 0) {
            throw new DatabaseException('Secret already exists! Update the secret instead.');
        }

        $result = $this->insert($data);

        return (bool) $result;
    }

    /**
     * Updates existing encrypted value
     *
     * @throws DatabaseException
     */
    public function updateSecret(string $key, string $encrypted): bool
    {
        $data = [
            'encrypted_value' => $encrypted,
        ];

        $result = $this->where('key_name', $key)->set($data)->update();

        if (! $result) {
            throw new DatabaseException('Failed to update encrypted value!');
        }

        return true;
    }

    /**
     * Deletes stored encrypted value
     */
    public function deleteSecret(string $key): bool
    {
        return (bool) $this->where('key_name', $key)->delete();
    }

    /**
     * Retrieves stored encrypted value
     */
    public function retrieveSecret(string $key): ?string
    {
        $result = $this->select('encrypted_value')
            ->where('key_name', $key)
            ->first();

        return $result->encrypted_value ?? null;
    }
}
