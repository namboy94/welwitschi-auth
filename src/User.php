<?php declare(strict_types=1);
/**
 * Copyright Hermann Krumrey <hermann@krumreyh.com> 2017
 *
 * This file is part of welwitschi-auth.
 *
 * welwitschi-auth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * welwitschi-auth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with welwitschi-auth.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace welwitschi;
use mysqli;


/**
 * Class User
 * @package welwitschi
 *
 * A class that models a user in the database. It also offers
 * various methods to manipulate the user data.
 *
 * A user object should not exist if the user does not exist in the database
 */
class User {

	/**
	 * User constructor.
	 * @SuppressWarnings functionMaxParameters
	 * @param mysqli $db: The database connection for the user
	 * @param int $id: The user's ID in the database table
	 * @param string $username: The user's username
	 * @param string $email: The email address associated with the user
	 * @param string $pwHash: The user's password hash
	 * @param string $confirmation: The user's confirmation token or status
	 */
	public function __construct(
		mysqli $db,
		int $id, string $username, string $email,
		string $pwHash, string $confirmation) {

		$this->db = $db;
		$this->id = $id;
		$this->username = $username;
		$this->email = $email;
		$this->pwHash = $pwHash;
		$this->confirmed = $confirmation === "confirmed";
		$this->confirmationToken = ($this->confirmed) ? null : $confirmation;

		$this->sessionManager = new SessionManager($this);
	}

	/**
	 * @return string: The username with unescaped special HTML characters
	 */
	public function getRawUsername() : string {
		return htmlspecialchars_decode($this->username);
	}

	/**
	 * @return string: The email address with unescaped special HTML characters
	 */
	public function getRawEmailAddress() : string {
		return htmlspecialchars_decode($this->email);
	}

	/**
	 * Converts the confirmation_token and confirmed variables into a
	 * string for storage in the database
	 * @return string: The confirmation string
	 */
	public function getConfirmation(): string {
		return ($this->confirmed) ? "confirmed" : $this->confirmationToken;
	}

	/**
	 * Checks if a given password matches the user's password hash.
	 * @param string $password: The password to check
	 * @return bool: true if the password matches, false otherwise
	 */
	public function doesPasswordMatch(string $password) : bool {
		return password_verify($password, $this->pwHash);
	}

	/**
	 * Tries to confirm a user's account. This will succeed if the
	 * provided confirmationToken is the same as the one in the database.
	 * @param string $confirmationToken: The confirmationToken to use
	 * @return bool: true if the confirmation was successful, false otherwise
	 */
	public function confirm(string $confirmationToken) : bool {
		if ($confirmationToken === $this->confirmationToken) {

			$this->confirmed = true;
			$stmt = $this->db->prepare(
				"UPDATE accounts " .
				"SET confirmation='confirmed' " .
				"WHERE id=?"
			);
			$stmt->bind_param("i", $this->id);
			$stmt->execute();
			$this->db->commit();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Attempts to perform a login with this user.
	 * @param string $password: The password for this user,
	 *                          required for authentication
	 * @return bool: true if the user is logged in afterwards, false otherwise
	 */
	public function login(string $password) : bool {
		if ($this->isLoggedIn()) {
			return true;
		} elseif ($this->doesPasswordMatch($password)) {
			initializeSession();
			$loginToken = $this->sessionManager->login();
			$_SESSION["username"] = $this->username;
			$_SESSION["login_token"] = $loginToken;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if the user is logged in.
	 * @return bool: true if the user is logged in, false otherwise
	 */
	public function isLoggedIn() : bool {
		initializeSession();

		if (isset($_SESSION["login_token"])) {
			$loginToken = $_SESSION["login_token"];
			return $this->sessionManager->isValidLoginToken($loginToken);
		} else {
			return false;
		}
	}
}