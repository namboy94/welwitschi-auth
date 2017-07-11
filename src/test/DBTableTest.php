<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . "/../SessionManager.php";
require_once __DIR__ . "/../Authenticator.php";
require_once __DIR__ . "/../User.php";
use welwitschi\Authenticator;

/**
 * Class that tests the database tables themselves
 * @property mysqli db: The database connection to use
 */
final class DBTableTest extends TestCase
{

	/**
	 * Sets up the tests. Initializes a database connection and
	 * deletes any tables that will be created when initializing an
	 * Authenticator.
	 */
	public function setUp()
	{
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
	public function tearDown()
	{
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
	public function testCreatingTables()
	{
		$this->assertFalse($this->db->query("SELECT * FROM accounts;"));
		$this->assertFalse($this->db->query("SELECT * FROM sessions;"));
		new Authenticator($this->db);
		$this->assertNotFalse($this->db->query("SELECT * FROM accounts;"));
		$this->assertNotFalse($this->db->query("SELECT * FROM sessions;"));
	}
}