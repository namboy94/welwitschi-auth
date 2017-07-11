<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . "/../SessionManager.php";
require_once __DIR__ . "/../Authenticator.php";
require_once __DIR__ . "/../User.php";
use welwitschi\Authenticator;

/**
 * Class that tests the basic functionality of the Authenticator class
 * @property mysqli db: The database connection to use
 */
final class AuthenticatorTest extends TestCase {

	/**
	 * Sets up the tests. Initializes a database connection and
	 * deletes any tables that will be created by initializing an
	 * Authenticator.
	 */
	public function setUp() {
		parent::setUp();
		$this->db = new mysqli(
			"localhost",
			"phpunit",
			getenv("TEST_DB_PASS"), // Uses environment variable
			"welwitschi_auth_test");
		$this->db->query("DROP TABLE accounts;");
		$this->db->query("DROP TABLE sessions;");
		$this->db->commit();
	}

	/**
	 * Deletes any tables created during testing
	 */
	public function tearDown() {
		$this->db->query("DROP TABLE accounts;");
		$this->db->query("DROP TABLE sessions;");
		$this->db->commit();
		$this->db->close();
		parent::tearDown();
	}

	/**
	 * Tests initializing an Authenticator object, which creates
	 * the 'sessions' and 'accounts' tables in the database
	 */
	public function testCreatingTables() {
		$this->assertFalse($this->db->query("SELECT * FROM accounts;"));
		$this->assertFalse($this->db->query("SELECT * FROM sessions;"));
		new Authenticator($this->db);
		$this->assertNotFalse($this->db->query("SELECT * FROM accounts;"));
		$this->assertNotFalse($this->db->query("SELECT * FROM sessions;"));
	}

	/**
	 * Tests creating a user and retrieving the corresponding User object
	 * from the database afterwards.
	 */
	public function testCreatingAndFetchingUser() {
		$authenticator = new Authenticator($this->db);
		$this->assertNull($authenticator->getUserFromId(1));

		$this->assertTrue(
			$authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));

		$this->assertNotNull($authenticator->getUserFromId(1));
		$user = $authenticator->getUserFromUsername("Tester");

		$this->assertEquals($user->id, 1);
		$this->assertEquals($user->username, "Tester");
		$this->assertEquals($user->email, "test@namibsun.net");
	}

	/**
	 * Tests creating 3 users and makes sure that they are present in the
	 * database.
	 */
	public function testCreatingMultipleUsers() {
		$authenticator = new Authenticator($this->db);

		$this->assertTrue(
			$authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));
		$this->assertTrue(
			$authenticator->createUser(
				"Tester2", "test2@namibsun.net", "password2"));
		$this->assertTrue(
			$authenticator->createUser(
				"Tester3", "test3@namibsun.net", "password3"));

		$this->assertNotNull($authenticator->getUserFromUsername("Tester"));
		$this->assertNotNull($authenticator->getUserFromUsername("Tester2"));
		$this->assertNotNull($authenticator->getUserFromUsername("Tester3"));
	}

	/**
	 * Tests trying to create a user with the same name or email address
	 * as an existing user, which should fail.
	 */
	public function testCreatingDuplicateUsers() {
		$authenticator = new Authenticator($this->db);

		$this->assertTrue(
			$authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));
		$this->assertFalse(
			$authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));
		$this->assertFalse(
			$authenticator->createUser(
				"Tester2", "test@namibsun.net", "password"));
		$this->assertFalse(
			$authenticator->createUser(
				"Tester", "test@namibsun.net2", "password"));

		$this->assertNotNull($authenticator->getUserFromId(1));
		$this->assertNull($authenticator->getUserFromId(2));
	}
}