<?php

use PHPUnit\Framework\TestCase;
use welwitschi\Authenticator;
use welwitschi\User;

/**
 * Class that tests API functionality
 * @property Authenticator authenticator: The authenticator used in testing
 * @property User user: A sample User object for testing
 */
final class ApiTest extends TestCase {

	/**
	 * Sets up the tests. Initializes a database connection and
	 * deletes any tables that will be created when initializing an
	 * Authenticator, then initializes an Authenticator
	 */
	public function setUp() {
		parent::setUp();
		$db = new mysqli(
			"localhost",
			"phpunit",
			getenv("TEST_DB_PASS"), // Uses environment variable
			"welwitschi_auth_test");
		$db->query("DROP TABLE accounts;");
		$db->query("DROP TABLE sessions;");
		$db->commit();
		$this->authenticator = new Authenticator($db);
		$this->assertTrue(
			$this->authenticator->createUser("user", "email", "pass")
		);
		$this->user = $this->authenticator->getUserFromId(1);
	}

	/**
	 * Deletes any tables created during testing
	 */
	public function tearDown() {
		$this->authenticator->db->query("DROP TABLE accounts;");
		$this->authenticator->db->query("DROP TABLE sessions;");
		$this->authenticator->db->commit();
		$this->authenticator->db->close();
		parent::tearDown();
	}

	/**
	 * Tests generating an API key, first as an unconfirmed user, then as
	 * a confirmed user. Also checks if API key verification works as well.
	 */
	public function testApiKeyGeneration() {
		$this->assertFalse($this->user->verifyApiKey("No Key"));

		$this->assertNull($this->user->generateNewApiKey());
		$this->user->confirm($this->user->confirmationToken);
		$apiKey = $this->user->generateNewApiKey();

		$this->assertFalse($this->user->verifyApiKey("Wrong Key"));
		$this->assertTrue($this->user->verifyApiKey($apiKey));
	}
}