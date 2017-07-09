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
 * Class Authenticator
 * @package welwitschi
 *
 * This class offers convenience methods for accessing all authentication
 * related-functionality
 */
class Authenticator {

	/**
	 * Authenticator constructor.
	 * @param mysqli $db: The MySQL Database connection to use
	 */
	public function __construct(mysqli $db) {
		$this->db = $db;
		$this->createSchema();
	}

	/**
	 * Creates the Account Database Table.
	 * This method creates a table with the following properties:
	 *
	 * accounts:
	 * | id | username | email | pw_hash | confirmation |
	 *
	 * The id, username and email are always unique. The confirmation
	 * stores a confirmation token until the account was verified
	 */
	public function createSchema() {
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS accounts (" .
			"    id INTEGER NOT NULL," .
			"    username VARCHAR(255) NOT NULL," .
			"    email VARCHAR(255) NOT NULL," .
			"    pw_hash VARCHAR(255) NOT NULL," .
			"    confirmation VARCHAR(255) NOT NULL," .
			"    PRIMARY KEY(id)," .
			"    UNIQUE KEY(username)," .
			"    UNIQUE KEY(email));"
		);
	}

	/**
	 * Tries to retrieve a user from the database. If the user does not
	 * exist, this method returns null.
	 *
	 * Since id, username and email are all unique, all method parameters
	 * may also be null, only one is necessary for retrieving the user
	 * information.
	 *
	 * @param int $id: The ID of the user in the database
	 * @param string $username: The username of the user
	 * @param string $email: The user's email address
	 * @return User|null: The generated User object,
	 *                    or null if no user was found
	 */
	public function getUser(int $id, string $username, string $email): User {

		$stmt = $this->db->prepare(
			"SELECT id, username, email, pw_hash, confirmation " .
			"FROM accounts " .
			"WHERE id=?" .
			"OR username=?" .
			"OR email=?;"
		);

		$stmt->bind_param("iss", $id, $username, $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if (!$result) { // SQL Error
			return null;
		} elseif ($result->num_rows !== 1) { // No result found
			return null;
		} else {
			$values = $result->fetch_array(MYSQLI_ASSOC);
			return new User(
				$this->db,
				$values["id"],
				$values["username"],
				$values["email"],
				$values["pw_hash"],
				$values["confirmation"]);
		}
	}
}
