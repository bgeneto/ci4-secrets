<?php

declare(strict_types=1);

namespace Bgeneto\Secrets\Tests;

use Bgeneto\Secrets\Libraries\Secrets;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;


class SecretsTest extends CIUnitTestCase
{
	protected $secrets;

	protected function setUp(): void
	{
		parent::setUp();

		// Ensure encryption key is properly configured
		$this->secrets = new Secrets(
			Services::db(),
			Services::encrypted(),
			Services::logger(),
			Services::cache()
		);
	}

	public function testEncryptAndDecrypt()
	{
		$data = 'mysecret123';
		$encrypted = $this->secrets->encrypt($data);
		$decrypted = $this->secrets->decrypt($encrypted);

		$this->assertEquals($data, $decrypted);
	}

	public function testStore()
	{
		$key = 'test_key';
		$value = 'test_value';

		$this->assertTrue($this->secrets->store($key, $value));

		// Check if stored in database
		$result = Services::db()->table('secrets')
			->select('encrypted_value')
			->where('key_name', $key)
			->get()
			->getRowArray();

		$this->assertNotNull($result);
		$this->assertEquals($value, $this->secrets->decrypt($result['encrypted_value']));

		// Clean up
		Services::db()->table('secrets')->where('key_name', $key)->delete();
	}

	public function testUpdate()
	{
		$key = 'test_key';
		$value = 'test_value';
		$newValue = 'new_test_value';

		// Store initial value
		$this->secrets->store($key, $value);

		$this->assertTrue($this->secrets->update($key, $newValue));

		// Check if updated in database
		$result = Services::db()->table('secrets')
			->select('encrypted_value')
			->where('key_name', $key)
			->get()
			->getRowArray();

		$this->assertNotNull($result);
		$this->assertEquals($newValue, $this->secrets->decrypt($result['encrypted_value']));

		// Clean up
		Services::db()->table('secrets')->where('key_name', $key)->delete();
	}

	public function testRetrieve()
	{
		$key = 'test_key';
		$value = 'test_value';

		// Store initial value
		$this->secrets->store($key, $value);

		$retrievedValue = $this->secrets->retrieve($key);

		$this->assertEquals($value, $retrievedValue);

		// Clean up
		Services::db()->table('secrets')->where('key_name', $key)->delete();
	}

	public function testDelete()
	{
		$key = 'test_key';
		$value = 'test_value';

		// Store initial value
		$this->secrets->store($key, $value);

		$this->assertTrue($this->secrets->delete($key));

		// Check if deleted from database
		$result = Services::db()->table('secrets')
			->select('encrypted_value')
			->where('key_name', $key)
			->get()
			->getRowArray();

		$this->assertNull($result);
	}
}
