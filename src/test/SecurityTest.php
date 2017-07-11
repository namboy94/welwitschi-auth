<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . "/../SessionManager.php";
require_once __DIR__ . "/../Authenticator.php";
require_once __DIR__ . "/../User.php";
use welwitschi\Authenticator;

/**
 * Class that tests various security-related functionality
 * @property Authenticator authenticator: The authenticator with which
 *                                        to manage user creation
 */
final class SecurityTest extends TestCase {

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
	 * Tests if the password hashes are salted. This is done by changing the
	 * password to the same as it was previously. The new hash should be
	 * different now though due to a different salt being used.
	 */
	public function testIfHashesAreSalted() {
		$this->assertTrue($this->authenticator->createUser("1", "1", "1"));
		$user = $this->authenticator->getUserFromId(1);
		$hash = $user->pwHash;
		$this->assertTrue($user->changePassword("1", "1"));
		$newHash = $user->pwHash;

		// Well, could theoretically fail if the hashes were identical,
		// but the chances of this happening are slim.
		$this->assertNotEquals($hash, $newHash);
	}

	/**
	 * Makes sure that passwords are stored securely as a salted, hashed
	 * string which makes it improbable to crack
	 */
	public function testIfPasswordsAreStoredHashed() {
		$this->assertTrue($this->authenticator->createUser("1", "1", "1"));
		$user = $this->authenticator->getUserFromId(1);
		$this->assertNotEquals("1", $user->pwHash);
		$pwHashFromDb = $user->db->query(
			"SELECT pw_hash FROM accounts WHERE id=1")
			->fetch_array()["pw_hash"];
		$this->assertNotEquals("1", $pwHashFromDb);
	}
}