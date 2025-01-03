<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Config;

use CodeIgniter\Config\BaseConfig;


class Secrets extends BaseConfig
{
	/**
	 * Whether to use the cache to fast retrieve encrypted values.
	 *
	 * @var boolean
	 */
	public $useCache = true;

	/**
	 * Whether to log access to secrets to database.
	 *
	 * @var boolean
	 */
	public $useLog = true;

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
}
