<?php

use PHPUnit\Framework\TestCase;
use welwitschi\Authenticator;

/**
 * Class that tests the creation, fetching and deletion of Users
 * @property Authenticator authenticator: The authenticator used by the tests
 */
final class UserManagementTest extends TestCase {

	/**
	 * Sets up the tests. Initializes a database connection and
	 * deletes any tables that will be created when initializing an
	 * Authenticator.
	 */
	public function setUp() {
		parent::setUp();
		$db = new mysqli(
			"localhost",
			"phpunit",
			getenv("TEST_DB_PASS"), // Uses environment variable
			"welwitschi_auth_test");

		// Make sure that tables are empty, then create them
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
	 * Tests creating a user and retrieving the corresponding User object
	 * from the database afterwards.
	 */
	public function testCreatingAndFetchingUser() {
		$this->assertNull($this->authenticator->getUserFromId(1));

		$this->assertTrue(
			$this->authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));

		$this->assertNotNull($this->authenticator->getUserFromId(1));
		$user = $this->authenticator->getUserFromUsername("Tester");

		$this->assertEquals($user->id, 1);
		$this->assertEquals($user->username, "Tester");
		$this->assertEquals($user->email, "test@namibsun.net");
	}

	/**
	 * Tests creating 3 users and makes sure that they are present in the
	 * database.
	 */
	public function testCreatingMultipleUsers() {
		$this->assertTrue(
			$this->authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));
		$this->assertTrue(
			$this->authenticator->createUser(
				"Tester2", "test2@namibsun.net", "password2"));
		$this->assertTrue(
			$this->authenticator->createUser(
				"Tester3", "test3@namibsun.net", "password3"));

		$this->assertNotNull(
			$this->authenticator->getUserFromUsername("Tester"));
		$this->assertNotNull(
			$this->authenticator->getUserFromUsername("Tester2"));
		$this->assertNotNull(
			$this->authenticator->getUserFromUsername("Tester3"));
	}

	/**
	 * Tests trying to create a user with the same name or email address
	 * as an existing user, which should fail.
	 */
	public function testCreatingDuplicateUsers() {
		$this->assertTrue(
			$this->authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));
		$this->assertFalse(
			$this->authenticator->createUser(
				"Tester", "test@namibsun.net", "password"));
		$this->assertFalse(
			$this->authenticator->createUser(
				"Tester2", "test@namibsun.net", "password"));
		$this->assertFalse(
			$this->authenticator->createUser(
				"Tester", "test@namibsun.net2", "password"));

		$this->assertNotNull($this->authenticator->getUserFromId(1));
		$this->assertNull($this->authenticator->getUserFromId(2));
	}

	/**
	 * Tests deleting a user after generating. Also tests if sessions
	 * from that user are deleted.
	 */
	public function testDeletingUser() {
		$this->assertTrue($this->authenticator->createUser(
			"Tester", "test@namibsun.net", "password"));
		$this->assertNotNull(
			$user = $this->authenticator->getUserFromId(1));
		$user->confirm($user->confirmationToken);

		$this->assertTrue($user->login("password"));
		$this->assertNotNull($user->sessionManager->getTokenHashes());

		$this->assertTrue($this->authenticator->deleteUser($user, "password"));
		$this->assertNull($this->authenticator->getUserFromId(1));

		$this->assertNull($user->sessionManager->getTokenHashes());
	}

	/**
	 * Tests deleting a user when a wrong password was provided.
	 * Of course, a wrong password means that the user won't be deleted.
	 */
	public function testDeletingUserWithWrongPassword() {
		$this->assertTrue($this->authenticator->createUser(
			"Tester", "test@namibsun.net", "password"));
		$user = $this->authenticator->getUserFromId(1);
		$this->assertFalse($this->authenticator->deleteUser($user, "pass"));
		$this->assertNotNull($this->authenticator->getUserFromId(1));
	}

	/**
	 * Tests the getUser methods of the Authenticator class
	 */
	public function testGettingUsers() {
		$this->assertTrue($this->authenticator->createUser("1a", "1b", "1c"));
		$this->assertTrue($this->authenticator->createUser("2a", "2b", "2c"));

		$oneOne = $this->authenticator->getUserFromId(1);
		$oneTwo = $this->authenticator->getUserFromUsername("1a");
		$oneThree = $this->authenticator->getUserFromEmailAddress("1b");
		$oneFour = $this->authenticator->getUser(1, "", "");
		$oneFive = $this->authenticator->getUser(-1, "1a", "");
		$oneSix = $this->authenticator->getUser(-1, "", "1b");

		$this->assertEquals($oneOne, $oneTwo);
		$this->assertEquals($oneTwo, $oneThree);
		$this->assertEquals($oneThree, $oneFour);
		$this->assertEquals($oneFour, $oneFive);
		$this->assertEquals($oneFive, $oneSix);
		$this->assertEquals($oneSix, $oneOne);

		$two = $this->authenticator->getUserFromId(2);
		$this->assertNotEquals($oneOne, $two);
	}
}