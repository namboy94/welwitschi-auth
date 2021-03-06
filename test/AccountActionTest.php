<?php

use PHPUnit\Framework\TestCase;
use welwitschi\Authenticator;
use welwitschi\User;

/**
 * Class that tests logging users in and out as well as password management
 * @property Authenticator authenticator: The authenticator to use
 * @property User userOne: The first user
 * @property User userTwo: The second user
 */
final class AccountActionTest extends TestCase {

	/**
	 * Sets up the tests. Initializes a database connection and
	 * Authenticator object as well as 2 new users.
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

		$this->authenticator->createUser("userOne", "user@1.net", "pass1");
		$this->authenticator->createUser("userTwo", "user@2.net", "pass2");

		$this->userOne = $this->authenticator->getUserFromUsername("userOne");
		$this->userTwo = $this->authenticator->getUserFromUsername("userTwo");

		// Confirm users to enable logging in
		$confirmationOne = $this->userOne->getConfirmation();
		$confirmationTwo = $this->userTwo->getConfirmation();
		$this->assertTrue($this->userOne->confirm($confirmationOne));
		$this->assertTrue($this->userTwo->confirm($confirmationTwo));
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
	 * Test logging in a user. First makes sure that the user is not logged in,
	 * then log the user in, then make sure that the user is logged in.
	 */
	public function testLoggingIn() {
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertTrue(isset($_SESSION["login_token"]));
		$this->assertTrue(isset($_SESSION["user_id"]));
	}

	/**
	 * Test logging in a user with an incorrect password.
	 */
	public function testLoggingInWrongPassword() {
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertFalse($this->userOne->login("pass"));
		$this->assertFalse($this->userOne->isLoggedIn());
	}

	/**
	 * Tests logging in multiple users. Only one user can be logged in
	 * on the same session at any given time
	 */
	public function testLoggingInMultipleUsers() {
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());

		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());
		$this->assertEquals($_SESSION["user_id"], 1);

		$this->assertFalse($this->userTwo->login("pass1"));
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertFalse($this->userTwo->isLoggedIn());
		$this->assertEquals($_SESSION["user_id"], 1);

		$this->assertTrue($this->userTwo->login("pass2"));
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertTrue($this->userTwo->isLoggedIn());
		$this->assertEquals($_SESSION["user_id"], 2);
	}

	/**
	 * Tests if logging in while already logged in keeps the user logged in.
	 */
	public function testLoggingInWhileLoggedIn() {
		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->login("randomNonsense"));
	}

	/**
	 * Tests if logging out works, as well as a subsequent login.
	 */
	public function testLogoutAndAnotherLogin() {

		// Test Logout
		$this->assertTrue($this->userOne->login("pass1"));
		$this->userTwo->logout();
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertFalse(isset($_SESSION["user_id"]));
		$this->assertFalse(isset($_SESSION["login_token"]));

		// Test new login
		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->isLoggedIn());
		$this->assertTrue(isset($_SESSION["user_id"]));
		$this->assertTrue(isset($_SESSION["login_token"]));
	}

	/**
	 * Tests changing a password after logging in, then logging out
	 * and making sure that the new password will be used.
	 * Also checks that wrong original passwords are rejected
	 */
	public function testChangingPassword() {
		$originalHash = $this->userOne->pwHash;

		$this->assertTrue($this->userOne->login("pass1"));

		// With wrong credentials
		$this->assertFalse($this->userOne->changePassword("aaa", "newpass"));
		$this->assertFalse($this->userOne->doesPasswordMatch("newpass"));
		$this->assertTrue($this->userOne->doesPasswordMatch("pass1"));

		// With correct credentials
		$this->assertTrue($this->userOne->changePassword("pass1", "newpass"));
		$this->assertTrue($this->userOne->doesPasswordMatch("newpass"));
		$this->assertTrue($this->userOne->isLoggedIn());

		$this->userOne->logout();
		$this->assertFalse($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->login("newpass"));
		$this->assertTrue($this->userOne->isLoggedIn());

		$dbPwHash = $this->authenticator->db->query(
			"SELECT pw_hash FROM accounts " .
			"WHERE id = " . $this->userOne->id)
			->fetch_array(MYSQLI_ASSOC)["pw_hash"];

		$this->assertNotEquals($originalHash, $dbPwHash);
		$this->assertEquals($dbPwHash, $this->userOne->pwHash);
	}

	/**
	 * Tests resetting a password while logged in
	 */
	public function testResettingPassword() {

		$originalHash = $this->userOne->pwHash;

		$this->assertTrue($this->userOne->login("pass1"));
		$newPass = $this->userOne->resetPassword();
		$this->assertFalse($this->userOne->isLoggedIn());
		$this->assertFalse($this->userOne->doesPasswordMatch("pass1"));
		$this->assertTrue($this->userOne->doesPasswordMatch($newPass));
		$dbPwHash = $this->authenticator->db->query(
			"SELECT pw_hash FROM accounts WHERE id = 1")
			->fetch_array(MYSQLI_ASSOC)["pw_hash"];

		$this->assertNotEquals($originalHash, $dbPwHash);
		$this->assertEquals($dbPwHash, $this->userOne->pwHash);
	}

	/**
	 * Tests if confirming a user account works as well as unconfirmed
	 * accounts are unable to log in.
	 */
	public function testConfirmingAccountWhileTryingToLogin() {

		$this->assertTrue($this->authenticator->createUser("3", "3", "3"));
		$user = $this->authenticator->getUserFromId(3);

		$this->assertFalse($user->login("3"));

		$this->assertFalse($user->confirm("Nonsense"));
		$this->assertFalse($user->login("3"));

		$confirmation = $user->getConfirmation();
		$this->assertTrue($user->confirm($confirmation));
		$this->assertTrue($user->login("3"));

	}

	/**
	 * Tests changing a username
	 */
	public function testChangingUsername() {
		$this->assertFalse($this->userOne->changeUsername("New Name"));
		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->changeUsername("New Name"));
		$this->assertEquals($this->userOne->username, "New Name");

		$dbName = $this->authenticator->db->query(
			"SELECT username FROM accounts WHERE id=1")
			->fetch_array(MYSQLI_ASSOC)["username"];

		$this->assertEquals($this->userOne->username, $dbName);
		$this->assertFalse($this->userOne->changeUsername("UserTwo"));
		$this->assertEquals($this->userOne->username, "New Name");
	}

	/**
	 * Tests changing a username
	 */
	public function testChangingEmail() {
		$this->assertFalse($this->userOne->changeEmail("new@about.com"));
		$this->assertTrue($this->userOne->login("pass1"));
		$this->assertTrue($this->userOne->changeEmail("new@about.com"));
		$this->assertEquals($this->userOne->email, "new@about.com");

		$dbEmail = $this->authenticator->db->query(
			"SELECT email FROM accounts WHERE id=1")
			->fetch_array(MYSQLI_ASSOC)["email"];

		$this->assertEquals($this->userOne->email, $dbEmail);
		$this->assertFalse($this->userOne->changeEmail("user@2.net"));
		$this->assertEquals($this->userOne->email, "new@about.com");
	}
}