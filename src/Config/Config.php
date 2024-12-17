<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Config;

use CodeIgniter\Config\BaseConfig;


class Config extends BaseConfig
{
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
	public $cacheTTL = 3600;
}
