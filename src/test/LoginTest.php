<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . "/../Authenticator.php";
require_once __DIR__ . "/../User.php";
require_once __DIR__ . "/../SessionManager.php";
use welwitschi\Authenticator;
use welwitschi\User;

/**
 * Class that tests logging in users
 * @property mysqli db: The database connection to use
 * @property Authenticator authenticator: The authenticator to use
 * @property User userOne: The first user
 * @property User userTwo: The second user
 * @property User userThree: The third user
 */
final class LoginTest extends TestCase {

	/**
	 * Sets up the tests. Initializes a database connection and
	 * Authenticator object as well as 3 new users.
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

		$this->authenticator = new Authenticator($this->db);

		$this->authenticator->createUser("userOne", "user@1.net", "pass1");
		$this->authenticator->createUser("userTwo", "user@2.net", "pass2");
		$this->authenticator->createUser("userThree", "user@3.net", "pass3");

		$this->userOne = $this->authenticator->getUserFromUsername("userOne");
		$this->userTwo = $this->authenticator->getUserFromUsername("userTwo");
		$this->userThree =
			$this->authenticator->getUserFromUsername("userThree");
	}

	/**
	 * Deletes any data created during testing
	 */
	public function tearDown() {
		$this->db->query("DROP TABLE accounts;");
		$this->db->query("DROP TABLE sessions;");
		$this->db->commit();
		$this->db->close();
		parent::tearDown();
	}

	/**
	 * Test logging in a user. First makes sure that the user is not logged in,
	 * then log the user in, then make sure that the user is logged in.
	 */
	public function testLoggingIn() {
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->userOne->login("pass1");
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertTrue(isset($_SESSION["login_token"]));
		$this->assertTrue(isset($_SESSION["user_id"]));
	}

	/**
	 * Test logging in a user with an incorrect password.
	 */
	public function testLoggingInWrongPassword() {
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->userOne->login("pass");
		$this->assertFalse($this->userOne->isLoggedIn());
	}

	/**
	 * Tests logging in multiple users. Only one user can be logged in
	 * on the same session at any given time
	 */
	public function testLoggingInMultipleUsers() {
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());
		$this->assertFalse($this->userThree->isLoggedIn());

		$this->userOne->login("pass1");
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());
		$this->assertFalse($this->userThree->isLoggedIn());
		$this->assertEquals($_SESSION["user_id"], 1);

		$this->userTwo->login("pass1");
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());
		$this->assertFalse($this->userThree->isLoggedIn());
		$this->assertEquals($_SESSION["user_id"], 1);

		$this->userThree->login("pass3");
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());
		$this->assertTrue($this->userThree->isLoggedIn());
		$this->assertEquals($_SESSION["user_id"], 3);
	}

}