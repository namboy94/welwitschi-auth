<?php
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
			$this->confirmed = true;
			$this->confirmationToken = "";
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Attempts to perform a login with this user. The user account
	 * must be confirmed before logging in
	 * @param string $password: The password for this user,
	 *                          required for authentication
	 * @return bool: true if the user is logged in afterwards, false otherwise
	 */
	public function login(string $password) : bool {
		if (!$this->confirmed) {
			return false;
		} elseif ($this->isLoggedIn()) {
			return true;
		} elseif ($this->doesPasswordMatch($password)) {

			// Requires a started session
			$loginToken = $this->sessionManager->login();
			$_SESSION["user_id"] = $this->id;
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

		// Requires a started session
		if (isset($_SESSION["login_token"])) {
			$loginToken = $_SESSION["login_token"];
			return $this->sessionManager->isValidLoginToken($loginToken);
		} else {
			return false;
		}
	}

	/**
	 * Unsets the session variables that contain the login token and user ID.
	 * This effectively logs the user out.
	 */
	public function logout() {
		unset($_SESSION["login_token"]);
		unset($_SESSION["user_id"]);
	}

	/**
	 * Generates a new API key and stores it in the database
	 * Previous API keys will be overwritten
	 * @return string: The generated API key.
	 *                 null if the user is not confirmed yet
	 */
	public function generateNewApiKey() : ? string {

		if ($this->confirmed) {
			$apiKey = bin2hex(random_bytes(64));
			$this->sessionManager->storeApiKey($apiKey);
			return $apiKey;
		} else {
			return null;
		}

	}

	/**
	 * Checks if a given API key is valid for the user
	 * @param string $apiKey: The API key to check
	 * @return bool: true if the API key is valid, false otherwise
	 */
	public function verifyApiKey(string $apiKey) : bool {
		return $this->sessionManager->isValidApiToken($apiKey);
	}

	/**
	 * Changes a user's password
	 * @param string $previous: The previous password, which is required
	 *                          to match the currently stored password hash
	 * @param string $new: The new password for which
	 *                     a new has will be generated
	 * @return bool: true if the password was changed successfully,
	 *               false otherwise
	 */
	public function changePassword(string $previous, string $new) : bool {

		if ($this->doesPasswordMatch($previous)) {

			$hash = password_hash($new, PASSWORD_BCRYPT);

			$stmt = $this->db->prepare(
				"UPDATE accounts SET pw_hash=? WHERE id=?;"
			);
			$stmt->bind_param("is", $hash, $this->id);
			$stmt->execute();
			$this->db->commit();
			$this->pwHash = $hash;
			return true;

		} else {
			return false;
		}
	}

	/**
	 * Resets the password and sets it to a new randomized 20-character
	 * long password.
	 * @return string: The generated password
	 */
	public function resetPassword() : string {
		$newPass = bin2hex(random_bytes(20));
		$newHash = password_hash($newPass, PASSWORD_BCRYPT);

		$stmt = $this->db->prepare(
			"UPDATE accounts SET pw_hash=? WHERE id=?;"
		);
		$stmt->bind_param("si", $newHash, $this->id);
		$stmt->execute();
		$this->db->commit();
		$this->pwHash = $newHash;

		$this->sessionManager->wipeLoginSession();
		return $newPass;
	}
}