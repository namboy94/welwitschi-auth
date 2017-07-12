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


/**
 * Class SessionManager
 * @package welwitschi
 *
 * Handles login sessions for users. This includes normal sessions/logins
 * and API access.
 */
class SessionManager {

	/**
	 * SessionManager constructor.
	 * @param User $user: The user for which to manage the sessions
	 */
	public function __construct(User $user) {
		$this->user = $user;
		$this->db = $this->user->db;
	}

	/**
	 * Checks if a give login token is valid
	 * @param string $loginToken: The token to check
	 * @return bool: true if the token is valid, false otherwise
	 */
	public function isValidLoginToken(string $loginToken) : bool {
		$hashes = $this->getTokenHashes();

		if ($hashes === null) {
			return false;
		} else {
			return password_verify($loginToken, $hashes["login_hash"]);
		}
	}

	/**
	 * Checks if a give API token is valid
	 * @param string $apiToken: The token to check
	 * @return bool: true if the token is valid, false otherwise
	 */
	public function isValidApiToken(string $apiToken) : bool {
		$hashes = $this->getTokenHashes();

		if ($hashes === null) {
			return false;
		} else {
			return password_verify($apiToken, $hashes["api_hash"]);
		}
	}

	/**
	 * Retrieves the login and API token hashes from the database
	 * @return array: The token hashes in an associative array, with the
	 *                keys `login_hash` and `api_hash`.
	 */
	public function getTokenHashes() : ? array {
		$stmt = $this->db->prepare(
			"SELECT login_hash, api_hash FROM sessions WHERE user_id=?"
		);
		$stmt->bind_param("i", $this->user->id);
		$stmt->execute();
		return $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
	}

	/**
	 * Logs the user in. Generates a new login session token and stores
	 * the corresponding hash in the database
	 * @return string: The generated token
	 */
	public function login() : string {
		$token = bin2hex(random_bytes(64));
		$loginHash = password_hash($token, PASSWORD_BCRYPT);

		$stmt = $this->db->prepare(
			"INSERT INTO sessions (user_id, login_hash, api_hash) " .
			"VALUES (?, ?, NULL) " .
			"ON DUPLICATE KEY UPDATE login_hash=?;"
		);
		$stmt->bind_param("iss",
			$this->user->id, $loginHash, $loginHash);
		$stmt->execute();
		$this->db->commit();

		return $token;
	}

	/**
	 * Stores a hash of a new API key in the database.
	 * The previously stored hash will be deleted
	 * @param string $apiKey: The API Key to set
	 */
	public function storeApiKey(string $apiKey) {
		$hash = password_hash($apiKey, PASSWORD_BCRYPT);

		$stmt = $this->db->prepare(
			"INSERT INTO sessions (user_id, login_hash, api_hash) " .
			"VALUES (?, NULL, ?) " .
			"ON DUPLICATE KEY UPDATE api_hash=?;"
		);
		$stmt->bind_param("iss",
			$this->user->id, $hash, $hash);
		$stmt->execute();
		$this->db->commit();
	}

	/**
	 * Deletes the Login token hash from the database
	 */
	public function wipeLoginSession() {
		$stmt = $this->db->prepare("DELETE FROM sessions WHERE user_id = ?;");
		$stmt->bind_param("i", $this->user->id);
		$stmt->execute();
		$this->db->commit();
	}
}