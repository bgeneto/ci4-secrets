<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Config;

use CodeIgniter\Config\BaseConfig;

class Secrets extends BaseConfig
{
    /**
     * Whether to use the cache to fast retrieve encrypted values.
     *
     * @var bool
     */
    public $useCache = true;

    /**
     * Cache prefix for storing encrypted values.
     *
     * @var string
     */
    public $cachePrefix = 'secrets_';

    /**
     * Cache TTL (Time To Live) in seconds for storing encrypted values.
     *
     * @var int
     */
    public $cacheTTL = 21600;  // 6 hours

    /**
     * The model class to use for managing secrets.
     *
     * @var string
     */
    public $modelClass = '\Bgeneto\Secrets\Models\BaseSecretModel';
}
