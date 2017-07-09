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
	 * @param $db: The MySQL Database connection to use
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
}
