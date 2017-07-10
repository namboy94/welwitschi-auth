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
	 * Creates the Sessions Database Table.
	 * This method creates a table with the following properties:
	 *
	 * sessions:
	 * | user_id | login_hash | api_hash |
	 *
	 * The user_id directly references a user in the accounts database.
	 * The login_hash is used for normal session token hashes, the api_hash
	 * is used to store the API token hash.
	 */
	public function createSchema() {
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS sessions (" .
			"    user_id INTEGER NOT NULL," .
			"    login_hash VARCHAR(255) NOT NULL," .
			"    api_hash VARCHAR(255) NOT NULL," .
			"    FOREIGN KEY(user_id) REFERENCES accounts(id));"
		);
		$this->db->commit();
	}

	/**
	 * Checks if a give login token is valid
	 * @param string $loginToken: The token to check
	 * @return bool: true if the token is valid, false otherwise
	 */
	public function isValidLoginToken(string $loginToken) : bool {
		return password_verify(
			$loginToken,
			$this->getTokenHashes()["login_hash"]
		);
	}

	/**
	 * Checks if a give API token is valid
	 * @param string $apiToken: The token to check
	 * @return bool: true if the token is valid, false otherwise
	 */
	public function isValidApiToken(string $apiToken) : bool {
		return password_verify(
			$apiToken,
			$this->getTokenHashes()["api_hash"]
		);
	}

	/**
	 * Retrieves the login and API token hashes from the database
	 * @return array: The token hashes in an associative array, with the
	 *                keys `login_hash` and `api_hash`.
	 */
	public function getTokenHashes() : array {
		$stmt = $this->db->prepare(
			"SELECT login_hash, api_hash FROM sessions WHERE user_id=?"
		);
		$stmt->bind_param("s", $this->user->username);
		$stmt->execute();
		return $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
	}
}