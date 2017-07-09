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
		$this->confirmation_token = ($this->confirmed) ? null : $confirmation;
	}

	/**
	 * Converts the confirmation_token and confirmed variables into a
	 * string for storage in the database
	 * @return string: The confirmation string
	 */
	public function getConfirmation(): string {
		return ($this->confirmed) ? $this->confirmation_token : "confirmed";
	}

}