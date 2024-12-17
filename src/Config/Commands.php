<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Class Commands
 *
 * Configures the list of commands that the spark command line tool will
 * recognize and be able to run.
 *
 * @package Secrets\Config
 */
class Commands extends BaseConfig
{
	/**
	 * A list of command class names that should be registered with the spark CLI tool.
	 *
	 * @var array
	 */
	public $commands = [
		'Bgeneto\\Secrets\\Commands\\SecretsCommand',
	];
}
