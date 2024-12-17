<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
	/**
	 * An array of namespaces and their associated directories.
	 * Only one namespace per directory is allowed.
	 */
	public $psr4 = [
		'Bgeneto\\Secrets\\' => __DIR__ . '/../',
	];

	/**
	 * An array of classmap directories.
	 */
	public $classmap = [];

	/**
	 * An array of files to include.
	 */
	public $files = [];
}
