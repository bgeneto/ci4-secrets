<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Config;

use Bgeneto\Secrets\Libraries\Secrets;
use CodeIgniter\Config\BaseService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Encryption\EncrypterInterface;
use Config\Services as ConfigServices;
use Psr\Log\LoggerInterface;


class Services extends BaseService
{
	public static function secrets(?BaseConnection $db = null, ?EncrypterInterface $encrypter = null, ?LoggerInterface $logger = null, ?bool $getShared = true)
	{
		if ($getShared) {
			return static::getSharedInstance('secrets', $db, $encrypter, $logger);
		}

		$db = $db ?? ConfigServices::db();
		$encrypter = $encrypter ?? ConfigServices::encrypted();
		$logger = $logger ?? ConfigServices::logger();

		return new Secrets($db, $encrypter, $logger);
	}
}
